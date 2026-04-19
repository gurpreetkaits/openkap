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
use Symfony\Component\Process\Process;

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
            'merge_video_ids' => $edit->merge_video_ids,
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

            // Add merge videos as additional inputs
            $mergeInputIndices = []; // merge_video_id => ffmpeg input index
            $mergeVideoIds = $edit->merge_video_ids ?? [];
            foreach ($mergeVideoIds as $mergeVideoId) {
                $mergeVideo = Video::find($mergeVideoId);
                if ($mergeVideo) {
                    $mergeMedia = $mergeVideo->getFirstMedia('videos');
                    if ($mergeMedia && file_exists($mergeMedia->getPath())) {
                        $inputArgs .= ' -i '.escapeshellarg($mergeMedia->getPath());
                        $mergeInputIndices[$mergeVideoId] = $inputIndex;
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
                $fontColor = preg_replace('/[^a-zA-Z0-9#@.()]/', '', $textOverlay['font_color'] ?? 'white');
                $bgColor = isset($textOverlay['background_color']) ? preg_replace('/[^a-zA-Z0-9#@.()]/', '', $textOverlay['background_color']) : null;

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

            // --- Merge (multi-video) ---
            if (! empty($mergeInputIndices)) {
                $mainVideoPosition = $edit->main_video_position ?? 0;

                // Scale each merge video to match main dimensions
                $mergeScaledLabels = []; // mergeVideoId => { v: label, a: label }
                $mergeIdx = 0;
                foreach ($mergeInputIndices as $mvId => $ffmpegIdx) {
                    $scaledLabel = "merge_scaled_{$mergeIdx}";
                    $filterComplex[] = "[{$ffmpegIdx}:v]scale={$dimensions['width']}:{$dimensions['height']},setsar=1[{$scaledLabel}]";
                    $mergeScaledLabels[$mvId] = [
                        'v' => $scaledLabel,
                        'a' => "{$ffmpegIdx}:a",
                    ];
                    $mergeIdx++;
                }

                // Build ordered sequence: insert main video at mainVideoPosition among merge videos
                $orderedStreams = []; // each entry: { v: label, a: label }
                $mergeOrder = [];
                foreach ($mergeVideoIds as $mvId) {
                    if (isset($mergeScaledLabels[$mvId])) {
                        $mergeOrder[] = $mergeScaledLabels[$mvId];
                    }
                }

                // Insert main video at the correct position
                $mainStream = ['v' => $currentVideoLabel, 'a' => $currentAudioLabel];
                $insertPos = min($mainVideoPosition, count($mergeOrder));
                array_splice($mergeOrder, $insertPos, 0, [$mainStream]);
                $orderedStreams = $mergeOrder;

                // Build concat filter
                $concatInputs = '';
                foreach ($orderedStreams as $stream) {
                    $concatInputs .= "[{$stream['v']}][{$stream['a']}]";
                }
                $n = count($orderedStreams);
                $concatV = 'concat_v';
                $concatA = 'concat_a';
                $filterComplex[] = "{$concatInputs}concat=n={$n}:v=1:a=1[{$concatV}][{$concatA}]";

                $currentVideoLabel = $concatV;
                $currentAudioLabel = $concatA;
            }

            // --- Style settings (background, padding, roundness, shadow, camera) ---
            $style = $edit->style_settings ?? [];
            $pad = (int) ($style['padding'] ?? 0);
            $bgType = $style['background_type'] ?? 'none';
            $cameraEnabled = filter_var($style['camera_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $vidW = $dimensions['width'];
            $vidH = $dimensions['height'];

            // Scale down to max 1280px width to fit container memory limits
            if ($vidW > 1280) {
                $scaleH = (int) round(1280 * $vidH / $vidW);
                $scaleH = $scaleH + ($scaleH % 2);
                $scaledLabel = "scaled_{$stepIndex}";
                $filterComplex[] = "[{$currentVideoLabel}]scale=1280:{$scaleH}[{$scaledLabel}]";
                $currentVideoLabel = $scaledLabel;
                $vidW = 1280;
                $vidH = $scaleH;
                $stepIndex++;
            }

            // --- Zoom keyframes ---
            // Each keyframe: { time, duration, scale, x, y }
            // Phases per keyframe: ease-in (0.4s) → hold (duration) → ease-out (0.4s)
            $zoomKeyframes = $style['zoom_keyframes'] ?? [];
            // Ensure zoom_keyframes is a proper array of arrays (not a string from bad FormData encoding)
            if (is_string($zoomKeyframes)) {
                $zoomKeyframes = json_decode($zoomKeyframes, true) ?: [];
            }
            if (! is_array($zoomKeyframes)) {
                $zoomKeyframes = [];
            }
            // Filter out invalid entries
            $zoomKeyframes = array_filter($zoomKeyframes, fn ($kf) => is_array($kf) && isset($kf['time'], $kf['scale'], $kf['x'], $kf['y']));
            if (! empty($zoomKeyframes)) {
                usort($zoomKeyframes, fn ($a, $b) => (float) $a['time'] <=> (float) $b['time']);
                $ease = 0.4;

                // Build segments: for each keyframe → ease-in, hold, ease-out
                $segments = [];
                foreach ($zoomKeyframes as $kf) {
                    $kfTime = (float) ($kf['time'] ?? 0);
                    $kfDur = max(0.1, (float) ($kf['duration'] ?? 2));
                    $kfScale = max(1.01, (float) ($kf['scale'] ?? 2));
                    $kfX = max(0, min(100, (float) ($kf['x'] ?? 50)));
                    $kfY = max(0, min(100, (float) ($kf['y'] ?? 50)));
                    $holdEnd = $kfTime + $kfDur;

                    // Ease in: 1x → full zoom
                    $easeStart = max(0, $kfTime - $ease);
                    if ($easeStart < $kfTime) {
                        $segments[] = [
                            'start' => $easeStart, 'end' => $kfTime,
                            'fromScale' => 1, 'toScale' => $kfScale,
                            'fromX' => 50, 'toX' => $kfX,
                            'fromY' => 50, 'toY' => $kfY,
                        ];
                    }
                    // Hold at full zoom
                    $segments[] = [
                        'start' => $kfTime, 'end' => $holdEnd,
                        'fromScale' => $kfScale, 'toScale' => $kfScale,
                        'fromX' => $kfX, 'toX' => $kfX,
                        'fromY' => $kfY, 'toY' => $kfY,
                    ];
                    // Ease out: full zoom → 1x
                    $segments[] = [
                        'start' => $holdEnd, 'end' => $holdEnd + $ease,
                        'fromScale' => $kfScale, 'toScale' => 1,
                        'fromX' => $kfX, 'toX' => 50,
                        'fromY' => $kfY, 'toY' => 50,
                    ];
                }

                // Build nested if() expressions for crop w/h/x/y
                // smoothstep: p*p*(3-2*p) where p = clip((t-start)/span, 0, 1)
                $buildExpr = function ($segments, $vidDim, $dimType) {
                    // Default: full size / zero offset
                    $expr = (string) ($dimType === 'w' || $dimType === 'h' ? $vidDim : 0);

                    for ($i = count($segments) - 1; $i >= 0; $i--) {
                        $seg = $segments[$i];
                        $s = $seg['start'];
                        $e = $seg['end'];
                        $span = max(0.001, $e - $s);

                        if ($dimType === 'w' || $dimType === 'h') {
                            $from = $vidDim / $seg['fromScale'];
                            $to = $vidDim / $seg['toScale'];
                        } elseif ($dimType === 'x') {
                            $fromCropW = $vidDim / $seg['fromScale'];
                            $toCropW = $vidDim / $seg['toScale'];
                            $from = ($seg['fromX'] / 100) * ($vidDim - $fromCropW);
                            $to = ($seg['toX'] / 100) * ($vidDim - $toCropW);
                        } else { // y
                            $fromCropH = $vidDim / $seg['fromScale'];
                            $toCropH = $vidDim / $seg['toScale'];
                            $from = ($seg['fromY'] / 100) * ($vidDim - $fromCropH);
                            $to = ($seg['toY'] / 100) * ($vidDim - $toCropH);
                        }

                        $pExpr = "clip((t-{$s})/{$span}\\,0\\,1)";
                        $smoothExpr = "{$pExpr}*{$pExpr}*(3-2*{$pExpr})";
                        $lerpExpr = sprintf('%.1f+%.1f*%s', $from, $to - $from, $smoothExpr);

                        $expr = sprintf(
                            "if(between(t\\,%.3f\\,%.3f)\\,%s\\,%s)",
                            $s, $e, $lerpExpr, $expr
                        );
                    }

                    return $expr;
                };

                $cropW = $buildExpr($segments, $vidW, 'w');
                $cropH = $buildExpr($segments, $vidH, 'h');
                $cropX = $buildExpr($segments, $vidW, 'x');
                $cropY = $buildExpr($segments, $vidH, 'y');

                $zoomLabel = "zoomed_{$stepIndex}";
                $filterComplex[] = "[{$currentVideoLabel}]crop=w='max(2\\,floor(({$cropW})/2)*2)':h='max(2\\,floor(({$cropH})/2)*2)':x='{$cropX}':y='{$cropY}',scale={$vidW}:{$vidH}[{$zoomLabel}]";
                $currentVideoLabel = $zoomLabel;
                $stepIndex++;
            }

            // --- Camera PiP overlay ---
            if ($cameraEnabled) {
                $cameraMedia = $video->getFirstMedia('camera');
                if ($cameraMedia && file_exists($cameraMedia->getPath())) {
                    $cameraPath = $cameraMedia->getPath();
                    $inputArgs .= ' -i '.escapeshellarg($cameraPath);
                    $cameraInputIdx = $inputIndex;
                    $inputIndex++;

                    $camSize = (int) ($style['camera_size'] ?? 25);
                    $camPosition = $style['camera_position'] ?? 'bottom-left';
                    $camShape = $style['camera_shape'] ?? 'portrait';

                    // Camera dimensions
                    $camW = (int) round($vidW * $camSize / 100);
                    $camW = $camW + ($camW % 2);
                    $camH = $camShape === 'circle' || $camShape === 'square'
                        ? $camW
                        : (int) round($camW * 4 / 3); // portrait 3:4
                    $camH = $camH + ($camH % 2);

                    // Camera position — use custom drag position if available, otherwise quadrant preset
                    $camDragX = $style['camera_drag_x'] ?? null;
                    $camDragY = $style['camera_drag_y'] ?? null;

                    if ($camDragX !== null && $camDragY !== null) {
                        $camX = (int) round($vidW * $camDragX / 100);
                        $camY = (int) round($vidH * $camDragY / 100);
                    } else {
                        $camMargin = max(16, (int) round($vidW * 0.02));
                        $camX = str_contains($camPosition, 'right') ? ($vidW - $camW - $camMargin) : $camMargin;
                        $camY = str_contains($camPosition, 'top') ? $camMargin : ($vidH - $camH - $camMargin);
                    }

                    $camRoundness = (int) ($style['camera_roundness'] ?? 18);
                    $camBorderBlur = (int) ($style['camera_border_blur'] ?? 6);
                    $camShadowPct = (int) ($style['camera_shadow'] ?? 30);
                    $camScaledLabel = "cam_scaled_{$stepIndex}";
                    $camOutLabel = "cam_overlay_{$stepIndex}";

                    // All values from frontend settings — nothing hardcoded
                    // Feather: frontend uses cameraBorderBlur directly as px on the preview element.
                    // In FFmpeg, scale proportionally: preview camera is ~25% of viewport width (~300px),
                    // exported camera is $camW px. Scale feather to match visual appearance.
                    $feather = $camBorderBlur > 0 ? max(1, (int) round($camBorderBlur * $camW / 300)) : 0;
                    $feather = min($feather, min($camW, $camH) / 4);
                    $camR = min($camRoundness, min($camW, $camH) / 2);

                    // Camera shadow: frontend renders box-shadow: 0 8px 32px rgba(0,0,0,{shadow/100})
                    // In FFmpeg: generate a blurred black rect behind camera with matching opacity
                    $camShadowAlpha = round($camShadowPct / 100, 2);

                    $needsAlpha = ($camR > 0 || $camShape === 'circle') && $feather > 0;

                    if ($needsAlpha) {
                        if ($camShape === 'circle') {
                            $cx = $camW / 2;
                            $cy = $camH / 2;
                            $filterComplex[] = "[{$cameraInputIdx}:v]scale={$camW}:{$camH},setsar=1,format=yuva420p,geq=lum='lum(X\\,Y)':cb='cb(X\\,Y)':cr='cr(X\\,Y)':a='if(lt(hypot(X-{$cx}\\,Y-{$cy})\\,{$cx}-{$feather})\\,255\\,if(gt(hypot(X-{$cx}\\,Y-{$cy})\\,{$cx})\\,0\\,255*(({$cx}-hypot(X-{$cx}\\,Y-{$cy}))/{$feather})))'[{$camScaledLabel}]";
                        } else {
                            $filterComplex[] = "[{$cameraInputIdx}:v]scale={$camW}:{$camH},setsar=1,format=yuva420p,geq=lum='lum(X\\,Y)':cb='cb(X\\,Y)':cr='cr(X\\,Y)':a='if(gt(X\\,{$camR})*gt(X\\,W-{$camR}-1)+lt(X\\,{$camR}+1)*lt(X\\,{$camR}+1)+gt(Y\\,{$camR})*gt(Y\\,H-{$camR}-1)+lt(Y\\,{$camR}+1)*lt(Y\\,{$camR}+1)\\,if(lt(X\\,{$camR})*lt(Y\\,{$camR})*gt(hypot(X-{$camR}\\,Y-{$camR})\\,{$camR})+ lt(X\\,{$camR})*gt(Y\\,H-{$camR}-1)*gt(hypot(X-{$camR}\\,Y-H+{$camR}+1)\\,{$camR})+ gt(X\\,W-{$camR}-1)*lt(Y\\,{$camR})*gt(hypot(X-W+{$camR}+1\\,Y-{$camR})\\,{$camR})+ gt(X\\,W-{$camR}-1)*gt(Y\\,H-{$camR}-1)*gt(hypot(X-W+{$camR}+1\\,Y-H+{$camR}+1)\\,{$camR})\\,0\\,255)\\,255)'[{$camScaledLabel}]";
                        }
                    } else {
                        $filterComplex[] = "[{$cameraInputIdx}:v]scale={$camW}:{$camH},setsar=1[{$camScaledLabel}]";
                    }

                    // Camera drop shadow — skip in FFmpeg to avoid OOM on constrained containers.
                    // Camera shadow is a subtle visual polish that's handled by the player overlay.

                    $camOutLabel = "cam_overlay_{$stepIndex}";
                    $filterComplex[] = "[{$currentVideoLabel}][{$camScaledLabel}]overlay={$camX}:{$camY}:shortest=1[{$camOutLabel}]";
                    $currentVideoLabel = $camOutLabel;
                    $stepIndex++;
                }
            }

            // --- Background padding + compositing ---
            // Note: Video roundness and drop shadow use geq which is too memory-intensive
            // for constrained Docker containers. These are visual polish applied in the
            // player overlay. The export focuses on layout-accurate background/padding/camera.
            if ($pad > 0 || $bgType !== 'none') {
                $hasShadow = false; // Disabled to prevent OOM — shadow is player-side only
                $outW = $vidW + ($pad * 2);
                $outH = $vidH + ($pad * 2);
                $outW = $outW + ($outW % 2);
                $outH = $outH + ($outH % 2);

                // Generate the background canvas label
                $bgCanvasLabel = null;

                if ($bgType === 'image') {
                    $bgImageUrl = $style['background_image_url'] ?? '';
                    $bgImagePath = null;

                    if ($bgImageUrl) {
                        $cacheDir = storage_path('app/bg-cache');
                        if (! is_dir($cacheDir)) {
                            mkdir($cacheDir, 0755, true);
                        }
                        $cacheKey = md5($bgImageUrl);
                        $cachedPath = $cacheDir.'/'.$cacheKey.'.img';

                        if (file_exists($cachedPath) && filesize($cachedPath) > 100) {
                            $bgImagePath = $cachedPath;
                        } else {
                            $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'OpenKap/1.0']]);
                            $imageData = @file_get_contents($bgImageUrl, false, $ctx);
                            if ($imageData && strlen($imageData) > 100) {
                                file_put_contents($cachedPath, $imageData);
                                $bgImagePath = $cachedPath;
                            } else {
                                Log::warning('Failed to download background image', ['url' => $bgImageUrl]);
                            }
                        }
                    }

                    if ($bgImagePath && file_exists($bgImagePath)) {
                        $inputArgs .= ' -loop 1 -i '.escapeshellarg($bgImagePath);
                        $bgInputIdx = $inputIndex;
                        $inputIndex++;

                        $bgCanvasLabel = "bg_canvas_{$stepIndex}";
                        $filterComplex[] = "[{$bgInputIdx}:v]scale={$outW}:{$outH},setsar=1[{$bgCanvasLabel}]";
                        $stepIndex++;
                    }
                } elseif ($bgType === 'gradient') {
                    $gFrom = ltrim($style['gradient_from'] ?? '#1e293b', '#');
                    $gTo = ltrim($style['gradient_to'] ?? '#0f172a', '#');
                    $gradDir = $style['gradient_direction'] ?? 'b';
                    $bgCanvasLabel = "bg_canvas_{$stepIndex}";
                    $gradDuration = max(300, (int) ($video->duration ?: 300));

                    // Parse hex colors to RGB for geq
                    $fromR = hexdec(substr($gFrom, 0, 2));
                    $fromG = hexdec(substr($gFrom, 2, 2));
                    $fromB = hexdec(substr($gFrom, 4, 2));
                    $toR = hexdec(substr($gTo, 0, 2));
                    $toG = hexdec(substr($gTo, 2, 2));
                    $toB = hexdec(substr($gTo, 4, 2));

                    // Gradient direction matching frontend:
                    // 'b' = vertical (180deg: top→bottom), 'r' = horizontal (90deg: left→right)
                    // 'br' = diagonal (135deg: top-left→bottom-right)
                    if ($gradDir === 'r') {
                        // Horizontal: interpolate based on X/W
                        $pExpr = 'X/W';
                    } elseif ($gradDir === 'br') {
                        // Diagonal (135deg): interpolate based on (X+Y)/(W+H)
                        $pExpr = '(X+Y)/(W+H)';
                    } else {
                        // Vertical (default): interpolate based on Y/H
                        $pExpr = 'Y/H';
                    }

                    $rExpr = "{$fromR}+({$toR}-{$fromR})*({$pExpr})";
                    $gExpr = "{$fromG}+({$toG}-{$fromG})*({$pExpr})";
                    $bExpr = "{$fromB}+({$toB}-{$fromB})*({$pExpr})";

                    // Generate 1 gradient frame, then loop — avoids per-frame geq overhead
                    $gradRawLabel = "grad_raw_{$stepIndex}";
                    $filterComplex[] = "color=c=black:s={$outW}x{$outH}:d=1:r=1,format=rgb24,geq=r='{$rExpr}':g='{$gExpr}':b='{$bExpr}'[{$gradRawLabel}]";
                    $filterComplex[] = "[{$gradRawLabel}]loop=loop=-1:size=1:start=0[{$bgCanvasLabel}]";
                    $stepIndex++;
                }

                if ($bgCanvasLabel) {
                    // Image or gradient background — overlay video onto canvas
                    $composited = "bg_comp_{$stepIndex}";
                    $filterComplex[] = "[{$bgCanvasLabel}][{$currentVideoLabel}]overlay={$pad}:{$pad}:shortest=1[{$composited}]";
                    $currentVideoLabel = $composited;
                    $stepIndex++;
                } else {
                    // Solid color or fallback — use simple pad filter
                    $bgColor = '000000';
                    if ($bgType === 'solid') {
                        $bgColor = ltrim($style['background_color'] ?? '#000000', '#');
                    }
                    $paddedLabel = "padded_{$stepIndex}";
                    $filterComplex[] = "[{$currentVideoLabel}]pad={$outW}:{$outH}:{$pad}:{$pad}:color=0x{$bgColor}[{$paddedLabel}]";
                    $currentVideoLabel = $paddedLabel;
                    $stepIndex++;
                }
            }

            // Always allow processing (style is always sent now)
            if (empty($filterComplex)) {
                // No filters needed — just copy
                $filterComplex[] = "[0:v]null[passthrough]";
                $currentVideoLabel = 'passthrough';
            }

            $filterString = implode(';', $filterComplex);

            // Determine audio mapping
            $audioMap = ! empty($mergeInputIndices)
                ? sprintf('-map "[%s]"', $currentAudioLabel)
                : '-map 0:a?';

            // Build ffmpeg arguments array for Symfony Process
            $audioMapArgs = ! empty($mergeInputIndices)
                ? ['-map', "[{$currentAudioLabel}]"]
                : ['-map', '0:a?'];

            // Parse input args string into array — supports flags before -i (e.g. -loop 1 -i 'file')
            $inputArgParts = [];
            // Match optional flags before each -i, then the file path
            preg_match_all('/(?:(-loop\s+\d+)\s+)?-i\s+\'([^\']+)\'/', $inputArgs, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                if (! empty($m[1])) {
                    // Add pre-input flags (e.g. -loop 1)
                    $parts = preg_split('/\s+/', trim($m[1]));
                    foreach ($parts as $p) {
                        $inputArgParts[] = $p;
                    }
                }
                $inputArgParts[] = '-i';
                $inputArgParts[] = $m[2];
            }
            if (empty($inputArgParts)) {
                $inputArgParts = ['-i', $inputPath];
            }

            $processArgs = array_merge(
                [$ffmpegPath, '-y', '-progress', 'pipe:2', '-threads', '1', '-filter_threads', '1'],
                $inputArgParts,
                ['-filter_complex', $filterString],
                ['-map', "[{$currentVideoLabel}]"],
                $audioMapArgs,
                ['-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '26', '-threads', '1', '-c:a', 'aac'],
                [$outputPath]
            );

            Log::info('Running FFmpeg edits via Process', [
                'edit_id' => $edit->id,
                'filter' => $filterString,
            ]);

            $process = new Process($processArgs);
            $process->setTimeout($this->timeout);

            // Get video duration for progress calculation
            $videoDuration = $video->duration ?: 60;

            // Run with callback to track ffmpeg progress
            $process->run(function ($type, $buffer) use ($edit, $videoDuration) {
                // Parse ffmpeg output for time= progress
                if (preg_match('/time=(\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $buffer, $m)) {
                    $currentSec = ($m[1] * 3600) + ($m[2] * 60) + $m[3] + ($m[4] / 100);
                    // Map encoding progress to 40-90% range (setup is 0-40%, finalize is 90-100%)
                    $pct = min(90, 40 + (int) (($currentSec / $videoDuration) * 50));
                    $edit->update(['progress' => $pct]);
                }
            });

            $returnCode = $process->getExitCode();
            $outputText = $process->getErrorOutput();

            Log::info('FFmpeg edits output', [
                'edit_id' => $edit->id,
                'return_code' => $returnCode,
                'output_length' => strlen($outputText),
            ]);

            if (! $process->isSuccessful()) {
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

            // Clean up temp files
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            // Note: background images are cached in bg-cache/, not deleted

            // Send notification for async edits
            try {
                $notificationManager = app(NotificationManager::class);
                $notificationManager->createEditCompleteNotification($newVideo, $video);
            } catch (\Throwable $e) {
                Log::warning('Failed to send edit complete notification', ['error' => $e->getMessage()]);
            }

            // Dispatch HLS conversion for the new video
            ProcessHlsConversionJob::dispatch($newVideo)->delay(now()->addSeconds(5));

        } catch (\Throwable $e) {
            Log::error('Video edits application failed', [
                'edit_id' => $edit->id,
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($outputPath) && file_exists($outputPath)) {
                @unlink($outputPath);
            }
            // Note: background images are cached in bg-cache/, not deleted

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
