<?php

namespace App\Http\Controllers;

use App\Managers\VideoManager;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HlsController extends Controller
{
    public function __construct(
        protected VideoManager $videoManager
    ) {}

    /**
     * Serve HLS master playlist for a shared video.
     */
    public function masterPlaylist(string $token): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            abort(404, 'Video not found');
        }

        if (! $video->isHlsReady()) {
            abort(404, 'HLS not ready');
        }

        $path = 'public/hls/'.$video->id.'/master.m3u8';

        if (! Storage::exists($path)) {
            abort(404, 'Playlist not found');
        }

        return $this->streamFile($path, 'application/vnd.apple.mpegurl');
    }

    /**
     * Serve HLS variant playlist for a shared video.
     */
    public function variantPlaylist(string $token, string $variant): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            abort(404, 'Video not found');
        }

        if (! $video->isHlsReady()) {
            abort(404, 'HLS not ready');
        }

        // Sanitize variant name to prevent directory traversal
        $variant = basename($variant);
        $path = 'public/hls/'.$video->id.'/'.$variant;

        if (! Storage::exists($path)) {
            abort(404, 'Variant playlist not found');
        }

        return $this->streamFile($path, 'application/vnd.apple.mpegurl');
    }

    /**
     * Serve HLS segment for a shared video.
     */
    public function segment(string $token, string $segment): StreamedResponse
    {
        $video = $this->videoManager->findByShareToken($token);

        if (! $video || ! $video->isShareLinkValid()) {
            abort(404, 'Video not found');
        }

        if (! $video->isHlsReady()) {
            abort(404, 'HLS not ready');
        }

        // Sanitize segment name to prevent directory traversal
        $segment = basename($segment);
        $path = 'public/hls/'.$video->id.'/'.$segment;

        if (! Storage::exists($path)) {
            abort(404, 'Segment not found');
        }

        return $this->streamFile($path, 'video/mp2t');
    }

    /**
     * Stream a file with CORS headers.
     */
    protected function streamFile(string $path, string $mimeType): StreamedResponse
    {
        $fileSize = Storage::size($path);

        return new StreamedResponse(function () use ($path) {
            $stream = Storage::readStream($path);
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
