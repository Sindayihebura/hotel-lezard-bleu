<?php
declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Security\CsrfGuard;

/**
 * Tests sécurité — Protection CSRF
 */
class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Simuler une session PHP
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['csrf_token']);
    }

    public function testTokenIsGenerated(): void
    {
        $token = CsrfGuard::token();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes → 64 hex chars
    }

    public function testTokenIsPersisted(): void
    {
        $t1 = CsrfGuard::token();
        $t2 = CsrfGuard::token();
        $this->assertSame($t1, $t2); // Même token dans la session
    }

    public function testValidTokenVerifies(): void
    {
        $token = CsrfGuard::token();
        $this->assertTrue(CsrfGuard::verify($token));
    }

    public function testWrongTokenFails(): void
    {
        CsrfGuard::token();
        $this->assertFalse(CsrfGuard::verify('invalid_token_abc'));
    }

    public function testEmptyTokenFails(): void
    {
        CsrfGuard::token();
        $this->assertFalse(CsrfGuard::verify(''));
    }

    public function testTokenRotationChangesToken(): void
    {
        $t1 = CsrfGuard::token();
        $t2 = CsrfGuard::rotate();
        $this->assertNotSame($t1, $t2);
    }

    public function testOldTokenInvalidAfterRotation(): void
    {
        $old = CsrfGuard::token();
        CsrfGuard::rotate();
        $this->assertFalse(CsrfGuard::verify($old));
    }

    public function testFieldContainsHiddenInput(): void
    {
        $field = CsrfGuard::field();
        $this->assertStringContainsString('<input', $field);
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
    }

    public function testFieldDoesNotContainRawHtml(): void
    {
        // Le token ne doit pas permettre d'injecter du HTML
        $_SESSION['csrf_token'] = '<script>alert(1)</script>';
        $field = CsrfGuard::field();
        $this->assertStringNotContainsString('<script>', $field);
    }

    public function testTimingSafeComparison(): void
    {
        // hash_equals doit être utilisé — test que des tokens différents échouent
        $token = CsrfGuard::token();
        $modified = substr($token, 0, -1) . ($token[-1] === 'a' ? 'b' : 'a');
        $this->assertFalse(CsrfGuard::verify($modified));
    }
}
