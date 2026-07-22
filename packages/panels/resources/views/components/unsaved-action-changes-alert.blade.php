@if (filament()->hasUnsavedChangesAlerts())
    @script
        <script nonce="{{ \Filament\Support\csp_nonce() }}">
            setUpUnsavedActionChangesAlert({
                resolveLivewireComponentUsing: () => @this,
                $wire,
            })
        </script>
    @endscript
@endif
