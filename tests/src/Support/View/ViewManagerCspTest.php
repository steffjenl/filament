<?php

use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\Tests\TestCase;

use function Filament\Support\csp_nonce;

uses(TestCase::class);

describe('`useCspNonce()` and `getCspNonce()`', function (): void {
    it('returns `null` when no nonce is configured', function (): void {
        expect(app(ViewManager::class)->getCspNonce())->toBeNull();
    });

    it('returns a static string nonce', function (): void {
        FilamentView::useCspNonce('abc123');

        expect(FilamentView::getCspNonce())->toBe('abc123');
    });

    it('resolves a `Closure` nonce', function (): void {
        FilamentView::useCspNonce(fn (): string => 'closure-nonce');

        expect(FilamentView::getCspNonce())->toBe('closure-nonce');
    });

    it('can be unset by passing `null`', function (): void {
        FilamentView::useCspNonce('abc123');
        FilamentView::useCspNonce(null);

        expect(FilamentView::getCspNonce())->toBeNull();
    });
});

describe('`csp_nonce()` helper', function (): void {
    it('returns `null` when no nonce is configured', function (): void {
        expect(csp_nonce())->toBeNull();
    });

    it('delegates to `FilamentView::getCspNonce()`', function (): void {
        FilamentView::useCspNonce('abc123');

        expect(csp_nonce())->toBe('abc123');
    });
});
