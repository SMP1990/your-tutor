<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;

/**
 * Turns whatever the admin uploads into one GD image, or says in plain words why it cannot be done.
 * GD reads JPG, PNG, WebP, GIF (first frame), BMP and AVIF. iPhone pictures (HEIC) and TIFF go through Imagick when the
 * server has it. Nothing else is opened: SVG, PDF, text and so on are refused without being read as pictures.
 */
class HeroPictureReader
{
    /** What GD can decode. */
    private const GD_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_BMP];

    /** Brands of HEIC / HEIF files (the 4 letters after "ftyp"). */
    private const HEIC_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1', 'msf1'];

    /** A HEIC picture is shrunk to this many pixels on its longest side before it is opened by GD (memory). */
    private const IMAGICK_MAX_SIDE = 4000;

    public static function read(UploadedFile $file): GdImage
    {
        $path = (string) $file->getRealPath();
        $info = @getimagesize($path);

        if ($info !== false) {
            $type = $info[2];
            if (in_array($type, self::GD_TYPES, true) || (defined('IMAGETYPE_AVIF') && $type === IMAGETYPE_AVIF)) {
                self::checkMemory((int) $info[0], (int) $info[1], (int) filesize($path));

                return self::fromGd($path, $type);
            }
            if (in_array($type, [IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM], true)) {
                return self::fromImagick($path, 'TIFF');
            }

            throw new HeroPictureException('This kind of picture is not supported. Please use a JPG, PNG, WebP, GIF, BMP or iPhone (HEIC) picture.');
        }

        $head = (string) @file_get_contents($path, false, null, 0, 1024);
        if (self::isHeic($head)) {
            return self::fromImagick($path, 'HEIC');
        }
        if (str_contains(strtolower($head), '<svg')) {
            throw new HeroPictureException('SVG drawings are not accepted here. Please save the picture as PNG or JPG and upload that.');
        }
        if (str_starts_with($head, '%PDF')) {
            throw new HeroPictureException('This is a PDF, not a picture. Please upload a JPG, PNG or WebP picture.');
        }

        throw new HeroPictureException('This file is not a picture, or it is damaged. Please try a JPG, PNG, WebP, GIF, BMP or iPhone (HEIC) picture.');
    }

    private static function isHeic(string $head): bool
    {
        return strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp' && in_array(substr($head, 8, 4), self::HEIC_BRANDS, true);
    }

    private static function fromGd(string $path, int $type): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) {
            throw new HeroPictureException('This picture is damaged or could not be read. Please try another one.');
        }

        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($path)['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($image, $angle, 0))) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        return $image;
    }

    private static function fromImagick(string $path, string $format): GdImage
    {
        if (! extension_loaded('imagick') || ! class_exists(\Imagick::class)) {
            throw new HeroPictureException('This kind of picture (iPhone HEIC or TIFF) cannot be read on this server. Please save it as JPG or PNG and upload that.');
        }

        try {
            $imagick = new \Imagick;
            $imagick->readImage($format.':'.$path); // the reader is chosen by us, not by what the file claims to be
            $imagick->setIteratorIndex(0);
            if (method_exists($imagick, 'autoOrient')) {
                $imagick->autoOrient();
            }
            if (max($imagick->getImageWidth(), $imagick->getImageHeight()) > self::IMAGICK_MAX_SIDE) {
                $imagick->thumbnailImage(self::IMAGICK_MAX_SIDE, self::IMAGICK_MAX_SIDE, true);
            }
            $imagick->setImageFormat('png');
            $blob = $imagick->getImageBlob();
            $imagick->clear();
        } catch (\Throwable) {
            throw new HeroPictureException('This picture could not be read. Please save it as JPG or PNG and upload that.');
        }

        $image = @imagecreatefromstring($blob);
        if (! $image) {
            throw new HeroPictureException('This picture could not be read. Please save it as JPG or PNG and upload that.');
        }

        return $image;
    }

    /** Refuses only a picture so big that opening it would crash the server, and says so in plain words. */
    private static function checkMemory(int $width, int $height, int $fileBytes): void
    {
        $need = $width * $height * 4 + $fileBytes * 2 + 32 * 1024 * 1024;
        $limit = self::memoryLimit();

        if ($limit !== -1 && $need > $limit * 0.85) {
            @ini_set('memory_limit', '1024M');
            $limit = self::memoryLimit();
        }
        if ($limit !== -1 && $need > $limit * 0.85) {
            $megapixels = (int) round($width * $height / 1_000_000);

            throw new HeroPictureException("This picture is extremely large ({$megapixels} megapixels). Please make it smaller first (for example send it to yourself on WhatsApp, or choose a smaller size on your phone) and upload that.");
        }
    }

    private static function memoryLimit(): int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '-1') {
            return -1;
        }
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
