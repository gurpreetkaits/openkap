<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ClipForgeService
{
    private string $tmpDir;

    private string $ffmpegPath;

    private string $ffprobePath;

    private string $ytdlpPath;

    public function __construct()
    {
        $this->tmpDir = storage_path('app/clipforge');
        $this->ffmpegPath = config('clipforge.ffmpeg_path', '/opt/homebrew/bin/ffmpeg');
        $this->ffprobePath = config('clipforge.ffprobe_path', '/opt/homebrew/bin/ffprobe');
        $this->ytdlpPath = config('clipforge.ytdlp_path', '/opt/homebrew/bin/yt-dlp');

        if (! is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
    }

    public function getTmpDir(): string
    {
        return $this->tmpDir;
    }

    /**
     * Validate a YouTube URL
     */
    public function validateYouTubeUrl(string $url): bool
    {
        $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|shorts\/|embed\/)|youtu\.be\/)[a-zA-Z0-9_-]{11}/';

        return (bool) preg_match($pattern, $url);
    }

    /**
     * Download a YouTube video using yt-dlp
     */
    public function downloadYouTube(string $url): array
    {
        $sessionId = Str::uuid()->toString();
        $outputPath = $this->tmpDir.'/'.$sessionId.'.mp4';

        // Step 1: Fetch metadata only (fast, no download)
        $metaResult = Process::timeout(30)->run([
            $this->ytdlpPath,
            '--dump-json',
            '--no-download',
            '--no-playlist',
            '--no-warnings',
            $url,
        ]);

        $title = 'Unknown';
        $thumbnail = null;
        $videoDuration = 0;

        if ($metaResult->successful()) {
            $meta = json_decode(trim($metaResult->output()), true);
            if ($meta) {
                $title = $meta['title'] ?? 'Unknown';
                $thumbnail = $meta['thumbnail'] ?? null;
                $videoDuration = (float) ($meta['duration'] ?? 0);
            }
        }

        Log::info('ClipForge: downloading YouTube video', [
            'url' => $url,
            'sessionId' => $sessionId,
            'title' => $title,
            'duration' => $videoDuration,
        ]);

        // Step 2: Download the video (720p max to keep size reasonable)
        $downloadResult = Process::timeout(600)->run([
            $this->ytdlpPath,
            '-f', 'bestvideo[height<=720][ext=mp4]+bestaudio[ext=m4a]/best[height<=720][ext=mp4]/best',
            '--merge-output-format', 'mp4',
            '-o', $outputPath,
            '--no-playlist',
            '--no-warnings',
            '--no-progress',
            $url,
        ]);

        if (! $downloadResult->successful()) {
            Log::error('ClipForge: yt-dlp download failed', [
                'error' => $downloadResult->errorOutput(),
                'output' => $downloadResult->output(),
            ]);
            throw new \RuntimeException('Failed to download video: '.$downloadResult->errorOutput());
        }

        if (! file_exists($outputPath)) {
            Log::error('ClipForge: output file missing after download', ['path' => $outputPath]);
            throw new \RuntimeException('Download completed but output file was not created');
        }

        // Use ffprobe duration if metadata fetch didn't get it
        if ($videoDuration <= 0) {
            $videoDuration = $this->probeDuration($outputPath);
        }

        return [
            'session_id' => $sessionId,
            'filename' => $sessionId.'.mp4',
            'title' => $title,
            'duration' => $videoDuration,
            'thumbnail' => $thumbnail,
            'size' => filesize($outputPath),
        ];
    }

    /**
     * Probe video duration using ffprobe
     */
    public function probeDuration(string $filePath): float
    {
        $result = Process::timeout(30)->run([
            $this->ffprobePath,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            $filePath,
        ]);

        if (! $result->successful()) {
            return 0;
        }

        $data = json_decode($result->output(), true);

        return (float) ($data['format']['duration'] ?? 0);
    }

    /**
     * Probe video metadata using ffprobe
     */
    public function probeVideo(string $filePath): array
    {
        $result = Process::timeout(30)->run([
            $this->ffprobePath,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $filePath,
        ]);

        if (! $result->successful()) {
            return [];
        }

        $data = json_decode($result->output(), true);
        $format = $data['format'] ?? [];
        $videoStream = null;

        foreach (($data['streams'] ?? []) as $stream) {
            if ($stream['codec_type'] === 'video') {
                $videoStream = $stream;
                break;
            }
        }

        return [
            'duration' => (float) ($format['duration'] ?? 0),
            'size' => (int) ($format['size'] ?? 0),
            'format' => $format['format_name'] ?? 'unknown',
            'width' => $videoStream['width'] ?? null,
            'height' => $videoStream['height'] ?? null,
            'codec' => $videoStream['codec_name'] ?? null,
        ];
    }

    /**
     * Extract a clip from a video file
     */
    public function extractClip(string $sourceFile, float $startTime, float $endTime, string $format): array
    {
        $sessionId = Str::uuid()->toString();
        $extension = $this->getExtension($format);
        $outputFilename = $sessionId.'.'.$extension;
        $outputPath = $this->tmpDir.'/'.$outputFilename;
        $sourcePath = $this->tmpDir.'/'.basename($sourceFile);

        if (! file_exists($sourcePath)) {
            throw new \RuntimeException('Source video not found');
        }

        $duration = $endTime - $startTime;

        $command = match ($format) {
            'mp4' => $this->buildMp4Command($sourcePath, $outputPath, $startTime, $duration),
            'webm' => $this->buildWebmCommand($sourcePath, $outputPath, $startTime, $duration),
            'gif' => $this->buildGifCommand($sourcePath, $outputPath, $startTime, $duration),
            'mp3' => $this->buildMp3Command($sourcePath, $outputPath, $startTime, $duration),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        Log::info('ClipForge: extracting clip', [
            'source' => $sourceFile,
            'format' => $format,
            'start' => $startTime,
            'end' => $endTime,
        ]);

        $result = Process::timeout(300)->run($command);

        if (! $result->successful()) {
            Log::error('ClipForge: ffmpeg failed', ['error' => $result->errorOutput()]);
            throw new \RuntimeException('Failed to extract clip: '.$result->errorOutput());
        }

        if (! file_exists($outputPath)) {
            throw new \RuntimeException('Output file was not created');
        }

        return [
            'filename' => $outputFilename,
            'size' => filesize($outputPath),
            'format' => $format,
            'duration' => $duration,
        ];
    }

    /**
     * Build MP4 extraction command
     * libx264 + aac, -preset fast, -crf 23, -movflags +faststart
     */
    private function buildMp4Command(string $input, string $output, float $start, float $duration): array
    {
        return [
            $this->ffmpegPath,
            '-y',
            '-ss', (string) $start,
            '-i', $input,
            '-t', (string) $duration,
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '23',
            '-c:a', 'aac',
            '-b:a', '128k',
            '-movflags', '+faststart',
            $output,
        ];
    }

    /**
     * Build WebM extraction command
     * libvpx + libopus, -crf 30, -b:v 0
     */
    private function buildWebmCommand(string $input, string $output, float $start, float $duration): array
    {
        return [
            $this->ffmpegPath,
            '-y',
            '-ss', (string) $start,
            '-i', $input,
            '-t', (string) $duration,
            '-c:v', 'libvpx-vp9',
            '-crf', '30',
            '-b:v', '0',
            '-c:a', 'libopus',
            '-b:a', '128k',
            $output,
        ];
    }

    /**
     * Build GIF extraction command
     * Two-pass (palettegen → paletteuse), fps=12, scale=480px wide
     */
    private function buildGifCommand(string $input, string $output, float $start, float $duration): array
    {
        $palettePath = $this->tmpDir.'/'.Str::uuid()->toString().'_palette.png';

        // Sanitize float params to prevent shell injection
        $safeStart = sprintf('%.4f', (float) $start);
        $safeDuration = sprintf('%.4f', (float) $duration);

        // For GIF we need two passes - use a shell command
        // First: generate palette, then: use palette to create GIF
        $filters = 'fps=12,scale=480:-1:flags=lanczos';

        return [
            'bash', '-c',
            "{$this->ffmpegPath} -y -ss {$safeStart} -i ".escapeshellarg($input).
            " -t {$safeDuration} -vf \"{$filters},palettegen\" ".escapeshellarg($palettePath).
            " && {$this->ffmpegPath} -y -ss {$safeStart} -i ".escapeshellarg($input).
            " -t {$safeDuration} -i ".escapeshellarg($palettePath).
            " -lavfi \"{$filters} [x]; [x][1:v] paletteuse\" ".escapeshellarg($output).
            ' && rm -f '.escapeshellarg($palettePath),
        ];
    }

    /**
     * Build MP3 extraction command
     * libmp3lame, -q:a 2 (VBR ~190kbps), no video
     */
    private function buildMp3Command(string $input, string $output, float $start, float $duration): array
    {
        return [
            $this->ffmpegPath,
            '-y',
            '-ss', (string) $start,
            '-i', $input,
            '-t', (string) $duration,
            '-vn',
            '-c:a', 'libmp3lame',
            '-q:a', '2',
            $output,
        ];
    }

    private function getExtension(string $format): string
    {
        return match ($format) {
            'mp4' => 'mp4',
            'webm' => 'webm',
            'gif' => 'gif',
            'mp3' => 'mp3',
            default => 'mp4',
        };
    }

    /**
     * Get the MIME type for a format
     */
    public function getMimeType(string $format): string
    {
        return match ($format) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'gif' => 'image/gif',
            'mp3' => 'audio/mpeg',
            default => 'application/octet-stream',
        };
    }

    /**
     * Clean up files older than the given minutes
     */
    public function cleanupStaleFiles(int $minutes = 10): void
    {
        $threshold = now()->subMinutes($minutes)->getTimestamp();
        $files = glob($this->tmpDir.'/*');

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                unlink($file);
                Log::info('ClipForge: cleaned up stale file', ['file' => basename($file)]);
            }
        }
    }

    /**
     * Delete all files in the tmp directory
     */
    public function purgeAllFiles(): int
    {
        $files = glob($this->tmpDir.'/*');
        $count = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Delete a specific file from the tmp directory
     */
    public function deleteFile(string $filename): void
    {
        $path = $this->tmpDir.'/'.basename($filename);

        if (is_file($path)) {
            unlink($path);
            Log::info('ClipForge: deleted file', ['file' => basename($filename)]);
        }
    }

    /**
     * Check if a file exists in the tmp directory
     */
    public function fileExists(string $filename): bool
    {
        $path = $this->tmpDir.'/'.basename($filename);

        return file_exists($path);
    }

    /**
     * Get the full path to a tmp file
     */
    public function getFilePath(string $filename): string
    {
        return $this->tmpDir.'/'.basename($filename);
    }
}
