<?php

use Filament\Support\Assets\CspManager;
use Filament\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->manager = new CspManager;
});

describe('`isEnabled()`', function (): void {
    it('returns `false` by default', function (): void {
        expect($this->manager->isEnabled())->toBeFalse();
    });

    it('returns `true` when `csp.enabled` is `true`', function (): void {
        config(['filament.csp.enabled' => true]);

        expect($this->manager->isEnabled())->toBeTrue();
    });
});

describe('`getNonce()`', function (): void {
    it('returns `null` when no nonce is configured', function (): void {
        expect($this->manager->getNonce())->toBeNull();
    });

    it('returns a nonce from `csp.nonce` static string', function (): void {
        config(['filament.csp.nonce' => 'abc123']);

        expect($this->manager->getNonce())->toBe('abc123');
    });

    it('resolves nonce from a `csp.nonce_resolver` Closure', function (): void {
        config(['filament.csp.nonce_resolver' => fn (): string => 'resolver-nonce']);

        expect($this->manager->getNonce())->toBe('resolver-nonce');
    });

    it('resolves nonce from a callable `csp.nonce_resolver`', function (): void {
        config(['filament.csp.nonce_resolver' => function (): string {
            return 'callable-nonce';
        }]);

        expect($this->manager->getNonce())->toBe('callable-nonce');
    });

    it('prefers `csp.nonce` over `csp.nonce_resolver`', function (): void {
        config([
            'filament.csp.nonce' => 'static-nonce',
            'filament.csp.nonce_resolver' => fn (): string => 'resolver-nonce',
        ]);

        expect($this->manager->getNonce())->toBe('static-nonce');
    });

    it('caches the resolved nonce across multiple calls', function (): void {
        $callCount = 0;

        config(['filament.csp.nonce_resolver' => function () use (&$callCount): string {
            $callCount++;

            return 'nonce-' . $callCount;
        }]);

        $first = $this->manager->getNonce();
        $second = $this->manager->getNonce();

        expect($first)->toBe('nonce-1');
        expect($second)->toBe('nonce-1');
        expect($callCount)->toBe(1);
    });

    it('returns `null` when `csp.nonce_resolver` returns an empty string', function (): void {
        config(['filament.csp.nonce_resolver' => fn (): string => '']);

        expect($this->manager->getNonce())->toBeNull();
    });

    it('flushes the cached nonce via `flushNonce()`', function (): void {
        config(['filament.csp.nonce' => 'first']);
        $this->manager->getNonce();

        $this->manager->flushNonce();
        config(['filament.csp.nonce' => 'second']);

        expect($this->manager->getNonce())->toBe('second');
    });
});

describe('`getScriptAttributes()`', function (): void {
    it('returns empty array when no nonce or script attributes are configured', function (): void {
        expect($this->manager->getScriptAttributes())->toBe([]);
    });

    it('includes the nonce in script attributes when configured', function (): void {
        config(['filament.csp.nonce' => 'abc123']);

        expect($this->manager->getScriptAttributes())->toBe(['nonce' => 'abc123']);
    });

    it('merges `csp.script_attributes` with the nonce', function (): void {
        config([
            'filament.csp.nonce' => 'abc123',
            'filament.csp.script_attributes' => ['crossorigin' => 'anonymous'],
        ]);

        $attributes = $this->manager->getScriptAttributes();

        expect($attributes)->toBe([
            'crossorigin' => 'anonymous',
            'nonce' => 'abc123',
        ]);
    });

    it('does not override a nonce already present in `csp.script_attributes`', function (): void {
        config([
            'filament.csp.nonce' => 'from-config',
            'filament.csp.script_attributes' => ['nonce' => 'from-attributes'],
        ]);

        expect($this->manager->getScriptAttributes()['nonce'])->toBe('from-attributes');
    });
});

describe('`getStyleAttributes()`', function (): void {
    it('returns empty array when no nonce or style attributes are configured', function (): void {
        expect($this->manager->getStyleAttributes())->toBe([]);
    });

    it('includes the nonce in style attributes when configured', function (): void {
        config(['filament.csp.nonce' => 'abc123']);

        expect($this->manager->getStyleAttributes())->toBe(['nonce' => 'abc123']);
    });

    it('merges `csp.style_attributes` with the nonce', function (): void {
        config([
            'filament.csp.nonce' => 'abc123',
            'filament.csp.style_attributes' => ['data-tracked' => 'true'],
        ]);

        $attributes = $this->manager->getStyleAttributes();

        expect($attributes)->toBe([
            'data-tracked' => 'true',
            'nonce' => 'abc123',
        ]);
    });
});

describe('`renderAttributeString()`', function (): void {
    it('returns an empty string for an empty array', function (): void {
        expect($this->manager->renderAttributeString([]))->toBe('');
    });

    it('renders a simple key/value attribute', function (): void {
        expect($this->manager->renderAttributeString(['nonce' => 'abc123']))->toBe('nonce="abc123"');
    });

    it('renders multiple attributes separated by spaces', function (): void {
        $html = $this->manager->renderAttributeString([
            'nonce' => 'abc123',
            'crossorigin' => 'anonymous',
        ]);

        expect($html)->toBe('nonce="abc123" crossorigin="anonymous"');
    });

    it('renders a boolean `true` value as a bare attribute name', function (): void {
        expect($this->manager->renderAttributeString(['async' => true]))->toBe('async');
    });

    it('omits attributes with `false` values', function (): void {
        $html = $this->manager->renderAttributeString([
            'nonce' => 'abc123',
            'defer' => false,
        ]);

        expect($html)->toBe('nonce="abc123"');
        expect($html)->not->toContain('defer');
    });

    it('omits attributes with `null` values', function (): void {
        $html = $this->manager->renderAttributeString([
            'nonce' => 'abc123',
            'data-foo' => null,
        ]);

        expect($html)->toBe('nonce="abc123"');
        expect($html)->not->toContain('data-foo');
    });

    it('HTML-escapes attribute values', function (): void {
        $html = $this->manager->renderAttributeString(['data-value' => '"<script>alert(1)</script>"']);

        expect($html)->not->toContain('<script>');
        expect($html)->toContain('&lt;script&gt;');
        expect($html)->toContain('&quot;');
    });

    it('HTML-escapes attribute keys', function (): void {
        $html = $this->manager->renderAttributeString(['"bad-key"' => 'value']);

        expect($html)->toContain('&quot;bad-key&quot;');
    });
});
