<?php

namespace App\Support;

/**
 * Parses lesson video links from the supported hosts into an embeddable
 * form. Videos are never stored on our server, only linked.
 *
 * Supported:
 * - YouTube: youtube.com/watch?v=ID, youtu.be/ID, /embed/ID, /shorts/ID
 * - Vimeo: vimeo.com/ID, vimeo.com/ID/HASH (unlisted), player.vimeo.com/video/ID?h=HASH
 * - Bunny Stream: iframe.mediadelivery.net/{embed|play}/LIBRARY/VIDEO, player.mediadelivery.net/...
 */
final readonly class VideoUrl
{
    public const YOUTUBE = 'youtube';

    public const VIMEO = 'vimeo';

    public const BUNNY = 'bunny';

    private function __construct(
        public string $provider,
        public string $videoId,
        public string $embedUrl,
    ) {}

    public static function parse(?string $url): ?self
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url(trim($url));
        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host']) ?? '');
        $path = trim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);

        return match (true) {
            in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true) => self::youtube($host, $path, $query),
            in_array($host, ['vimeo.com', 'player.vimeo.com'], true) => self::vimeo($path, $query),
            in_array($host, ['iframe.mediadelivery.net', 'player.mediadelivery.net'], true) => self::bunny($path),
            default => null,
        };
    }

    public static function isSupported(?string $url): bool
    {
        return self::parse($url) !== null;
    }

    /**
     * @param  array<mixed>  $query
     */
    private static function youtube(string $host, string $path, array $query): ?self
    {
        $id = match (true) {
            $host === 'youtu.be' => explode('/', $path)[0],
            $path === 'watch' => is_string($query['v'] ?? null) ? $query['v'] : '',
            (bool) preg_match('#^(embed|shorts|live)/([^/]+)#', $path, $m) => $m[2],
            default => '',
        };

        if (! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return null;
        }

        return new self(self::YOUTUBE, $id, "https://www.youtube-nocookie.com/embed/{$id}");
    }

    /**
     * @param  array<mixed>  $query
     */
    private static function vimeo(string $path, array $query): ?self
    {
        if (! preg_match('#^(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?$#', $path, $m)) {
            return null;
        }

        $id = $m[1];
        // Unlisted videos need their privacy hash.
        $hash = $m[2] ?? (is_string($query['h'] ?? null) ? $query['h'] : null);
        $embedUrl = "https://player.vimeo.com/video/{$id}".($hash ? "?h={$hash}" : '');

        return new self(self::VIMEO, $id, $embedUrl);
    }

    private static function bunny(string $path): ?self
    {
        if (! preg_match('#^(?:embed|play)/(\d+)/([0-9a-fA-F-]{36})$#', $path, $m)) {
            return null;
        }

        return new self(self::BUNNY, $m[2], "https://iframe.mediadelivery.net/embed/{$m[1]}/{$m[2]}");
    }
}
