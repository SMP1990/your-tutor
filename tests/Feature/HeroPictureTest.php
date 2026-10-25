<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HeroPicture;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

/** The admin adds, replaces, describes and removes the Home page picture. */
class HeroPictureTest extends TestCase
{
    use SeedsDemoContent;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
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

    public function test_an_uploaded_picture_becomes_two_4_by_3_webp_files_and_replaces_the_drawing_on_home(): void
    {
        $this->upload(['alt' => 'A student learning online'])->assertRedirect(route('admin.settings.edit'))->assertSessionHas('notice');

        $files = $this->files();
        $this->assertCount(2, $files);
        foreach ($files as $path) {
            $bytes = Storage::disk('public')->get($path);
            $this->assertStringStartsWith('RIFF', $bytes);
            $this->assertSame('WEBP', substr($bytes, 8, 4), 'real WebP, not the uploaded file');
            $size = getimagesizefromstring($bytes);
            $this->assertSame(IMAGETYPE_WEBP, $size[2]);
            $this->assertEqualsWithDelta(4 / 3, $size[0] / $size[1], 0.01);
        }
        $widths = array_map(fn ($p) => getimagesizefromstring(Storage::disk('public')->get($p))[0], $files);
        sort($widths);
        $this->assertSame([600, 1200], $widths);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('class="hero__photo"', $home);
        $this->assertStringContainsString('alt="A student learning online"', $home);
        $this->assertStringContainsString('fetchpriority="high"', $home);
        $this->assertMatchesRegularExpression('#<link rel="preload" as="image" href="[^"]*/storage/hero/[a-f0-9]{16}-1200\.webp"#', $home);
        $this->assertMatchesRegularExpression('#srcset="[^"]*-600\.webp 600w, [^"]*-1200\.webp 1200w"#', $home);
        $this->assertStringNotContainsString('demo-card', $home);
        $this->assertStringContainsString('Remove picture', $this->asAdmin()->get('/admin/settings')->getContent());
    }

    public function test_replacing_deletes_the_old_files_and_removing_brings_the_drawing_back(): void
    {
        $this->upload();
        $first = $this->files();

        $this->upload([], UploadedFile::fake()->image('second.png', 2000, 1200));
        $second = $this->files();
        $this->assertCount(2, $second, 'only the new pair is kept');
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

    public function test_wrong_files_are_refused_and_nothing_is_saved(): void
    {
        $bad = [
            'not a picture' => UploadedFile::fake()->createWithContent('hero.jpg', 'this is only text'),
            'svg' => UploadedFile::fake()->createWithContent('hero.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="700"><script>alert(1)</script></svg>'),
            'gif' => UploadedFile::fake()->image('hero.gif', 1600, 1000),
            'pdf' => UploadedFile::fake()->create('hero.pdf', 100, 'application/pdf'),
            'php' => UploadedFile::fake()->createWithContent('hero.php', '<?php echo 1;'),
            'too small' => UploadedFile::fake()->image('hero.jpg', 300, 200),
            'too big a file' => UploadedFile::fake()->image('hero.jpg', 1600, 1000)->size(HeroPicture::MAX_UPLOAD_KB + 1),
            'too many pixels' => UploadedFile::fake()->image('hero.png', HeroPicture::MAX_SIDE + 1, 700),
        ];

        foreach ($bad as $label => $file) {
            $this->upload([], $file)->assertSessionHasErrors('picture', errorBag: 'hero');
            $this->assertSame([], $this->files(), "{$label} must not be stored");
        }
        $this->asAdmin()->post(route('admin.hero.store'), [])->assertSessionHasErrors('picture', errorBag: 'hero');
        $this->upload(['alt' => str_repeat('a', HeroPicture::ALT_MAX + 1)])->assertSessionHasErrors('alt', errorBag: 'hero');
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
