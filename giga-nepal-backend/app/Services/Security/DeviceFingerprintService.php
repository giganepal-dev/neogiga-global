<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use UAParser\Parser;

/**
 * Class DeviceFingerprintService
 * 
 * Generates and manages device fingerprints for session tracking.
 */
class DeviceFingerprintService
{
    /**
     * Generate a unique fingerprint for the current device.
     */
    public function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $this->getScreenResolution($request),
            $this->getTimezone($request),
            $this->getLanguage($request),
            $this->getPlatform($request),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Parse user agent to extract browser, OS, and device info.
     */
    public function parseUserAgent(string $userAgent): array
    {
        try {
            $parser = Parser::create();
            $result = $parser->parse($userAgent);

            return [
                'browser' => $this->formatBrowser($result->ua),
                'os' => $this->formatOS($result->os),
                'device_type' => $this->detectDeviceType($result->device),
            ];
        } catch (\Exception $e) {
            // Fallback if UAParser is not available
            return $this->simpleUserAgentParse($userAgent);
        }
    }

    /**
     * Get location data from IP address.
     */
    public function getLocationFromIp(string $ipAddress): ?array
    {
        // This would integrate with a geolocation service like MaxMind GeoIP2
        // For now, return null - can be enhanced later
        return null;
    }

    /**
     * Check if this is a known suspicious IP.
     */
    public function isSuspiciousIp(string $ipAddress): bool
    {
        // Integrate with threat intelligence feeds
        // For now, check against simple blacklist
        $blacklist = config('security.suspicious_ip_blacklist', []);
        return in_array($ipAddress, $blacklist);
    }

    /**
     * Detect if login is from an unusual location.
     */
    public function isUnusualLocation(int $userId, array $currentLocation): bool
    {
        // Get recent successful logins
        $recentLogins = \App\Models\LoginHistory::where('user_id', $userId)
            ->successful()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($recentLogins->isEmpty()) {
            return false; // No history to compare
        }

        // Check if current country differs from most recent logins
        $currentCountry = $currentLocation['country'] ?? null;
        
        if (!$currentCountry) {
            return false;
        }

        $previousCountries = $recentLogins->pluck('location_data')
            ->filter(fn($data) => isset($data['country']))
            ->map(fn($data) => $data['country'])
            ->toArray();

        // If majority of recent logins are from different country, flag as unusual
        $sameCountryCount = count(array_filter($previousCountries, fn($c) => $c === $currentCountry));
        
        return $sameCountryCount < (count($previousCountries) / 2);
    }

    /**
     * Format browser name from UA parser result.
     */
    protected function formatBrowser($ua): string
    {
        if (!$ua || !$ua->family) {
            return 'Unknown';
        }

        $version = $ua->toVersionString();
        return $version ?: $ua->family;
    }

    /**
     * Format OS name from UA parser result.
     */
    protected function formatOS($os): string
    {
        if (!$os || !$os->family) {
            return 'Unknown';
        }

        $version = $os->toVersionString();
        return $version ?: $os->family;
    }

    /**
     * Detect device type from UA parser result.
     */
    protected function detectDeviceType($device): string
    {
        if (!$device) {
            return 'desktop';
        }

        $type = strtolower($device->family);
        
        if (str_contains($type, 'mobile') || str_contains($type, 'phone')) {
            return 'mobile';
        }
        
        if (str_contains($type, 'tablet') || str_contains($type, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Simple fallback user agent parsing.
     */
    protected function simpleUserAgentParse(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceType = 'desktop';

        // Simple browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/MSIE|Trident/', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edg/', $userAgent)) {
            $browser = 'Edge';
        }

        // Simple OS detection
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent)) {
            $os = 'Android';
            $deviceType = 'mobile';
        } elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
            if (preg_match('/iPad/', $userAgent)) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'mobile';
            }
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
        ];
    }

    /**
     * Get screen resolution from request (if available via JS).
     */
    protected function getScreenResolution(Request $request): ?string
    {
        return $request->header('X-Screen-Resolution');
    }

    /**
     * Get timezone from request.
     */
    protected function getTimezone(Request $request): ?string
    {
        return $request->header('X-Timezone');
    }

    /**
     * Get language from request.
     */
    protected function getLanguage(Request $request): ?string
    {
        return $request->header('Accept-Language');
    }

    /**
     * Get platform hint from request.
     */
    protected function getPlatform(Request $request): ?string
    {
        return $request->header('X-Platform');
    }
}
