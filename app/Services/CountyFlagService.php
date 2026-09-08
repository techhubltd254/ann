<?php

namespace App\Services;

use App\Models\County;

/**
 * CountyFlagService — generates waving flag data for any county.
 *
 * Each county gets a unique flag derived from:
 *   - 3 horizontal stripes with colors hashed from county name
 *   - The county's icon_emoji centered on the flag
 *   - The county name as a ribbon banner
 *
 * The service returns structured data consumable by the Three.js waving flag.
 */
class CountyFlagService
{
    /** Generate complete flag data array for a county. */
    public function forCounty(County $county): array
    {
        $emoji = $county->icon_emoji ?? '🏴';
        $colors = $this->flagColors($county->name, $emoji);
        $emblem = $this->emblemSvg($emoji, $county->name);

        return [
            'county' => $county->name,
            'slug' => $county->slug,
            'emoji' => $emoji,
            'colors' => $colors,
            'stripes' => $colors['stripes'],
            'emblem_svg' => $emblem,
            'flag_data_uri' => $this->dataUri($emblem),
        ];
    }

    /** Derive 3 stripe colors from county name + emoji hash. */
    protected function flagColors(string $name, string $emoji): array
    {
        $hash = crc32($name . $emoji);
        $base = $this->hslToRgb(($hash & 0xFF) / 256 * 360, 0.65, 0.35);

        return [
            'top' => $this->formatHex($base),
            'middle' => $this->formatHex($this->shiftHue($base, 120)),
            'bottom' => $this->formatHex($this->shiftHue($base, 240)),
            'stripes' => [
                $this->formatHex($base),
                $this->formatHex($this->shiftHue($base, 120)),
                $this->formatHex($this->shiftHue($base, 240)),
            ],
        ];
    }

    /** Generate the full flag SVG with stripes + emblem. */
    protected function emblemSvg(string $emoji, string $countyName): string
    {
        $colors = $this->flagColors($countyName, $emoji);
        $top = $colors['top'];
        $mid = $colors['middle'];
        $bot = $colors['bottom'];
        $escaped = htmlspecialchars($countyName, ENT_QUOTES);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 460 280" width="460" height="280">
  <rect y="0" width="460" height="93.3" fill="{$top}" />
  <rect y="93.3" width="460" height="93.3" fill="{$mid}" />
  <rect y="186.6" width="460" height="93.4" fill="{$bot}" />
  <g transform="translate(230, 140)">
    <ellipse cx="0" cy="0" rx="60" ry="60" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
    <text x="0" y="10" text-anchor="middle" font-size="48">{$emoji}</text>
    <path d="M -50 35 Q 0 48 50 35 L 45 46 Q 0 58 -45 46 Z" fill="rgba(0,0,0,0.2)"/>
  </g>
  <text x="230" y="260" text-anchor="middle" font-family="system-ui,sans-serif" font-size="18" font-weight="700" fill="rgba(255,255,255,0.9)">{$escaped}</text>
</svg>
SVG;
    }

    protected function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    /** Simple HSL → RGB conversion. Returns 0-255 range. */
    protected function hslToRgb(float $h, float $s, float $l): array
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;
        $h /= 60;

        $r = $g = $b = 0;
        if ($h < 1)      { $r = $c + $m; $g = $x + $m; $b = $m; }
        elseif ($h < 2)  { $r = $x + $m; $g = $c + $m; $b = $m; }
        elseif ($h < 3)  { $r = $m; $g = $c + $m; $b = $x + $m; }
        elseif ($h < 4)  { $r = $m; $g = $x + $m; $b = $c + $m; }
        elseif ($h < 5)  { $r = $x + $m; $g = $m; $b = $c + $m; }
        else             { $r = $c + $m; $g = $m; $b = $x + $m; }

        return [(int)round($r * 255), (int)round($g * 255), (int)round($b * 255)];
    }

    protected function shiftHue(array $rgb, float $degrees): array
    {
        // Convert RGB → HSL → shift hue → back to RGB
        $r = $rgb[0] / 255; $g = $rgb[1] / 255; $b = $rgb[2] / 255;
        $max = max($r, $g, $b); $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) return [$r * 255, $g * 255, $b * 255];

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        if ($max === $r)       $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        elseif ($max === $g)   $h = ($b - $r) / $d + 2;
        else                   $h = ($r - $g) / $d + 4;
        $h /= 6;

        $h = fmod($h + $degrees / 360, 1);
        if ($h < 0) $h += 1;

        return $this->hslToRgb($h * 360, $s, $l);
    }

    protected function formatHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', (int)round($rgb[0]), (int)round($rgb[1]), (int)round($rgb[2]));
    }
}