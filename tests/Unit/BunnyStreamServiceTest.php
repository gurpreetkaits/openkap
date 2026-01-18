<?php

namespace Tests\Unit;

use App\Services\BunnyStreamService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BunnyStreamServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('services.bunny', [
            'library_id' => 'test-library-123',
            'api_key' => 'test-api-key-456',
            'cdn_hostname' => 'test-cdn.b-cdn.net',
            'security_key' => 'test-security-key-789',
            'playback_expiry' => 3600,
            'upload_expiry' => 7200,
            'base_url' => 'https://video.bunnycdn.com',
            'tus_endpoint' => 'https://video.bunnycdn.com/tusupload',
        ]);
    }

    // ==========================================
    // CONFIGURATION TESTS
    // ==========================================

    #[Test]
    public function is_configured_returns_true_when_library_and_api_key_set(): void
    {
        $service = new BunnyStreamService;

        $this->assertTrue($service->isConfigured());
    }

    #[Test]
    public function is_configured_returns_false_when_library_id_missing(): void
    {
        Config::set('services.bunny.library_id', '');

        $service = new BunnyStreamService;

        $this->assertFalse($service->isConfigured());
    }

    #[Test]
    public function is_configured_returns_false_when_api_key_missing(): void
    {
        Config::set('services.bunny.api_key', '');

        $service = new BunnyStreamService;

        $this->assertFalse($service->isConfigured());
    }

    #[Test]
    public function get_library_id_returns_configured_value(): void
    {
        $service = new BunnyStreamService;

        $this->assertEquals('test-library-123', $service->getLibraryId());
    }

    #[Test]
    public function get_cdn_hostname_returns_configured_value(): void
    {
        $service = new BunnyStreamService;

        $this->assertEquals('test-cdn.b-cdn.net', $service->getCdnHostname());
    }

    // ==========================================
    // CREATE VIDEO TESTS
    // ==========================================

    #[Test]
    public function create_video_makes_correct_api_call(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos' => Http::response([
                'guid' => 'new-video-guid-123',
                'title' => 'Test Video',
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $result = $service->createVideo('Test Video');

        $this->assertEquals('new-video-guid-123', $result['guid']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://video.bunnycdn.com/library/test-library-123/videos'
                && $request->method() === 'POST'
                && $request->header('AccessKey')[0] === 'test-api-key-456'
                && $request['title'] === 'Test Video';
        });
    }

    #[Test]
    public function create_video_includes_collection_id_when_provided(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos' => Http::response([
                'guid' => 'new-video-guid-123',
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $service->createVideo('Test Video', 'collection-abc');

        Http::assertSent(function ($request) {
            return $request['title'] === 'Test Video'
                && $request['collectionId'] === 'collection-abc';
        });
    }

    #[Test]
    public function create_video_throws_exception_on_failure(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create video in Bunny Stream');

        $service = new BunnyStreamService;
        $service->createVideo('Test Video');
    }

    // ==========================================
    // UPLOAD CREDENTIALS TESTS
    // ==========================================

    #[Test]
    public function generate_upload_credentials_returns_correct_structure(): void
    {
        $service = new BunnyStreamService;
        $credentials = $service->generateUploadCredentials('video-guid-123');

        $this->assertArrayHasKey('uploadUrl', $credentials);
        $this->assertArrayHasKey('libraryId', $credentials);
        $this->assertArrayHasKey('videoId', $credentials);
        $this->assertArrayHasKey('expireTime', $credentials);
        $this->assertArrayHasKey('signature', $credentials);

        $this->assertEquals('https://video.bunnycdn.com/tusupload', $credentials['uploadUrl']);
        $this->assertEquals('test-library-123', $credentials['libraryId']);
        $this->assertEquals('video-guid-123', $credentials['videoId']);
    }

    #[Test]
    public function generate_upload_credentials_creates_valid_signature(): void
    {
        $service = new BunnyStreamService;
        $credentials = $service->generateUploadCredentials('video-guid-123');

        // Verify signature format (SHA256 = 64 hex chars)
        $this->assertEquals(64, strlen($credentials['signature']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $credentials['signature']);
    }

    #[Test]
    public function generate_upload_credentials_uses_custom_expiry(): void
    {
        $service = new BunnyStreamService;

        $beforeTime = time();
        $credentials = $service->generateUploadCredentials('video-guid-123', 3600);
        $afterTime = time();

        // Expiry should be within expected range
        $this->assertGreaterThanOrEqual($beforeTime + 3600, $credentials['expireTime']);
        $this->assertLessThanOrEqual($afterTime + 3600, $credentials['expireTime']);
    }

    // ==========================================
    // PLAYBACK URL TESTS
    // ==========================================

    #[Test]
    public function generate_signed_playback_url_returns_correct_structure(): void
    {
        $service = new BunnyStreamService;
        $urls = $service->generateSignedPlaybackUrl('video-guid-123');

        $this->assertArrayHasKey('hlsUrl', $urls);
        $this->assertArrayHasKey('embedUrl', $urls);
        $this->assertArrayHasKey('thumbnailUrl', $urls);
        $this->assertArrayHasKey('expiresAt', $urls);
    }

    #[Test]
    public function generate_signed_playback_url_contains_video_id(): void
    {
        $service = new BunnyStreamService;
        $urls = $service->generateSignedPlaybackUrl('my-video-id');

        $this->assertStringContainsString('my-video-id', $urls['hlsUrl']);
        $this->assertStringContainsString('my-video-id', $urls['embedUrl']);
        $this->assertStringContainsString('my-video-id', $urls['thumbnailUrl']);
    }

    #[Test]
    public function generate_signed_playback_url_includes_token_and_expires(): void
    {
        $service = new BunnyStreamService;
        $urls = $service->generateSignedPlaybackUrl('video-guid-123');

        $this->assertStringContainsString('token=', $urls['hlsUrl']);
        $this->assertStringContainsString('expires=', $urls['hlsUrl']);
    }

    #[Test]
    public function generate_signed_playback_url_uses_cdn_hostname(): void
    {
        $service = new BunnyStreamService;
        $urls = $service->generateSignedPlaybackUrl('video-guid-123');

        $this->assertStringContainsString('test-cdn.b-cdn.net', $urls['hlsUrl']);
        $this->assertStringContainsString('test-cdn.b-cdn.net', $urls['thumbnailUrl']);
    }

    // ==========================================
    // GET VIDEO STATUS TESTS
    // ==========================================

    #[Test]
    public function get_video_status_returns_mapped_status(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'guid' => 'video-123',
                'status' => 4, // finished
                'length' => 120,
                'storageSize' => 5000000,
                'width' => 1920,
                'height' => 1080,
                'availableResolutions' => '720p,1080p',
                'encodeProgress' => 100,
                'title' => 'My Video',
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $status = $service->getVideoStatus('video-123');

        $this->assertEquals('video-123', $status['videoId']);
        $this->assertEquals('ready', $status['status']);
        $this->assertEquals(4, $status['rawStatus']);
        $this->assertEquals(120, $status['duration']);
        $this->assertEquals(5000000, $status['size']);
        $this->assertEquals(1920, $status['width']);
        $this->assertEquals(1080, $status['height']);
        $this->assertEquals(100, $status['encodeProgress']);
    }

    #[Test]
    public function get_video_status_maps_pending_status(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'guid' => 'video-123',
                'status' => 0,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('pending', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_uploaded_status(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'guid' => 'video-123',
                'status' => 1,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('uploaded', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_ready_status(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'guid' => 'video-123',
                'status' => 4,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('ready', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_error_status(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'guid' => 'video-123',
                'status' => 5,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('error', $status['status']);
    }

    #[Test]
    public function get_video_status_throws_exception_on_failure(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'error' => 'Not found',
            ], 404),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get video status from Bunny Stream');

        $service = new BunnyStreamService;
        $service->getVideoStatus('video-123');
    }

    // ==========================================
    // DELETE VIDEO TESTS
    // ==========================================

    #[Test]
    public function delete_video_returns_true_on_success(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([], 200),
        ]);

        $service = new BunnyStreamService;
        $result = $service->deleteVideo('video-123');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'video-123');
        });
    }

    #[Test]
    public function delete_video_returns_false_on_failure(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'error' => 'Not found',
            ], 404),
        ]);

        $service = new BunnyStreamService;
        $result = $service->deleteVideo('video-123');

        $this->assertFalse($result);
    }

    // ==========================================
    // UPDATE VIDEO TESTS
    // ==========================================

    #[Test]
    public function update_video_returns_true_on_success(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([], 200),
        ]);

        $service = new BunnyStreamService;
        $result = $service->updateVideo('video-123', ['title' => 'New Title']);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request['title'] === 'New Title';
        });
    }

    #[Test]
    public function update_video_returns_false_on_failure(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos/video-123' => Http::response([
                'error' => 'Invalid data',
            ], 400),
        ]);

        $service = new BunnyStreamService;
        $result = $service->updateVideo('video-123', ['title' => '']);

        $this->assertFalse($result);
    }

    // ==========================================
    // LIST VIDEOS TESTS
    // ==========================================

    #[Test]
    public function list_videos_returns_paginated_results(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos*' => Http::response([
                'items' => [
                    ['guid' => 'video-1', 'title' => 'Video 1'],
                    ['guid' => 'video-2', 'title' => 'Video 2'],
                ],
                'totalItems' => 50,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $result = $service->listVideos(1, 10);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(50, $result['totalItems']);
    }

    #[Test]
    public function list_videos_includes_search_parameter(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos*' => Http::response([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $service->listVideos(1, 10, 'test search');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'search=test+search')
                || str_contains($request->url(), 'search=test%20search');
        });
    }

    #[Test]
    public function list_videos_includes_collection_filter(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos*' => Http::response([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $service->listVideos(1, 10, null, 'collection-abc');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'collection=collection-abc');
        });
    }

    #[Test]
    public function list_videos_caps_per_page_at_100(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos*' => Http::response([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $service = new BunnyStreamService;
        $service->listVideos(1, 500); // Request 500

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'itemsPerPage=100'); // Should be capped at 100
        });
    }

    #[Test]
    public function list_videos_returns_empty_on_failure(): void
    {
        Http::fake([
            'video.bunnycdn.com/library/test-library-123/videos*' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $service = new BunnyStreamService;
        $result = $service->listVideos();

        $this->assertEquals(['items' => [], 'totalItems' => 0], $result);
    }
}
