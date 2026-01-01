<?php

namespace App\Http\Controllers;

use App\Managers\VideoManager;
use App\Models\Video;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HlsController extends Controller
{
    public function __construct(
        protected VideoManager $videoManager
    ) {}

    /**
     * Check if the current user can access the video's HLS stream.
     */
    protected function canAccessVideo(Video $video): bool
    {
        // Allow if share link is valid (public video)
        if ($video->isShareLinkValid()) {
            return true;
        }

        // Allow if authenticated user is the owner
        $user = Auth::guard('sanctum')->user();

        return $user && $user->id === $video->user_id;
    }

    /**
     * Serve HLS master playlist for a shared video.
     */
    public function masterPlaylist(string $token): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video) {
            Log::error('HLS: Video not found for token', ['token' => substr($token, 0, 20).'...']);
            abort(404, 'Video not found');
        }

        if (! $this->canAccessVideo($video)) {
            Log::error('HLS: Access denied', [
                'video_id' => $video->id,
                'is_public' => $video->is_public,
                'is_share_valid' => $video->isShareLinkValid(),
            ]);
            abort(403, 'Access denied');
        }

        if (! $video->isHlsReady()) {
            Log::error('HLS: Not ready', [
                'video_id' => $video->id,
                'hls_status' => $video->hls_status,
                'hls_path' => $video->hls_path,
            ]);
            abort(404, 'HLS not ready');
        }

        $filePath = $this->getFilePath($video->id, 'master.m3u8');

        if (! $filePath) {
            Log::error('HLS: Master playlist not found', [
                'video_id' => $video->id,
                'expected_path' => Storage::path('public/hls/'.$video->id.'/master.m3u8'),
                'file_exists' => file_exists(Storage::path('public/hls/'.$video->id.'/master.m3u8')),
            ]);
            abort(404, 'Playlist not found');
        }

        return $this->streamFile($filePath, 'application/vnd.apple.mpegurl');
    }

    /**
     * Serve HLS variant playlist for a shared video.
     */
    public function variantPlaylist(string $token, string $variant): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video || ! $this->canAccessVideo($video)) {
            abort(404, 'Video not found');
        }

        if (! $video->isHlsReady()) {
            abort(404, 'HLS not ready');
        }

        // Sanitize variant name to prevent directory traversal
        $variant = basename($variant);
        $filePath = $this->getFilePath($video->id, $variant.'.m3u8');

        if (! $filePath) {
            abort(404, 'Variant playlist not found');
        }

        return $this->streamFile($filePath, 'application/vnd.apple.mpegurl');
    }

    /**
     * Serve HLS segment for a shared video.
     */
    public function segment(string $token, string $segment): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video || ! $this->canAccessVideo($video)) {
            abort(404, 'Video not found');
        }

        if (! $video->isHlsReady()) {
            abort(404, 'HLS not ready');
        }

        // Sanitize segment name to prevent directory traversal
        $segment = basename($segment);
        $filePath = $this->getFilePath($video->id, $segment.'.ts');

        if (! $filePath) {
            abort(404, 'Segment not found');
        }

        return $this->streamFile($filePath, 'video/mp2t');
    }

    /**
     * Get the actual file path from storage.
     * Laravel's public/storage symlinks to storage/app/public
     */
    protected function getFilePath(int $videoId, string $filename): ?string
    {
        // Path relative to storage/app/ (Storage facade root)
        // Files are at: storage/app/public/hls/{id}/{file}
        // Accessible via symlink at: public/storage/hls/{id}/{file}
        $storagePath = 'public/hls/'.$videoId.'/'.$filename;

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        return null;
    }

    /**
     * Stream a file with CORS headers.
     */
    protected function streamFile(string $filePath, string $mimeType): StreamedResponse
    {
        $fileSize = filesize($filePath);

        return new StreamedResponse(function () use ($filePath) {
            $stream = fopen($filePath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Range',
            'Cache-Control' => $mimeType === 'video/mp2t' ? 'public, max-age=31536000' : 'no-cache',
        ]);
    }
}
