<?php

namespace App\Services\Integrations\Providers;

use App\Data\IntegrationShareResultData;
use App\Data\IntegrationTokenData;
use App\Models\Integration;
use App\Models\Video;
use App\Services\Integrations\IntegrationProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraProvider implements IntegrationProviderInterface
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.integrations.jira.client_id', '');
        $this->clientSecret = config('services.integrations.jira.client_secret', '');
        $this->redirectUri = config('services.integrations.jira.redirect_uri', '');
    }

    public function getProviderName(): string
    {
        return 'jira';
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'audience' => 'api.atlassian.com',
            'client_id' => $this->clientId,
            'scope' => 'read:jira-work write:jira-work read:me offline_access',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'prompt' => 'consent',
        ]);

        return "https://auth.atlassian.com/authorize?{$params}";
    }

    public function handleCallback(string $code): IntegrationTokenData
    {
        $response = Http::post('https://auth.atlassian.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \RuntimeException('Jira OAuth failed: '.($data['error_description'] ?? $data['error']));
        }

        // Discover cloud ID
        $resources = Http::withToken($data['access_token'])
            ->get('https://api.atlassian.com/oauth/token/accessible-resources')
            ->json();

        $cloudId = $resources[0]['id'] ?? null;
        $siteName = $resources[0]['name'] ?? null;

        return new IntegrationTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 3600,
            external_user_id: $cloudId,
            external_user_name: $siteName,
            metadata: [
                'cloud_id' => $cloudId,
                'site_name' => $siteName,
                'site_url' => $resources[0]['url'] ?? null,
            ],
        );
    }

    public function refreshToken(string $refreshToken): IntegrationTokenData
    {
        $response = Http::post('https://auth.atlassian.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new \RuntimeException('Jira token refresh failed: '.($data['error_description'] ?? $data['error']));
        }

        return new IntegrationTokenData(
            access_token: $data['access_token'],
            refresh_token: $data['refresh_token'] ?? null,
            expires_in: $data['expires_in'] ?? 3600,
            external_user_id: null,
            external_user_name: null,
            metadata: null,
        );
    }

    public function shareVideo(Integration $integration, Video $video, array $options): IntegrationShareResultData
    {
        $projectKey = $options['target_id'];
        $message = $options['message'] ?? '';
        $cloudId = $integration->metadata['cloud_id'] ?? $integration->external_user_id;
        $shareUrl = $video->share_token
            ? config('services.frontend.url').'/share/video/'.$video->share_token
            : config('services.frontend.url').'/video/'.$video->id;

        if (! $cloudId) {
            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: 'Jira cloud ID not found. Please reconnect.',
                metadata: null,
            );
        }

        try {
            $description = $message
                ? "{$message}\n\nVideo: [{$video->title}|{$shareUrl}]"
                : "Video: [{$video->title}|{$shareUrl}]";

            $issueType = $this->findIssueType($integration->access_token, $cloudId, $projectKey, 'Task');

            $response = Http::withToken($integration->access_token)
                ->post("https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue", [
                    'fields' => [
                        'project' => ['key' => $projectKey],
                        'summary' => "Video: {$video->title}",
                        'description' => [
                            'type' => 'doc',
                            'version' => 1,
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => $message ?: "Shared from ScreenBuddy: {$video->title}"],
                                    ],
                                ],
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => $shareUrl,
                                            'marks' => [['type' => 'link', 'attrs' => ['href' => $shareUrl]]],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'issuetype' => $issueType,
                    ],
                ]);

            $data = $response->json();

            if (isset($data['key'])) {
                $siteUrl = $integration->metadata['site_url'] ?? null;
                $issueUrl = $siteUrl ? "{$siteUrl}/browse/{$data['key']}" : null;

                return new IntegrationShareResultData(
                    success: true,
                    external_url: $issueUrl,
                    external_id: $data['key'],
                    error: null,
                    metadata: ['project_key' => $projectKey],
                );
            }

            $errors = $data['errors'] ?? $data['errorMessages'] ?? ['Unknown error'];

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: is_array($errors) ? implode(', ', $errors) : $errors,
                metadata: null,
            );
        } catch (\Exception $e) {
            Log::error('Jira share failed', ['error' => $e->getMessage()]);

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: $e->getMessage(),
                metadata: null,
            );
        }
    }

    public function createBugIssue(Integration $integration, Video $video, array $bugData): IntegrationShareResultData
    {
        $projectKey = $bugData['target_id'];
        $cloudId = $integration->metadata['cloud_id'] ?? $integration->external_user_id;
        $shareUrl = $video->share_token
            ? config('services.frontend.url').'/share/video/'.$video->share_token
            : config('services.frontend.url').'/video/'.$video->id;

        if (! $cloudId) {
            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: 'Jira cloud ID not found. Please reconnect.',
                metadata: null,
            );
        }

        try {
            // Build ADF description content
            $descriptionContent = [];

            // Bug description
            if (! empty($bugData['bug_description'])) {
                $descriptionContent[] = [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $bugData['bug_description']],
                    ],
                ];
            }

            // Severity
            if (! empty($bugData['bug_severity'])) {
                $descriptionContent[] = [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Severity: ', 'marks' => [['type' => 'strong']]],
                        ['type' => 'text', 'text' => ucfirst($bugData['bug_severity'])],
                    ],
                ];
            }

            // Steps to reproduce
            $steps = $bugData['steps_to_reproduce'] ?? [];
            if (! empty($steps)) {
                $descriptionContent[] = [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Steps to Reproduce:', 'marks' => [['type' => 'strong']]],
                    ],
                ];

                $listItems = array_map(function ($step) {
                    return [
                        'type' => 'listItem',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => $step],
                                ],
                            ],
                        ],
                    ];
                }, $steps);

                $descriptionContent[] = [
                    'type' => 'orderedList',
                    'content' => $listItems,
                ];
            }

            // Video link
            $descriptionContent[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'Video Recording: ', 'marks' => [['type' => 'strong']]],
                    [
                        'type' => 'text',
                        'text' => $video->title ?: 'View Recording',
                        'marks' => [['type' => 'link', 'attrs' => ['href' => $shareUrl]]],
                    ],
                ],
            ];

            $issueType = $this->findIssueType($integration->access_token, $cloudId, $projectKey, 'Bug');
            $accountId = $this->getCurrentUserAccountId($integration->access_token, $cloudId);

            $fields = [
                'project' => ['key' => $projectKey],
                'summary' => $bugData['bug_title'] ?? 'Bug from ScreenBuddy',
                'description' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => $descriptionContent,
                ],
                'issuetype' => $issueType,
            ];

            if ($accountId) {
                $fields['assignee'] = ['accountId' => $accountId];
            }

            $response = Http::withToken($integration->access_token)
                ->post("https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue", [
                    'fields' => $fields,
                ]);

            $data = $response->json();

            if (isset($data['key'])) {
                $siteUrl = $integration->metadata['site_url'] ?? null;
                $issueUrl = $siteUrl ? "{$siteUrl}/browse/{$data['key']}" : null;

                return new IntegrationShareResultData(
                    success: true,
                    external_url: $issueUrl,
                    external_id: $data['key'],
                    error: null,
                    metadata: ['project_key' => $projectKey, 'bug_id' => $bugData['bug_id'] ?? null],
                );
            }

            $errors = $data['errors'] ?? $data['errorMessages'] ?? ['Unknown error'];

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: is_array($errors) ? implode(', ', $errors) : $errors,
                metadata: null,
            );
        } catch (\Exception $e) {
            Log::error('Jira bug creation failed', ['error' => $e->getMessage()]);

            return new IntegrationShareResultData(
                success: false,
                external_url: null,
                external_id: null,
                error: $e->getMessage(),
                metadata: null,
            );
        }
    }

    /**
     * Get the current authenticated Jira user's account ID.
     */
    protected function getCurrentUserAccountId(string $accessToken, string $cloudId): ?string
    {
        try {
            // Use Atlassian platform /me endpoint (requires read:me scope)
            $response = Http::withToken($accessToken)
                ->get('https://api.atlassian.com/me');

            $accountId = $response->json('account_id');

            if (! $accountId) {
                Log::warning('Atlassian /me returned no account_id', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            }

            return $accountId;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Jira user for assignment', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Find the best matching issue type for a project.
     * Searches for the target name first, then falls back to common alternatives.
     */
    protected function findIssueType(string $accessToken, string $cloudId, string $projectKey, string $preferredType = 'Bug'): array
    {
        $response = Http::withToken($accessToken)
            ->get("https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/issue/createmeta/{$projectKey}/issuetypes");

        $issueTypes = $response->json('issueTypes', $response->json('values', []));

        if (empty($issueTypes)) {
            return ['name' => $preferredType];
        }

        // Preferred type names in order of priority
        $candidates = $preferredType === 'Bug'
            ? ['Bug', 'Defect', 'bug', 'defect']
            : ['Task', 'Story', 'task', 'story'];

        foreach ($candidates as $name) {
            foreach ($issueTypes as $type) {
                if (strcasecmp($type['name'], $name) === 0) {
                    return ['id' => $type['id']];
                }
            }
        }

        // Fall back to first available non-subtask type
        foreach ($issueTypes as $type) {
            if (empty($type['subtask'])) {
                return ['id' => $type['id']];
            }
        }

        // Last resort: first type
        return ['id' => $issueTypes[0]['id']];
    }

    public function revokeAccess(Integration $integration): bool
    {
        // Atlassian doesn't have a token revocation endpoint
        return true;
    }

    public function getAvailableTargets(Integration $integration): array
    {
        $targets = [];
        $cloudId = $integration->metadata['cloud_id'] ?? $integration->external_user_id;

        if (! $cloudId) {
            return $targets;
        }

        try {
            $response = Http::withToken($integration->access_token)
                ->get("https://api.atlassian.com/ex/jira/{$cloudId}/rest/api/3/project/search", [
                    'maxResults' => 50,
                    'orderBy' => 'name',
                ]);

            $data = $response->json();

            foreach ($data['values'] ?? [] as $project) {
                $targets[] = [
                    'id' => $project['key'],
                    'name' => $project['name'].' ('.$project['key'].')',
                    'type' => 'project',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Jira targets fetch failed', ['error' => $e->getMessage()]);
        }

        return $targets;
    }
}
