@isset($tenant)
@push('head')
@php($primary = $tenant->branding['primary'] ?? '#0d9488')
@php($accent = $tenant->branding['accent'] ?? '#0f766e')
<style>
    :root {
        --pc-brand: {{ $primary }};
        --pc-brand-accent: {{ $accent }};
    }
    .pc-brand-ring:focus { --tw-ring-color: var(--pc-brand); }
    .pc-brand-brd:focus { border-color: var(--pc-brand); }
    .pc-btn { background-color: var(--pc-brand); }
    .pc-btn:hover { filter: brightness(0.92); }
    .pc-text { color: var(--pc-brand); }
</style>
@endpush
@endisset
