<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The picture on the Home page hero (admin: Site settings). The admin may upload almost any picture and nothing is
 * ever cut off: it is decoded, turned the right way up (EXIF), made smaller if it is big (never enlarged), and saved
 * again as WebP in two sizes, keeping its own shape.
 *
 * Two looks, chosen automatically:
 *  - "cutout": the picture has a transparent background (a person cut out of her background). The empty margins are
 *    trimmed so the person stands tight on the coloured circles of the hero.
 *  - "photo": an ordinary picture. It is shown in a rounded frame.
 *
 * The uploaded file itself is never kept, so hidden data (GPS position, camera) and anything that is not really a
 * picture is gone. Without a picture the Home page shows its drawn "demo card".
 */
class HeroPicture
{
    public const DISK = 'public';

    public const DIR = 'hero';

    /** Longest side of the big file (desktop) and of the small file (phones). A smaller source is not enlarged. */
    public const BIG_SIDE = 1200;

    public const SMALL_SIDE = 640;

    /** Only a safety net against absurd files: normal phone photos (3-15 MB) are far below it. */
    public const MAX_UPLOAD_KB = 51200;

    public const ALT_MAX = 120;

    /** GD alpha runs 0 (solid) .. 127 (clear). Above this a pixel counts as "empty background". */
    private const CLEAR_ALPHA = 100;

    /** @return array{src: string, small: string, alt: string, width: int, height: int, small_width: int, small_height: int, mode: string}|null */
    public static function current(): ?array
    {
        $stored = SiteSettings::stored();
        $base = $stored['hero_image'] ?? null;
        if (! self::validBase($base)) {
            return null;
        }

        $big = (int) ($stored['hero_image_w'] ?? 0);
        $bigH = (int) ($stored['hero_image_h'] ?? 0);
        $small = (int) ($stored['hero_image_sw'] ?? 0);
        $smallH = (int) ($stored['hero_image_sh'] ?? 0);
        if ($big < 1 || $bigH < 1 || $small < 1 || $smallH < 1) {
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
            'height' => $bigH,
            'small_width' => $small,
            'small_height' => $smallH,
            'mode' => ($stored['hero_image_mode'] ?? 'photo') === 'cutout' ? 'cutout' : 'photo',
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
            $cutout = self::hasTransparentBackground($source);
            if ($cutout) {
                $source = self::trimEmptyMargins($source);
            }

            $big = self::scaled($source, self::BIG_SIDE);
            $small = self::scaled($source, self::SMALL_SIDE);
            $sameFile = imagesx($big) === imagesx($small) && imagesy($big) === imagesy($small);

            $bigW = imagesx($big);
            $smallW = $sameFile ? $bigW : imagesx($small);
            foreach ($sameFile ? [[$bigW, $big]] : [[$bigW, $big], [$smallW, $small]] as [$width, $image]) {
                $path = "{$base}-{$width}.webp";
                Storage::disk(self::DISK)->put($path, self::toWebp($image, $cutout));
                $saved[] = $path;
            }
            $settings = [
                'hero_image' => $base, 'hero_image_alt' => self::cleanAlt($alt), 'hero_image_mode' => $cutout ? 'cutout' : 'photo',
                'hero_image_w' => (string) $bigW, 'hero_image_h' => (string) imagesy($big),
                'hero_image_sw' => (string) $smallW, 'hero_image_sh' => (string) ($sameFile ? imagesy($big) : imagesy($small)),
            ];
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($saved);

            throw $e instanceof HeroPictureException ? $e : new HeroPictureException('This picture could not be saved. Please try another one.', 0, $e);
        } finally {
            foreach ([$source ?? null, $big ?? null, $small ?? null] as $image) {
                if ($image instanceof GdImage) {
                    imagedestroy($image);
                }
            }
        }

        $old = self::oldFiles();
        SiteSettings::save($settings);
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
        SiteSettings::save([
            'hero_image' => null, 'hero_image_alt' => null, 'hero_image_mode' => null,
            'hero_image_w' => null, 'hero_image_h' => null, 'hero_image_sw' => null, 'hero_image_sh' => null,
        ]);
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

        $widths = array_filter([(int) ($stored['hero_image_w'] ?? 0), (int) ($stored['hero_image_sw'] ?? 0)]);

        return array_values(array_map(fn (int $w) => "{$base}-{$w}.webp", array_unique($widths)));
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

    /** True when at least 2% of the picture is see-through: a cut-out person, a logo, an icon. */
    private static function hasTransparentBackground(GdImage $image): bool
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $step = max(1, (int) floor(max($w, $h) / 120));
        $clear = $total = 0;

        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $total++;
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) >= self::CLEAR_ALPHA) {
                    $clear++;
                }
            }
        }

        return $total > 0 && $clear / $total >= 0.02;
    }

    /** Cuts away the empty (see-through) border around a cut-out, leaving a small breathing space. */
    private static function trimEmptyMargins(GdImage $image): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $step = max(1, (int) floor(max($w, $h) / 400));
        $minX = $w;
        $minY = $h;
        $maxX = $maxY = -1;

        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) < self::CLEAR_ALPHA) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }
        if ($maxX < 0) {
            return $image; // nothing solid at all: leave it alone
        }

        $pad = (int) round(max($w, $h) * 0.01) + $step;
        $x0 = max(0, $minX - $pad);
        $y0 = max(0, $minY - $pad);
        $cropW = min($w, $maxX + $pad + 1) - $x0;
        $cropH = min($h, $maxY + $pad + 1) - $y0;
        if ($cropW >= $w * 0.97 && $cropH >= $h * 0.97) {
            return $image; // already tight
        }

        $out = self::blank($cropW, $cropH);
        imagecopy($out, $image, 0, 0, $x0, $y0, $cropW, $cropH);
        imagedestroy($image);

        return $out;
    }

    /** The picture made to fit inside a square of $side pixels, in its own shape. Never enlarged. */
    private static function scaled(GdImage $image, int $side): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $ratio = min(1, $side / max($w, $h));
        $newW = max(1, (int) round($w * $ratio));
        $newH = max(1, (int) round($h * $ratio));

        $out = self::blank($newW, $newH);
        imagecopyresampled($out, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);

        return $out;
    }

    private static function blank(int $w, int $h): GdImage
    {
        $image = imagecreatetruecolor($w, $h);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));

        return $image;
    }

    private static function toWebp(GdImage $image, bool $keepTransparency): string
    {
        ob_start();
        imagewebp($image, null, $keepTransparency ? 88 : 82);
        $bytes = (string) ob_get_clean();

        if ($bytes === '') {
            throw new HeroPictureException('This picture could not be saved. Please try another one.');
        }

        return $bytes;
    }
}
