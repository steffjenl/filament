<?php

namespace Filament\Support\Assets;

use Closure;

class CspManager
{
    protected ?string $resolvedNonce = null;

    protected bool $nonceResolved = false;

    public function isEnabled(): bool
    {
        return (bool) config('filament.csp.enabled', false);
    }

    public function getNonce(): ?string
    {
        if (! $this->nonceResolved) {
            $this->resolvedNonce = $this->resolveNonce();
            $this->nonceResolved = true;
        }

        return $this->resolvedNonce;
    }

    protected function resolveNonce(): ?string
    {
        $static = config('filament.csp.nonce');

        if (filled($static)) {
            return (string) $static;
        }

        $resolver = config('filament.csp.nonce_resolver');

        if ($resolver === null) {
            return null;
        }

        if ($resolver instanceof Closure || is_callable($resolver)) {
            return (string) $resolver() ?: null;
        }

        if (is_string($resolver) && app()->bound($resolver)) {
            $resolved = app($resolver);

            if (is_callable($resolved)) {
                return (string) $resolved() ?: null;
            }

            if (is_string($resolved)) {
                return filled($resolved) ? $resolved : null;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getScriptAttributes(): array
    {
        return $this->buildAttributes(config('filament.csp.script_attributes', []));
    }

    /**
     * @return array<string, mixed>
     */
    public function getStyleAttributes(): array
    {
        return $this->buildAttributes(config('filament.csp.style_attributes', []));
    }

    /**
     * @param  array<string, mixed>  $configAttributes
     * @return array<string, mixed>
     */
    protected function buildAttributes(array $configAttributes): array
    {
        $attributes = $configAttributes;

        $nonce = $this->getNonce();

        if (filled($nonce) && ! array_key_exists('nonce', $attributes)) {
            $attributes['nonce'] = $nonce;
        }

        return $attributes;
    }

    /**
     * Render an array of HTML attributes into a safe string.
     *
     * - String values are HTML-escaped.
     * - `true` / boolean attributes render as bare attribute names (e.g. `async`).
     * - `false` / `null` values are omitted entirely.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function renderAttributeString(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if ($value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $parts[] = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                continue;
            }

            $escapedKey = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $parts[] = "{$escapedKey}=\"{$escapedValue}\"";
        }

        return implode(' ', $parts);
    }

    /**
     * Invalidate the cached nonce, forcing re-resolution on next call.
     * Primarily useful in tests.
     */
    public function flushNonce(): void
    {
        $this->resolvedNonce = null;
        $this->nonceResolved = false;
    }
}
