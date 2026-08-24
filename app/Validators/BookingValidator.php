<?php

declare(strict_types=1);

namespace App\Validators;

/**
 * BookingValidator — Validation des données de réservation
 * Hôtel Le Lézard Bleu & Spa
 *
 * Principe : allowlist stricte, vérification de type, longueur, format, plage.
 * Ne jamais faire confiance aux montants venant du navigateur.
 */
class BookingValidator
{
    private array $errors = [];

    public function validate(array $data): bool
    {
        $this->errors = [];

        $this->validateDate('date_arrivee', $data['date_arrivee'] ?? '', true);
        $this->validateDate('date_depart',  $data['date_depart']  ?? '', true);

        // Vérifier ordre des dates
        if (empty($this->errors['date_arrivee']) && empty($this->errors['date_depart'])) {
            $arr = new \DateTimeImmutable($data['date_arrivee']);
            $dep = new \DateTimeImmutable($data['date_depart']);
            if ($dep <= $arr) {
                $this->errors['date_depart'] = 'La date de départ doit être après la date d\'arrivée.';
            }
            // Vérifier que l'arrivée n'est pas dans le passé
            $today = new \DateTimeImmutable('today');
            if ($arr < $today) {
                $this->errors['date_arrivee'] = 'La date d\'arrivée ne peut pas être dans le passé.';
            }
            // Horizon max : 365 jours
            $maxDate = $today->modify('+365 days');
            if ($arr > $maxDate) {
                $this->errors['date_arrivee'] = 'La réservation ne peut pas dépasser 365 jours à l\'avance.';
            }
        }

        $this->validateInt('room_id', $data['room_id'] ?? 0, 1, 9999);
        $this->validateInt('nb_adults', $data['nb_adults'] ?? 1, 1, 10);
        $this->validateInt('nb_children', $data['nb_children'] ?? 0, 0, 10);

        $this->validateString('guest_first_name', $data['guest_first_name'] ?? '', 2, 80);
        $this->validateString('guest_last_name',  $data['guest_last_name']  ?? '', 2, 80);
        $this->validateEmail('guest_email', $data['guest_email'] ?? '');
        $this->validatePhone('guest_phone', $data['guest_phone'] ?? '');

        // Devise
        if (!in_array($data['currency_chosen'] ?? 'BIF', ['BIF', 'USD'], true)) {
            $this->errors['currency_chosen'] = 'Devise non supportée.';
        }

        // Mode de paiement (allowlist)
        $validMethods = ['cash_bif','cash_usd','lumicash','ecocash','easypay',
                         'bank_local','card_visa','card_mastercard','paypal','manual'];
        if (!in_array($data['payment_method'] ?? '', $validMethods, true)) {
            $this->errors['payment_method'] = 'Mode de paiement invalide.';
        }

        // Téléphone Mobile Money si requis
        if (in_array($data['payment_method'] ?? '', ['lumicash', 'ecocash'], true)) {
            $this->validatePhone('mobile_number', $data['mobile_number'] ?? '');
        }

        // Offre (optionnelle)
        if (!empty($data['offer_code'])) {
            $this->validateString('offer_code', $data['offer_code'], 1, 30);
            if (!preg_match('/^[A-Z0-9_-]{1,30}$/i', $data['offer_code'])) {
                $this->errors['offer_code'] = 'Code promo invalide.';
            }
        }

        // Demandes spéciales (optionnel, longueur max)
        if (!empty($data['special_requests'])) {
            $this->validateString('special_requests', $data['special_requests'], 0, 1000);
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }

    // ── Validateurs internes ─────────────────────────────────────────

    private function validateDate(string $field, mixed $value, bool $required = false): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            if ($required) $this->errors[$field] = 'Ce champ est obligatoire.';
            return;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->errors[$field] = 'Format de date invalide (YYYY-MM-DD).';
            return;
        }
        $parts = explode('-', $value);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            $this->errors[$field] = 'Date invalide.';
        }
    }

    private function validateInt(string $field, mixed $value, int $min, int $max): void
    {
        $int = filter_var($value, FILTER_VALIDATE_INT,
                          ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($int === false) {
            $this->errors[$field] = "Valeur invalide (attendu entre {$min} et {$max}).";
        }
    }

    private function validateString(string $field, mixed $value, int $min, int $max): void
    {
        $len = mb_strlen(trim((string) $value), 'UTF-8');
        if ($len < $min) {
            $this->errors[$field] = "Trop court (min {$min} caractères).";
        } elseif ($len > $max) {
            $this->errors[$field] = "Trop long (max {$max} caractères).";
        }
    }

    private function validateEmail(string $field, mixed $value): void
    {
        if (!filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Adresse e-mail invalide.';
        }
    }

    private function validatePhone(string $field, mixed $value): void
    {
        $phone = trim((string) $value);
        if ($phone === '') {
            $this->errors[$field] = 'Numéro de téléphone obligatoire.';
            return;
        }
        // Accepter formats : +257 XX XX XX XX, +1 XXX XXX XXXX, etc.
        if (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone)) {
            $this->errors[$field] = 'Numéro de téléphone invalide.';
        }
    }
}
