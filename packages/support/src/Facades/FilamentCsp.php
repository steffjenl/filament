<?php

namespace Filament\Support\Facades;

use Filament\Support\Assets\CspManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static string|null getNonce()
 * @method static array<string, mixed> getScriptAttributes()
 * @method static array<string, mixed> getStyleAttributes()
 * @method static string renderAttributeString(array $attributes)
 * @method static void flushNonce()
 *
 * @see CspManager
 */
class FilamentCsp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CspManager::class;
    }
}
