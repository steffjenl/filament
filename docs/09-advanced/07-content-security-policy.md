---
title: Content Security Policy
---

import Aside from "@components/Aside.astro"

## Introduction

A [Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP) (CSP) is an HTTP response header that tells the browser which sources of scripts, styles, and other resources are allowed to run on your page. A strict `script-src` directive helps prevent cross-site scripting (XSS) attacks, but it also blocks browsers from running any inline `<script>` tag unless that tag carries a matching cryptographic nonce.

Filament renders a number of small inline `<script>` tags itself — for example, to bootstrap dark mode before the page paints, to persist collapsed sidebar groups, and to wire up Livewire Echo listeners for notifications. You can have Filament add a nonce attribute to all of these, so they keep working under a nonce-based CSP.

<Aside variant="info">
    This feature only covers `<script>` tags. Filament's inline `<style>` tags are not nonced at this time.
</Aside>

## Configuring a nonce

Use `Filament\Support\Facades\FilamentView::useCspNonce()` to tell Filament which nonce to use for the current request. This is usually called from middleware, so that a fresh nonce is generated per request and shared with the header you send back to the browser:

```php
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Str;

class AddContentSecurityPolicyHeader
{
    public function handle($request, \Closure $next)
    {
        $nonce = Str::random(32);

        FilamentView::useCspNonce($nonce);

        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            "script-src 'self' 'nonce-{$nonce}'",
        );

        return $response;
    }
}
```

You can also pass a closure, which is only resolved the first time the nonce is needed during the request:

```php
FilamentView::useCspNonce(fn (): string => once(fn () => Str::random(32)));
```

Anywhere Filament renders one of its own `<script>` tags, it reads the current nonce back through the `Filament\Support\csp_nonce()` helper. You can use the same helper in your own Blade views to nonce your own inline scripts:

```blade
<script nonce="{{ \Filament\Support\csp_nonce() }}">
    // ...
</script>
```

If no nonce has been configured, `csp_nonce()` returns `null` and Filament renders an empty `nonce=""` attribute, which browsers ignore.

## `'unsafe-eval'` and Alpine.js

Nonce-ing `<script>` tags is only part of a strict CSP. Livewire ships its own bundled copy of Alpine.js, which by default evaluates directives like `x-data` and `x-on` using `new Function()` — this requires `'unsafe-eval'` in your `script-src` directive, and Filament cannot change that from within this package, since it does not bundle or control Livewire's Alpine instance.

If you want to drop `'unsafe-eval'` entirely, enable Livewire's own CSP-safe mode in `config/livewire.php`:

```php
'csp_safe' => true,
```

<Aside variant="danger">
    Livewire's CSP-safe mode changes how Alpine expressions are parsed application-wide, and does not support some advanced JavaScript syntax (arrow functions, template literals, spread operators, dynamic property access) inside inline directives. Read [Livewire's CSP documentation](https://livewire.laravel.com/docs/csp) before enabling it, and test your panels thoroughly afterwards.
</Aside>

With Livewire's CSP-safe mode enabled, a `script-src` directive using a nonce plus `'strict-dynamic'` is a good baseline, since it lets scripts that Livewire and Alpine insert dynamically inherit trust from the initially nonced script, without needing to allowlist every possible source:

```
Content-Security-Policy: script-src 'self' 'nonce-{random}' 'strict-dynamic';
```
