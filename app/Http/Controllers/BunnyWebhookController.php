<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Repositories\WorkspaceRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BunnyWebhookController extends Controller
{
    /**
     * Handle Bunny Stream webhooks
     *
     * POST /api/webhooks/bunny
     *
     * Bunny sends webhooks for:
     * - VideoCreated
     * - VideoUploaded
     * - VideoProcessingStarted
     * - VideoProcessingFinished
     * - VideoProcessingFailed
     * - VideoDeleted
     */
    public function handle(Request $request): Response
    {
        // Get the raw payload
        $payload = $request->all();

        Log::info('Bunny webhook received', [
            'payload' => $payload,
        ]);

        // Validate required fields
        if (! isset($payload['VideoGuid']) || ! isset($payload['Status'])) {
            Log::warning('Bunny webhook: Missing required fields', $payload);

            return response('Invalid payload', 400);
        }

        $videoGuid = $payload['VideoGuid'];
        $status = (int) $payload['Status'];

        // Find the video in our database
        $video = Video::where('bunny_video_id', $videoGuid)->first();

        if (! $video) {
            Log::warning('Bunny webhook: Video not found', [
                'bunny_video_id' => $videoGuid,
            ]);

            // Return 200 to acknowledge receipt (don't retry)
            return response('Video not found', 200);
        }

        // Map Bunny status codes to our status
        // 0=created, 1=uploaded, 2=processing, 3=transcoding, 4=finished, 5=error
        $statusMap = [
            0 => 'pending',
            1 => 'uploaded',
            2 => 'processing',
            3 => 'transcoding',
            4 => 'ready',
            5 => 'error',
        ];

        $newStatus = $statusMap[$status] ?? 'unknown';

        // Never regress from 'ready' — Bunny can send webhooks out of order
        // (e.g. a delayed 'uploaded'/'transcoding' arriving after 'ready')
        if ($video->bunny_status === 'ready' && $newStatus !== 'error') {
            Log::info('Bunny webhook: ignoring regression from ready', [
                'video_id' => $video->id,
                'attempted_status' => $newStatus,
            ]);

            return response('OK', 200);
        }

        // Update video status
        $updateData = [
            'bunny_status' => $newStatus,
        ];

        // If finished, get additional metadata
        if ($status === 4) {
            // Video is ready
            if (isset($payload['Length'])) {
                $updateData['duration'] = (int) $payload['Length'];
            }
            if (isset($payload['Width']) && isset($payload['Height'])) {
                $updateData['bunny_resolution'] = $payload['Height'].'p';
            }
            if (isset($payload['StorageSize'])) {
                $updateData['bunny_file_size'] = (int) $payload['StorageSize'];
                $updateData['file_size_bytes'] = (int) $payload['StorageSize'];
            }

            Log::info('Bunny video ready', [
                'video_id' => $video->id,
                'bunny_video_id' => $videoGuid,
                'duration' => $updateData['duration'] ?? null,
                'resolution' => $updateData['bunny_resolution'] ?? null,
            ]);
        }

        // If error, capture the error message
        if ($status === 5) {
            $updateData['bunny_error'] = $payload['ErrorMessage'] ?? 'Unknown error during processing';

            Log::error('Bunny video processing failed', [
                'video_id' => $video->id,
                'bunny_video_id' => $videoGuid,
                'error' => $updateData['bunny_error'],
            ]);
        }

        $video->update($updateData);

        // Recalculate workspace storage if video belongs to a workspace
        if ($video->workspace_id) {
            $video->load('workspace');
            if ($video->workspace) {
                app(WorkspaceRepository::class)->recalculateStorage($video->workspace);
            }
        }

        Log::info('Bunny webhook processed', [
            'video_id' => $video->id,
            'bunny_video_id' => $videoGuid,
            'old_status' => $video->getOriginal('bunny_status'),
            'new_status' => $newStatus,
        ]);

        return response('OK', 200);
    }
}
