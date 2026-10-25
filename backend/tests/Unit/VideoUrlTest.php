<?php

namespace Tests\Unit;

use App\Support\VideoUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function supportedUrls(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', VideoUrl::YOUTUBE, 'dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube short link' => ['https://youtu.be/dQw4w9WgXcQ', VideoUrl::YOUTUBE, 'dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', VideoUrl::YOUTUBE, 'dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'youtube mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', VideoUrl::YOUTUBE, 'dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
            'vimeo public' => ['https://vimeo.com/76979871', VideoUrl::VIMEO, '76979871', 'https://player.vimeo.com/video/76979871'],
            'vimeo unlisted' => ['https://vimeo.com/76979871/abc123def4', VideoUrl::VIMEO, '76979871', 'https://player.vimeo.com/video/76979871?h=abc123def4'],
            'vimeo player' => ['https://player.vimeo.com/video/76979871?h=abc123def4', VideoUrl::VIMEO, '76979871', 'https://player.vimeo.com/video/76979871?h=abc123def4'],
            'bunny embed' => ['https://iframe.mediadelivery.net/embed/12345/0f8b5d2c-1a2b-4c3d-9e8f-123456789abc', VideoUrl::BUNNY, '0f8b5d2c-1a2b-4c3d-9e8f-123456789abc', 'https://iframe.mediadelivery.net/embed/12345/0f8b5d2c-1a2b-4c3d-9e8f-123456789abc'],
            'bunny play' => ['https://iframe.mediadelivery.net/play/12345/0f8b5d2c-1a2b-4c3d-9e8f-123456789abc', VideoUrl::BUNNY, '0f8b5d2c-1a2b-4c3d-9e8f-123456789abc', 'https://iframe.mediadelivery.net/embed/12345/0f8b5d2c-1a2b-4c3d-9e8f-123456789abc'],
        ];
    }

    #[DataProvider('supportedUrls')]
    public function test_parses_supported_urls(string $url, string $provider, string $id, string $embedUrl): void
    {
        $video = VideoUrl::parse($url);

        $this->assertNotNull($video);
        $this->assertSame($provider, $video->provider);
        $this->assertSame($id, $video->videoId);
        $this->assertSame($embedUrl, $video->embedUrl);
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function unsupportedUrls(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'not a url' => ['just some text'],
            'other host' => ['https://example.com/video.mp4'],
            'youtube without id' => ['https://www.youtube.com/watch'],
            'youtube channel' => ['https://www.youtube.com/@somechannel'],
            'vimeo non-numeric' => ['https://vimeo.com/channels/staffpicks'],
            'bunny bad id' => ['https://iframe.mediadelivery.net/embed/12345/not-a-guid'],
        ];
    }

    #[DataProvider('unsupportedUrls')]
    public function test_rejects_unsupported_urls(?string $url): void
    {
        $this->assertNull(VideoUrl::parse($url));
    }
}
