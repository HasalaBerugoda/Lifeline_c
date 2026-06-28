<?php
// Simple .env file loader for LifeLine

function loadEnv() {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments or empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Split by the first '='
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/', $val, $matches) || preg_match('/^\'(.*)\'$/', $val, $matches)) {
                $val = $matches[1];
            }

            // Put in environment
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("$key=$val");
        }
    }
}

// Load immediately on inclusion
loadEnv();

// Define global constant APP_URL
if (!defined('APP_URL')) {
    $appUrl = getenv('APP_URL');
    if (!$appUrl) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $projectRootDisk = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $entryScriptDisk = str_replace('\\', '/', $scriptFilename);

        if ($scriptName && $scriptFilename && strpos($entryScriptDisk, $projectRootDisk) === 0) {
            $subPath = substr($entryScriptDisk, strlen($projectRootDisk));
            $subPathLen = strlen($subPath);
            if (substr($scriptName, -$subPathLen) === $subPath) {
                $baseUrlPath = substr($scriptName, 0, strlen($scriptName) - $subPathLen);
                $appUrl = $protocol . $host . rtrim($baseUrlPath, '/');
            }
        }
        
        if (!$appUrl) {
            $appUrl = 'http://localhost/LIFELINE-claud-prompt';
        }
    }
    define('APP_URL', rtrim($appUrl, '/'));
}
