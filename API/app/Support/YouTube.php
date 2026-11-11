<?php

namespace App\Support;

/** Reads the video ID (11 characters) from a YouTube link. */
class YouTube
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com', 'youtube-nocookie.com'];

    private const ID = '/^[A-Za-z0-9_-]{11}$/';

    /**
     * Works with: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID,
     * youtube.com/shorts/ID, youtube.com/live/ID. Anything else gives null.
     */
    public static function videoId(?string $url): ?string
    {
        $parts = parse_url(trim((string) $url));
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');

        if ($host === 'youtu.be') {
            $id = explode('/', $path)[0];
        } elseif ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);
            $id = $query['v'] ?? null;
        } else {
            [$type, $id] = array_pad(explode('/', $path), 2, null);
            $id = in_array($type, ['embed', 'shorts', 'live', 'v'], true) ? $id : null;
        }

        return is_string($id) && preg_match(self::ID, $id) ? $id : null;
    }
}
