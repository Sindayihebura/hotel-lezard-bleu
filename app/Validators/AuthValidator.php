<?php

declare(strict_types=1);

namespace App\Validators;

/**
 * AuthValidator — Validation des données d'authentification
 * Hôtel Le Lézard Bleu & Spa
 */
class AuthValidator
{
    private array $errors = [];
    private int   $passwordMinLen;

    public function __construct(int $passwordMinLen = 10)
    {
        $this->passwordMinLen = $passwordMinLen;
    }

    public function validateLogin(array $data): bool
    {
        $this->errors = [];

        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Adresse e-mail invalide.';
        }
        if (strlen($email) > 150) {
            $this->errors['email'] = 'Adresse e-mail trop longue.';
        }
        if (empty($password)) {
            $this->errors['password'] = 'Mot de passe obligatoire.';
        }
        if (strlen($password) > 128) {
            $this->errors['password'] = 'Mot de passe trop long.';
        }

        return empty($this->errors);
    }

    public function validateRegister(array $data): bool
    {
        $this->errors = [];

        $this->validateRequiredString('first_name', $data['first_name'] ?? '', 2, 80);
        $this->validateRequiredString('last_name',  $data['last_name']  ?? '', 2, 80);

        $email = trim($data['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $this->errors['email'] = 'Adresse e-mail invalide.';
        }

        $pass = $data['password'] ?? '';
        if (strlen($pass) < $this->passwordMinLen) {
            $this->errors['password'] = "Le mot de passe doit contenir au moins {$this->passwordMinLen} caractères.";
        } elseif (strlen($pass) > 128) {
            $this->errors['password'] = 'Mot de passe trop long.';
        }

        if (($data['password_confirm'] ?? '') !== $pass) {
            $this->errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        // Téléphone optionnel
        if (!empty($data['phone'])) {
            $phone = trim($data['phone']);
            if (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
                $this->errors['phone'] = 'Numéro de téléphone invalide.';
            }
        }

        // Pays optionnel
        if (!empty($data['country_code'])) {
            if (!preg_match('/^[A-Z]{2}$/', strtoupper($data['country_code']))) {
                $this->errors['country_code'] = 'Code pays invalide (ISO 3166-1 alpha-2).';
            }
        }

        return empty($this->errors);
    }

    public function validatePasswordReset(array $data): bool
    {
        $this->errors = [];

        $token = trim($data['token'] ?? '');
        if (strlen($token) !== 64 || !ctype_xdigit($token)) {
            $this->errors['token'] = 'Token invalide.';
        }

        $pass = $data['password'] ?? '';
        if (strlen($pass) < $this->passwordMinLen) {
            $this->errors['password'] = "Minimum {$this->passwordMinLen} caractères.";
        }
        if (($data['password_confirm'] ?? '') !== $pass) {
            $this->errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        return empty($this->errors);
    }

    public function errors(): array  { return $this->errors; }
    public function firstError(): string { return reset($this->errors) ?: ''; }

    private function validateRequiredString(string $field, mixed $val, int $min, int $max): void
    {
        $len = mb_strlen(trim((string) $val), 'UTF-8');
        if ($len < $min) {
            $this->errors[$field] = "Trop court (min {$min} caractères).";
        } elseif ($len > $max) {
            $this->errors[$field] = "Trop long (max {$max} caractères).";
        }
    }
}
