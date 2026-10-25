<?php

namespace Tests\Unit;

use App\Support\BackgroundRemover;
use GdImage;
use PHPUnit\Framework\TestCase;

/** Taking a plain background away without eating the person. */
class BackgroundRemoverTest extends TestCase
{
    private const SIZE = 360;

    protected function setUp(): void
    {
        parent::setUp();
        ini_set('memory_limit', '512M');
    }

    private function alphaAt(GdImage $image, int $x, int $y): int
    {
        return (imagecolorat($image, $x, $y) >> 24) & 0x7F;
    }

    /** A chequered "transparent" background painted into solid pixels (white and light grey squares). */
    private function checker(int $w = self::SIZE, int $h = self::SIZE): GdImage
    {
        $image = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($image, 255, 255, 255);
        $grey = imagecolorallocate($image, 238, 238, 238);
        for ($y = 0; $y < $h; $y += 30) {
            for ($x = 0; $x < $w; $x += 30) {
                imagefilledrectangle($image, $x, $y, $x + 29, $y + 29, (($x + $y) / 30) % 2 ? $grey : $white);
            }
        }

        return $image;
    }

    /** An orange "person" with a WHITE shirt inside her, in the middle of the picture. */
    private function addPerson(GdImage $image): void
    {
        $orange = imagecolorallocate($image, 200, 110, 40);
        $shirt = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, 180, 180, 200, 280, $orange);
        imagefilledellipse($image, 180, 215, 90, 120, $shirt);
    }

    public function test_a_chequered_background_goes_and_the_person_and_her_white_shirt_stay(): void
    {
        $image = $this->checker();
        $this->addPerson($image);

        $result = BackgroundRemover::remove($image, onlyLight: true);

        $this->assertInstanceOf(GdImage::class, $result);
        foreach ([[2, 2], [357, 2], [2, 357], [357, 357], [45, 180], [320, 100]] as [$x, $y]) {
            $this->assertSame(127, $this->alphaAt($result, $x, $y), "background at {$x},{$y} must be see-through");
        }
        $this->assertSame(0, $this->alphaAt($result, 180, 130), 'the person stays solid');
        $this->assertSame(0, $this->alphaAt($result, 180, 215), 'the white shirt inside the person is NOT removed');
        $this->assertSame(0, $this->alphaAt($result, 180, 300), 'the lower part of the person stays solid');
    }

    public function test_a_plain_white_background_goes_too(): void
    {
        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($image, 0, 0, imagecolorallocate($image, 252, 252, 252));
        $this->addPerson($image);

        $result = BackgroundRemover::remove($image, onlyLight: true);

        $this->assertNotNull($result);
        $this->assertSame(127, $this->alphaAt($result, 5, 5));
        $this->assertSame(0, $this->alphaAt($result, 180, 215), 'white shirt kept');
    }

    public function test_a_hair_thin_gap_in_the_outline_does_not_let_the_removal_into_the_white_inside(): void
    {
        $image = $this->checker();
        $orange = imagecolorallocate($image, 200, 110, 40);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, 180, 180, 240, 240, $orange);
        imagefilledellipse($image, 180, 180, 160, 160, $white);   // white inside, enclosed by a 40 px orange ring
        imagefilledrectangle($image, 60, 178, 100, 181, $white);  // a 4 px channel through the ring to the outside

        $result = BackgroundRemover::remove($image, onlyLight: true);

        $this->assertNotNull($result);
        $this->assertSame(0, $this->alphaAt($result, 180, 180), 'the white inside must survive the thin channel');
        $this->assertSame(127, $this->alphaAt($result, 5, 5));
    }

    public function test_a_busy_background_is_left_alone(): void
    {
        mt_srand(7);
        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        for ($y = 0; $y < self::SIZE; $y += 4) {
            for ($x = 0; $x < self::SIZE; $x += 4) {
                imagefilledrectangle($image, $x, $y, $x + 3, $y + 3, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            }
        }
        $this->addPerson($image);

        $this->assertNull(BackgroundRemover::remove($image, onlyLight: true));
        $this->assertNull(BackgroundRemover::remove($image, onlyLight: false));
    }

    public function test_a_coloured_flat_background_is_only_removed_when_asked(): void
    {
        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 100));
        $this->addPerson($image);

        $this->assertNull(BackgroundRemover::remove($image, onlyLight: true), 'automatic mode only touches light backgrounds');

        $result = BackgroundRemover::remove($image, onlyLight: false);
        $this->assertNotNull($result);
        $this->assertSame(127, $this->alphaAt($result, 4, 4));
        $this->assertSame(0, $this->alphaAt($result, 180, 130));
    }

    public function test_nothing_to_remove_or_nothing_left_means_no_change(): void
    {
        $blank = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($blank, 0, 0, imagecolorallocate($blank, 255, 255, 255));
        $this->assertNull(BackgroundRemover::remove($blank, onlyLight: true), 'a picture that is only background would vanish');

        $this->assertNull(BackgroundRemover::remove(imagecreatetruecolor(20, 20), onlyLight: true), 'too small to judge');

        // A picture that fills the whole frame (nothing at the border to call background): the white dress is not touched.
        $full = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($full, 0, 0, imagecolorallocate($full, 200, 110, 40));
        imagefilledellipse($full, 180, 180, 120, 120, imagecolorallocate($full, 255, 255, 255));
        $this->assertNull(BackgroundRemover::remove($full, onlyLight: true));
    }

    public function test_the_light_halo_along_the_edge_is_cleaned_up(): void
    {
        $image = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        // a person with a soft, nearly white edge ring (anti-aliasing against the white)
        imagefilledellipse($image, 180, 180, 206, 286, imagecolorallocate($image, 236, 226, 214));
        imagefilledellipse($image, 180, 180, 200, 280, imagecolorallocate($image, 200, 110, 40));

        $result = BackgroundRemover::remove($image, onlyLight: true);

        $this->assertNotNull($result);
        $this->assertGreaterThan(60, $this->alphaAt($result, 180, 180 - 143), 'the outer pixel of the pale outline is mostly see-through');
        $this->assertGreaterThan(60, $this->alphaAt($result, 180, 180 - 142), 'and so is the next one');
        $this->assertSame(0, $this->alphaAt($result, 180, 180 - 120), 'the person herself is solid');
    }
}
