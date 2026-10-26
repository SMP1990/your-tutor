<?php

namespace App\Support;

use GdImage;

/**
 * Makes a plain background see-through, so a person photographed (or cut out) against white, grey or a chequered
 * "transparent" pattern can stand on the coloured circles of the Home page.
 *
 * It is deliberately careful. It only removes colours that
 *  1. are the colours of the picture's own border (the border must be one to four flat colours, otherwise the
 *     background is "busy" and nothing is done), and
 *  2. are connected to that border. White clothes inside the person are never touched, because they are not
 *     connected to the border, and thin bridges (up to 4 px) do not let the removal leak into the person.
 * The one-pixel light halo along the edge is cleaned up. If the result is not sensible (almost nothing, or almost
 * everything, would be removed) the picture is left alone.
 */
class BackgroundRemover
{
    /** How far (per colour channel, 0-255) a pixel may be from a background colour to still count as background. */
    private const TOLERANCE = 16;

    /** Border colours are grouped in steps of this size. */
    private const BUCKET = 24;

    /** The one to four most common border colours must cover this share of the border, or it is "busy". */
    private const PLAIN_SHARE = 0.85;

    /** Bridges thinner than 2 x this many pixels are not crossed. */
    private const ERODE = 2;

    private const MIN_REMOVED = 0.04;

    private const MAX_REMOVED = 0.92;

    /** Edge pixels this close to the background colour become fully clear; up to FRINGE_SOFT they fade out. */
    private const FRINGE_CLEAR = 30;

    private const FRINGE_SOFT = 75;

    /** How many pixels wide the edge band is that gets un-mixed, how far around a pixel the person's colour is looked for, and how far from the background a pixel must be to count as surely person. */
    private const BAND = 6;

    private const WINDOW = 10;

    private const SURE_PERSON = 90;

    /** Beyond the band, light pixels (wisps of hair over the background) are followed this much further. */
    private const EXTRA = 30;

    /** A pixel this far from the background colour counts as a dark strand when looking for wisps. */
    private const DARK_STRAND = 120;

    /**
     * @param  bool  $onlyLight  true: act only on white / light grey / chequered backgrounds (safe for "automatic");
     *                           false: any flat colour (when the admin asked for it)
     * @return GdImage|null a see-through copy, or null when nothing should be done
     */
    public static function remove(GdImage $image, bool $onlyLight): ?GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w < 40 || $h < 40) {
            return null;
        }

        $pixels = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $pixels[] = imagecolorat($image, $x, $y) & 0xFFFFFF;
            }
        }

        $model = self::borderColours($pixels, $w, $h, $onlyLight);
        if ($model === null) {
            return null;
        }

        $match = [];
        foreach ($pixels as $i => $colour) {
            $match[$i] = self::distance($colour, $model) <= self::TOLERANCE ? 1 : 0;
        }

        $removed = self::floodFromBorder(self::erode($match, $w, $h, self::ERODE), $w, $h);
        if ($removed === null) {
            return null;
        }
        $removed = self::dilate($removed, $match, $w, $h, self::ERODE);

        $share = array_sum($removed) / ($w * $h);
        if ($share < self::MIN_REMOVED || $share > self::MAX_REMOVED) {
            return null;
        }

        return self::cutOut($image, $pixels, $removed, $model, $w, $h);
    }

    /** @return list<array{0: int, 1: int, 2: int}>|null the flat colours of the border, or null if it is busy */
    private static function borderColours(array $pixels, int $w, int $h, bool $onlyLight): ?array
    {
        $groups = [];
        $total = 0;
        $add = function (int $x, int $y) use (&$groups, &$total, $pixels, $w) {
            $c = $pixels[$y * $w + $x];
            [$r, $g, $b] = [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
            $key = intdiv($r, self::BUCKET) * 10000 + intdiv($g, self::BUCKET) * 100 + intdiv($b, self::BUCKET);
            $groups[$key] ??= [0, 0, 0, 0];
            $groups[$key][0]++;
            $groups[$key][1] += $r;
            $groups[$key][2] += $g;
            $groups[$key][3] += $b;
            $total++;
        };

        for ($t = 0; $t < 3; $t++) {
            for ($x = 0; $x < $w; $x++) {
                $add($x, $t);
                $add($x, $h - 1 - $t);
            }
            for ($y = 3; $y < $h - 3; $y++) {
                $add($t, $y);
                $add($w - 1 - $t, $y);
            }
        }

        uasort($groups, fn ($a, $b) => $b[0] <=> $a[0]);
        $top = array_slice($groups, 0, 4);
        if (array_sum(array_column($top, 0)) / $total < self::PLAIN_SHARE) {
            return null;
        }

        $colours = [];
        foreach ($top as [$count, $r, $g, $b]) {
            if ($count / $total < 0.03) {
                continue;
            }
            $colour = [intdiv($r, $count), intdiv($g, $count), intdiv($b, $count)];
            if ($onlyLight && (min($colour) < 165 || max($colour) - min($colour) > 34)) {
                return null; // a coloured or dark border: not for the automatic mode
            }
            $colours[] = $colour;
        }

        return $colours ?: null;
    }

    private static function distance(int $colour, array $model): int
    {
        $r = ($colour >> 16) & 255;
        $g = ($colour >> 8) & 255;
        $b = $colour & 255;
        $best = 255;
        foreach ($model as [$mr, $mg, $mb]) {
            $best = min($best, max(abs($r - $mr), abs($g - $mg), abs($b - $mb)));
        }

        return $best;
    }

    /** Keeps only the pixels whose four neighbours also match (outside the picture counts as a match). */
    private static function erode(array $match, int $w, int $h, int $times): array
    {
        for ($k = 0; $k < $times; $k++) {
            $out = $match;
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $i = $y * $w + $x;
                    if ($match[$i] && (($x > 0 && ! $match[$i - 1]) || ($x < $w - 1 && ! $match[$i + 1]) || ($y > 0 && ! $match[$i - $w]) || ($y < $h - 1 && ! $match[$i + $w]))) {
                        $out[$i] = 0;
                    }
                }
            }
            $match = $out;
        }

        return $match;
    }

    /** @return array<int, int>|null 1 for every pixel reached from the border through matching pixels */
    private static function floodFromBorder(array $core, int $w, int $h): ?array
    {
        $reached = array_fill(0, $w * $h, 0);
        $stack = [];
        for ($x = 0; $x < $w; $x++) {
            foreach ([$x, ($h - 1) * $w + $x] as $i) {
                if ($core[$i]) {
                    $stack[] = $i;
                }
            }
        }
        for ($y = 0; $y < $h; $y++) {
            foreach ([$y * $w, $y * $w + $w - 1] as $i) {
                if ($core[$i]) {
                    $stack[] = $i;
                }
            }
        }

        $any = false;
        while ($stack) {
            $i = array_pop($stack);
            if ($reached[$i] || ! $core[$i]) {
                continue;
            }
            $reached[$i] = 1;
            $any = true;
            $x = $i % $w;
            if ($x > 0) {
                $stack[] = $i - 1;
            }
            if ($x < $w - 1) {
                $stack[] = $i + 1;
            }
            if ($i >= $w) {
                $stack[] = $i - $w;
            }
            if ($i < ($h - 1) * $w) {
                $stack[] = $i + $w;
            }
        }

        return $any ? $reached : null;
    }

    /** Grows the reached area back, but only over pixels that are really background colours. */
    private static function dilate(array $reached, array $match, int $w, int $h, int $times): array
    {
        for ($k = 0; $k < $times; $k++) {
            $out = $reached;
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $i = $y * $w + $x;
                    if (! $reached[$i] && $match[$i] && (($x > 0 && $reached[$i - 1]) || ($x < $w - 1 && $reached[$i + 1]) || ($y > 0 && $reached[$i - $w]) || ($y < $h - 1 && $reached[$i + $w]))) {
                        $out[$i] = 1;
                    }
                }
            }
            $reached = $out;
        }

        return $reached;
    }

    private static function cutOut(GdImage $image, array $pixels, array $removed, array $model, int $w, int $h): GdImage
    {
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopy($out, $image, 0, 0, 0, 0, $w, $h);

        foreach ($removed as $i => $gone) {
            if ($gone) {
                imagesetpixel($out, $i % $w, intdiv($i, $w), 0x7F000000);
            }
        }

        // Hair wisps and other thin edges are a mix of person and background. Along a band next to the removed area
        // work out how much of each pixel is really person (its see-through level) and give it the person's own
        // colour back, so no white or grey outline is left behind.
        $band = self::band($pixels, $removed, $model, $w, $h);
        foreach (array_keys($band) as $i) {
            $x = $i % $w;
            $y = intdiv($i, $w);
            $fore = self::nearbyPerson($pixels, $removed, $model, $x, $y, $w, $h);
            [$alpha, $colour] = $fore === null
                ? self::plainFringe($pixels[$i], $model)
                : self::unmix($pixels[$i], $fore, $model);
            if ($alpha <= 0.06) {
                imagesetpixel($out, $x, $y, 0x7F000000);
            } elseif ($alpha < 0.97) {
                imagesetpixel($out, $x, $y, ((int) round(127 * (1 - $alpha)) << 24) | $colour);
            }
        }
        return $out;
    }
    /** @return array<int, int> kept pixels next to the removed area (BAND steps, then EXTRA more through wispy places), index => 1 */
    private static function band(array $pixels, array $removed, array $model, int $w, int $h): array
    {
        $band = [];
        $front = [];
        foreach ($removed as $i => $gone) {
            if ($gone) {
                $front[] = $i;
            }
        }
        for ($round = 0; $round < self::BAND + self::EXTRA; $round++) {
            $next = [];
            foreach ($front as $i) {
                $x = $i % $w;
                foreach ([$x > 0 ? $i - 1 : null, $x < $w - 1 ? $i + 1 : null, $i >= $w ? $i - $w : null, $i < ($h - 1) * $w ? $i + $w : null] as $j) {
                    if ($j !== null && ! $removed[$j] && ! isset($band[$j]) && ($round < self::BAND || self::wispy($pixels, $model, $j % $w, intdiv($j, $w), $w, $h))) {
                        $band[$j] = 1;
                        $next[] = $j;
                    }
                }
            }
            $front = $next;
        }

        return $band;
    }

    /**
     * True where the picture looks like thin strands over the background: in the 9 x 9 square around a pixel there are
     * some light (background-like) pixels and a good share of dark ones. A plain white shirt has no such mix.
     */
    private static function wispy(array $pixels, array $model, int $x, int $y, int $w, int $h): bool
    {
        $dark = 0;
        $light = 0;
        for ($yy = max(0, $y - 4); $yy <= min($h - 1, $y + 4); $yy++) {
            for ($xx = max(0, $x - 4); $xx <= min($w - 1, $x + 4); $xx++) {
                $d = self::distance($pixels[$yy * $w + $xx], $model);
                $dark += $d >= self::DARK_STRAND ? 1 : 0;
                $light += $d < self::SURE_PERSON ? 1 : 0;
            }
        }

        return $light >= 6 && $dark >= 8;
    }

    /** @return array{0: int, 1: int, 2: int}|null the average colour of the clearly-person pixels around a spot */
    private static function nearbyPerson(array $pixels, array $removed, array $model, int $x, int $y, int $w, int $h): ?array
    {
        $sum = [0, 0, 0];
        $n = 0;
        for ($yy = max(0, $y - self::WINDOW); $yy <= min($h - 1, $y + self::WINDOW); $yy++) {
            for ($xx = max(0, $x - self::WINDOW); $xx <= min($w - 1, $x + self::WINDOW); $xx++) {
                $j = $yy * $w + $xx;
                if ($removed[$j] || self::distance($pixels[$j], $model) < self::SURE_PERSON) {
                    continue;
                }
                $sum[0] += ($pixels[$j] >> 16) & 255;
                $sum[1] += ($pixels[$j] >> 8) & 255;
                $sum[2] += $pixels[$j] & 255;
                $n++;
            }
        }

        return $n >= 6 ? [intdiv($sum[0], $n), intdiv($sum[1], $n), intdiv($sum[2], $n)] : null;
    }

    /** The old rule, when there is no person colour nearby to compare with: fade by distance from the background. */
    private static function plainFringe(int $pixel, array $model): array
    {
        $d = self::distance($pixel, $model);

        return [max(0.0, min(1.0, ($d - self::FRINGE_CLEAR) / (self::FRINGE_SOFT - self::FRINGE_CLEAR))), $pixel];
    }

    /**
     * pixel = alpha x person + (1 - alpha) x background. Works out alpha, and returns the person colour instead of
     * the washed-out mix.
     *
     * @return array{0: float, 1: int}
     */
    private static function unmix(int $pixel, array $person, array $model): array
    {
        $p = [($pixel >> 16) & 255, ($pixel >> 8) & 255, $pixel & 255];
        $bg = $model[0];
        $best = PHP_INT_MAX;
        foreach ($model as $m) {
            $dist = ($p[0] - $m[0]) ** 2 + ($p[1] - $m[1]) ** 2 + ($p[2] - $m[2]) ** 2;
            if ($dist < $best) {
                $best = $dist;
                $bg = $m;
            }
        }
        $axis = [$person[0] - $bg[0], $person[1] - $bg[1], $person[2] - $bg[2]];
        $len = $axis[0] ** 2 + $axis[1] ** 2 + $axis[2] ** 2;
        if (max(abs($axis[0]), abs($axis[1]), abs($axis[2])) < 60) {
            return self::plainFringe($pixel, $model); // person and background look alike: cannot tell them apart
        }
        $alpha = (($p[0] - $bg[0]) * $axis[0] + ($p[1] - $bg[1]) * $axis[1] + ($p[2] - $bg[2]) * $axis[2]) / $len;
        $alpha = max(0.0, min(1.0, $alpha));

        return [$alpha, ($person[0] << 16) | ($person[1] << 8) | $person[2]];
    }
}