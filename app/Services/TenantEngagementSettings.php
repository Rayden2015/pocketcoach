<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Per-tenant engagement: public catalog copy, featured ordering, reflections + notifications.
 * Stored in tenants.settings JSON; see docs/SPACE_CATALOG_AND_REFLECTIONS.md keys.
 */
final class TenantEngagementSettings
{
    /**
     * @return array{
     *   intro_markdown: ?string,
     *   track_catalog_views: bool,
     *   show_featured_first: bool,
     * }
     */
    public static function catalog(Tenant $tenant): array
    {
        $defaults = [
            'intro_markdown' => null,
            'track_catalog_views' => true,
            'show_featured_first' => true,
        ];
        $from = (array) data_get($tenant->settings, 'catalog', []);

        $merged = array_replace_recursive($defaults, $from);
        $merged['intro_markdown'] ??= data_get($tenant->branding, 'welcome_headline');

        return $merged;
    }

    /**
     * @return array{
     *   enabled: bool,
     *   notify_email: bool,
     *   notify_database: bool,
     * }
     */
    public static function reflections(Tenant $tenant): array
    {
        $defaults = [
            'enabled' => true,
            'notify_email' => true,
            'notify_database' => true,
        ];

        return array_replace_recursive($defaults, (array) data_get($tenant->settings, 'reflections', []));
    }

    public static function catalogTrackViews(Tenant $tenant): bool
    {
        return (bool) self::catalog($tenant)['track_catalog_views'];
    }
}
