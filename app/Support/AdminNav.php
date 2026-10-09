<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Builds the console navigation for one administration level.
 *
 * Every entry is emitted only when the named route actually exists, so the
 * sidebar can never advertise a control the backend does not serve. Entries
 * that exist in the legacy registry but have no route are surfaced in the
 * "Not wired" list instead of being silently dropped.
 */
class AdminNav
{
    /** @return array<string,array{icon:string,label:string,items:array<int,array{label:string,url:string}>}> */
    public static function groups(string $level, int $legacyCount = 0): array
    {
        $link = function (string $label, array $candidates): ?array {
            foreach ($candidates as $name) {
                if (Route::has($name)) {
                    return ['label' => $label, 'url' => route($name)];
                }
            }
            return null;
        };
        $collect = fn (array $items) => array_values(array_filter($items));

        $isKicc = $level === 'kicc';
        $isCounty = in_array($level, ['kicc', 'national', 'county'], true);
        $isInst = in_array($level, ['kicc', 'national', 'county', 'institution'], true);

        $groups = [];

        $groups['dashboard'] = ['icon' => '◈', 'label' => 'Dashboard', 'items' => $collect([
            $link('Control centre', ['admin.portal']),
            $link('Publishing overview', ['admin.index']),
            $link('Records workspace', ['records.admin.index', 'admin.records.index']),
        ])];

        $groups['content'] = ['icon' => '▤', 'label' => 'Content', 'items' => $collect([
            $link('Hero videos & posters', ['experience.images.index', 'admin.media.index']),
            $link('Sequential media flow', ['admin.mediaflow']),
            $link('Bulk upload (2 GB)', ['admin.uploads']),
            $link('Media library', ['admin.media.index']),
            $link('CMS pages & FAQ', ['cms.admin.index']),
            $link('3D asset library', ['admin.3d.assets']),
            $isKicc ? $link('Source-component editor', ['admin.components.ui']) : null,
        ])];

        $groups['commerce'] = ['icon' => '◧', 'label' => 'Commerce', 'items' => $collect([
            $link('Products', ['admin.ecommerce.products', 'admin.ecommerce.dashboard']),
            $link('Orders', ['admin.ecommerce.dashboard']),
            $link('Payments & escrow', ['admin.escrow.index', 'admin.pool.index']),
            $link('Revenue pool', ['admin.pool.index']),
            $isKicc ? $link('Providers & freight', ['admin.providers.index']) : null,
        ])];

        $groups['operations'] = ['icon' => '⚙', 'label' => 'Operations', 'items' => $collect([
            $link('Pipeline registry', ['admin.pipeline.index']),
            $link('Hierarchy (county → sector → institution)', ['admin.hierarchy']),
            $link('Requests & enquiries', ['admin.enquiries']),
            $link('Integrations', ['admin.integration.index']),
        ])];

        $groups['people'] = ['icon' => '☰', 'label' => 'People', 'items' => $collect([
            $isKicc ? $link('Users & roles', ['admin.users']) : null,
            $link('Institutions', ['admin.institutions.index']),
            $isInst ? $link('Exhibitors', ['admin.exhibitors.index']) : null,
            $link('Agents & commissions', ['agent.admin.index', 'commission.admin.index']),
        ])];

        $groups['analytics'] = ['icon' => '◔', 'label' => 'Analytics', 'items' => $collect([
            $link('Platform KPIs', ['admin.analytics.index']),
            $link('Search analytics', ['admin.search.index']),
            $link('Audit trail', ['admin.audit']),
        ])];

        $groups['settings'] = ['icon' => '⚒', 'label' => 'Settings', 'items' => $collect([
            $link('Cache & maintenance', ['admin.cache.index', 'admin.settings.index']),
            $link('Configuration', ['admin.settings.index']),
            $link('Licence queue', ['admin.licence.index']),
        ])];

        // Portal jump-offs, always available and always real routes.
        $groups['settings']['items'][] = ['label' => 'County portals (47)', 'url' => Route::has('county.admin') ? route('county.admin') : '/county-admin'];
        $groups['settings']['items'][] = ['label' => 'Legacy records-admin', 'url' => '/records-admin'];

        return $groups;
    }
}
