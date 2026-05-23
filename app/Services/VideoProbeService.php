<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VideoProbeService
{
    /**
     * Extract the video duration (in whole seconds) from a file on disk.
     * Returns null if ffprobe is unavailable or the file is unreadable —
     * callers should fall back to leaving duration unset.
     */
    public function probeDurationSeconds(string $filePath): ?int
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $ffprobePath = config('media-library.ffprobe_path');

        $command = sprintf(
            '%s -v quiet -show_entries format=duration -of json %s 2>/dev/null',
            escapeshellarg($ffprobePath),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::warning('VideoProbeService: ffprobe failed', [
                'file' => $filePath,
                'return_code' => $returnCode,
            ]);

            return null;
        }

        $data = json_decode(implode('', $output), true);
        $duration = $data['format']['duration'] ?? null;

        if ($duration === null) {
            return null;
        }

        $seconds = (int) round((float) $duration);

        return $seconds > 0 ? $seconds : null;
    }
}
