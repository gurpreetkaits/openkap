<?php

namespace App\Services;

use App\Models\Video;

class CaptionService
{
    /**
     * Maximum duration in seconds for a single caption cue.
     */
    private const MAX_CUE_DURATION = 3.0;

    public function generateWebVtt(Video $video): ?string
    {
        if (! $video->isTranscriptionReady()) {
            return null;
        }

        $segments = $video->transcription_segments;

        if (empty($segments)) {
            return null;
        }

        $cues = $this->buildCues($segments);

        $vtt = "WEBVTT\n\n";

        foreach ($cues as $index => $cue) {
            $start = $this->formatVttTimestamp($cue['start']);
            $end = $this->formatVttTimestamp($cue['end']);

            $vtt .= ($index + 1)."\n";
            $vtt .= "{$start} --> {$end}\n";
            $vtt .= "{$cue['text']}\n\n";
        }

        return $vtt;
    }

    /**
     * Build cues from segments using word timestamps when available.
     * Groups words into 2-3 second cues based on actual speech timing.
     */
    private function buildCues(array $segments): array
    {
        $cues = [];

        foreach ($segments as $segment) {
            $words = $segment['words'] ?? null;
            $text = trim($segment['text'] ?? '');
            $start = (float) ($segment['start'] ?? 0);
            $end = (float) ($segment['end'] ?? 0);

            if ($text === '') {
                continue;
            }

            // If word timestamps are available, group words into natural cues
            if (! empty($words)) {
                $cues = array_merge($cues, $this->groupWordsIntoCues($words));

                continue;
            }

            // Fallback: split by duration when no word timestamps
            $duration = $end - $start;
            if ($duration <= self::MAX_CUE_DURATION) {
                $cues[] = ['start' => $start, 'end' => $end, 'text' => $text];

                continue;
            }

            $splitWords = preg_split('/\s+/', $text);
            $chunks = (int) ceil($duration / self::MAX_CUE_DURATION);
            $perChunk = max(1, (int) ceil(count($splitWords) / $chunks));
            $groups = array_chunk($splitWords, $perChunk);
            $chunkDur = $duration / count($groups);

            foreach ($groups as $i => $group) {
                $cues[] = [
                    'start' => round($start + ($i * $chunkDur), 2),
                    'end' => $i === count($groups) - 1 ? $end : round($start + (($i + 1) * $chunkDur), 2),
                    'text' => implode(' ', $group),
                ];
            }
        }

        return $cues;
    }

    /**
     * Group word timestamps into natural cues of ~2-3 seconds.
     */
    private function groupWordsIntoCues(array $words): array
    {
        $cues = [];
        $currentWords = [];
        $cueStart = null;

        foreach ($words as $word) {
            $wStart = (float) ($word['start'] ?? 0);
            $wEnd = (float) ($word['end'] ?? 0);
            $wText = trim($word['text'] ?? '');

            if ($wText === '') {
                continue;
            }

            if ($cueStart === null) {
                $cueStart = $wStart;
            }

            $currentWords[] = $wText;
            $cueDuration = $wEnd - $cueStart;

            // Flush cue when it reaches max duration
            if ($cueDuration >= self::MAX_CUE_DURATION) {
                $cues[] = [
                    'start' => $cueStart,
                    'end' => $wEnd,
                    'text' => implode(' ', $currentWords),
                ];
                $currentWords = [];
                $cueStart = null;
            }
        }

        // Flush remaining words
        if (! empty($currentWords) && $cueStart !== null) {
            $lastWord = end($words);
            $cues[] = [
                'start' => $cueStart,
                'end' => (float) ($lastWord['end'] ?? $lastWord['start'] ?? $cueStart),
                'text' => implode(' ', $currentWords),
            ];
        }

        return $cues;
    }

    public function formatVttTimestamp(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $milliseconds = round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $secs, $milliseconds);
    }
}
