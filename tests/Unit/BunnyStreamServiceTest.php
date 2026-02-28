<?php

namespace Tests\Unit;

use App\Http\Integrations\Bunny\BunnyConnector;
use App\Http\Integrations\Bunny\Requests\CreateVideoRequest;
use App\Http\Integrations\Bunny\Requests\DeleteVideoRequest;
use App\Http\Integrations\Bunny\Requests\GetVideoRequest;
use App\Http\Integrations\Bunny\Requests\ListVideosRequest;
use App\Http\Integrations\Bunny\Requests\UpdateVideoRequest;
use App\Services\BunnyStreamService;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Tests\TestCase;

class BunnyStreamServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->markTestSkipped('Bunny disabled - encoding costs too high');

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
        $mockClient = new MockClient([
            CreateVideoRequest::class => MockResponse::make([
                'guid' => 'new-video-guid-123',
                'title' => 'Test Video',
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->createVideo('Test Video');

        $this->assertEquals('new-video-guid-123', $result['guid']);

        $mockClient->assertSent(CreateVideoRequest::class);
    }

    #[Test]
    public function create_video_includes_collection_id_when_provided(): void
    {
        $mockClient = new MockClient([
            CreateVideoRequest::class => MockResponse::make([
                'guid' => 'new-video-guid-123',
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $service->createVideo('Test Video', 'collection-abc');

        $mockClient->assertSent(function (CreateVideoRequest $request) {
            $body = $request->body()->all();

            return $body['title'] === 'Test Video'
                && $body['collectionId'] === 'collection-abc';
        });
    }

    #[Test]
    public function create_video_throws_exception_on_failure(): void
    {
        $mockClient = new MockClient([
            CreateVideoRequest::class => MockResponse::make([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create video in Bunny Stream');

        $service = new BunnyStreamService($connector);
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
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
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

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
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
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
                'guid' => 'video-123',
                'status' => 0,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('pending', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_uploaded_status(): void
    {
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
                'guid' => 'video-123',
                'status' => 1,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('uploaded', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_ready_status(): void
    {
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
                'guid' => 'video-123',
                'status' => 4,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('ready', $status['status']);
    }

    #[Test]
    public function get_video_status_maps_error_status(): void
    {
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
                'guid' => 'video-123',
                'status' => 5,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $status = $service->getVideoStatus('video-123');
        $this->assertEquals('error', $status['status']);
    }

    #[Test]
    public function get_video_status_throws_exception_on_failure(): void
    {
        $mockClient = new MockClient([
            GetVideoRequest::class => MockResponse::make([
                'error' => 'Not found',
            ], 404),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get video status from Bunny Stream');

        $service = new BunnyStreamService($connector);
        $service->getVideoStatus('video-123');
    }

    // ==========================================
    // DELETE VIDEO TESTS
    // ==========================================

    #[Test]
    public function delete_video_returns_true_on_success(): void
    {
        $mockClient = new MockClient([
            DeleteVideoRequest::class => MockResponse::make([], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->deleteVideo('video-123');

        $this->assertTrue($result);

        $mockClient->assertSent(DeleteVideoRequest::class);
    }

    #[Test]
    public function delete_video_returns_false_on_failure(): void
    {
        $mockClient = new MockClient([
            DeleteVideoRequest::class => MockResponse::make([
                'error' => 'Not found',
            ], 404),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->deleteVideo('video-123');

        $this->assertFalse($result);
    }

    // ==========================================
    // UPDATE VIDEO TESTS
    // ==========================================

    #[Test]
    public function update_video_returns_true_on_success(): void
    {
        $mockClient = new MockClient([
            UpdateVideoRequest::class => MockResponse::make([], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->updateVideo('video-123', ['title' => 'New Title']);

        $this->assertTrue($result);

        $mockClient->assertSent(function (UpdateVideoRequest $request) {
            $body = $request->body()->all();

            return $body['title'] === 'New Title';
        });
    }

    #[Test]
    public function update_video_returns_false_on_failure(): void
    {
        $mockClient = new MockClient([
            UpdateVideoRequest::class => MockResponse::make([
                'error' => 'Invalid data',
            ], 400),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->updateVideo('video-123', ['title' => '']);

        $this->assertFalse($result);
    }

    // ==========================================
    // LIST VIDEOS TESTS
    // ==========================================

    #[Test]
    public function list_videos_returns_paginated_results(): void
    {
        $mockClient = new MockClient([
            ListVideosRequest::class => MockResponse::make([
                'items' => [
                    ['guid' => 'video-1', 'title' => 'Video 1'],
                    ['guid' => 'video-2', 'title' => 'Video 2'],
                ],
                'totalItems' => 50,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->listVideos(1, 10);

        $this->assertCount(2, $result['items']);
        $this->assertEquals(50, $result['totalItems']);
    }

    #[Test]
    public function list_videos_includes_search_parameter(): void
    {
        $mockClient = new MockClient([
            ListVideosRequest::class => MockResponse::make([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $service->listVideos(1, 10, 'test search');

        $mockClient->assertSent(function (ListVideosRequest $request) {
            $query = $request->query()->all();

            return isset($query['search']) && $query['search'] === 'test search';
        });
    }

    #[Test]
    public function list_videos_includes_collection_filter(): void
    {
        $mockClient = new MockClient([
            ListVideosRequest::class => MockResponse::make([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $service->listVideos(1, 10, null, 'collection-abc');

        $mockClient->assertSent(function (ListVideosRequest $request) {
            $query = $request->query()->all();

            return isset($query['collection']) && $query['collection'] === 'collection-abc';
        });
    }

    #[Test]
    public function list_videos_caps_per_page_at_100(): void
    {
        $mockClient = new MockClient([
            ListVideosRequest::class => MockResponse::make([
                'items' => [],
                'totalItems' => 0,
            ], 200),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $service->listVideos(1, 500); // Request 500

        $mockClient->assertSent(function (ListVideosRequest $request) {
            $query = $request->query()->all();

            return $query['itemsPerPage'] === 100; // Should be capped at 100
        });
    }

    #[Test]
    public function list_videos_returns_empty_on_failure(): void
    {
        $mockClient = new MockClient([
            ListVideosRequest::class => MockResponse::make([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $connector = new BunnyConnector;
        $connector->withoutLogging();
        $connector->withMockClient($mockClient);

        $service = new BunnyStreamService($connector);
        $result = $service->listVideos();

        $this->assertEquals(['items' => [], 'totalItems' => 0], $result);
    }
}
