<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\OperationMode;
use ZipStream\ZipStream;

class AccountExportController extends Controller
{
    /**
     * Stream a ZIP of all the authenticated user's recording files.
     */
    public function recordings(Request $request): StreamedResponse
    {
        $user = $request->user();

        $videos = Video::query()
            ->where('user_id', $user->id)
            ->whereNull('archived_at')
            ->with('media')
            ->orderBy('created_at')
            ->get();

        $filename = 'openkap-recordings-'.now()->format('Y-m-d-His').'.zip';

        return new StreamedResponse(function () use ($videos) {
            $zip = new ZipStream(
                outputName: null,
                sendHttpHeaders: false,
                operationMode: OperationMode::NORMAL,
            );

            $usedNames = [];

            foreach ($videos as $video) {
                $media = $video->getMedia('videos')->first();
                if (! $media) {
                    continue;
                }

                $path = $media->getPath();
                if (! is_readable($path)) {
                    continue;
                }

                $entryName = $this->buildEntryName($video, $media->file_name, $usedNames);

                $stream = fopen($path, 'rb');
                if (! $stream) {
                    continue;
                }

                $zip->addFileFromStream($entryName, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $zip->finish();
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Return all metadata + transcriptions for the authenticated user's recordings as JSON.
     */
    public function metadata(Request $request): JsonResponse
    {
        $user = $request->user();

        $videos = Video::query()
            ->where('user_id', $user->id)
            ->whereNull('archived_at')
            ->orderBy('created_at')
            ->get();

        $recordings = $videos->map(function (Video $video) {
            return [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'duration_seconds' => $video->duration,
                'file_size_bytes' => $video->file_size_bytes,
                'has_audio' => (bool) $video->has_audio,
                'has_camera' => (bool) $video->has_camera,
                'is_public' => (bool) $video->is_public,
                'share_token' => $video->share_token,
                'share_expires_at' => $video->share_expires_at,
                'conversion_status' => $video->conversion_status,
                'hls_status' => $video->hls_status,
                'storage_type' => $video->storage_type,
                'created_at' => $video->created_at?->toIso8601String(),
                'updated_at' => $video->updated_at?->toIso8601String(),
                'transcription' => [
                    'status' => $video->transcription_status,
                    'text' => $video->transcription,
                    'segments' => $video->transcription_segments,
                    'generated_at' => $video->transcription_generated_at,
                ],
                'summary' => [
                    'status' => $video->summary_status,
                    'text' => $video->summary,
                    'generated_at' => $video->summary_generated_at,
                ],
            ];
        });

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'recording_count' => $recordings->count(),
            'recordings' => $recordings,
        ]);
    }

    /**
     * Build a unique, filesystem-safe entry name for a video inside the zip.
     */
    protected function buildEntryName(Video $video, string $originalName, array &$usedNames): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'mp4';
        $title = $video->title ?: 'recording-'.$video->id;
        $safe = preg_replace('/[^A-Za-z0-9 _.\-]/', '', $title);
        $safe = trim($safe) ?: 'recording-'.$video->id;
        $base = $safe.' ('.$video->id.')';
        $name = $base.'.'.$extension;

        if (isset($usedNames[$name])) {
            $name = $base.'-'.uniqid().'.'.$extension;
        }
        $usedNames[$name] = true;

        return $name;
    }
}
