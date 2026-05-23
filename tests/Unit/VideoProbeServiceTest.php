<?php

namespace Tests\Unit;

use App\Services\VideoProbeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VideoProbeServiceTest extends TestCase
{
    #[Test]
    public function returns_null_when_file_does_not_exist(): void
    {
        $service = new VideoProbeService;

        $this->assertNull($service->probeDurationSeconds('/tmp/this-file-definitely-does-not-exist.mp4'));
    }

    #[Test]
    public function returns_null_when_ffprobe_cannot_parse_the_file(): void
    {
        // Write a non-video file — ffprobe will exit non-zero and the
        // service should swallow that as null so the upload doesn't 500.
        $path = tempnam(sys_get_temp_dir(), 'probe-test-');
        file_put_contents($path, 'this is not a video');

        $service = new VideoProbeService;

        $this->assertNull($service->probeDurationSeconds($path));

        @unlink($path);
    }
}
