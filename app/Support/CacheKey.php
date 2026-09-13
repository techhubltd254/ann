<?php

namespace App\Support;

final class CacheKey
{
    public const HOME_PAGE = 'kicc_home_page_data_v4_';
    public const MARKETPLACE = 'marketplace_data_';
    public const LIVE_BOOTHS = 'live_active_booths';
    public const COUNTY_INDEX = 'kicc_counties_index';
    public const COUNTY_SECTOR_COUNTS = 'kicc_county_sector_counts_';
    public const COUNTY_ATTRACTIONS = 'kicc_county_attractions_';
    public const COUNTY_HOTELS = 'kicc_county_hotels_';
    public const COUNTY_PRODUCTS = 'kicc_county_products_';
    public const COUNTY_EXHIBITIONS = 'kicc_county_exhibitions_';
    public const COUNTY_LINKED_SECTORS = 'kicc_county_linked_sectors_';
    public const COUNTY_HERO = 'resolve:county_hero_id_';
    public const COUNTY_PINS = 'county_pins_';
    public const SECTOR_ITEMS = 'kicc_county_sector_items_';
    public const SECTOR_PITCH = 'sector_pitch_';
    public const TILE_MEDIA = 'tile_media_ids_';
    public const IMAGE_BLUR = 'iblur:';
    public const NG_PREFIX = 'ng_';

    public static function key(string $name, ...$args): string
    {
        $prefix = match (true) {
            str_starts_with($name, 'kicc_') || str_starts_with($name, 'resolve:') || str_starts_with($name, 'county_pins') || str_starts_with($name, 'tile_media') => '',
            default => 'kicc_',
        };
        return $prefix . $name . ($args ? '_' . implode('_', $args) : '');
    }
}