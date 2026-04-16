---
title: Content Security Policy
---

## Introduction

Filament supports Content Security Policy (CSP) nonce-based asset protection. When enabled, Filament will automatically inject a `nonce` attribute onto every `<script>` and `<style>` tag it renders, allowing you to run strict CSP headers without `unsafe-inline`.

## Enabling CSP support

Publish the Filament configuration file if you haven't already:

```bash
php artisan vendor:publish --tag=filament-config
```

Then enable CSP support in `config/filament.php`:

```php
'csp' => [
    'enabled' => true,
    'nonce' => null,
    'nonce_resolver' => null,
    'script_attributes' => [],
    'style_attributes' => [],
],
```

## Setting a nonce

### Static nonce (development only)

You can set a static nonce string directly. This is only appropriate for development or testing because a CSP nonce **must** change on every HTTP request to be effective.

```php
'csp' => [
    'enabled' => true,
    'nonce' => 'my-static-nonce',
],
```

### Per-request nonce using a resolver

For production, use `nonce_resolver` — a callable, Closure, or container-binding string that returns a fresh nonce for each request:

```php
'csp' => [
    'enabled' => true,
    'nonce_resolver' => fn () => csp_nonce(), // e.g. spatie/laravel-csp
],
```

You may also reference a service that is bound in the container:

```php
'csp' => [
    'enabled' => true,
    'nonce_resolver' => App\Security\NonceProvider::class,
],
```

The container binding must be callable (implementing `__invoke`) or return a string when resolved.

## Using the nonce in your own code

The helper function `\Filament\Support\filament_csp_nonce()` returns the current Filament nonce, or `null` when CSP is disabled or no nonce is configured:

```php
$nonce = \Filament\Support\filament_csp_nonce();
```

You can also use the facade:

```php
use Filament\Support\Facades\FilamentCsp;

$nonce = FilamentCsp::getNonce();
```

## Custom script and style attributes

You may add additional HTML attributes to every Filament `<script>` or `<style>` tag via `script_attributes` and `style_attributes`:

```php
'csp' => [
    'enabled' => true,
    'nonce_resolver' => fn () => csp_nonce(),
    'script_attributes' => [
        'crossorigin' => 'anonymous',
    ],
    'style_attributes' => [],
],
```

The nonce is automatically merged into these attribute arrays, so you do not need to add it manually.

## Passing attributes per directive call

You can also pass attributes directly to the `@filamentScripts` and `@filamentStyles` Blade directives. These attributes are merged with — and take precedence over — the globally configured ones:

```blade
@filamentScripts(withCore: true, attributes: ['nonce' => csp_nonce()])
@filamentStyles(attributes: ['nonce' => csp_nonce()])
```

## Integration with Livewire CSP-safe mode

Livewire has its own CSP mechanism. To use both together, configure both systems to share the same nonce by pointing them to the same resolver:

```php
// config/livewire.php
'csp_safe' => true,
'nonce' => fn () => csp_nonce(),

// config/filament.php
'csp' => [
    'enabled' => true,
    'nonce_resolver' => fn () => csp_nonce(),
],
```

<Aside variant="info">
Filament's inline `<script>` blocks inside Livewire components (wrapped in `@script` / `@endscript`) are handled by Livewire's nonce mechanism, not Filament's. You must enable `csp_safe: true` in `config/livewire.php` to cover those blocks.
</Aside>

## Integration with spatie/laravel-csp

[spatie/laravel-csp](https://github.com/spatie/laravel-csp) is a popular package for generating and applying CSP headers in Laravel. To integrate it with Filament:

1. Install and configure **spatie/laravel-csp**.
2. In your CSP policy class, enable nonces:

    ```php
    public function configure(): void
    {
        $this
            ->addDirective(Directive::SCRIPT, Keyword::NONCE)
            ->addDirective(Directive::STYLE, Keyword::NONCE);
    }
    ```

3. Point both Filament and Livewire to the same nonce resolver:

    ```php
    // config/filament.php
    'csp' => [
        'enabled' => true,
        'nonce_resolver' => fn () => csp_nonce(),
    ],

    // config/livewire.php
    'csp_safe' => true,
    'nonce' => fn () => csp_nonce(),
    ```

## Plugin and custom asset support

### Plugin-registered file assets

Assets registered via `FilamentAsset::register()` automatically inherit the configured nonce when `csp.enabled` is `true`. Plugin authors do not need to do anything extra for file-based assets.

You may also set a per-asset `nonce` directly if needed:

```php
FilamentAsset::register([
    Js::make('my-script', __DIR__ . '/../dist/my-script.js')
        ->extraAttributes(['nonce' => filament_csp_nonce()]),
]);
```

<Aside variant="tip">
Attributes set directly on an asset via `extraAttributes()` take precedence over the global defaults. Use this when you need per-asset control.
</Aside>

### Custom inline scripts in render hooks

If you emit custom inline `<script>` or `<style>` tags inside render hooks, add the nonce manually:

```php
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

FilamentView::registerRenderHook(
    PanelsRenderHook::HEAD_END,
    fn (): string => '<script nonce="' . e(filament_csp_nonce()) . '">/* ... */</script>',
);
```

Always use `e()` or `htmlspecialchars()` to escape the nonce value in raw HTML strings.

## Known limitations

### Livewire `@script` blocks

The inline `<script>` blocks emitted via Livewire's `@script` / `@endscript` directive — used by components such as the unsaved-changes alert, error notifications, and the broadcasting setup — are controlled entirely by Livewire. Filament cannot inject its own nonce into them. Use `csp_safe: true` in `config/livewire.php` to cover these.

### Third-party plugins

Third-party plugins that emit raw `<script>` or `<style>` tags in their own Blade views are not automatically covered. Contact the plugin author to request CSP support, or override the view and add the nonce manually.

### `Css` / `<link>` tags

`<link rel="stylesheet">` tags do not require a nonce under a standard CSP `style-src` directive — the nonce requirement applies to inline `<style>` blocks. Filament's `<link>` tags are therefore CSP-compatible without a nonce. You can still attach additional attributes (e.g. `crossorigin`, `integrity`) via `Css::extraAttributes()` or `style_attributes` in config.

### No automatic CSP header generation

Filament does not generate or modify HTTP response headers. Configuring the `Content-Security-Policy` header is the responsibility of your application or a middleware package such as **spatie/laravel-csp**.
