<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HeroPicture;
use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

/** The admin adds, replaces, describes and removes the Home page picture. Almost any picture is accepted. */
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

        return UploadedFile::fake()->createWithContent("picture.{$format}", $bytes);
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

    public function test_home_shows_the_default_design_until_a_picture_is_added(): void
    {
        $this->get('/')->assertOk()->assertSee('demo-card', false)->assertDontSee('hero__photo', false);
        $this->asAdmin()->get('/admin/settings')->assertOk()->assertSee('Home page picture')->assertSee('Add a picture')->assertDontSee('Remove picture');
    }

    public function test_a_big_photo_becomes_two_4_by_3_webp_files_and_replaces_the_drawing_on_home(): void
    {
        $this->upload(['alt' => 'A student learning online'])->assertRedirect(route('admin.settings.edit'))->assertSessionHas('notice');

        $this->assertSame([600 => [600, 450], 1200 => [1200, 900]], $this->sizes());

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('class="hero__photo"', $home);
        $this->assertStringContainsString('alt="A student learning online"', $home);
        $this->assertStringContainsString('fetchpriority="high"', $home);
        $this->assertMatchesRegularExpression('#<link rel="preload" as="image" href="[^"]*/storage/hero/[a-f0-9]{16}-1200\.webp"#', $home);
        $this->assertMatchesRegularExpression('#srcset="[^"]*-600\.webp 600w, [^"]*-1200\.webp 1200w"#', $home);
        $this->assertStringNotContainsString('demo-card', $home);
        $this->assertStringContainsString('Remove picture', $this->asAdmin()->get('/admin/settings')->getContent());
    }

    public function test_a_small_picture_is_accepted_and_not_blown_up(): void
    {
        $this->upload([], $this->gd('jpg', 300, 200))->assertSessionHas('notice')->assertSessionHasNoErrors();

        // 300x200 is wider than 4:3, so the middle 267x200 is used; one file, no srcset, no fake sharpness.
        $this->assertSame([267 => [267, 200]], $this->sizes());
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('class="hero__photo"', $home);
        $this->assertStringNotContainsString('srcset=', $home);
        $this->assertStringContainsString('width="267" height="200"', $home);
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

            $this->assertNotEmpty($this->sizes(), "{$format} must be accepted");
            $this->assertSame([600, 1000], array_keys($this->sizes()), $format);
        }
    }

    public function test_a_tall_phone_photo_is_cut_to_the_frame_and_a_huge_one_is_made_smaller(): void
    {
        $this->upload([], $this->gd('jpg', 1300, 2000))->assertSessionHasNoErrors();
        $sizes = $this->sizes();
        $this->assertSame([600 => [600, 450], 1200 => [1200, 900]], $sizes);

        $this->upload([], $this->gd('jpg', 1900, 1300))->assertSessionHasNoErrors();
        $this->assertSame([600 => [600, 450], 1200 => [1200, 900]], $this->sizes());
        $this->assertCount(2, $this->files(), 'only the newest pair is kept');
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

        $this->asAdmin()->delete(route('admin.hero.destroy'))->assertRedirect(route('admin.settings.edit'))->assertSessionHas('notice');

        $this->assertSame([], $this->files());
        $this->assertNull(SiteSettings::stored()['hero_image'] ?? null);
        $this->get('/')->assertSee('demo-card', false)->assertDontSee('hero__photo', false);
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

    public function test_a_transparent_png_is_accepted(): void
    {
        $image = imagecreatetruecolor(1000, 800);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 10, 100, 80, 60));
        ob_start();
        imagepng($image);
        $png = UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean());

        $this->upload([], $png)->assertSessionHas('notice');

        $this->assertCount(2, $this->files());
    }

    public function test_the_admin_page_does_not_scare_with_limits(): void
    {
        $html = $this->asAdmin()->get('/admin/settings')->getContent();

        $this->assertStringContainsString('Any picture works, of any size', $html);
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
