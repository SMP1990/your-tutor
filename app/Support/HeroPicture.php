<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The picture on the Home page hero (admin: Site settings). The admin may upload almost any picture: it is decoded,
 * turned the right way up by its EXIF note, cut to 4:3 around the centre, made smaller if it is big (never bigger
 * than it is), and saved again as WebP in two sizes (up to 1200 and up to 600 px wide). The uploaded file itself is
 * never kept, so hidden data (GPS position, camera) and anything that is not really a picture is gone.
 * Without a picture the Home page shows its drawn "demo card".
 */
class HeroPicture
{
    public const DISK = 'public';

    public const DIR = 'hero';

    /** The widest picture we make, and the one for phones. A smaller source gives smaller files (no blurry enlarging). */
    public const BIG_WIDTH = 1200;

    public const SMALL_WIDTH = 600;

    /** Only a safety net against absurd files: normal phone photos (3-15 MB) are far below it. */
    public const MAX_UPLOAD_KB = 51200;

    public const ALT_MAX = 120;

    /** @return array{src: string, small: string, alt: string, width: int, small_width: int, height: int}|null */
    public static function current(): ?array
    {
        $stored = SiteSettings::stored();
        $base = $stored['hero_image'] ?? null;
        if (! self::validBase($base)) {
            return null;
        }

        $big = (int) ($stored['hero_image_w'] ?? self::BIG_WIDTH);
        $small = (int) ($stored['hero_image_sw'] ?? self::SMALL_WIDTH);
        if ($big < 1 || $small < 1 || $small > $big) {
            return null;
        }

        foreach (array_unique([$big, $small]) as $width) {
            if (! Storage::disk(self::DISK)->exists("{$base}-{$width}.webp")) {
                return null; // the files are gone (for example a restore without them): show the default design
            }
        }

        return [
            'src' => asset("storage/{$base}-{$big}.webp"),
            'small' => asset("storage/{$base}-{$small}.webp"),
            'alt' => (string) ($stored['hero_image_alt'] ?? ''),
            'width' => $big,
            'small_width' => $small,
            'height' => intdiv($big * 3, 4),
        ];
    }

    /** Replaces the picture. The old files are deleted only after the new ones were saved. */
    public static function store(UploadedFile $file, ?string $alt): void
    {
        $source = HeroPictureReader::read($file);
        $id = bin2hex(random_bytes(8));
        $base = self::DIR.'/'.$id;
        $saved = [];

        try {
            $sw = imagesx($source);
            $sh = imagesy($source);
            // The biggest 4:3 rectangle in the middle of the picture.
            $cropW = ($sw / $sh > 4 / 3) ? (int) round($sh * 4 / 3) : $sw;
            $cropH = ($sw / $sh > 4 / 3) ? $sh : (int) round($sw * 3 / 4);
            $cropW = max(1, $cropW);
            $cropH = max(1, $cropH);

            $big = min(self::BIG_WIDTH, $cropW);
            $small = min(self::SMALL_WIDTH, $big);

            foreach (array_unique([$big, $small]) as $width) {
                $path = "{$base}-{$width}.webp";
                Storage::disk(self::DISK)->put($path, self::resizeToWebp($source, $cropW, $cropH, $width));
                $saved[] = $path;
            }
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($saved);

            throw $e instanceof HeroPictureException ? $e : new HeroPictureException('This picture could not be saved. Please try another one.', 0, $e);
        } finally {
            imagedestroy($source);
        }

        $old = self::oldFiles();
        SiteSettings::save([
            'hero_image' => $base, 'hero_image_alt' => self::cleanAlt($alt),
            'hero_image_w' => (string) $big, 'hero_image_sw' => (string) $small,
        ]);
        Storage::disk(self::DISK)->delete($old);
    }

    public static function updateAlt(?string $alt): void
    {
        SiteSettings::save(['hero_image_alt' => self::cleanAlt($alt)]);
    }

    /** Back to the default design; the files are deleted. */
    public static function remove(): void
    {
        $old = self::oldFiles();
        SiteSettings::save(['hero_image' => null, 'hero_image_alt' => null, 'hero_image_w' => null, 'hero_image_sw' => null]);
        Storage::disk(self::DISK)->delete($old);
    }

    /** @return list<string> The files of the picture that is saved now (only paths of the right shape). */
    private static function oldFiles(): array
    {
        $stored = SiteSettings::stored();
        $base = $stored['hero_image'] ?? null;
        if (! self::validBase($base)) {
            return [];
        }

        $widths = [(int) ($stored['hero_image_w'] ?? self::BIG_WIDTH), (int) ($stored['hero_image_sw'] ?? self::SMALL_WIDTH)];

        return array_values(array_map(fn (int $w) => "{$base}-{$w}.webp", array_unique(array_filter($widths))));
    }

    private static function validBase(mixed $base): bool
    {
        return is_string($base) && (bool) preg_match('#^'.self::DIR.'/[a-f0-9]{16}$#', $base);
    }

    private static function cleanAlt(?string $alt): ?string
    {
        $alt = trim(strip_tags((string) $alt));

        return $alt === '' ? null : mb_substr($alt, 0, self::ALT_MAX);
    }

    /** Cuts the centred crop out of the source, scales it to $width (4:3) and returns the WebP bytes. */
    private static function resizeToWebp(GdImage $source, int $cropW, int $cropH, int $width): string
    {
        $height = max(1, intdiv($width * 3, 4));
        $sw = imagesx($source);
        $sh = imagesy($source);

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
            throw new HeroPictureException('This picture could not be saved. Please try another one.');
        }

        return $bytes;
    }
}
