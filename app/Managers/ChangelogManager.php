<?php

namespace App\Managers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChangelogManager
{
    /**
     * Commits to completely skip (noise)
     */
    protected array $skipPatterns = [
        '/^\.+$/',
        '/^wip$/i',
        '/^temp$/i',
        '/^test$/i',
        '/^fix$/i',
        '/^fixes$/i',
        '/^update$/i',
        '/^changes$/i',
        '/^commit$/i',
    ];

    /**
     * Get changelog grouped by week with meaningful descriptions
     */
    public function getChangelogByWeek(int $weeksBack = 12): Collection
    {
        $commits = $this->fetchCommitsWithFiles($weeksBack);
        $grouped = $this->groupByWeek($commits);

        return $grouped->map(function ($weekData) {
            return $this->processWeekChanges($weekData);
        });
    }

    /**
     * Fetch commits with file changes from git
     */
    protected function fetchCommitsWithFiles(int $weeksBack): Collection
    {
        $since = Carbon::now()->subWeeks($weeksBack)->format('Y-m-d');

        // Get commits with files changed
        $command = sprintf(
            'cd %s && git log --since="%s" --date=short --format="COMMIT:%%h|%%ad|%%s|%%an" --name-only 2>/dev/null',
            base_path(),
            $since
        );

        $output = [];
        exec($command, $output);

        return $this->parseCommitsWithFiles($output);
    }

    /**
     * Parse git log output with files
     */
    protected function parseCommitsWithFiles(array $output): Collection
    {
        $commits = collect();
        $currentCommit = null;
        $currentFiles = [];

        foreach ($output as $line) {
            if (str_starts_with($line, 'COMMIT:')) {
                // Save previous commit
                if ($currentCommit) {
                    $currentCommit['files'] = $currentFiles;
                    $currentCommit['description'] = $this->generateDescription($currentCommit);

                    if (! empty($currentCommit['description'])) {
                        $commits->push($currentCommit);
                    }
                }

                // Parse new commit
                $parts = explode('|', substr($line, 7), 4);
                if (count($parts) >= 4) {
                    $message = trim($parts[2]);

                    // Skip noise commits
                    if (! $this->shouldSkipCommit($message)) {
                        $currentCommit = [
                            'hash' => $parts[0],
                            'date' => Carbon::parse($parts[1]),
                            'message' => $message,
                            'author' => $parts[3],
                            'category' => $this->categorizeCommit($message),
                        ];
                        $currentFiles = [];
                    } else {
                        $currentCommit = null;
                        $currentFiles = [];
                    }
                }
            } elseif ($currentCommit && ! empty(trim($line))) {
                $currentFiles[] = trim($line);
            }
        }

        // Don't forget last commit
        if ($currentCommit) {
            $currentCommit['files'] = $currentFiles;
            $currentCommit['description'] = $this->generateDescription($currentCommit);

            if (! empty($currentCommit['description'])) {
                $commits->push($currentCommit);
            }
        }

        return $commits;
    }

    /**
     * Generate a meaningful description from commit message and files
     */
    protected function generateDescription(array $commit): string
    {
        $message = $commit['message'];
        $files = $commit['files'] ?? [];

        // If message is already descriptive (more than 3 words), clean and use it
        $wordCount = str_word_count($message);
        if ($wordCount >= 3) {
            return $this->cleanMessage($message);
        }

        // For short/vague messages, infer from files changed
        return $this->inferDescriptionFromFiles($message, $files);
    }

    /**
     * Infer a description based on files changed
     */
    protected function inferDescriptionFromFiles(string $message, array $files): string
    {
        if (empty($files)) {
            return $this->cleanMessage($message);
        }

        $lowerMessage = strtolower($message);

        // Analyze file patterns
        $areas = $this->analyzeFileAreas($files);

        // Build description based on message hints + files
        if (str_contains($lowerMessage, 'logo') || $this->hasFiles($files, ['logo', '.png', '.svg', '.ico'])) {
            return 'Updated application logo and branding assets';
        }

        if (str_contains($lowerMessage, 'favicon')) {
            return 'Updated favicon for browser tabs';
        }

        if ($this->hasFiles($files, ['landing', 'welcome', 'hero'])) {
            if (str_contains($lowerMessage, 'improve') || str_contains($lowerMessage, 'better')) {
                return 'Improved landing page design and conversion elements';
            }

            return 'Updated landing page content and layout';
        }

        if ($this->hasFiles($files, ['test', 'Test', 'spec'])) {
            return 'Updated test suite and test coverage';
        }

        if ($this->hasFiles($files, ['migration'])) {
            return 'Database schema changes and migrations';
        }

        if ($this->hasFiles($files, ['Controller'])) {
            $controller = $this->extractControllerName($files);

            return $controller ? "Updated {$controller} functionality" : 'Updated API controllers';
        }

        if ($this->hasFiles($files, ['Mail', 'email'])) {
            return 'Email template and notification updates';
        }

        if ($this->hasFiles($files, ['config'])) {
            return 'Configuration and settings updates';
        }

        if ($this->hasFiles($files, ['.css', '.scss', 'tailwind'])) {
            return 'Styling and visual improvements';
        }

        if ($this->hasFiles($files, ['routes'])) {
            return 'Routing and URL structure updates';
        }

        if ($this->hasFiles($files, ['middleware', 'Middleware'])) {
            return 'Security and middleware enhancements';
        }

        if ($this->hasFiles($files, ['Command', 'Console'])) {
            return 'CLI commands and automation updates';
        }

        if ($this->hasFiles($files, ['Job', 'Queue'])) {
            return 'Background job processing improvements';
        }

        if (! empty($areas['views'])) {
            return 'User interface and view updates';
        }

        if (! empty($areas['models'])) {
            return 'Data model and business logic updates';
        }

        if (! empty($areas['services'])) {
            return 'Service layer and integration updates';
        }

        // Fallback: clean the original message
        $cleaned = $this->cleanMessage($message);

        return ! empty($cleaned) ? $cleaned : '';
    }

    /**
     * Check if files contain certain patterns
     */
    protected function hasFiles(array $files, array $patterns): bool
    {
        foreach ($files as $file) {
            foreach ($patterns as $pattern) {
                if (str_contains($file, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Analyze which areas of the codebase were touched
     */
    protected function analyzeFileAreas(array $files): array
    {
        $areas = [
            'views' => [],
            'controllers' => [],
            'models' => [],
            'services' => [],
            'config' => [],
            'tests' => [],
            'assets' => [],
        ];

        foreach ($files as $file) {
            if (str_contains($file, 'views/') || str_contains($file, '.blade.php')) {
                $areas['views'][] = $file;
            }
            if (str_contains($file, 'Controller')) {
                $areas['controllers'][] = $file;
            }
            if (str_contains($file, 'Models/')) {
                $areas['models'][] = $file;
            }
            if (str_contains($file, 'Services/') || str_contains($file, 'Managers/')) {
                $areas['services'][] = $file;
            }
            if (str_contains($file, 'config/')) {
                $areas['config'][] = $file;
            }
            if (str_contains($file, 'test') || str_contains($file, 'Test')) {
                $areas['tests'][] = $file;
            }
            if (preg_match('/\.(png|jpg|svg|ico|css|js)$/', $file)) {
                $areas['assets'][] = $file;
            }
        }

        return $areas;
    }

    /**
     * Extract controller name from files
     */
    protected function extractControllerName(array $files): ?string
    {
        foreach ($files as $file) {
            if (preg_match('/(\w+)Controller\.php$/', $file, $matches)) {
                return Str::headline($matches[1]);
            }
        }

        return null;
    }

    /**
     * Clean commit message for display
     */
    protected function cleanMessage(string $message): string
    {
        // Remove conventional commit prefixes
        $message = preg_replace('/^(fix|feat|chore|docs|style|refactor|test|perf|ci|build)(\(.+?\))?:\s*/i', '', $message);

        // Remove issue references
        $message = preg_replace('/\s*\(#\d+\)\s*$/', '', $message);
        $message = preg_replace('/\s*#\d+\s*$/', '', $message);

        // Capitalize first letter
        $message = ucfirst(trim($message));

        // Skip if too short after cleaning
        if (strlen($message) < 5) {
            return '';
        }

        return $message;
    }

    /**
     * Check if commit should be skipped entirely
     */
    protected function shouldSkipCommit(string $message): bool
    {
        $message = trim($message);

        // Skip merge commits
        if (str_starts_with(strtolower($message), 'merge ')) {
            return true;
        }

        foreach ($this->skipPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return strlen($message) < 2;
    }

    /**
     * Group commits by week
     */
    protected function groupByWeek(Collection $commits): Collection
    {
        $grouped = $commits->groupBy(function ($commit) {
            return $commit['date']->startOfWeek()->format('Y-m-d');
        });

        return $grouped->map(function ($weekCommits, $weekStart) {
            $startDate = Carbon::parse($weekStart);

            return [
                'week_start' => $startDate,
                'week_end' => $startDate->copy()->endOfWeek(),
                'label' => $this->getWeekLabel($startDate),
                'commits' => $weekCommits->values(),
            ];
        })->sortByDesc('week_start')->values();
    }

    /**
     * Process and deduplicate week changes
     */
    protected function processWeekChanges(array $weekData): array
    {
        $commits = collect($weekData['commits']);

        // Group by category
        $byCategory = $commits->groupBy('category');

        // Build deduplicated changes
        $changes = [
            'features' => $this->deduplicateChanges($byCategory->get('feature', collect())),
            'improvements' => $this->deduplicateChanges($byCategory->get('improvement', collect())),
            'fixes' => $this->deduplicateChanges($byCategory->get('fix', collect())),
            'other' => $this->deduplicateChanges(
                $byCategory->except(['feature', 'improvement', 'fix'])->flatten(1)
            ),
        ];

        $summary = $this->generateWeekSummary($changes);

        return [
            'week_start' => $weekData['week_start'],
            'week_end' => $weekData['week_end'],
            'label' => $weekData['label'],
            'summary' => $summary,
            'changes' => $changes,
            'total_changes' => $changes['features']->count() +
                $changes['improvements']->count() +
                $changes['fixes']->count() +
                $changes['other']->count(),
        ];
    }

    /**
     * Deduplicate similar changes
     */
    protected function deduplicateChanges(Collection $commits): Collection
    {
        return $commits
            ->filter(fn ($c) => ! empty($c['description']))
            ->unique('description')
            ->map(fn ($commit) => [
                'text' => $commit['description'],
                'hash' => $commit['hash'],
                'date' => $commit['date'],
            ])
            ->values();
    }

    /**
     * Generate a summary for the week
     */
    protected function generateWeekSummary(array $changes): string
    {
        $parts = [];

        $featureCount = $changes['features']->count();
        $improvementCount = $changes['improvements']->count();
        $fixCount = $changes['fixes']->count();

        if ($featureCount > 0) {
            $parts[] = $featureCount === 1 ? '1 new feature' : "{$featureCount} new features";
        }

        if ($improvementCount > 0) {
            $parts[] = $improvementCount === 1 ? '1 improvement' : "{$improvementCount} improvements";
        }

        if ($fixCount > 0) {
            $parts[] = $fixCount === 1 ? '1 bug fix' : "{$fixCount} bug fixes";
        }

        if (empty($parts)) {
            return 'Maintenance and updates';
        }

        return Str::of(collect($parts)->join(', ', ' and '))->ucfirst()->toString();
    }

    /**
     * Get a human-readable week label
     */
    protected function getWeekLabel(Carbon $weekStart): string
    {
        $now = Carbon::now()->startOfWeek();

        if ($weekStart->isSameDay($now)) {
            return 'This Week';
        }

        if ($weekStart->isSameDay($now->copy()->subWeek())) {
            return 'Last Week';
        }

        return $weekStart->format('M j').' - '.$weekStart->copy()->endOfWeek()->format('M j');
    }

    /**
     * Categorize commit based on message
     */
    protected function categorizeCommit(string $message): string
    {
        $lower = strtolower($message);

        // Features - new functionality
        if (preg_match('/^feat(\(.+?\))?:/i', $message) ||
            str_starts_with($lower, 'add ') ||
            str_starts_with($lower, 'implement ') ||
            str_starts_with($lower, 'create ') ||
            str_contains($lower, 'new ')) {
            return 'feature';
        }

        // Fixes
        if (preg_match('/^fix(\(.+?\))?:/i', $message) ||
            str_contains($lower, 'fix') ||
            str_contains($lower, 'bug') ||
            str_contains($lower, 'resolve') ||
            str_contains($lower, 'issue')) {
            return 'fix';
        }

        // Improvements
        if (str_contains($lower, 'improve') ||
            str_contains($lower, 'enhance') ||
            str_contains($lower, 'update') ||
            str_contains($lower, 'refactor') ||
            str_contains($lower, 'better') ||
            str_contains($lower, 'optimi')) {
            return 'improvement';
        }

        return 'other';
    }
}
