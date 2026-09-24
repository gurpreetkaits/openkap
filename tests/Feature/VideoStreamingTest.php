<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Range requests used to read the whole requested range into a PHP string
 * before sending it — up to 10 MB per request on a 2 GB single-core box, so
 * a few concurrent viewers could exhaust memory. These lock in streaming
 * behaviour and correct range semantics.
 */
class VideoStreamingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        $this->user = User::factory()->create();
    }

    /** Minimal MP4 ftyp box so finfo reports video/mp4. */
    private const MP4_HEADER = "\x00\x00\x00\x20ftypisom\x00\x00\x02\x00isomiso2avc1mp41";

    /**
     * A file large enough to miss the small-file fast path (>= 50 MB) and not
     * be webm, so it exercises the range-streaming branch.
     */
    private function largeVideo(int $sizeBytes = 60 * 1024 * 1024): Video
    {
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $path = sys_get_temp_dir().'/large-'.bin2hex(random_bytes(6)).'.mp4';
        $handle = fopen($path, 'wb');
        fwrite($handle, self::MP4_HEADER);
        // Sparse tail: 60 MB of addressable bytes without writing 60 MB.
        fseek($handle, $sizeBytes - 1);
        fwrite($handle, "\0");
        fclose($handle);

        $video->addMedia($path)->usingFileName("video_{$video->id}.mp4")->toMediaCollection('videos');

        $media = $video->getFirstMedia('videos');
        $media->mime_type = 'video/mp4';
        $media->save();

        return $video->refresh();
    }

    #[Test]
    public function a_range_request_is_streamed_rather_than_buffered(): void
    {
        $video = $this->largeVideo();

        $response = $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=0-1048575',
            ]);

        $response->assertStatus(206);

        $this->assertInstanceOf(
            StreamedResponse::class,
            $response->baseResponse,
            'Ranges must stream from disk, not be buffered into memory'
        );

        $this->assertSame('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertSame('1048576', $response->headers->get('Content-Length'));
        $this->assertSame('bytes 0-1048575/62914560', $response->headers->get('Content-Range'));
    }

    #[Test]
    public function memory_does_not_grow_with_the_size_of_the_range(): void
    {
        $video = $this->largeVideo();

        $response = $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=0-10485759', // the full 10 MB cap
            ]);

        $delivered = 0;
        $peak = 0;
        $before = memory_get_usage();

        // Discard each chunk as it is produced and watch memory as we go.
        // Capturing the body instead would measure the test's own buffer,
        // not the response.
        ob_start(function (string $buffer) use (&$delivered, &$peak) {
            $delivered += strlen($buffer);
            $peak = max($peak, memory_get_usage());

            return '';
        }, 262144);

        $response->baseResponse->sendContent();
        ob_end_flush();

        $overhead = $peak - $before;

        $this->assertSame(10 * 1024 * 1024, $delivered, 'the whole range must still be delivered');
        $this->assertLessThan(
            2 * 1024 * 1024,
            $overhead,
            'streaming a 10 MB range should not cost anything close to 10 MB of memory (saw '.$overhead.' bytes)'
        );
    }

    #[Test]
    public function a_range_beyond_the_end_of_the_file_is_rejected(): void
    {
        $video = $this->largeVideo();

        $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=99999999999-',
            ])
            ->assertStatus(416);
    }

    #[Test]
    public function a_malformed_range_header_is_rejected_instead_of_crashing(): void
    {
        $video = $this->largeVideo();

        // The old code ran preg_match then indexed $matches[1] unconditionally.
        $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=abc-def',
            ])
            ->assertStatus(416);
    }

    #[Test]
    public function an_oversized_range_is_capped_so_one_viewer_cannot_hold_a_worker(): void
    {
        $video = $this->largeVideo();

        $response = $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=0-', // "send me everything"
            ]);

        $response->assertStatus(206);
        $this->assertSame(
            'bytes 0-10485759/62914560',
            $response->headers->get('Content-Range'),
            'a single request should serve at most the 10 MB cap'
        );
    }

    #[Test]
    public function a_request_with_no_range_returns_an_opening_slice(): void
    {
        $video = $this->largeVideo();

        $response = $this->actingAs($this->user)
            ->get("/api/videos/{$video->id}/stream");

        $response->assertStatus(206);
        $this->assertSame('bytes 0-2097152/62914560', $response->headers->get('Content-Range'));
    }

    #[Test]
    public function the_requested_bytes_are_the_bytes_delivered(): void
    {
        // Content correctness: the streamed window must match the file.
        $video = Video::factory()->create(['user_id' => $this->user->id]);

        $payload = self::MP4_HEADER.str_repeat('ABCDEFGHIJ', 7 * 1024 * 1024); // > small-file cap
        $path = sys_get_temp_dir().'/content-'.bin2hex(random_bytes(6)).'.mp4';
        file_put_contents($path, $payload);

        $video->addMedia($path)->usingFileName("video_{$video->id}.mp4")->toMediaCollection('videos');
        $media = $video->getFirstMedia('videos');
        $media->mime_type = 'video/mp4';
        $media->save();

        $response = $this->actingAs($this->user)
            ->call('GET', "/api/videos/{$video->id}/stream", [], [], [], [
                'HTTP_RANGE' => 'bytes=1000-1099',
            ]);

        ob_start();
        $response->baseResponse->sendContent();
        $body = ob_get_clean();

        $this->assertSame(substr($payload, 1000, 100), $body);
    }

    #[Test]
    public function another_user_cannot_stream_someone_elses_video(): void
    {
        $video = $this->largeVideo();

        $this->actingAs(User::factory()->create())
            ->get("/api/videos/{$video->id}/stream")
            ->assertStatus(403);
    }
}
