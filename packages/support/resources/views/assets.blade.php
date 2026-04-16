@if (isset($data))
    @php
        $__scriptAttrString = isset($scriptAttributes) ? \Filament\Support\Facades\FilamentCsp::renderAttributeString($scriptAttributes) : '';
    @endphp
    <script{!! filled($__scriptAttrString) ? ' ' . $__scriptAttrString : '' !!}>
        window.filamentData = @js($data)
    </script>
@endif

@foreach ($assets as $asset)
    @if (! $asset->isLoadedOnRequest())
        {{ $asset->getHtml() }}
    @endif
@endforeach

@php
    $__styleAttrString = isset($styleAttributes) ? \Filament\Support\Facades\FilamentCsp::renderAttributeString($styleAttributes) : '';
@endphp
<style{!! filled($__styleAttrString) ? ' ' . $__styleAttrString : '' !!}>
    :root {
        @foreach ($cssVariables ?? [] as $cssVariableName => $cssVariableValue) --{{ $cssVariableName }}:{{ $cssVariableValue }}; @endforeach
    }

    @foreach ($customColors ?? [] as $customColorName => $customColorShades) .fi-color-{{ $customColorName }} { @foreach ($customColorShades as $customColorShade) --color-{{ $customColorShade }}:var(--{{ $customColorName }}-{{ $customColorShade }}); @endforeach } @endforeach
</style>
