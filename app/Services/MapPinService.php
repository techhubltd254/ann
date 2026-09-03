<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\Marketplace\Product;

/**
 * MapPinService — independent mapping algorithm.
 *
 * Resolves ANY entity to a specific map pin URL (Google Maps / OpenStreetMap).
 * No hardcoded county names. No Leaflet dependency. Works for every entity type.
 *
 * Algorithm:
 *   1. If entity has lat/lng → generate Google Maps pin URL
 *   2. If entity has a county relationship → use county centroid
 *   3. If entity has a location string → generate search query
 *   4. Fallback → county centroid or null
 */
class MapPinService
{
    /**
     * Resolve the best map pin URL for any entity.
     */
    public function pinUrl($entity): ?string
    {
        $coords = $this->resolveCoordinates($entity);
        if ($coords) {
            return $this->googleMapsPin($coords['lat'], $coords['lng'], $this->entityName($entity));
        }

        $name = $this->entityName($entity);
        $location = $this->entityLocation($entity);
        if ($location) {
            return $this->googleMapsSearch($name . ', ' . $location);
        }
        if ($name) {
            return $this->googleMapsSearch($name);
        }

        return null;
    }

    /**
     * Get OpenStreetMap link for an entity.
     */
    public function osmUrl($entity): ?string
    {
        $coords = $this->resolveCoordinates($entity);
        if ($coords) {
            return "https://www.openstreetmap.org/?mlat={$coords['lat']}&mlon={$coords['lng']}#map=17/{$coords['lat']}/{$coords['lng']}";
        }
        return null;
    }

    /**
     * Get Google Maps embed URL for an entity.
     */
    public function embedUrl($entity): ?string
    {
        $coords = $this->resolveCoordinates($entity);
        if ($coords) {
            return "https://www.google.com/maps/embed/v1/place?key=&q={$coords['lat']},{$coords['lng']}&center={$coords['lat']},{$coords['lng']}&zoom=15";
        }
        return null;
    }

    /**
     * Resolve coordinates from any entity type.
     */
    public function resolveCoordinates($entity): ?array
    {
        // Direct lat/lng properties
        $lat = null;
        $lng = null;

        if ($entity instanceof CountyInstitution) {
            $lat = $entity->lat;
            $lng = $entity->lng;
        } elseif ($entity instanceof County) {
            $lat = $entity->latitude;
            $lng = $entity->longitude;
        } elseif ($entity instanceof CountyTourismAttraction) {
            $lat = $entity->latitude;
            $lng = $entity->longitude;
        } elseif ($entity instanceof CountyHotel) {
            $lat = $entity->latitude;
            $lng = $entity->longitude;
        } elseif ($entity instanceof Product) {
            // Fall back to county centroid
            if ($entity->county) {
                return $this->resolveCoordinates($entity->county);
            }
        } elseif (is_object($entity)) {
            // Generic fallback: try common property names
            $lat = $entity->lat ?? $entity->latitude ?? $entity->lat ?? null;
            $lng = $entity->lng ?? $entity->longitude ?? $entity->lng ?? null;
        }

        if ($lat && $lng && (float) $lat !== 0.0 && (float) $lng !== 0.0) {
            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
            ];
        }

        return null;
    }

    /**
     * Get a human-readable address/location string for an entity.
     */
    public function entityLocation($entity): ?string
    {
        if ($entity instanceof CountyInstitution) {
            return $entity->location;
        }
        if ($entity instanceof County) {
            return $entity->name . ' County, Kenya';
        }
        if ($entity instanceof CountyTourismAttraction) {
            return $entity->location;
        }
        if ($entity instanceof CountyHotel) {
            return $entity->location;
        }
        if (property_exists($entity, 'location') && $entity->location) {
            return $entity->location;
        }
        return null;
    }

    /**
     * Get the display name for an entity.
     */
    public function entityName($entity): string
    {
        if (method_exists($entity, 'name')) {
            return $entity->name;
        }
        if (property_exists($entity, 'name')) {
            return $entity->name;
        }
        return 'Location';
    }

    /**
     * Generate a Google Maps pin URL (opens with a pin at exact coordinates).
     */
    public function googleMapsPin(float $lat, float $lng, string $label = ''): string
    {
        $query = urlencode($label ?: "{$lat},{$lng}");
        return "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
    }

    /**
     * Generate a Google Maps search URL.
     */
    public function googleMapsSearch(string $query): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($query);
    }

    /**
     * Check if an entity has usable coordinates.
     */
    public function hasCoordinates($entity): bool
    {
        return $this->resolveCoordinates($entity) !== null;
    }

    /**
     * Get all institutions with pins for a county (for the location list).
     */
    public function countyPins(County $county): array
    {
        $institutions = CountyInstitution::where('county_id', $county->id)
            ->where('is_published', true)
            ->get(['id', 'name', 'slug', 'lat', 'lng', 'location', 'website', 'type']);

        $pins = [];
        foreach ($institutions as $inst) {
            $coords = $this->resolveCoordinates($inst);
            $pins[] = [
                'id' => $inst->id,
                'name' => $inst->name,
                'slug' => $inst->slug,
                'type' => $inst->type,
                'location' => $inst->location,
                'website' => $inst->website,
                'lat' => $coords['lat'] ?? null,
                'lng' => $coords['lng'] ?? null,
                'pin_url' => $coords ? $this->googleMapsPin($coords['lat'], $coords['lng'], $inst->name) : null,
                'osm_url' => $coords ? $this->osmUrl($inst) : null,
                'has_pin' => $coords !== null,
            ];
        }

        return $pins;
    }
}