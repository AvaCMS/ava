<?php

declare(strict_types=1);

namespace Ava\Admin;

/**
 * Simple session-based authentication for admin.
 */
final class Auth
{
    private const SESSION_KEY = 'ava_admin_user';
    private const CSRF_KEY = 'ava_csrf_token';
    private const SESSION_IP_KEY = 'ava_session_ip';
    private const SESSION_LAST_ACTIVITY_KEY = 'ava_last_activity';

    // Rate limiting constants (IP-based)
    private const MAX_ATTEMPTS = 5;           // Max attempts before lockout
    private const LOCKOUT_DURATION = 900;     // 15 minutes in seconds
    private const ATTEMPT_WINDOW = 3600;      // Clear attempts after 1 hour

    // Username-based rate limiting (protects against distributed attacks)
    private const USERNAME_MAX_ATTEMPTS = 10;      // Higher threshold (multiple IPs expected)
    private const USERNAME_LOCKOUT_DURATION = 1800; // 30 minutes for username lockouts

    // Session security defaults
    private const DEFAULT_SESSION_TIMEOUT = 1800;  // 30 minutes idle timeout
    private const DEFAULT_IP_BINDING = false;      // Disabled by default (can break mobile/VPN users)

    private string $usersFile;
    private string $storagePath;
    private ?array $users = null;
    private bool $ipBindingEnabled;
    private ?int $sessionTimeout;

    /**
     * @param string $usersFile Path to users.php config file
     * @param string|null $storagePath Path to storage directory
     * @param bool $ipBinding Bind sessions to IP address (prevents cookie theft from different IP)
     * @param int|null $sessionTimeout Session idle timeout in seconds (null = no timeout)
     */
    public function __construct(
        string $usersFile,
        ?string $storagePath = null,
        bool $ipBinding = self::DEFAULT_IP_BINDING,
        ?int $sessionTimeout = self::DEFAULT_SESSION_TIMEOUT
    ) {
        $this->usersFile = $usersFile;
        $this->storagePath = $storagePath ?? dirname($usersFile, 2) . '/storage';
        $this->ipBindingEnabled = $ipBinding;
        $this->sessionTimeout = $sessionTimeout;
    }

    /**
     * Start session if not already started.
     */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Harden session handling.
            // - strict_mode: reject uninitialized session IDs (helps prevent fixation)
            // - use_only_cookies: never accept session IDs via URL
            // - use_trans_sid: ensure transparent SID propagation is off
            @ini_set('session.use_strict_mode', '1');
            @ini_set('session.use_only_cookies', '1');
            @ini_set('session.use_trans_sid', '0');

            // Isolate admin session cookie name from any front-end/session usage.
            // (Safe even if the public site never uses sessions.)
            @session_name('ava_admin');

            // Set secure session cookie parameters
            session_set_cookie_params([
                'lifetime' => 0,          // Session cookie
                'path' => '/',
                'secure' => (
                    (($_SERVER['HTTPS'] ?? 'off') !== 'off') ||
                    ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
                ),
                'httponly' => true,       // Prevent JavaScript access
                'samesite' => 'Lax',      // CSRF protection
            ]);
            session_start();
        }
    }

    /**
     * Check if a user is logged in.
     * 
     * Also validates:
     * - IP binding (if enabled): Session must be from same IP as login
     * - Session timeout (if configured): Session must not be idle too long
     */
    public function check(): bool
    {
        $this->startSession();
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        
        // Check IP binding - invalidate if IP changed
        if ($this->ipBindingEnabled) {
            $sessionIp = $_SESSION[self::SESSION_IP_KEY] ?? null;
            $currentIp = $this->getClientIp();
            
            if ($sessionIp !== null && !$this->ipMatches($sessionIp, $currentIp)) {
                // IP mismatch - possible session hijacking, force logout
                $this->logout();
                return false;
            }
        }
        
        // Check session timeout - invalidate if idle too long
        if ($this->sessionTimeout !== null) {
            $lastActivity = $_SESSION[self::SESSION_LAST_ACTIVITY_KEY] ?? 0;
            
            if (time() - $lastActivity > $this->sessionTimeout) {
                // Session expired due to inactivity
                $this->logout();
                return false;
            }
            
            // Update last activity timestamp
            $_SESSION[self::SESSION_LAST_ACTIVITY_KEY] = time();
        }
        
        return true;
    }

    /**
     * Get the current user email.
     */
    public function user(): ?string
    {
        $this->startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get the current user's data.
     */
    public function userData(): ?array
    {
        $email = $this->user();
        if ($email === null) {
            return null;
        }

        $users = $this->loadUsers();
        return $users[$email] ?? null;
    }

    /**
     * Attempt to log in with email and password.
     * Includes rate limiting to prevent brute-force attacks.
     * 
     * Rate limiting is applied at two levels:
     * - IP-based: Prevents a single source from brute-forcing any account
     * - Username-based: Prevents distributed attacks targeting a single account
     */
    public function attempt(string $email, string $password): bool
    {
        // Normalize email for consistent rate limiting
        $normalizedEmail = strtolower(trim($email));

        // Check rate limiting (both IP and username)
        $ip = $this->getClientIp();
        if ($this->isLockedOut($ip) || $this->isUsernameLocked($normalizedEmail)) {
            return false;
        }

        $users = $this->loadUsers();

        if (!isset($users[$email])) {
            // Prevent timing attacks
            password_verify($password, '$2y$10$dummyhashtopreventtimingattacks');
            $this->recordFailedAttempt($ip);
            $this->recordFailedUsernameAttempt($normalizedEmail);
            return false;
        }

        $user = $users[$email];

        if (!password_verify($password, $user['password'])) {
            $this->recordFailedAttempt($ip);
            $this->recordFailedUsernameAttempt($normalizedEmail);
            return false;
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($ip);
        $this->clearFailedUsernameAttempts($normalizedEmail);

        // Regenerate session ID to prevent fixation
        $this->startSession();
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = $email;
        
        // Store IP for binding validation (prevents session hijacking from different IP)
        if ($this->ipBindingEnabled) {
            $_SESSION[self::SESSION_IP_KEY] = $this->getClientIp();
        }
        
        // Initialize activity timestamp for timeout tracking
        if ($this->sessionTimeout !== null) {
            $_SESSION[self::SESSION_LAST_ACTIVITY_KEY] = time();
        }

        // Update last login time
        $this->updateLastLogin($email);

        return true;
    }

    /**
     * Check if IP is locked out due to too many failed attempts.
     */
    public function isLockedOut(?string $ip = null): bool
    {
        $ip = $ip ?? $this->getClientIp();
        $attempts = $this->getFailedAttempts($ip);

        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $lockoutEnd = $attempts['last_attempt'] + self::LOCKOUT_DURATION;
            return time() < $lockoutEnd;
        }

        return false;
    }

    /**
     * Get remaining lockout time in seconds.
     */
    public function getLockoutRemaining(?string $ip = null): int
    {
        $ip = $ip ?? $this->getClientIp();
        $attempts = $this->getFailedAttempts($ip);

        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $lockoutEnd = $attempts['last_attempt'] + self::LOCKOUT_DURATION;
            $remaining = $lockoutEnd - time();
            return max(0, $remaining);
        }

        return 0;
    }

    /**
     * Check if a username is locked out due to too many failed attempts.
     * This protects against distributed brute-force attacks targeting a single account.
     */
    public function isUsernameLocked(string $email): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $attempts = $this->getFailedUsernameAttempts($normalizedEmail);

        if ($attempts['count'] >= self::USERNAME_MAX_ATTEMPTS) {
            $lockoutEnd = $attempts['last_attempt'] + self::USERNAME_LOCKOUT_DURATION;
            return time() < $lockoutEnd;
        }

        return false;
    }

    /**
     * Get remaining username lockout time in seconds.
     */
    public function getUsernameLockoutRemaining(string $email): int
    {
        $normalizedEmail = strtolower(trim($email));
        $attempts = $this->getFailedUsernameAttempts($normalizedEmail);

        if ($attempts['count'] >= self::USERNAME_MAX_ATTEMPTS) {
            $lockoutEnd = $attempts['last_attempt'] + self::USERNAME_LOCKOUT_DURATION;
            $remaining = $lockoutEnd - time();
            return max(0, $remaining);
        }

        return 0;
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        $this->startSession();

        // Clear authentication and CSRF state.
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::CSRF_KEY]);

        // Clear all session data to reduce residual risk.
        $_SESSION = [];

        // Regenerate session ID and destroy session to invalidate cookie.
        session_regenerate_id(true);
        @session_destroy();
    }

    /**
     * Generate a CSRF token.
     */
    public function csrfToken(): string
    {
        $this->startSession();

        if (!isset($_SESSION[self::CSRF_KEY])) {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CSRF_KEY];
    }

    /**
     * Verify a CSRF token.
     */
    public function verifyCsrf(string $token): bool
    {
        $this->startSession();
        return isset($_SESSION[self::CSRF_KEY]) && hash_equals($_SESSION[self::CSRF_KEY], $token);
    }

    /**
     * Regenerate CSRF token (after form submission).
     */
    public function regenerateCsrf(): void
    {
        $this->startSession();
        $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
    }

    /**
     * Get all users (for admin display).
     */
    public function allUsers(): array
    {
        return $this->loadUsers();
    }

    /**
     * Update user's last login time.
     */
    private function updateLastLogin(string $email): void
    {
        $users = $this->loadUsers();
        if (!isset($users[$email])) {
            return;
        }

        $users[$email]['last_login'] = date('Y-m-d H:i:s');

        // Write back to file with exclusive lock to prevent corruption
        $content = "<?php\n\ndeclare(strict_types=1);\n\n// Prevent direct access\ndefined('AVA_ROOT') || exit;\n\n/**\n * Users Configuration\n *\n * Managed by CLI. Do not edit manually.\n */\n\nreturn " . var_export($users, true) . ";\n";
        file_put_contents($this->usersFile, $content, LOCK_EX);

        // Update cache
        $this->users = $users;
    }

    /**
     * Load users from config file.
     */
    private function loadUsers(): array
    {
        if ($this->users === null) {
            if (file_exists($this->usersFile)) {
                $this->users = require $this->usersFile;
                if (!is_array($this->users)) {
                    $this->users = [];
                }
            } else {
                $this->users = [];
            }
        }

        return $this->users;
    }

    /**
     * Check if any users exist.
     */
    public function hasUsers(): bool
    {
        return count($this->loadUsers()) > 0;
    }

    /**
     * Get the client IP address.
     * 
     * Only uses REMOTE_ADDR for security - proxy headers can be spoofed.
     */
    private function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return '0.0.0.0';
    }

    /**
     * Check if two IP addresses match for session binding.
     * 
     * For IPv4: Exact match required.
     * For IPv6: Compares normalized addresses (handles different representations).
     * 
     * Note: This is intentionally strict. For users behind load balancers that
     * rotate IPs, IP binding should be disabled in config.
     */
    private function ipMatches(string $sessionIp, string $currentIp): bool
    {
        // Normalize both IPs for comparison
        $normalizedSession = $this->normalizeIp($sessionIp);
        $normalizedCurrent = $this->normalizeIp($currentIp);
        
        return $normalizedSession === $normalizedCurrent;
    }

    /**
     * Normalize an IP address for consistent comparison.
     * 
     * Handles IPv6 variations (::1, 0:0:0:0:0:0:0:1, etc.)
     */
    private function normalizeIp(string $ip): string
    {
        // Use PHP's built-in IP validation and normalization
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip; // Return as-is if invalid
        }
        
        return inet_ntop($packed);
    }

    /**
     * Get the path to the rate limiting data file.
     */
    private function getRateLimitPath(): string
    {
        return $this->storagePath . '/auth_attempts.json';
    }

    /**
     * Get failed login attempts for an IP.
     *
     * @return array{count: int, last_attempt: int}
     */
    private function getFailedAttempts(string $ip): array
    {
        $path = $this->getRateLimitPath();
        $data = [];

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $data = json_decode($content, true) ?? [];
        }

        $ipHash = hash('sha256', $ip);

        if (!isset($data[$ipHash])) {
            return ['count' => 0, 'last_attempt' => 0];
        }

        $attempts = $data[$ipHash];

        // Clear if attempt window expired
        if (time() - $attempts['last_attempt'] > self::ATTEMPT_WINDOW) {
            $this->clearFailedAttempts($ip);
            return ['count' => 0, 'last_attempt' => 0];
        }

        return $attempts;
    }

    /**
     * Record a failed login attempt.
     * 
     * Uses exclusive file locking to prevent race conditions where
     * concurrent requests could bypass rate limiting.
     */
    private function recordFailedAttempt(string $ip): void
    {
        $path = $this->getRateLimitPath();
        $ipHash = hash('sha256', $ip);

        // Use exclusive lock for the entire read-modify-write cycle
        $this->withFileLock($path, function ($data) use ($ipHash) {
            $current = $data[$ipHash] ?? ['count' => 0, 'last_attempt' => 0];

            // Reset if window expired
            if (time() - $current['last_attempt'] > self::ATTEMPT_WINDOW) {
                $current = ['count' => 0, 'last_attempt' => 0];
            }

            $current['count']++;
            $current['last_attempt'] = time();
            $data[$ipHash] = $current;

            // Clean up old entries
            $this->cleanupOldAttempts($data);

            return $data;
        });
    }

    /**
     * Clear failed attempts for an IP.
     */
    private function clearFailedAttempts(string $ip): void
    {
        $path = $this->getRateLimitPath();

        if (!file_exists($path)) {
            return;
        }

        $ipHash = hash('sha256', $ip);

        $this->withFileLock($path, function ($data) use ($ipHash) {
            unset($data[$ipHash]);
            return $data;
        });
    }

    /**
     * Execute a callback with exclusive file lock on the rate limit file.
     * Ensures atomic read-modify-write operations.
     * 
     * @param string $path File path
     * @param callable $callback Receives current data, returns modified data
     */
    private function withFileLock(string $path, callable $callback): void
    {
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Open file for reading and writing, create if doesn't exist
        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return; // Fail silently - don't break login on file issues
        }

        try {
            // Acquire exclusive lock (blocking)
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            // Read current data
            $content = '';
            $size = filesize($path);
            if ($size > 0) {
                rewind($handle);
                $content = fread($handle, $size);
            }
            $data = $content ? (json_decode($content, true) ?? []) : [];

            // Execute callback to modify data
            $data = $callback($data);

            // Write back
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($data));
            fflush($handle);

            // Release lock
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Clean up old attempt records.
     */
    private function cleanupOldAttempts(array &$data): void
    {
        $now = time();
        foreach ($data as $ip => $attempts) {
            if ($now - $attempts['last_attempt'] > self::ATTEMPT_WINDOW) {
                unset($data[$ip]);
            }
        }
    }

    /**
     * Get the path to the username rate limiting data file.
     */
    private function getUsernameRateLimitPath(): string
    {
        return $this->storagePath . '/auth_username_attempts.json';
    }

    /**
     * Get failed login attempts for a username.
     *
     * @return array{count: int, last_attempt: int}
     */
    private function getFailedUsernameAttempts(string $email): array
    {
        $path = $this->getUsernameRateLimitPath();
        $data = [];

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $data = json_decode($content, true) ?? [];
        }

        // Hash the email to avoid storing plaintext usernames
        $emailHash = hash('sha256', $email);

        if (!isset($data[$emailHash])) {
            return ['count' => 0, 'last_attempt' => 0];
        }

        $attempts = $data[$emailHash];

        // Clear if attempt window expired
        if (time() - $attempts['last_attempt'] > self::ATTEMPT_WINDOW) {
            $this->clearFailedUsernameAttempts($email);
            return ['count' => 0, 'last_attempt' => 0];
        }

        return $attempts;
    }

    /**
     * Record a failed login attempt for a username.
     */
    private function recordFailedUsernameAttempt(string $email): void
    {
        $path = $this->getUsernameRateLimitPath();
        $emailHash = hash('sha256', $email);

        $this->withFileLock($path, function ($data) use ($emailHash) {
            $current = $data[$emailHash] ?? ['count' => 0, 'last_attempt' => 0];

            // Reset if window expired
            if (time() - $current['last_attempt'] > self::ATTEMPT_WINDOW) {
                $current = ['count' => 0, 'last_attempt' => 0];
            }

            $current['count']++;
            $current['last_attempt'] = time();
            $data[$emailHash] = $current;

            // Clean up old entries
            $this->cleanupOldAttempts($data);

            return $data;
        });
    }

    /**
     * Clear failed attempts for a username.
     */
    private function clearFailedUsernameAttempts(string $email): void
    {
        $path = $this->getUsernameRateLimitPath();

        if (!file_exists($path)) {
            return;
        }

        $emailHash = hash('sha256', $email);

        $this->withFileLock($path, function ($data) use ($emailHash) {
            unset($data[$emailHash]);
            return $data;
        });
    }
}
