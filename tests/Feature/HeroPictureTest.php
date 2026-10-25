<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HeroPicture;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

/** The admin adds, replaces, describes and removes the Home page picture. Almost any picture is accepted and never cut. */
class HeroPictureTest extends TestCase
{
    use SeedsDemoContent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // The whole test suite runs in one PHP process; picture work needs room (the live server has 512 MB).
        ini_set('memory_limit', '512M');
    }

    private function asAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create())->withSession(['two_factor_passed' => true]);
    }

    private function upload(array $data = [], ?UploadedFile $file = null)
    {
        return $this->asAdmin()->post(route('admin.hero.store'), $data + ['picture' => $file ?? UploadedFile::fake()->image('hero.jpg', 1600, 1000)]);
    }

    private function files(): array
    {
        return Storage::disk('public')->allFiles('hero');
    }

    /** @return array<int, array{0: int, 1: int}> width => [width, height] of every stored WebP, sorted by width */
    private function sizes(): array
    {
        $sizes = [];
        foreach ($this->files() as $path) {
            $bytes = Storage::disk('public')->get($path);
            $this->assertStringStartsWith('RIFF', $bytes);
            $this->assertSame('WEBP', substr($bytes, 8, 4), 'always real WebP, never the uploaded file');
            $info = getimagesizefromstring($bytes);
            $sizes[$info[0]] = [$info[0], $info[1]];
        }
        ksort($sizes);

        return $sizes;
    }

    private function gd(string $format, int $w, int $h): UploadedFile
    {
        $image = imagecreatetruecolor($w, $h);
        imagefilledrectangle($image, 0, 0, $w, $h, imagecolorallocate($image, 20, 120, 100));
        imagefilledellipse($image, intdiv($w, 2), intdiv($h, 2), intdiv($w, 2), intdiv($h, 2), imagecolorallocate($image, 240, 165, 58));

        return $this->encode($image, $format, "picture.{$format}");
    }

    /** A "person" (an orange oval) on a see-through background with big empty margins around her. */
    private function cutout(int $w = 800, int $h = 1000): UploadedFile
    {
        $image = imagecreatetruecolor($w, $h);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        imagefilledellipse($image, intdiv($w, 2), intdiv($h, 2), intdiv($w, 4), intdiv($h, 2), imagecolorallocatealpha($image, 240, 165, 58, 0));

        return $this->encode($image, 'png', 'person.png');
    }

    private function encode(\GdImage $image, string $format, string $name): UploadedFile
    {
        ob_start();
        match ($format) {
            'png' => imagepng($image),
            'gif' => imagegif($image),
            'bmp' => imagebmp($image),
            'webp' => imagewebp($image),
            default => imagejpeg($image),
        };
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    public function test_only_an_admin_with_the_code_can_change_the_picture(): void
    {
        $file = UploadedFile::fake()->image('hero.jpg', 1600, 1000);

        $this->post(route('admin.hero.store'), ['picture' => $file])->assertRedirect('/login');
        $this->delete(route('admin.hero.destroy'))->assertRedirect('/login');
        $this->actingAs(User::factory()->parent()->create())->post(route('admin.hero.store'), ['picture' => $file])->assertForbidden();
        $this->actingAs(User::factory()->parent()->create())->delete(route('admin.hero.destroy'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.hero.store'), ['picture' => $file])->assertRedirect(route('two-factor.show'));

        $this->assertSame([], $this->files());
    }

    public function test_home_shows_the_sample_card_inside_the_new_stage_until_a_picture_is_added(): void
    {
        $home = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('hero-stage hero-stage--sample', $home);
        $this->assertStringContainsString('demo-card', $home);
        $this->assertStringContainsString('<span class="hero__mark">trusted tutor</span>', $home);
        $this->assertSame(3, substr_count($home, 'class="hero-chip hero-chip--'), 'three floating cards');
        $this->assertSame(5, substr_count($home, 'class="hero-stage__shape hero-stage__shape--'), 'five decorative shapes');
        $this->asAdmin()->get('/admin/settings')->assertOk()->assertSee('Home page picture')->assertSee('Add a picture')->assertDontSee('Remove picture');
    }

    public function test_a_big_photo_is_made_smaller_in_its_own_shape_and_shown_in_a_frame(): void
    {
        $this->upload(['alt' => 'A student learning online'])->assertRedirect(route('admin.settings.edit'))->assertSessionHas('notice');

        // 1600 x 1000 keeps its 16:10 shape: nothing is cut off.
        $this->assertSame([640 => [640, 400], 1200 => [1200, 750]], $this->sizes());

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('hero-stage hero-stage--photo', $home);
        $this->assertStringContainsString('alt="A student learning online"', $home);
        $this->assertStringContainsString('fetchpriority="high"', $home);
        $this->assertStringContainsString('width="1200" height="750"', $home);
        $this->assertMatchesRegularExpression('#<link rel="preload" as="image" href="[^"]*/storage/hero/[a-f0-9]{16}-1200\.webp"#', $home);
        $this->assertMatchesRegularExpression('#srcset="[^"]*-640\.webp 640w, [^"]*-1200\.webp 1200w"#', $home);
        $this->assertStringNotContainsString('demo-card', $home);
        $this->assertStringContainsString('hero-chip--verified', $home, 'the floating cards stay');
        $admin = $this->asAdmin()->get('/admin/settings')->getContent();
        $this->assertStringContainsString('Remove picture', $admin);
        $this->assertStringContainsString('an ordinary photo', $admin);
    }

    public function test_nothing_is_ever_cut_off_the_shape_is_kept(): void
    {
        foreach ([[1300, 2000], [2000, 1000], [900, 900], [1900, 1300]] as [$w, $h]) {
            Storage::disk('public')->deleteDirectory('hero');
            $this->upload([], $this->gd('jpg', $w, $h))->assertSessionHasNoErrors();

            foreach ($this->sizes() as [$sw, $sh]) {
                $this->assertEqualsWithDelta($w / $h, $sw / $sh, 0.01, "{$w}x{$h} must keep its shape");
                $this->assertLessThanOrEqual(1200, max($sw, $sh));
            }
            $this->assertSame(min(1200, max($w, $h)), max(...array_map('max', $this->sizes())), 'the big file is as big as allowed, never bigger than the source');
        }
    }

    public function test_a_small_picture_is_accepted_and_not_blown_up(): void
    {
        $this->upload([], $this->gd('jpg', 300, 200))->assertSessionHas('notice')->assertSessionHasNoErrors();

        $this->assertSame([300 => [300, 200]], $this->sizes()); // one file, same size as the source
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('class="hero-stage__figure"', $home);
        $this->assertStringNotContainsString('srcset=', $home);
        $this->assertStringContainsString('width="300" height="200"', $home);
    }

    public function test_a_tiny_picture_works_too(): void
    {
        $this->upload([], $this->gd('png', 40, 30))->assertSessionHasNoErrors();

        $this->assertSame([40 => [40, 30]], $this->sizes());
    }

    public function test_every_common_format_is_converted_to_webp(): void
    {
        foreach (['jpg', 'png', 'gif', 'bmp', 'webp'] as $format) {
            Storage::disk('public')->deleteDirectory('hero');
            $this->upload([], $this->gd($format, 1000, 800))->assertSessionHasNoErrors();

            $this->assertSame([640, 1000], array_keys($this->sizes()), $format);
        }
    }

    public function test_a_person_with_a_transparent_background_is_trimmed_and_shown_on_the_circles(): void
    {
        $this->upload([], $this->cutout())->assertSessionHasNoErrors();

        // The oval is 200 x 500 inside an 800 x 1000 picture: the empty margins are gone, the shape stays tall.
        $sizes = $this->sizes();
        [$w, $h] = reset($sizes);
        $this->assertLessThan(400, $w, 'the empty left and right margins are trimmed');
        $this->assertLessThan(700, $h, 'the empty top and bottom margins are trimmed');
        $this->assertGreaterThan($w, $h, 'still a tall figure');
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('public')->get($this->files()[0]))[2]);

        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('hero-stage hero-stage--cutout', $home);
        $this->assertStringContainsString('a cut-out with a transparent background', $this->asAdmin()->get('/admin/settings')->getContent());
    }

    public function test_the_transparency_survives_in_the_stored_file(): void
    {
        $this->upload([], $this->cutout())->assertSessionHasNoErrors();

        $image = imagecreatefromstring(Storage::disk('public')->get($this->files()[0]));
        $corner = (imagecolorat($image, 0, 0) >> 24) & 0x7F;
        $middle = (imagecolorat($image, intdiv(imagesx($image), 2), intdiv(imagesy($image), 2)) >> 24) & 0x7F;

        $this->assertSame(0, $middle, 'the person stays solid');
        $this->assertGreaterThan(0, $corner + 1, 'the file keeps an alpha channel');
    }

    public function test_a_picture_that_only_looks_transparent_is_an_ordinary_photo(): void
    {
        // A chequered "transparent" pattern painted into solid pixels (what a screenshot of a cut-out gives).
        $image = imagecreatetruecolor(600, 600);
        $light = imagecolorallocate($image, 255, 255, 255);
        $grey = imagecolorallocate($image, 235, 235, 235);
        for ($y = 0; $y < 600; $y += 40) {
            for ($x = 0; $x < 600; $x += 40) {
                imagefilledrectangle($image, $x, $y, $x + 39, $y + 39, (($x + $y) / 40) % 2 ? $grey : $light);
            }
        }
        $this->upload([], $this->encode($image, 'png', 'fake.png'))->assertSessionHasNoErrors();

        $this->get('/')->assertSee('hero-stage--photo', false)->assertDontSee('hero-stage--cutout', false);
    }

    public function test_replacing_deletes_the_old_files_of_any_size_and_removing_brings_the_drawing_back(): void
    {
        $this->upload([], $this->gd('jpg', 300, 200));
        $first = $this->files();
        $this->assertCount(1, $first);

        $this->upload([], UploadedFile::fake()->image('second.png', 2000, 1200));
        $second = $this->files();
        $this->assertCount(2, $second);
        $this->assertEmpty(array_intersect($first, $second));

        $this->upload([], $this->cutout(1600, 2000));
        $this->assertCount(2, $this->files(), 'a photo is replaced by a cut-out and the old pair is gone');
        $this->get('/')->assertSee('hero-stage--cutout', false);

        $this->asAdmin()->delete(route('admin.hero.destroy'))->assertRedirect(route('admin.settings.edit'))->assertSessionHas('notice');

        $this->assertSame([], $this->files());
        $this->assertNull(SiteSettings::stored()['hero_image'] ?? null);
        $this->assertNull(SiteSettings::stored()['hero_image_mode'] ?? null);
        $this->get('/')->assertSee('hero-stage--sample', false)->assertSee('demo-card', false);
    }

    public function test_the_description_can_be_changed_alone(): void
    {
        $this->asAdmin()->put(route('admin.hero.update'), ['alt' => 'x'])->assertNotFound(); // no picture yet

        $this->upload(['alt' => 'First text']);
        $this->asAdmin()->put(route('admin.hero.update'), ['alt' => '<b>Second</b> text'])->assertRedirect()->assertSessionHas('notice');
        $this->get('/')->assertSee('alt="Second text"', false);

        $this->asAdmin()->put(route('admin.hero.update'), ['alt' => str_repeat('a', HeroPicture::ALT_MAX + 1)])->assertSessionHasErrors('alt', errorBag: 'hero');
        $this->asAdmin()->put(route('admin.hero.update'), ['alt' => ''])->assertRedirect();
        $this->get('/')->assertSee('alt=""', false);
    }

    public function test_things_that_are_not_pictures_are_refused_with_a_reason_and_nothing_is_saved(): void
    {
        $bad = [
            'text called .jpg' => [UploadedFile::fake()->createWithContent('hero.jpg', 'this is only text'), 'not a picture'],
            'svg' => [UploadedFile::fake()->createWithContent('hero.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="700"><script>alert(1)</script></svg>'), 'SVG'],
            'pdf' => [UploadedFile::fake()->createWithContent('hero.pdf', "%PDF-1.4\n1 0 obj<<>>endobj"), 'PDF'],
            'php' => [UploadedFile::fake()->createWithContent('hero.php', '<?php echo 1;'), 'not a picture'],
            'fake heic' => [UploadedFile::fake()->createWithContent('hero.heic', "\0\0\0\x18ftypheic".str_repeat("\0", 40)), 'save it as JPG or PNG'],
            'empty' => [UploadedFile::fake()->createWithContent('hero.jpg', ''), 'not a picture'],
        ];

        foreach ($bad as $label => [$file, $reason]) {
            $response = $this->upload([], $file);
            $response->assertSessionHasErrors('picture', errorBag: 'hero');
            $this->assertStringContainsString($reason, session('errors')->getBag('hero')->first('picture'), $label);
            $this->assertSame([], $this->files(), "{$label} must not be stored");
        }
        $this->asAdmin()->post(route('admin.hero.store'), [])->assertSessionHasErrors('picture', errorBag: 'hero');
        $this->upload(['alt' => str_repeat('a', HeroPicture::ALT_MAX + 1)])->assertSessionHasErrors('alt', errorBag: 'hero');
        $this->assertSame([], $this->files());
    }

    public function test_only_an_absurdly_large_file_or_picture_is_refused_and_the_reason_is_clear(): void
    {
        $this->upload([], UploadedFile::fake()->image('hero.jpg', 1600, 1000)->size(HeroPicture::MAX_UPLOAD_KB + 1))
            ->assertSessionHasErrors('picture', errorBag: 'hero');
        $this->assertStringContainsString('50 MB', session('errors')->getBag('hero')->first('picture'));

        // A phone photo of 20 MB is fine.
        $this->upload([], UploadedFile::fake()->image('hero.jpg', 1600, 1000)->size(20000))->assertSessionHasNoErrors();
        Storage::disk('public')->deleteDirectory('hero');

        // A picture whose header says 25000 x 25000 pixels (625 megapixels) is refused before it can use the memory.
        $header = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NNCCCCC', 25000, 25000, 8, 2, 0, 0, 0);
        $header .= pack('N', crc32('IHDR'.pack('NNCCCCC', 25000, 25000, 8, 2, 0, 0, 0)));
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '256M');
        try {
            $this->upload([], UploadedFile::fake()->createWithContent('huge.png', $header))->assertSessionHasErrors('picture', errorBag: 'hero');
            $this->assertStringContainsString('extremely large', session('errors')->getBag('hero')->first('picture'));
        } finally {
            ini_set('memory_limit', $previous);
        }
        $this->assertSame([], $this->files());
    }

    public function test_hidden_code_inside_a_picture_does_not_survive_because_the_picture_is_re_encoded(): void
    {
        $real = UploadedFile::fake()->image('hero.jpg', 1600, 1000);
        $withCode = UploadedFile::fake()->createWithContent('hero.jpg', file_get_contents($real->getRealPath()).'<?php system($_GET["x"]); ?> SECRET-GPS-NOTE');

        $this->upload([], $withCode)->assertSessionHas('notice');

        $this->assertCount(2, $this->files());
        foreach ($this->files() as $path) {
            $bytes = Storage::disk('public')->get($path);
            $this->assertStringNotContainsString('<?php', $bytes);
            $this->assertStringNotContainsString('SECRET-GPS-NOTE', $bytes);
        }
    }

    public function test_a_half_see_through_png_is_accepted(): void
    {
        $image = imagecreatetruecolor(1000, 800);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 100, 80, 60));

        $this->upload([], $this->encode($image, 'png', 'logo.png'))->assertSessionHas('notice');

        $this->assertCount(2, $this->files());
    }

    public function test_the_admin_page_does_not_scare_with_limits(): void
    {
        $html = $this->asAdmin()->get('/admin/settings')->getContent();

        $this->assertStringContainsString('Any picture works, of any size', $html);
        $this->assertStringContainsString('never cuts the picture', $html);
        $this->assertStringNotContainsString('at least 600', $html);
        $this->assertStringNotContainsString('pixels', $html);
        $this->assertStringContainsString('accept="image/*,.heic,.heif"', $html);
    }

    public function test_home_falls_back_to_the_drawing_when_the_files_are_missing_or_the_setting_is_odd(): void
    {
        $this->upload();
        Storage::disk('public')->deleteDirectory('hero'); // e.g. a restore that had no uploads
        $this->get('/')->assertOk()->assertSee('demo-card', false);

        foreach (['../../.env', 'hero/../../x', 'hero/short', '/etc/passwd', 'hero/ABCDEF0123456789'] as $odd) {
            SiteSettings::save(['hero_image' => $odd]);
            $this->assertNull(HeroPicture::current(), $odd);
        }
        $this->get('/')->assertOk()->assertSee('demo-card', false);
    }
}
