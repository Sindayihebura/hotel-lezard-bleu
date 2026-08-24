<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Réponse HTTP standardisée — Hôtel Le Lézard Bleu & Spa
 */
class Response
{
    private int    $status;
    private array  $headers;
    private string $body;

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body    = $body;
        $this->status  = $status;
        $this->headers = $headers;
    }

    // ── Factory JSON ─────────────────────────────────────────────────────

    public static function json(mixed $data, int $status = 200): static
    {
        $body = json_encode([
            'success' => true,
            'data'    => $data,
            'error'   => null,
            'meta'    => ['request_id' => request_id()],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new static($body ?: '', $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function jsonError(string $code, string $message, int $status = 400): static
    {
        $body = json_encode([
            'success' => false,
            'data'    => null,
            'error'   => ['code' => $code, 'message' => $message],
            'meta'    => ['request_id' => request_id()],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new static($body ?: '', $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function notFound(string $message = 'Ressource introuvable.'): static
    {
        return static::jsonError('NOT_FOUND', $message, 404);
    }

    public static function forbidden(string $message = 'Accès refusé.'): static
    {
        return static::jsonError('FORBIDDEN', $message, 403);
    }

    public static function unauthorized(string $message = 'Authentification requise.'): static
    {
        return static::jsonError('UNAUTHORIZED', $message, 401);
    }

    // ── Redirection ───────────────────────────────────────────────────────

    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    // ── Envoi ─────────────────────────────────────────────────────────────

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }

    // ── Getters ───────────────────────────────────────────────────────────

    public function getStatus(): int    { return $this->status; }
    public function getBody(): string   { return $this->body; }
    public function getHeaders(): array { return $this->headers; }
}
