<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The picture on the Home page hero (admin: Site settings). The uploaded file itself is never kept: it is decoded,
 * rotated by its EXIF note, cut to 4:3 around the centre, and saved again as two WebP files (1200 and 600 px wide).
 * That removes hidden data (GPS position, camera) and anything that is not really an image, and keeps the page fast.
 * Without a picture the Home page shows its drawn "demo card".
 */
class HeroPicture
{
    public const DISK = 'public';

    public const DIR = 'hero';

    /** width => height (4:3). The big one is shown on desktop, the small one on phones. */
    public const SIZES = [1200 => 900, 600 => 450];

    public const MIN_WIDTH = 600;

    public const MIN_HEIGHT = 450;

    public const MAX_SIDE = 6000;

    public const MAX_UPLOAD_KB = 4096;

    public const ALT_MAX = 120;

    /** @return array{src: string, small: string, alt: string, width: int, height: int}|null */
    public static function current(): ?array
    {
        $base = SiteSettings::stored()['hero_image'] ?? null;
        if (! $base || ! preg_match('#^'.self::DIR.'/[a-f0-9]{16}$#', $base)) {
            return null;
        }

        [$big, $small] = array_map(fn (int $w) => "{$base}-{$w}.webp", array_keys(self::SIZES));
        if (! Storage::disk(self::DISK)->exists($big) || ! Storage::disk(self::DISK)->exists($small)) {
            return null; // the files are gone (for example a restore without them): show the default design
        }

        return [
            'src' => asset("storage/{$big}"),
            'small' => asset("storage/{$small}"),
            'alt' => (string) (SiteSettings::stored()['hero_image_alt'] ?? ''),
            'width' => array_key_first(self::SIZES),
            'height' => self::SIZES[array_key_first(self::SIZES)],
        ];
    }

    /** Replaces the picture. The old files are deleted only after the new ones were saved. */
    public static function store(UploadedFile $file, ?string $alt): void
    {
        $source = self::decode($file);
        $id = bin2hex(random_bytes(8));
        $base = self::DIR.'/'.$id;
        $saved = [];

        try {
            foreach (self::SIZES as $width => $height) {
                $path = "{$base}-{$width}.webp";
                Storage::disk(self::DISK)->put($path, self::resizeToWebp($source, $width, $height));
                $saved[] = $path;
            }
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($saved);

            throw $e;
        } finally {
            imagedestroy($source);
        }

        $old = SiteSettings::stored()['hero_image'] ?? null;
        SiteSettings::save(['hero_image' => $base, 'hero_image_alt' => self::cleanAlt($alt)]);
        self::deleteFiles($old);
    }

    public static function updateAlt(?string $alt): void
    {
        SiteSettings::save(['hero_image_alt' => self::cleanAlt($alt)]);
    }

    /** Back to the default design; the files are deleted. */
    public static function remove(): void
    {
        $old = SiteSettings::stored()['hero_image'] ?? null;
        SiteSettings::save(['hero_image' => null, 'hero_image_alt' => null]);
        self::deleteFiles($old);
    }

    private static function cleanAlt(?string $alt): ?string
    {
        $alt = trim(strip_tags((string) $alt));

        return $alt === '' ? null : mb_substr($alt, 0, self::ALT_MAX);
    }

    private static function deleteFiles(?string $base): void
    {
        if ($base && preg_match('#^'.self::DIR.'/[a-f0-9]{16}$#', $base)) {
            Storage::disk(self::DISK)->delete(array_map(fn (int $w) => "{$base}-{$w}.webp", array_keys(self::SIZES)));
        }
    }

    private static function decode(UploadedFile $file): GdImage
    {
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('This is not a JPG, PNG or WebP picture.');
        }
        if ($info[0] > self::MAX_SIDE || $info[1] > self::MAX_SIDE) {
            throw new RuntimeException('This picture is too large.');
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $image) {
            throw new RuntimeException('This picture could not be read.');
        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($image, $angle, 0))) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        return $image;
    }

    /** Centre-crops to 4:3, resizes, and returns the WebP bytes. */
    private static function resizeToWebp(GdImage $source, int $width, int $height): string
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $ratio = $width / $height;
        $cropW = $sw / $sh > $ratio ? (int) round($sh * $ratio) : $sw;
        $cropH = $sw / $sh > $ratio ? $sh : (int) round($sw / $ratio);

        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 255, 255, 255, 127));
        imagecopyresampled($target, $source, 0, 0, intdiv($sw - $cropW, 2), intdiv($sh - $cropH, 2), $width, $height, $cropW, $cropH);

        ob_start();
        imagewebp($target, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($target);

        if ($bytes === '') {
            throw new RuntimeException('The picture could not be saved.');
        }

        return $bytes;
    }
}
