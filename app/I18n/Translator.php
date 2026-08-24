<?php

declare(strict_types=1);

namespace App\I18n;

/**
 * Moteur de traduction — Hôtel Le Lézard Bleu & Spa
 *
 * Résolution de la langue :
 * 1. Paramètre GET ?lang=xx
 * 2. Session ($_SESSION['locale'])
 * 3. Cookie (hotel_lang)
 * 4. Header Accept-Language du navigateur
 * 5. Fallback : français (fr)
 */
class Translator
{
    private static ?Translator $instance = null;

    private string $locale;
    private string $fallback = 'fr';
    private array  $supported = ['fr', 'en', 'rn'];

    /** Translations chargées : ['fr' => [...], 'en' => [...]] */
    private array $translations = [];

    private string $langPath;

    private function __construct()
    {
        $this->langPath = base_path('resources/lang');
        $this->locale   = $this->resolveLocale();
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // ── Résolution ────────────────────────────────────────────────────────

    private function resolveLocale(): string
    {
        // 1. GET ?lang=
        if (isset($_GET['lang'])) {
            $lang = $this->sanitizeLang($_GET['lang']);
            if ($lang !== null) {
                $this->setLocale($lang);
                return $lang;
            }
        }

        // 2. Session
        if (isset($_SESSION['locale'])) {
            $lang = $this->sanitizeLang($_SESSION['locale']);
            if ($lang !== null) {
                return $lang;
            }
        }

        // 3. Cookie
        if (isset($_COOKIE['hotel_lang'])) {
            $lang = $this->sanitizeLang($_COOKIE['hotel_lang']);
            if ($lang !== null) {
                return $lang;
            }
        }

        // 4. Accept-Language navigateur
        $lang = $this->detectFromBrowser();
        if ($lang !== null) {
            return $lang;
        }

        // 5. Fallback
        return $this->fallback;
    }

    private function detectFromBrowser(): ?string
    {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($acceptLang === '') {
            return null;
        }
        // Parser "fr-FR,fr;q=0.9,en;q=0.8"
        $langs = explode(',', $acceptLang);
        foreach ($langs as $entry) {
            $parts = explode(';', $entry);
            $code  = strtolower(trim(substr($parts[0], 0, 2)));
            if (in_array($code, $this->supported, true)) {
                return $code;
            }
        }
        return null;
    }

    private function sanitizeLang(string $lang): ?string
    {
        $lang = strtolower(trim(preg_replace('/[^a-zA-Z-]/', '', $lang)));
        // Extraire les deux premiers caractères (ex: "fr-BE" → "fr")
        $short = substr($lang, 0, 2);
        return in_array($short, $this->supported, true) ? $short : null;
    }

    // ── Changement de locale ─────────────────────────────────────────────

    public function setLocale(string $locale): void
    {
        $lang = $this->sanitizeLang($locale);
        if ($lang === null) {
            return;
        }
        $this->locale = $lang;
        $_SESSION['locale'] = $lang;

        // Persister dans un cookie (30 jours) — SameSite=Lax
        if (!headers_sent()) {
            setcookie('hotel_lang', $lang, [
                'expires'  => time() + 30 * 86400,
                'path'     => '/',
                'secure'   => filter_var(env('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOLEAN),
                'httponly' => false, // Accessible en JS pour persistence UI
                'samesite' => 'Lax',
            ]);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    // ── Chargement des traductions ────────────────────────────────────────

    private function load(string $locale): void
    {
        if (isset($this->translations[$locale])) {
            return; // Déjà chargé
        }
        $file = $this->langPath . '/' . $locale . '.php';
        if (file_exists($file)) {
            $this->translations[$locale] = require $file;
        } else {
            $this->translations[$locale] = [];
        }
    }

    // ── Traduction ────────────────────────────────────────────────────────

    /**
     * Traduire une clé.
     *
     * @param string $key       Clé pointée ("booking.checkin")
     * @param array  $replace   Variables à substituer (['hours' => 48])
     * @param string|null $locale Forcer une locale (null = locale active)
     * @return string
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        $this->load($locale);
        $value = $this->translations[$locale][$key] ?? null;

        // Fallback vers français
        if ($value === null && $locale !== $this->fallback) {
            $this->load($this->fallback);
            $value = $this->translations[$this->fallback][$key] ?? null;
        }

        // Fallback ultime : retourner la clé
        if ($value === null) {
            return $key;
        }

        // Substitution des variables — échappement HTML automatique
        foreach ($replace as $placeholder => $val) {
            $value = str_replace(':' . $placeholder, htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $value);
        }

        return $value;
    }

    /**
     * Alias court.
     */
    public function t(string $key, array $replace = []): string
    {
        return $this->trans($key, $replace);
    }

    /**
     * Vérifier si une clé existe.
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;
        $this->load($locale);
        return isset($this->translations[$locale][$key]);
    }
}

// ── Helper global ─────────────────────────────────────────────────────────
if (!function_exists('__')) {
    /**
     * Traduire une clé avec le Translator singleton.
     * Usage : __('booking.checkin') ou __('error.rate_limit', ['minutes' => 15])
     */
    function __(string $key, array $replace = []): string
    {
        return \App\I18n\Translator::getInstance()->trans($key, $replace);
    }
}
