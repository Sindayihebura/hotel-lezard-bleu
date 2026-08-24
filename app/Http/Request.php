<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Encapsule les données de la requête HTTP entrante.
 * Fournit un accès sécurisé aux inputs.
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private ?array $jsonBody = null;

    public function __construct(
        array $get    = [],
        array $post   = [],
        array $server = [],
        array $files  = []
    ) {
        $this->get    = $get;
        $this->post   = $post;
        $this->server = $server;
        $this->files  = $files;
    }

    public static function fromGlobals(): static
    {
        return new static($_GET, $_POST, $_SERVER, $_FILES);
    }

    // ── Méthode HTTP ──────────────────────────────────────────────────────

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool  { return $this->method() === 'GET'; }
    public function isPost(): bool { return $this->method() === 'POST'; }

    // ── Inputs ────────────────────────────────────────────────────────────

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->isJsonRequest()) {
            $body = $this->jsonBody();
            return $body[$key] ?? $default;
        }
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        if ($this->isJsonRequest()) {
            return $this->jsonBody();
        }
        return $this->post;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key) ?? $this->query($key, $default);
    }

    // ── Helpers de typage ─────────────────────────────────────────────────

    public function str(string $key, string $default = ''): string
    {
        $val = $this->input($key, $this->query($key, $default));
        return trim((string) $val);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->input($key, $this->query($key, $default));
    }

    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->input($key, $this->query($key, $default));
        return filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    // ── JSON ──────────────────────────────────────────────────────────────

    public function isJsonRequest(): bool
    {
        $ct = $this->server['CONTENT_TYPE'] ?? '';
        return str_contains(strtolower($ct), 'application/json');
    }

    public function jsonBody(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                $this->jsonBody = is_array($decoded) ? $decoded : [];
            } else {
                $this->jsonBody = [];
            }
        }
        return $this->jsonBody;
    }

    // ── Headers ───────────────────────────────────────────────────────────

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? '';
    }

    public function bearerToken(): string
    {
        $auth = $this->header('Authorization');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return '';
    }

    // ── IP ────────────────────────────────────────────────────────────────

    public function ip(): string
    {
        return filter_var(
            $this->server['REMOTE_ADDR'] ?? '0.0.0.0',
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6
        ) ?: '0.0.0.0';
    }

    // ── Path ─────────────────────────────────────────────────────────────

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?: '/';
    }
}
