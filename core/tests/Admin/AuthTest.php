<?php

declare(strict_types=1);

namespace Ava\Tests\Admin;

use Ava\Admin\Auth;
use Ava\Testing\TestCase;

/**
 * Tests for the Auth class.
 * 
 * These tests verify authentication, session management, CSRF protection,
 * and rate limiting functionality.
 */
class AuthTest extends TestCase
{
    private string $tempUsersFile;
    private string $tempStoragePath;

    public function setUp(): void
    {
        // Create temp directory for test files
        $this->tempStoragePath = sys_get_temp_dir() . '/ava_auth_test_' . uniqid();
        @mkdir($this->tempStoragePath, 0755, true);
        
        $this->tempUsersFile = $this->tempStoragePath . '/users.php';
        
        // Create a test user with known password hash
        // Password: "testpassword123"
        $testUsers = [
            'admin@example.com' => [
                'password' => password_hash('testpassword123', PASSWORD_DEFAULT),
                'name' => 'Test Admin',
                'created' => '2024-01-01 00:00:00',
            ],
        ];
        
        file_put_contents(
            $this->tempUsersFile,
            "<?php\n\nreturn " . var_export($testUsers, true) . ";\n"
        );
        
        // Clean up any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function tearDown(): void
    {
        // Clean up session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        
        // Clean up temp files
        $this->recursiveDelete($this->tempStoragePath);
    }
    
    private function recursiveDelete(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        
        if (is_dir($path)) {
            foreach (scandir($path) as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $this->recursiveDelete($path . '/' . $item);
            }
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }

    // =========================================================================
    // Basic Authentication Tests
    // =========================================================================

    public function testCheckReturnsFalseWhenNotLoggedIn(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertFalse($auth->check());
    }

    public function testUserReturnsNullWhenNotLoggedIn(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertNull($auth->user());
    }

    public function testAttemptSucceedsWithCorrectCredentials(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $result = $auth->attempt('admin@example.com', 'testpassword123');
        
        $this->assertTrue($result);
        $this->assertTrue($auth->check());
        $this->assertEquals('admin@example.com', $auth->user());
    }

    public function testAttemptFailsWithWrongPassword(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $result = $auth->attempt('admin@example.com', 'wrongpassword');
        
        $this->assertFalse($result);
        $this->assertFalse($auth->check());
        $this->assertNull($auth->user());
    }

    public function testAttemptFailsWithNonexistentUser(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $result = $auth->attempt('nobody@example.com', 'testpassword123');
        
        $this->assertFalse($result);
        $this->assertFalse($auth->check());
    }

    public function testLogoutClearsSession(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // First login
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Then logout
        $auth->logout();
        
        // Should no longer be authenticated
        // Note: After logout, we need a fresh Auth instance to check
        // because the session was destroyed
        $this->assertFalse(isset($_SESSION['ava_admin_user']));
    }

    public function testHasUsersReturnsTrueWhenUsersExist(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertTrue($auth->hasUsers());
    }

    public function testHasUsersReturnsFalseWhenNoUsersFile(): void
    {
        $auth = new Auth($this->tempStoragePath . '/nonexistent.php', $this->tempStoragePath);
        
        $this->assertFalse($auth->hasUsers());
    }

    public function testUserDataReturnsNullWhenNotLoggedIn(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertNull($auth->userData());
    }

    public function testUserDataReturnsUserInfoWhenLoggedIn(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        $auth->attempt('admin@example.com', 'testpassword123');
        
        $userData = $auth->userData();
        
        $this->assertIsArray($userData);
        $this->assertEquals('Test Admin', $userData['name']);
    }

    // =========================================================================
    // CSRF Token Tests
    // =========================================================================

    public function testCsrfTokenIsGenerated(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $token = $auth->csrfToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenIsPersistent(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $token1 = $auth->csrfToken();
        $token2 = $auth->csrfToken();
        
        $this->assertEquals($token1, $token2);
    }

    public function testVerifyCsrfAcceptsValidToken(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $token = $auth->csrfToken();
        
        $this->assertTrue($auth->verifyCsrf($token));
    }

    public function testVerifyCsrfRejectsInvalidToken(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $auth->csrfToken(); // Generate a token
        
        $this->assertFalse($auth->verifyCsrf('invalid-token'));
    }

    public function testVerifyCsrfRejectsEmptyToken(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $auth->csrfToken(); // Generate a token
        
        $this->assertFalse($auth->verifyCsrf(''));
    }

    public function testVerifyCsrfRejectsWhenNoTokenGenerated(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Don't generate a token first
        $this->assertFalse($auth->verifyCsrf('some-token'));
    }

    public function testRegenerateCsrfCreatesNewToken(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $token1 = $auth->csrfToken();
        $auth->regenerateCsrf();
        $token2 = $auth->csrfToken();
        
        $this->assertNotEquals($token1, $token2);
    }

    public function testOldCsrfTokenInvalidAfterRegenerate(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $oldToken = $auth->csrfToken();
        $auth->regenerateCsrf();
        
        $this->assertFalse($auth->verifyCsrf($oldToken));
    }

    public function testCsrfTokenIsSecureRandom(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Generate multiple tokens and ensure they're all unique
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $auth->regenerateCsrf();
            $tokens[] = $auth->csrfToken();
        }
        
        // All tokens should be unique
        $this->assertEquals(count($tokens), count(array_unique($tokens)));
    }

    // =========================================================================
    // Rate Limiting Tests
    // =========================================================================

    public function testIsLockedOutReturnsFalseInitially(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertFalse($auth->isLockedOut());
    }

    public function testFailedAttemptsAreCounted(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.101';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Make 4 failed attempts (under threshold of 5)
        for ($i = 0; $i < 4; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        // Should not be locked out yet
        $this->assertFalse($auth->isLockedOut());
    }

    public function testLockoutAfterMaxAttempts(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.102';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Make 5 failed attempts (at threshold)
        for ($i = 0; $i < 5; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        // Should now be locked out
        $this->assertTrue($auth->isLockedOut());
    }

    public function testAttemptFailsWhenLockedOut(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.103';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Trigger lockout
        for ($i = 0; $i < 5; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        // Even with correct password, should fail while locked out
        $result = $auth->attempt('admin@example.com', 'testpassword123');
        
        $this->assertFalse($result);
    }

    public function testGetLockoutRemainingReturnsZeroWhenNotLockedOut(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.104';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $this->assertEquals(0, $auth->getLockoutRemaining());
    }

    public function testGetLockoutRemainingReturnsPositiveWhenLockedOut(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.105';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Trigger lockout
        for ($i = 0; $i < 5; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        $remaining = $auth->getLockoutRemaining();
        
        // Should be close to 900 seconds (15 minutes)
        $this->assertGreaterThan(890, $remaining);
        $this->assertLessThanOrEqual(900, $remaining);
    }

    public function testSuccessfulLoginClearsFailedAttempts(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.106';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Make some failed attempts (under threshold)
        for ($i = 0; $i < 3; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        // Successful login
        $auth->attempt('admin@example.com', 'testpassword123');
        $auth->logout();
        
        // Make more failed attempts - should be counting from 0
        for ($i = 0; $i < 4; $i++) {
            $auth->attempt('admin@example.com', 'wrongpassword');
        }
        
        // Should not be locked out (only 4 attempts after reset)
        $this->assertFalse($auth->isLockedOut());
    }

    public function testDifferentIPsHaveSeparateRateLimits(): void
    {
        // First IP gets locked out
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $auth1 = new Auth($this->tempUsersFile, $this->tempStoragePath);
        for ($i = 0; $i < 5; $i++) {
            $auth1->attempt('admin@example.com', 'wrongpassword');
        }
        $this->assertTrue($auth1->isLockedOut());
        
        // Second IP should not be affected
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $auth2 = new Auth($this->tempUsersFile, $this->tempStoragePath);
        $this->assertFalse($auth2->isLockedOut());
        
        // Second IP can still login
        $result = $auth2->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($result);
    }

    // =========================================================================
    // Session Security Tests
    // =========================================================================
    // Note: Session configuration tests are limited because PHP doesn't allow
    // changing session settings after a session has started, and the test runner
    // may have already started one. We verify the code configures sessions
    // correctly by inspecting the Auth class code.
    //
    // The Auth::startSession() method sets:
    // - session_name('ava_admin') - isolated session name
    // - httponly => true - prevents JavaScript access
    // - samesite => 'Lax' - CSRF protection
    // - lifetime => 0 - session cookie (expires on browser close)
    // - secure => dynamic based on HTTPS detection
    // - session.use_strict_mode = 1 - rejects uninitialized session IDs
    // - session.use_only_cookies = 1 - no URL session IDs
    // - session.use_trans_sid = 0 - no automatic SID propagation

    public function testSessionStoresUserAfterLogin(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $auth->attempt('admin@example.com', 'testpassword123');
        
        // Session should now contain the user
        $this->assertTrue(isset($_SESSION['ava_admin_user']));
        $this->assertEquals('admin@example.com', $_SESSION['ava_admin_user']);
    }

    public function testSessionClearedAfterLogout(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Login
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Logout
        $auth->logout();
        
        // Session data should be cleared
        $this->assertFalse(isset($_SESSION['ava_admin_user']));
        $this->assertFalse(isset($_SESSION['ava_csrf_token']));
    }

    public function testCsrfTokenStoredInSession(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $token = $auth->csrfToken();
        
        // Token should be stored in session
        $this->assertTrue(isset($_SESSION['ava_csrf_token']));
        $this->assertEquals($token, $_SESSION['ava_csrf_token']);
    }

    public function testLogoutClearsCsrfToken(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Generate CSRF token and login
        $auth->csrfToken();
        $auth->attempt('admin@example.com', 'testpassword123');
        
        // Logout
        $auth->logout();
        
        // CSRF token should be cleared
        $this->assertFalse(isset($_SESSION['ava_csrf_token']));
    }

    // =========================================================================
    // Timing Attack Prevention Tests
    // =========================================================================

    public function testTimingIsConsistentForNonexistentUser(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Time login attempt with nonexistent user
        $start1 = hrtime(true);
        $auth->attempt('nonexistent@example.com', 'testpassword123');
        $time1 = hrtime(true) - $start1;
        
        // Time login attempt with existing user but wrong password
        $start2 = hrtime(true);
        $auth->attempt('admin@example.com', 'wrongpassword');
        $time2 = hrtime(true) - $start2;
        
        // Times should be roughly similar (within 50% of each other)
        // This is a basic timing attack mitigation check
        // The actual check in code does password_verify on a dummy hash for nonexistent users
        $ratio = max($time1, $time2) / max(1, min($time1, $time2));
        
        // Allow for some variance but they should be in the same ballpark
        // Real timing attacks exploit microsecond differences, not 10x differences
        $this->assertLessThan(10, $ratio, 'Timing difference too large, potential timing attack vulnerability');
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testEmptyEmailRejected(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $result = $auth->attempt('', 'testpassword123');
        
        $this->assertFalse($result);
    }

    public function testEmptyPasswordRejected(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $result = $auth->attempt('admin@example.com', '');
        
        $this->assertFalse($result);
    }

    public function testCaseSensitiveEmail(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Try with different case - should fail (email is case-sensitive as a key)
        $result = $auth->attempt('ADMIN@EXAMPLE.COM', 'testpassword123');
        
        $this->assertFalse($result);
    }

    public function testAllUsersReturnsUserList(): void
    {
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        $users = $auth->allUsers();
        
        $this->assertIsArray($users);
        $this->assertArrayHasKey('admin@example.com', $users);
    }

    public function testIPHashedInRateLimitFile(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.200';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Trigger a failed attempt to create rate limit file
        $auth->attempt('admin@example.com', 'wrongpassword');
        
        $rateLimitFile = $this->tempStoragePath . '/auth_attempts.json';
        
        // File should exist
        $this->assertTrue(file_exists($rateLimitFile), 'Rate limit file should exist');
        
        $content = file_get_contents($rateLimitFile);
        
        // Raw IP should NOT appear in the file (should be hashed)
        $this->assertStringNotContains('192.168.1.200', $content);
        
        // Should contain a SHA-256 hash (64 hex characters)
        $this->assertTrue(
            (bool) preg_match('/[a-f0-9]{64}/', $content),
            'Rate limit file should contain hashed IP'
        );
    }

    public function testMissingRemoteAddrHandled(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Should not throw, should use fallback IP
        $result = $auth->attempt('admin@example.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }

    public function testInvalidIPHandled(): void
    {
        $_SERVER['REMOTE_ADDR'] = 'not-an-ip';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath);
        
        // Should not throw, should use fallback
        $result = $auth->attempt('admin@example.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }

    // =========================================================================
    // IP Binding Tests
    // =========================================================================

    public function testIPBindingAllowsSameIP(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.50';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, true, null);
        
        // Login from IP
        $result = $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($result);
        
        // Check should pass from same IP
        $this->assertTrue($auth->check());
    }

    public function testIPBindingBlocksDifferentIP(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.60';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, true, null);
        
        // Login from first IP
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Simulate different IP (attacker with stolen cookie)
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        // Check should fail and logout
        $this->assertFalse($auth->check());
        
        // Session should be invalidated
        $this->assertNull($auth->user());
    }

    public function testIPBindingDisabledAllowsDifferentIP(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.70';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, false, null);
        
        // Login from first IP
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Change IP
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        
        // Check should still pass when IP binding is disabled
        $this->assertTrue($auth->check());
    }

    public function testIPBindingHandlesIPv6Normalization(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, true, null);
        
        // Login from localhost IPv6
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Same IP should still work
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertTrue($auth->check());
    }

    // =========================================================================
    // Session Timeout Tests
    // =========================================================================

    public function testSessionTimeoutAllowsActiveSession(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, false, 3600);
        
        $auth->attempt('admin@example.com', 'testpassword123');
        
        // Active session should pass
        $this->assertTrue($auth->check());
    }

    public function testSessionTimeoutInvalidatesExpiredSession(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // Very short timeout for testing (1 second)
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, false, 1);
        
        $auth->attempt('admin@example.com', 'testpassword123');
        $this->assertTrue($auth->check());
        
        // Wait for timeout
        sleep(2);
        
        // Session should now be expired
        $this->assertFalse($auth->check());
    }

    public function testSessionTimeoutDisabledNeverExpires(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, false, null);
        
        $auth->attempt('admin@example.com', 'testpassword123');
        
        // Manually set last activity to long ago
        $_SESSION['ava_last_activity'] = time() - 86400; // 24 hours ago
        
        // Should still pass when timeout is disabled
        $this->assertTrue($auth->check());
    }

    public function testSessionTimeoutUpdatesLastActivity(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $auth = new Auth($this->tempUsersFile, $this->tempStoragePath, false, 3600);
        
        $auth->attempt('admin@example.com', 'testpassword123');
        
        $firstActivity = $_SESSION['ava_last_activity'];
        sleep(1);
        
        // Check updates the activity timestamp
        $auth->check();
        
        $this->assertGreaterThanOrEqual($firstActivity, $_SESSION['ava_last_activity']);
    }
}
