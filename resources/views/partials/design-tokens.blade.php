{{--
    A space is a tenant (one coach practice + learners).

    • On /{slug}/… routes we use that tenant’s branding (primary/accent).
    • On platform routes (/my-learning, /profile, /my-coaching, /, etc.) we always use the default
      palette so the app shell stays consistent when you move between spaces and account-wide pages.

    Theme follows request()->route('tenant') only (path-based tenancy), so global pages never pick up
    a stray $tenant variable from partials.
--}}
@php
    use App\Models\Tenant;

    $brand = '#1a3a5c';
    $accent = '#0d9488';

    $tenantForTheme = request()->route('tenant');
    if ($tenantForTheme instanceof Tenant && is_array($tenantForTheme->branding ?? null)) {
        $brand = $tenantForTheme->branding['primary'] ?? $brand;
        $accent = $tenantForTheme->branding['accent'] ?? $accent;
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
