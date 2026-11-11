<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ServerLogController extends Controller
{
    private $isWindows;
    private $isMac;
    private $herdLogPathWindows;

    // Linux/Ubuntu paths
    private $nginxLogPathLinux = '/var/log/nginx/access.log';
    private $apacheLogPathLinux = '/var/log/apache2/access.log';

    // Windows paths
    private $nginxLogPathWindows = 'C:/nginx/logs/access.log';
    private $apacheLogPathWindows = 'C:/Apache24/logs/access.log';

    // macOS paths (including Herd)
    private $herdLogPathMac;
    private $nginxLogPathMac = '/usr/local/var/log/nginx/access.log';
    private $apacheLogPathMac = '/usr/local/var/log/httpd/access_log';

    public function __construct()
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        $this->isWindows = $os === 'WIN';
        $this->isMac = $os === 'DAR'; // Darwin = macOS

        // Laravel Herd log path (macOS and Windows)
        if ($this->isMac) {
            $homeDir = getenv('HOME');
            $this->herdLogPathMac = $homeDir . '/Library/Application Support/Herd/logs/nginx/access.log';
        } elseif ($this->isWindows) {
            $homeDir = getenv('USERPROFILE');
            $this->herdLogPathWindows = $homeDir . '\\AppData\\Local\\Herd\\logs\\nginx\\access.log';
        }
    }

    /**
     * Find all possible Herd log locations on Windows
     */
    private function findHerdLogsWindows(): array
    {
        $possiblePaths = [];
        $homeDir = getenv('USERPROFILE');

        // Common Herd paths on Windows (prioritize the actual location)
        $herdPaths = [
            $homeDir . '\\.config\\herd\\config\\nginx\\logs\\access.log', // Actual Herd location
            $homeDir . '\\AppData\\Local\\Herd\\logs\\nginx\\access.log',
            $homeDir . '\\AppData\\Roaming\\Herd\\logs\\nginx\\access.log',
            $homeDir . '\\.config\\herd\\logs\\nginx\\access.log',
        ];

        return $herdPaths;
    }

    /**
     * Scan for any nginx access logs in common locations
     */
    private function scanForLogs(): array
    {
        $foundLogs = [];

        if ($this->isWindows) {
            $homeDir = getenv('USERPROFILE');

            // Search in common directories
            $searchDirs = [
                $homeDir . '\\AppData\\Local\\Herd',
                $homeDir . '\\AppData\\Roaming\\Herd',
                $homeDir . '\\.config\\herd',
                'C:\\herd',
                'C:\\nginx',
                'C:\\laragon',
                'C:\\xampp',
                'C:\\wamp64',
            ];

            foreach ($searchDirs as $dir) {
                if (File::exists($dir)) {
                    $this->findAccessLogs($dir, $foundLogs);
                }
            }
        }

        return $foundLogs;
    }

    /**
     * Recursively find access.log files
     */
    private function findAccessLogs(string $directory, array &$foundLogs, int $depth = 0): void
    {
        // Limit recursion depth to avoid performance issues
        if ($depth > 5) return;

        try {
            $items = @scandir($directory);
            if ($items === false) return;

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;

                $path = $directory . DIRECTORY_SEPARATOR . $item;

                if (is_file($path) && $item === 'access.log') {
                    $foundLogs[] = $path;
                } elseif (is_dir($path)) {
                    $this->findAccessLogs($path, $foundLogs, $depth + 1);
                }
            }
        } catch (\Exception $e) {
            // Ignore permission errors
        }
    }

    /**
     * Shows the last 500 raw log lines from either Nginx or Apache.
     */
    public function showRawLog(Request $request)
    {
        $logPath = $this->getReadableLogPath();

        if (!$logPath) {
            return response()->json([
                'error' => 'No readable Nginx, Apache, or Herd log file found.',
                'os' => $this->getOSName(),
                'checked_paths' => $this->getCheckedPaths(),
                'scanned_logs' => $this->scanForLogs(),
            ], 404);
        }

        $logContent = $this->getLastLines($logPath, 500);

        if ($logContent === null) {
            return response()->json([
                'error' => 'Failed to read log file.',
                'log_path' => $logPath,
                'file_exists' => File::exists($logPath),
                'file_size' => File::exists($logPath) ? File::size($logPath) : 0,
                'is_readable' => File::exists($logPath) && File::isReadable($logPath),
            ], 500);
        }

        // Handle empty log file
        if (empty($logContent)) {
            return response()->json([
                'os' => $this->getOSName(),
                'server_type' => $this->getServerType($logPath),
                'log_path' => $logPath,
                'log_entries' => [],
                'message' => 'Log file is empty. Make some requests to your application to generate logs.'
            ]);
        }

        $logLines = explode("\n", trim($logContent));

        return response()->json([
            'os' => $this->getOSName(),
            'server_type' => $this->getServerType($logPath),
            'log_path' => $logPath,
            'log_entries' => array_reverse($logLines),
        ]);
    }

    /**
     * Shows the last 500 PARSED log lines into structured JSON.
     */
    public function showParsedLog(Request $request)
    {
        $logPath = $this->getReadableLogPath();

        if (!$logPath) {
            return response()->json([
                'error' => 'No readable Nginx, Apache, or Herd log file found.',
                'os' => $this->getOSName(),
                'checked_paths' => $this->getCheckedPaths(),
                'scanned_logs' => $this->scanForLogs(),
            ], 404);
        }

        $logContent = $this->getLastLines($logPath, 500);

        if ($logContent === null) {
            return response()->json([
                'error' => 'Failed to read log file.',
                'log_path' => $logPath,
                'file_exists' => File::exists($logPath),
                'file_size' => File::exists($logPath) ? File::size($logPath) : 0,
                'is_readable' => File::exists($logPath) && File::isReadable($logPath),
            ], 500);
        }

        // Handle empty log file
        if (empty($logContent)) {
            return response()->json([
                'os' => $this->getOSName(),
                'server_type' => $this->getServerType($logPath),
                'log_path' => $logPath,
                'log_entries' => [],
                'message' => 'Log file is empty. Make some requests to your application to generate logs.'
            ]);
        }

        $logLines = explode("\n", trim($logContent));
        $parsedLogs = [];

        $regex = '/^(\S+) (\S+) (\S+) \[([^\]]+)\] "(\S+) (\S+) (\S+)" (\d{3}) (\d+) "([^"]*)" "([^"]*)"$/';

        foreach ($logLines as $line) {
            if (preg_match($regex, $line, $matches)) {
                $parsedLogs[] = [
                    'ip_address' => $matches[1],
                    'timestamp' => \DateTime::createFromFormat('d/M/Y:H:i:s O', $matches[4])->format('Y-m-d H:i:s'),
                    'method' => $matches[5],
                    'url' => $matches[6],
                    'protocol' => $matches[7],
                    'status_code' => (int) $matches[8],
                    'bytes_sent' => (int) $matches[9],
                    'referrer' => $matches[10] !== '-' ? $matches[10] : null,
                    'user_agent' => $matches[11],
                ];
            }
        }

        return response()->json([
            'os' => $this->getOSName(),
            'server_type' => $this->getServerType($logPath),
            'log_path' => $logPath,
            'log_entries' => array_reverse($parsedLogs),
        ]);
    }

    /**
     * Get the last N lines from a file (cross-platform).
     */
    private function getLastLines(string $filePath, int $lines = 500): ?string
    {
        try {
            // Check if file is empty first
            if (!File::exists($filePath)) {
                \Log::error('Log file does not exist: ' . $filePath);
                return null;
            }

            $fileSize = File::size($filePath);
            if ($fileSize === 0) {
                \Log::info('Log file is empty: ' . $filePath);
                return ''; // Return empty string for empty file
            }

            if ($this->isWindows) {
                // Windows: Use PowerShell with proper escaping
                $escapedPath = str_replace('/', '\\', $filePath);
                // Use -LiteralPath to handle paths with special characters
                $command = sprintf(
                    'powershell -NoProfile -Command "Get-Content -LiteralPath \'%s\' -Tail %d -ErrorAction Stop"',
                    addslashes($escapedPath),
                    $lines
                );
            } else {
                // Linux/Unix/macOS: Use tail
                $command = sprintf(
                    'tail -n %d %s 2>&1',
                    $lines,
                    escapeshellarg($filePath)
                );
            }

            $output = shell_exec($command);

            if ($output === null) {
                \Log::error('shell_exec returned null for command: ' . $command);
                return null;
            }

            return $output;
        } catch (\Exception $e) {
            \Log::error('Failed to read log file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper function to find the first available and readable log path.
     */
    private function getReadableLogPath(): ?string
    {
        $paths = [];

        if ($this->isWindows) {
            // Add all possible Herd paths first
            $herdPaths = $this->findHerdLogsWindows();
            $paths = array_merge($herdPaths, [
                $this->nginxLogPathWindows,
                $this->apacheLogPathWindows,
                'C:/xampp/apache/logs/access.log',
                'C:/wamp64/logs/access.log',
                'C:/laragon/etc/nginx/logs/access.log',
            ]);
        } elseif ($this->isMac) {
            $paths = [
                $this->herdLogPathMac ?? null,
                $this->nginxLogPathMac,
                $this->apacheLogPathMac,
                '/opt/homebrew/var/log/nginx/access.log',
                '/usr/local/opt/nginx/logs/access.log',
            ];
        } else {
            $paths = [
                $this->nginxLogPathLinux,
                $this->apacheLogPathLinux,
                '/var/log/httpd/access_log',
            ];
        }

        foreach ($paths as $path) {
            if ($path && File::exists($path) && File::isReadable($path)) {
                return $path;
            }
        }

        // If still not found, try scanning
        if ($this->isWindows) {
            $scannedLogs = $this->scanForLogs();
            foreach ($scannedLogs as $log) {
                if (File::exists($log) && File::isReadable($log)) {
                    return $log;
                }
            }
        }

        return null;
    }

    /**
     * Get all checked paths for debugging.
     */
    private function getCheckedPaths(): array
    {
        if ($this->isWindows) {
            return [
                'herd_primary' => $this->herdLogPathWindows ?? 'Not configured',
                'herd_all' => $this->findHerdLogsWindows(),
                'nginx' => $this->nginxLogPathWindows,
                'apache' => $this->apacheLogPathWindows,
                'xampp_apache' => 'C:/xampp/apache/logs/access.log',
                'wamp_apache' => 'C:/wamp64/logs/access.log',
                'laragon_nginx' => 'C:/laragon/etc/nginx/logs/access.log',
            ];
        } elseif ($this->isMac) {
            return [
                'herd' => $this->herdLogPathMac ?? 'Not configured',
                'nginx' => $this->nginxLogPathMac,
                'apache' => $this->apacheLogPathMac,
                'homebrew_nginx_arm' => '/opt/homebrew/var/log/nginx/access.log',
                'homebrew_nginx_intel' => '/usr/local/opt/nginx/logs/access.log',
            ];
        } else {
            return [
                'nginx' => $this->nginxLogPathLinux,
                'apache_debian' => $this->apacheLogPathLinux,
                'apache_rhel' => '/var/log/httpd/access_log',
            ];
        }
    }

    /**
     * Get OS name for display.
     */
    private function getOSName(): string
    {
        if ($this->isWindows) return 'Windows';
        if ($this->isMac) return 'macOS';
        return 'Linux';
    }

    /**
     * Helper to identify the server type based on the log path.
     */
    private function getServerType(string $path): string
    {
        if (stripos($path, 'herd') !== false) {
            return 'Laravel Herd (Nginx)';
        }
        if (stripos($path, 'nginx') !== false) {
            return 'Nginx';
        }
        if (stripos($path, 'apache') !== false || stripos($path, 'httpd') !== false ||
            stripos($path, 'xampp') !== false || stripos($path, 'wamp') !== false) {
            return 'Apache';
        }
        if (stripos($path, 'laragon') !== false) {
            return 'Laragon (Nginx)';
        }
        return 'Unknown';
    }

    /**
     * Get traffic statistics for charts
     */
    public function getTrafficStats(Request $request)
    {
        $logPath = $this->getReadableLogPath();

        if (!$logPath) {
            return response()->json([
                'error' => 'No readable log file found.',
            ], 404);
        }

        // Get grouping parameter (hour, day, week, month)
        $groupBy = $request->input('group_by', 'hour'); // hour, day, week, month
        $limit = $request->input('limit', 1000); // Number of lines to analyze

        $logContent = $this->getLastLines($logPath, $limit);

        if ($logContent === null || empty($logContent)) {
            return response()->json([
                'error' => 'Log file is empty or cannot be read.',
                'traffic_by_time' => [],
                'status_code_distribution' => [],
                'top_urls' => [],
                'http_methods' => [],
            ]);
        }

        $logLines = explode("\n", trim($logContent));
        $regex = '/^(\S+) (\S+) (\S+) \[([^\]]+)\] "(\S+) (\S+) (\S+)" (\d{3}) (\d+) "([^"]*)" "([^"]*)"$/';

        $trafficByTime = [];
        $statusCodes = [];
        $urls = [];
        $methods = [];

        foreach ($logLines as $line) {
            if (preg_match($regex, $line, $matches)) {
                $timestamp = \DateTime::createFromFormat('d/M/Y:H:i:s O', $matches[4]);
                $method = $matches[5];
                $url = $matches[6];
                $statusCode = (int) $matches[8];

                if (!$timestamp) continue;

                // Group by time period
                $timeKey = $this->getTimeKey($timestamp, $groupBy);
                $displayTime = $this->getDisplayTime($timestamp, $groupBy);

                if (!isset($trafficByTime[$timeKey])) {
                    $trafficByTime[$timeKey] = [
                        'time' => $displayTime,
                        'timestamp' => $timeKey,
                        'requests' => 0,
                        'success' => 0,
                        'client_error' => 0,
                        'server_error' => 0,
                    ];
                }

                $trafficByTime[$timeKey]['requests']++;

                // Group by status code category
                if ($statusCode >= 200 && $statusCode < 300) {
                    $trafficByTime[$timeKey]['success']++;
                } elseif ($statusCode >= 400 && $statusCode < 500) {
                    $trafficByTime[$timeKey]['client_error']++;
                } elseif ($statusCode >= 500 && $statusCode < 600) {
                    $trafficByTime[$timeKey]['server_error']++;
                }

                // Count status codes
                $statusCodes[$statusCode] = ($statusCodes[$statusCode] ?? 0) + 1;

                // Count URLs
                $urls[$url] = ($urls[$url] ?? 0) + 1;

                // Count HTTP methods
                $methods[$method] = ($methods[$method] ?? 0) + 1;
            }
        }

        // Sort and prepare data
        ksort($trafficByTime);
        arsort($statusCodes);
        arsort($urls);
        arsort($methods);

        return response()->json([
            'os' => $this->getOSName(),
            'server_type' => $this->getServerType($logPath),
            'log_path' => $logPath,
            'group_by' => $groupBy,
            'analyzed_lines' => count($logLines),
            'traffic_by_time' => array_values($trafficByTime),
            'status_code_distribution' => array_map(function($code, $count) {
                return ['status_code' => $code, 'count' => $count];
            }, array_keys($statusCodes), $statusCodes),
            'top_urls' => array_slice(array_map(function($url, $count) {
                return ['url' => $url, 'count' => $count];
            }, array_keys($urls), $urls), 0, 20),
            'http_methods' => array_map(function($method, $count) {
                return ['method' => $method, 'count' => $count];
            }, array_keys($methods), $methods),
            'summary' => [
                'total_requests' => array_sum(array_column($trafficByTime, 'requests')),
                'unique_urls' => count($urls),
                'success_requests' => array_sum(array_column($trafficByTime, 'success')),
                'client_errors' => array_sum(array_column($trafficByTime, 'client_error')),
                'server_errors' => array_sum(array_column($trafficByTime, 'server_error')),
            ]
        ]);
    }

    /**
     * Get time key based on grouping (for sorting/grouping)
     */
    private function getTimeKey(\DateTime $timestamp, string $groupBy): string
    {
        switch ($groupBy) {
            case 'minute':
                return $timestamp->format('Y-m-d H:i');
            case 'hour':
                return $timestamp->format('Y-m-d H:00');
            case 'day':
                return $timestamp->format('Y-m-d');
            case 'week':
                return $timestamp->format('Y') . '-W' . $timestamp->format('W');
            case 'month':
                return $timestamp->format('Y-m');
            default:
                return $timestamp->format('Y-m-d H:00');
        }
    }

    /**
     * Get display time format (human readable)
     */
    private function getDisplayTime(\DateTime $timestamp, string $groupBy): string
    {
        switch ($groupBy) {
            case 'minute':
                return $timestamp->format('g:i A'); // e.g., "6:30 AM"
            case 'hour':
                return $timestamp->format('g A'); // e.g., "6 AM", "7 AM"
            case 'day':
                return $timestamp->format('M d'); // e.g., "Oct 13"
            case 'week':
                return 'Week ' . $timestamp->format('W, Y'); // e.g., "Week 41, 2024"
            case 'month':
                return $timestamp->format('M Y'); // e.g., "Oct 2024"
            default:
                return $timestamp->format('g A'); // e.g., "6 AM"
        }
    }

    /**
     * Test endpoint to check which paths exist.
     */
    public function testPaths()
    {
        $paths = $this->getCheckedPaths();
        $results = [];

        foreach ($paths as $name => $path) {
            if ($name === 'herd_all') {
                $results[$name] = [];
                foreach ($path as $herdPath) {
                    $results[$name][] = [
                        'path' => $herdPath,
                        'exists' => File::exists($herdPath),
                        'readable' => File::exists($herdPath) && File::isReadable($herdPath)
                    ];
                }
            } elseif ($path === 'Not configured') {
                $results[$name] = [
                    'path' => $path,
                    'exists' => false,
                    'readable' => false
                ];
            } else {
                $results[$name] = [
                    'path' => $path,
                    'exists' => File::exists($path),
                    'readable' => File::exists($path) && File::isReadable($path)
                ];
            }
        }

        // Add scanned logs
        $scannedLogs = $this->scanForLogs();

        return response()->json([
            'os' => $this->getOSName(),
            'php_os' => PHP_OS,
            'user_profile' => getenv('USERPROFILE'),
            'username' => getenv('USERNAME'),
            'current_log_path' => $this->getReadableLogPath(),
            'paths' => $results,
            'scanned_logs' => array_map(function ($log) {
                return [
                    'path' => $log,
                    'exists' => File::exists($log),
                    'readable' => File::exists($log) && File::isReadable($log),
                    'size' => File::exists($log) ? File::size($log) : 0
                ];
            }, $scannedLogs)
        ]);
    }
}
