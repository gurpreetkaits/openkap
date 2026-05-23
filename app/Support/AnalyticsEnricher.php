<?php

namespace App\Support;

/**
 * Parses tracking-related request fields into normalised columns
 * for video_views (device, browser, OS, referrer source, country code).
 *
 * Kept dependency-free — no GeoIP or UA libraries are pulled in.
 * Country is inferred from an optional client-supplied timezone, which
 * is good enough for the analytics dashboard's "top countries" panel.
 */
class AnalyticsEnricher
{
    /**
     * @return array{device_type: string|null, browser: string|null, os: string|null}
     */
    public static function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['device_type' => null, 'browser' => null, 'os' => null];
        }

        $isTablet = (bool) preg_match('/iPad|Tablet|Nexus 7|Nexus 10|Kindle|PlayBook/i', $ua);
        $isMobile = ! $isTablet && (bool) preg_match('/Mobile|Android|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini/i', $ua);
        $device = $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop');

        $browser = match (true) {
            (bool) preg_match('/Edg\//', $ua) => 'Edge',
            (bool) preg_match('/OPR\//', $ua) => 'Opera',
            (bool) preg_match('/Firefox\//', $ua) => 'Firefox',
            (bool) preg_match('/Chrome\//', $ua) => 'Chrome',
            (bool) preg_match('/Safari\//', $ua) => 'Safari',
            default => 'Other',
        };

        $os = match (true) {
            (bool) preg_match('/Windows NT/', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/', $ua) => 'macOS',
            (bool) preg_match('/Android/', $ua) => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            (bool) preg_match('/Linux/', $ua) => 'Linux',
            default => 'Other',
        };

        return ['device_type' => $device, 'browser' => $browser, 'os' => $os];
    }

    /**
     * @return array{referrer_source: string, referrer_url: string|null}
     */
    public static function classifyReferrer(?string $referrer): array
    {
        if (! $referrer) {
            return ['referrer_source' => 'direct', 'referrer_url' => null];
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $clean = substr($referrer, 0, 500);

        if ($host === '' || $host === 'null') {
            return ['referrer_source' => 'direct', 'referrer_url' => null];
        }

        $source = match (true) {
            str_contains($host, 'mail.google.com'),
            str_contains($host, 'outlook.live.com'),
            str_contains($host, 'outlook.office.com'),
            str_contains($host, 'mail.yahoo.com'),
            str_contains($host, 'protonmail.com') => 'email',

            str_contains($host, 'slack.com'),
            str_contains($host, 'app.slack.com') => 'slack',

            str_contains($host, 'notion.so'),
            str_contains($host, 'docs.google.com'),
            str_contains($host, 'confluence'),
            str_contains($host, 'coda.io') => 'docs',

            str_contains($host, 'twitter.com'),
            str_contains($host, 'x.com'),
            str_contains($host, 'linkedin.com'),
            str_contains($host, 'facebook.com') => 'social',

            str_contains($host, 'discord.com'),
            str_contains($host, 'teams.microsoft.com'),
            str_contains($host, 'zoom.us') => 'chat',

            default => 'other',
        };

        return ['referrer_source' => $source, 'referrer_url' => $clean];
    }

    /**
     * Crude timezone → ISO country-code mapping. Covers the most common
     * cases for "Top countries" without taking on a GeoIP dependency.
     */
    public static function countryFromTimezone(?string $tz): ?array
    {
        if (! $tz) {
            return null;
        }

        $map = [
            'America/New_York' => ['US', 'United States'],
            'America/Chicago' => ['US', 'United States'],
            'America/Denver' => ['US', 'United States'],
            'America/Los_Angeles' => ['US', 'United States'],
            'America/Phoenix' => ['US', 'United States'],
            'America/Anchorage' => ['US', 'United States'],
            'Pacific/Honolulu' => ['US', 'United States'],
            'America/Toronto' => ['CA', 'Canada'],
            'America/Vancouver' => ['CA', 'Canada'],
            'America/Edmonton' => ['CA', 'Canada'],
            'America/Mexico_City' => ['MX', 'Mexico'],
            'America/Sao_Paulo' => ['BR', 'Brazil'],
            'America/Argentina/Buenos_Aires' => ['AR', 'Argentina'],
            'Europe/London' => ['GB', 'United Kingdom'],
            'Europe/Dublin' => ['IE', 'Ireland'],
            'Europe/Paris' => ['FR', 'France'],
            'Europe/Berlin' => ['DE', 'Germany'],
            'Europe/Madrid' => ['ES', 'Spain'],
            'Europe/Amsterdam' => ['NL', 'Netherlands'],
            'Europe/Brussels' => ['BE', 'Belgium'],
            'Europe/Rome' => ['IT', 'Italy'],
            'Europe/Lisbon' => ['PT', 'Portugal'],
            'Europe/Stockholm' => ['SE', 'Sweden'],
            'Europe/Oslo' => ['NO', 'Norway'],
            'Europe/Copenhagen' => ['DK', 'Denmark'],
            'Europe/Helsinki' => ['FI', 'Finland'],
            'Europe/Warsaw' => ['PL', 'Poland'],
            'Europe/Zurich' => ['CH', 'Switzerland'],
            'Europe/Vienna' => ['AT', 'Austria'],
            'Europe/Athens' => ['GR', 'Greece'],
            'Europe/Istanbul' => ['TR', 'Türkiye'],
            'Europe/Moscow' => ['RU', 'Russia'],
            'Europe/Kiev' => ['UA', 'Ukraine'],
            'Asia/Tokyo' => ['JP', 'Japan'],
            'Asia/Seoul' => ['KR', 'South Korea'],
            'Asia/Shanghai' => ['CN', 'China'],
            'Asia/Hong_Kong' => ['HK', 'Hong Kong'],
            'Asia/Taipei' => ['TW', 'Taiwan'],
            'Asia/Singapore' => ['SG', 'Singapore'],
            'Asia/Bangkok' => ['TH', 'Thailand'],
            'Asia/Jakarta' => ['ID', 'Indonesia'],
            'Asia/Manila' => ['PH', 'Philippines'],
            'Asia/Kolkata' => ['IN', 'India'],
            'Asia/Calcutta' => ['IN', 'India'],
            'Asia/Dubai' => ['AE', 'United Arab Emirates'],
            'Asia/Tel_Aviv' => ['IL', 'Israel'],
            'Asia/Jerusalem' => ['IL', 'Israel'],
            'Australia/Sydney' => ['AU', 'Australia'],
            'Australia/Melbourne' => ['AU', 'Australia'],
            'Australia/Perth' => ['AU', 'Australia'],
            'Pacific/Auckland' => ['NZ', 'New Zealand'],
            'Africa/Johannesburg' => ['ZA', 'South Africa'],
            'Africa/Cairo' => ['EG', 'Egypt'],
            'Africa/Lagos' => ['NG', 'Nigeria'],
            'Africa/Nairobi' => ['KE', 'Kenya'],
        ];

        return $map[$tz] ?? null;
    }
}
