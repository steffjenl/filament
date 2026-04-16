<?php

namespace Filament\Support\Assets;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Css extends Asset
{
    protected string | Htmlable | Closure | null $html = null;

    protected ?string $relativePublicPath = null;

    /**
     * @var array<string, mixed>
     */
    protected array $extraAttributes = [];

    public function html(string | Htmlable | Closure | null $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function relativePublicPath(?string $relativePublicPath): static
    {
        $this->relativePublicPath = $relativePublicPath;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function extraAttributes(array $attributes): static
    {
        $this->extraAttributes = $attributes;

        return $this;
    }

    /**
     * Merge default attributes under any existing per-asset values.
     * Keys already set via `extraAttributes()` are not overwritten.
     *
     * @param  array<string, mixed>  $defaults
     */
    public function mergeExtraAttributes(array $defaults): static
    {
        $this->extraAttributes = array_merge($defaults, $this->extraAttributes);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraAttributes(): array
    {
        return $this->extraAttributes;
    }

    public function getExtraAttributesHtml(): string
    {
        $parts = [];

        foreach ($this->getExtraAttributes() as $key => $value) {
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

    public function getHref(): string
    {
        if ($this->isRemote()) {
            return $this->getPath();
        }

        return asset($this->getRelativePublicPath()) . '?v=' . $this->getVersion();
    }

    public function getHtml(): Htmlable
    {
        $html = value($this->html);

        if (str($html)->contains('<link')) {
            return $html instanceof Htmlable ? $html : new HtmlString($html);
        }

        $html ??= $this->getHref();

        $extraAttributesHtml = $this->getExtraAttributesHtml();
        $extraPart = filled($extraAttributesHtml) ? "\n            {$extraAttributesHtml}" : '';

        return new HtmlString("<link
            href=\"{$html}\"
            rel=\"stylesheet\"
            data-navigate-track{$extraPart}
        />");
    }

    public function getRelativePublicPath(): string
    {
        if (filled($this->relativePublicPath)) {
            return $this->relativePublicPath;
        }

        $path = config('filament.assets_path', '');

        return ltrim("{$path}/css/{$this->getPackage()}/{$this->getId()}.css", '/');
    }

    public function getPublicPath(): string
    {
        return public_path($this->getRelativePublicPath());
    }
}
