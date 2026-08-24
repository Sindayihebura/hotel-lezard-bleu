<?php
declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Validators\AuthValidator;

/**
 * Tests sécurité — Authentification et validation
 */
class AuthTest extends TestCase
{
    // ── Validation login ─────────────────────────────────────────────

    public function testValidLoginData(): void
    {
        $v = new AuthValidator();
        $this->assertTrue($v->validateLogin([
            'email'    => 'test@lezardbleu.bi',
            'password' => 'monmotdepasse',
        ]));
    }

    public function testInvalidEmailFails(): void
    {
        $v = new AuthValidator();
        $this->assertFalse($v->validateLogin(['email' => 'notanemail', 'password' => 'pass']));
    }

    public function testEmptyPasswordFails(): void
    {
        $v = new AuthValidator();
        $this->assertFalse($v->validateLogin(['email' => 'a@b.com', 'password' => '']));
    }

    public function testTooLongPasswordFails(): void
    {
        $v = new AuthValidator();
        $this->assertFalse($v->validateLogin([
            'email'    => 'a@b.com',
            'password' => str_repeat('a', 129), // >128 chars
        ]));
    }

    // ── Validation inscription ────────────────────────────────────────

    public function testValidRegistrationData(): void
    {
        $v = new AuthValidator(10);
        $this->assertTrue($v->validateRegister([
            'first_name'       => 'Jean-Paul',
            'last_name'        => 'Hakizimana',
            'email'            => 'jp@test.bi',
            'password'         => 'MotDePasse2026!',
            'password_confirm' => 'MotDePasse2026!',
        ]));
    }

    public function testPasswordTooShortFails(): void
    {
        $v = new AuthValidator(10);
        $this->assertFalse($v->validateRegister([
            'first_name' => 'Jean', 'last_name' => 'Test',
            'email' => 'x@x.com',
            'password' => '12345', 'password_confirm' => '12345',
        ]));
        $this->assertArrayHasKey('password', $v->errors());
    }

    public function testPasswordMismatchFails(): void
    {
        $v = new AuthValidator(10);
        $this->assertFalse($v->validateRegister([
            'first_name' => 'Jean', 'last_name' => 'Test',
            'email' => 'x@x.com',
            'password' => 'MotDePasse123', 'password_confirm' => 'Différent456',
        ]));
        $this->assertArrayHasKey('password_confirm', $v->errors());
    }

    // ── Hachage de mots de passe ─────────────────────────────────────

    public function testPasswordHashIsNotPlaintext(): void
    {
        $password = 'MonMotDePasse2026!';
        $hash     = password_hash($password, PASSWORD_DEFAULT);
        $this->assertNotSame($password, $hash);
    }

    public function testPasswordVerifyWorks(): void
    {
        $password = 'TestHotelLezardBleu#1';
        $hash     = password_hash($password, PASSWORD_DEFAULT, ['cost' => 4]);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testWrongPasswordFails(): void
    {
        $hash = password_hash('correctpass', PASSWORD_DEFAULT, ['cost' => 4]);
        $this->assertFalse(password_verify('wrongpass', $hash));
    }

    public function testPasswordHashIsDifferentEachTime(): void
    {
        $password = 'SamePassword2026!';
        $h1 = password_hash($password, PASSWORD_DEFAULT, ['cost' => 4]);
        $h2 = password_hash($password, PASSWORD_DEFAULT, ['cost' => 4]);
        $this->assertNotSame($h1, $h2); // Sel aléatoire
    }

    // ── Sécurité tokens ───────────────────────────────────────────────

    public function testEmailVerificationTokenLength(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->assertSame(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));
    }

    public function testTokenHashIsNotReversible(): void
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $this->assertNotSame($token, $hash);
        $this->assertSame(64, strlen($hash));
    }

    // ── Validation reset password ─────────────────────────────────────

    public function testValidResetTokenFormat(): void
    {
        $v     = new AuthValidator(10);
        $token = bin2hex(random_bytes(32)); // 64 hex chars
        $this->assertTrue($v->validatePasswordReset([
            'token'            => $token,
            'password'         => 'NouveauMDP2026!!',
            'password_confirm' => 'NouveauMDP2026!!',
        ]));
    }

    public function testInvalidTokenFormatFails(): void
    {
        $v = new AuthValidator(10);
        $this->assertFalse($v->validatePasswordReset([
            'token'            => 'short',
            'password'         => 'NouveauMDP2026!!',
            'password_confirm' => 'NouveauMDP2026!!',
        ]));
    }
}
