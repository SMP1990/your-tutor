<?php

namespace App\Support;

/**
 * The big heading on the Home page, edited by the admin (settings `hero_title` and `hero_mark`). `hero_mark` is the
 * part of the heading that gets the coloured highlight. Nothing set = the original wording. The site shows the
 * heading in capital letters, so the admin can type it in normal sentence case.
 */
class HeroHeading
{
    public const DEFAULT_TITLE = 'Find a trusted tutor for every subject, online.';

    public const DEFAULT_MARK = 'trusted tutor';

    public const TITLE_MAX = 90;

    public const MARK_MAX = 50;

    /** @return array{title: string, mark: string} what the admin typed ('' = not set) */
    public static function stored(): array
    {
        $stored = SiteSettings::stored();

        return ['title' => (string) ($stored['hero_title'] ?? ''), 'mark' => (string) ($stored['hero_mark'] ?? '')];
    }

    /** @return array{title: string, mark: string} what the Home page really uses */
    public static function current(): array
    {
        $stored = self::stored();

        return $stored['title'] === ''
            ? ['title' => self::DEFAULT_TITLE, 'mark' => self::DEFAULT_MARK]
            : $stored;
    }

    /**
     * The heading cut into [before, highlighted, after]. If the highlighted words are not in the heading
     * (or none were given) there is no highlight: everything is in "before".
     *
     * @return array{0: string, 1: string, 2: string}
     */
    public static function parts(): array
    {
        ['title' => $title, 'mark' => $mark] = self::current();
        $at = $mark === '' ? false : mb_stripos($title, $mark);
        if ($at === false) {
            return [$title, '', ''];
        }

        return [mb_substr($title, 0, $at), mb_substr($title, $at, mb_strlen($mark)), mb_substr($title, $at + mb_strlen($mark))];
    }
}
