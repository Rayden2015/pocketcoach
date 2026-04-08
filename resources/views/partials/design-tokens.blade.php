{{-- Default learner shell theme; tenant branding overrides when primary/accent are set. --}}
@php
    $brand = '#1a3a5c';
    $accent = '#0d9488';
    if (isset($tenant) && is_object($tenant) && is_array($tenant->branding ?? null)) {
        $brand = $tenant->branding['primary'] ?? $brand;
        $accent = $tenant->branding['accent'] ?? $accent;
    }
@endphp
<style>
    :root {
        --pc-brand: {{ $brand }};
        --pc-brand-rgb: 26, 58, 92;
        --pc-accent: {{ $accent }};
        --pc-accent-rgb: 13, 148, 136;
        --pc-surface: #ffffff;
        --pc-text-muted: #64748b;
        --pc-radius: 1rem;
        --pc-shadow: 0 1px 3px rgba(26, 58, 92, 0.06), 0 8px 24px rgba(26, 58, 92, 0.08);
        --pc-shadow-lg: 0 4px 6px rgba(26, 58, 92, 0.05), 0 20px 50px rgba(26, 58, 92, 0.12);
    }
</style>
