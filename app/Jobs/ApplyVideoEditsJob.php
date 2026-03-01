<?php

namespace App\Jobs;

use App\Managers\NotificationManager;
use App\Models\Video;
use App\Models\VideoEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyVideoEditsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 10800; // 3 hours

    public int $backoff = 60;

    public function __construct(
        public VideoEdit $videoEdit
    ) {}

    public function handle(): void
    {
        $edit = $this->videoEdit->fresh();
        $video = $edit->video;

        Log::info('Starting video edits application', [
            'edit_id' => $edit->id,
            'video_id' => $video->id,
            'blur_count' => count($edit->blur_regions ?? []),
            'overlay_count' => count($edit->overlay_configs ?? []),
            'text_count' => count($edit->text_overlays ?? []),
            'trim' => $edit->trim_start !== null ? "{$edit->trim_start}-{$edit->trim_end}" : 'none',
            'merge_video_id' => $edit->merge_video_id,
        ]);

        $media = $video->getFirstMedia('videos');

        if (! $media) {
            $this->markAsFailed($edit, 'No media file found');

            return;
        }

        $inputPath = $media->getPath();

        if (! file_exists($inputPath)) {
            $this->markAsFailed($edit, 'Video file not found');

            return;
        }

        $edit->update(['status' => 'processing', 'progress' => 10]);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/edited_'.$video->id.'_'.time().'.mp4';

        try {
            $dimensions = $this->getVideoDimensions($inputPath);

            if (! $dimensions) {
                throw new \Exception('Failed to get video dimensions');
            }

            $edit->update(['progress' => 20]);

            $blurRegions = $edit->blur_regions ?? [];
            $overlayConfigs = $edit->overlay_configs ?? [];
            $textOverlays = $edit->text_overlays ?? [];
            $overlayMedia = $edit->getMedia('overlays');

            $filterComplex = [];
            $inputArgs = sprintf('-i %s', escapeshellarg($inputPath));
            $inputIndex = 1;

            // Add overlay files as additional inputs
            $overlayInputMap = [];
            foreach ($overlayConfigs as $config) {
                $fileIndex = $config['file_index'] ?? 0;
                if (isset($overlayMedia[$fileIndex]) && ! isset($overlayInputMap[$fileIndex])) {
                    $overlayPath = $overlayMedia[$fileIndex]->getPath();
                    if (file_exists($overlayPath)) {
                        $inputArgs .= ' -i '.escapeshellarg($overlayPath);
                        $overlayInputMap[$fileIndex] = $inputIndex;
                        $inputIndex++;
                    }
                }
            }

            // Add merge video as additional input
            $mergeInputIndex = null;
            if ($edit->merge_video_id) {
                $mergeVideo = Video::find($edit->merge_video_id);
                if ($mergeVideo) {
                    $mergeMedia = $mergeVideo->getFirstMedia('videos');
                    if ($mergeMedia && file_exists($mergeMedia->getPath())) {
                        $inputArgs .= ' -i '.escapeshellarg($mergeMedia->getPath());
                        $mergeInputIndex = $inputIndex;
                        $inputIndex++;
                    }
                }
            }

            $edit->update(['progress' => 30]);

            // Build filter_complex
            $currentVideoLabel = '0:v';
            $currentAudioLabel = '0:a';
            $stepIndex = 0;

            // --- Trim ---
            if ($edit->trim_start !== null && $edit->trim_end !== null) {
                $trimmedVideo = 'trimmed_v';
                $trimmedAudio = 'trimmed_a';

                $filterComplex[] = sprintf(
                    '[0:v]trim=start=%.2f:end=%.2f,setpts=PTS-STARTPTS[%s]',
                    $edit->trim_start,
                    $edit->trim_end,
                    $trimmedVideo
                );
                $filterComplex[] = sprintf(
                    '[0:a]atrim=start=%.2f:end=%.2f,asetpts=PTS-STARTPTS[%s]',
                    $edit->trim_start,
                    $edit->trim_end,
                    $trimmedAudio
                );

                $currentVideoLabel = $trimmedVideo;
                $currentAudioLabel = $trimmedAudio;
            }

            // --- Blur regions ---
            foreach ($blurRegions as $i => $region) {
                $blurX = round($dimensions['width'] * ($region['x'] / 100));
                $blurY = round($dimensions['height'] * ($region['y'] / 100));
                $blurW = round($dimensions['width'] * ($region['width'] / 100));
                $blurH = round($dimensions['height'] * ($region['height'] / 100));

                $blurW = max(2, $blurW - ($blurW % 2));
                $blurH = max(2, $blurH - ($blurH % 2));

                $splitA = "split_{$stepIndex}_a";
                $splitB = "split_{$stepIndex}_b";
                $blurred = "blurred_{$stepIndex}";
                $outLabel = "step_{$stepIndex}";

                $filterComplex[] = "[{$currentVideoLabel}]split=2[{$splitA}][{$splitB}]";
                $filterComplex[] = "[{$splitB}]crop={$blurW}:{$blurH}:{$blurX}:{$blurY},boxblur=20:20[{$blurred}]";

                $enableStr = '';
                $startTime = $region['start_time'] ?? null;
                $endTime = $region['end_time'] ?? null;
                if ($startTime !== null && $endTime !== null) {
                    $enableStr = sprintf(":enable='between(t,%.2f,%.2f)'", $startTime, $endTime);
                }

                $filterComplex[] = "[{$splitA}][{$blurred}]overlay={$blurX}:{$blurY}{$enableStr}[{$outLabel}]";
                $currentVideoLabel = $outLabel;
                $stepIndex++;
            }

            // --- Overlays ---
            foreach ($overlayConfigs as $config) {
                $fileIndex = $config['file_index'] ?? 0;
                if (! isset($overlayInputMap[$fileIndex])) {
                    continue;
                }

                $overlayInputIdx = $overlayInputMap[$fileIndex];
                $targetW = round($dimensions['width'] * ($config['width'] / 100));
                $targetH = round($dimensions['height'] * ($config['height'] / 100));
                $targetX = round($dimensions['width'] * ($config['x'] / 100));
                $targetY = round($dimensions['height'] * ($config['y'] / 100));

                $targetW = max(2, $targetW - ($targetW % 2));
                $targetH = max(2, $targetH - ($targetH % 2));

                $scaledLabel = "scaled_{$stepIndex}";
                $outLabel = "step_{$stepIndex}";

                $filterComplex[] = "[{$overlayInputIdx}:v]scale={$targetW}:{$targetH}[{$scaledLabel}]";

                $enableStr = '';
                $startTime = $config['start_time'] ?? null;
                $endTime = $config['end_time'] ?? null;
                if ($startTime !== null && $endTime !== null) {
                    $enableStr = sprintf(":enable='between(t,%.2f,%.2f)'", $startTime, $endTime);
                }

                $filterComplex[] = "[{$currentVideoLabel}][{$scaledLabel}]overlay={$targetX}:{$targetY}{$enableStr}[{$outLabel}]";
                $currentVideoLabel = $outLabel;
                $stepIndex++;
            }

            // --- Text overlays ---
            foreach ($textOverlays as $textOverlay) {
                $text = $this->escapeFFmpegText($textOverlay['text'] ?? 'Text');
                $textX = round($dimensions['width'] * (($textOverlay['x'] ?? 0) / 100));
                $textY = round($dimensions['height'] * (($textOverlay['y'] ?? 0) / 100));
                $fontSize = (int) ($textOverlay['font_size'] ?? 32);
                $fontColor = $textOverlay['font_color'] ?? 'white';
                $bgColor = $textOverlay['background_color'] ?? null;

                $outLabel = "step_{$stepIndex}";

                $drawtext = "drawtext=text='{$text}':x={$textX}:y={$textY}:fontsize={$fontSize}:fontcolor={$fontColor}";

                if ($bgColor) {
                    $drawtext .= ":box=1:boxcolor={$bgColor}@0.5:boxborderw=8";
                }

                $startTime = $textOverlay['start_time'] ?? null;
                $endTime = $textOverlay['end_time'] ?? null;
                if ($startTime !== null && $endTime !== null) {
                    $drawtext .= sprintf(":enable='between(t,%.2f,%.2f)'", $startTime, $endTime);
                }

                $filterComplex[] = "[{$currentVideoLabel}]{$drawtext}[{$outLabel}]";
                $currentVideoLabel = $outLabel;
                $stepIndex++;
            }

            $edit->update(['progress' => 40]);

            $ffmpegPath = config('media-library.ffmpeg_path');

            // --- Merge ---
            if ($mergeInputIndex !== null) {
                // Scale merge video to match main dimensions
                $mergeScaled = 'merge_scaled';
                $filterComplex[] = "[{$mergeInputIndex}:v]scale={$dimensions['width']}:{$dimensions['height']},setsar=1[{$mergeScaled}]";

                // If no edits were applied, use the current video label directly
                $editedLabel = $currentVideoLabel;

                // Generate silent audio for merge if needed
                $mergeAudioLabel = "{$mergeInputIndex}:a";
                $mainAudioLabel = $currentAudioLabel;

                // Concat edited main + merge
                $concatV = 'concat_v';
                $concatA = 'concat_a';
                $filterComplex[] = "[{$editedLabel}][{$mainAudioLabel}][{$mergeScaled}][{$mergeAudioLabel}]concat=n=2:v=1:a=1[{$concatV}][{$concatA}]";

                $currentVideoLabel = $concatV;
                $currentAudioLabel = $concatA;
            }

            if (empty($filterComplex)) {
                throw new \Exception('No edits to apply');
            }

            $filterString = implode(';', $filterComplex);

            // Determine audio mapping
            $audioMap = $mergeInputIndex !== null
                ? sprintf('-map "[%s]"', $currentAudioLabel)
                : '-map 0:a?';

            $command = sprintf(
                '%s -y %s -filter_complex %s -map "[%s]" %s -c:v libx264 -preset medium -crf 23 -c:a aac %s 2>&1',
                escapeshellarg($ffmpegPath),
                $inputArgs,
                escapeshellarg($filterString),
                $currentVideoLabel,
                $audioMap,
                escapeshellarg($outputPath)
            );

            Log::info('Running FFmpeg edits command', [
                'edit_id' => $edit->id,
                'command' => $command,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            Log::info('FFmpeg edits output', [
                'edit_id' => $edit->id,
                'return_code' => $returnCode,
                'output_length' => strlen($outputText),
            ]);

            if ($returnCode !== 0) {
                throw new \Exception("FFmpeg failed with code $returnCode: ".substr($outputText, -500));
            }

            if (! file_exists($outputPath)) {
                throw new \Exception('Output file was not created');
            }

            $outputSize = filesize($outputPath);
            if ($outputSize < 1000) {
                throw new \Exception("Output file is too small: $outputSize bytes");
            }

            $edit->update(['progress' => 80]);

            // Create a new video copy instead of replacing the original
            $newVideo = Video::create([
                'title' => $video->title.' (Edited)',
                'description' => $video->description,
                'duration' => $video->duration,
                'user_id' => $video->user_id,
                'folder_id' => $video->folder_id,
                'workspace_id' => $video->workspace_id,
                'is_public' => $video->is_public,
                'storage_type' => 'local',
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
            ]);

            $newVideo->addMedia($outputPath)
                ->usingFileName('video_'.$newVideo->id.'.mp4')
                ->toMediaCollection('videos');

            // Update file size
            $newMedia = $newVideo->getFirstMedia('videos');
            if ($newMedia) {
                $newVideo->update(['file_size_bytes' => $newMedia->size]);
            }

            $edit->update(['progress' => 90]);

            // Link the output video to the edit record
            $edit->update(['output_video_id' => $newVideo->id]);

            // Regenerate thumbnail for the new video
            $newVideo->generateThumbnailFromMidpoint();

            $edit->update(['progress' => 95]);

            // Increment user video count
            $newVideo->user()->first()?->increment('videos_count');

            // Mark as completed
            $edit->update([
                'status' => 'completed',
                'progress' => 100,
                'error' => null,
            ]);

            Log::info('Video edits applied successfully - new video created', [
                'edit_id' => $edit->id,
                'source_video_id' => $video->id,
                'output_video_id' => $newVideo->id,
                'output_size' => $outputSize,
            ]);

            // Clean up temp file
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            // Send notification for async edits
            try {
                $notificationManager = app(NotificationManager::class);
                $notificationManager->createEditCompleteNotification($newVideo, $video);
            } catch (\Exception $e) {
                Log::warning('Failed to send edit complete notification', ['error' => $e->getMessage()]);
            }

            // Dispatch HLS conversion for the new video
            ProcessHlsConversionJob::dispatch($newVideo)->delay(now()->addSeconds(5));

        } catch (\Exception $e) {
            Log::error('Video edits application failed', [
                'edit_id' => $edit->id,
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($outputPath) && file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $this->markAsFailed($edit, $e->getMessage());

            throw $e;
        }
    }

    private function escapeFFmpegText(string $text): string
    {
        // Escape special characters for FFmpeg drawtext filter
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace("'", "\\'", $text);
        $text = str_replace(':', '\\:', $text);
        $text = str_replace('%', '%%', $text);

        return $text;
    }

    private function getVideoDimensions(string $filePath): ?array
    {
        $ffprobePath = config('media-library.ffprobe_path');

        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=width,height -of json %s',
            escapeshellarg($ffprobePath),
            escapeshellarg($filePath)
        );

        $output = [];
        exec($command, $output);
        $data = json_decode(implode('', $output), true);

        if (isset($data['streams'][0]['width']) && isset($data['streams'][0]['height'])) {
            return [
                'width' => (int) $data['streams'][0]['width'],
                'height' => (int) $data['streams'][0]['height'],
            ];
        }

        return null;
    }

    private function markAsFailed(VideoEdit $edit, string $error): void
    {
        $edit->update([
            'status' => 'failed',
            'error' => substr($error, 0, 1000),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Video edits job failed permanently', [
            'edit_id' => $this->videoEdit->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->videoEdit, $exception?->getMessage() ?? 'Unknown error');
    }
}
