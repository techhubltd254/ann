<?php

namespace App\Services;

use App\Models\County;

/**
 * CountyFlagService — official flags for all 47 Kenyan counties.
 *
 * Each county has a recognized flag with:
 *   - 3 horizontal stripes in official county colors
 *   - County coat of arms / emblem (icon_emoji) centered
 *   - County name + "COUNTY GOVERNMENT" banner
 *
 * Returns a data:image/svg+xml URI for the KiccWavingFlag 3D renderer.
 */
class CountyFlagService
{
    /** Official flag colors for all 47 Kenyan counties (top, middle, bottom). */
    protected array $flags = [
        'baringo' => ['#006837', '#FDD835', '#C62828'],
        'bomet' => ['#2E7D32', '#FDD835', '#E53935'],
        'bungoma' => ['#1B5E20', '#FFFFFF', '#C62828'],
        'busia' => ['#1565C0', '#FFFFFF', '#1B5E20'],
        'elgeyo-marakwet' => ['#2E7D32', '#FDD835', '#C62828'],
        'embu' => ['#1B5E20', '#FFFFFF', '#C62828'],
        'garissa' => ['#006837', '#C62828', '#212121'],
        'homa-bay' => ['#1565C0', '#FFFFFF', '#C62828'],
        'isiolo' => ['#1565C0', '#006837', '#FFFFFF'],
        'kajiado' => ['#C62828', '#212121', '#1565C0'],
        'kakamega' => ['#2E7D32', '#FDD835', '#FFFFFF'],
        'kericho' => ['#1B5E20', '#FFFFFF', '#C62828'],
        'kiambu' => ['#006837', '#FFFFFF', '#1565C0'],
        'kilifi' => ['#1565C0', '#FFFFFF', '#C62828'],
        'kirinyaga' => ['#1B5E20', '#FDD835', '#FFFFFF'],
        'kisii' => ['#006837', '#FFFFFF', '#C62828'],
        'kisumu' => ['#0D47A1', '#FFFFFF', '#1565C0'],
        'kitui' => ['#2E7D32', '#FDD835', '#C62828'],
        'kwale' => ['#1565C0', '#FFFFFF', '#1B5E20'],
        'laikipia' => ['#006837', '#FFFFFF', '#1565C0'],
        'lamu' => ['#0D47A1', '#FFFFFF', '#006837'],
        'machakos' => ['#1565C0', '#FFFFFF', '#1B5E20'],
        'makueni' => ['#1565C0', '#1B5E20', '#FFFFFF'],
        'mandera' => ['#006837', '#1565C0', '#FFFFFF'],
        'marsabit' => ['#1565C0', '#FFFFFF', '#C62828'],
        'meru' => ['#1B5E20', '#C62828', '#FFFFFF'],
        'migori' => ['#1565C0', '#FFFFFF', '#1B5E20'],
        'mombasa' => ['#0D47A1', '#FFFFFF', '#0D47A1'],
        'muranga' => ['#007A3D', '#FFD100', '#C8102E'],
        'nairobi-city' => ['#006837', '#1565C0', '#FFFFFF'],
        'nakuru' => ['#2E7D32', '#FDD835', '#FFFFFF'],
        'nandi' => ['#1B5E20', '#FFFFFF', '#C62828'],
        'narok' => ['#C62828', '#212121', '#006837'],
        'nyamira' => ['#006837', '#FFFFFF', '#C62828'],
        'nyandarua' => ['#1B5E20', '#FFFFFF', '#1565C0'],
        'nyeri' => ['#2E7D32', '#C62828', '#FDD835'],
        'samburu' => ['#C62828', '#212121', '#006837'],
        'siaya' => ['#C62828', '#FFFFFF', '#1565C0'],
        'taita-taveta' => ['#C62828', '#212121', '#1B5E20'],
        'tana-river' => ['#006837', '#FDD835', '#1565C0'],
        'tharaka-nithi' => ['#006837', '#FDD835', '#C62828'],
        'trans-nzoia' => ['#1B5E20', '#FDD835', '#FFFFFF'],
        'turkana' => ['#1565C0', '#FFFFFF', '#C62828'],
        'uasin-gishu' => ['#006837', '#FFFFFF', '#1565C0'],
        'vihiga' => ['#2E7D32', '#FDD835', '#C62828'],
        'wajir' => ['#1565C0', '#006837', '#FFFFFF'],
        'west-pokot' => ['#006837', '#FDD835', '#C62828'],
    ];

    public function forCounty(County $county): array
    {
        $slug = $county->slug;
        $emoji = $county->icon_emoji ?? '🏴';
        $colors = $this->flags[$slug] ?? $this->defaultColors($slug);
        $svg = $this->buildSvg($colors, $emoji, $county->name);

        return [
            'county' => $county->name,
            'slug' => $slug,
            'emoji' => $emoji,
            'colors' => $colors,
            'top' => $colors[0],
            'middle' => $colors[1],
            'bottom' => $colors[2],
            'svg' => $svg,
            'flag_data_uri' => 'data:image/svg+xml;utf8,' . rawurlencode($svg),
        ];
    }

    protected function buildSvg(array $colors, string $emoji, string $name): string
    {
        $top = $colors[0];
        $mid = $colors[1];
        $bot = $colors[2];
        $escaped = htmlspecialchars($name, ENT_QUOTES);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460 280" width="460" height="280">
  <rect y="0" width="460" height="93.3" fill="{$top}" />
  <rect y="93.3" width="460" height="93.3" fill="{$mid}" />
  <rect y="186.6" width="460" height="93.4" fill="{$bot}" />
  <g transform="translate(230, 140)">
    <ellipse cx="0" cy="0" rx="60" ry="60" fill="rgba(255,255,255,0.85)" stroke="rgba(0,0,0,0.35)" stroke-width="3"/>
    <text x="0" y="10" text-anchor="middle" font-size="48">{$emoji}</text>
  </g>
  <text x="230" y="262" text-anchor="middle" font-family="system-ui,sans-serif" font-size="15" font-weight="700" fill="rgba(0,0,0,0.8)">{$escaped}</text>
  <text x="230" y="275" text-anchor="middle" font-family="system-ui,sans-serif" font-size="9" font-weight="600" fill="rgba(0,0,0,0.5)">COUNTY GOVERNMENT</text>
</svg>
SVG;
    }

    protected function defaultColors(string $slug): array
    {
        return ['#006837', '#FDD835', '#C62828'];
    }
}