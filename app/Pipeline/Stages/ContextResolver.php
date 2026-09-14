<?php

namespace App\Pipeline\Stages;

use App\Models\County;
use App\Models\CountyTourismAttraction;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\Travel\Attraction;

class ContextResolver
{
    public function handle(array $context): array
    {
        $anchor = $context['anchor'];

        if ($anchor instanceof Attraction || $anchor instanceof CountyTourismAttraction) {
            $context['anchor_type'] = 'attraction';
            $context['county'] = $anchor->county;
            $context['coordinates'] = ['lat' => (float) ($anchor->latitude ?? 0), 'lng' => (float) ($anchor->longitude ?? 0)];
            $context['anchor_name'] = $anchor->name;
            $context['anchor_id'] = $anchor->id;
        } elseif ($anchor instanceof CountyInstitution) {
            $context['anchor_type'] = 'institution';
            $context['county'] = $anchor->county;
            $context['coordinates'] = ['lat' => (float) ($anchor->lat ?? 0), 'lng' => (float) ($anchor->lng ?? 0)];
            $context['anchor_name'] = $anchor->name;
            $context['anchor_id'] = $anchor->id;
        } elseif ($anchor instanceof Product) {
            $context['anchor_type'] = 'product';
            $context['county'] = $anchor->county;
            $context['coordinates'] = null;
            $context['anchor_name'] = $anchor->name;
            $context['anchor_id'] = $anchor->id;
        } elseif ($anchor instanceof County) {
            $context['anchor_type'] = 'county';
            $context['county'] = $anchor;
            $context['coordinates'] = null;
            $context['anchor_name'] = $anchor->name;
            $context['anchor_id'] = $anchor->id;
        }

        return $context;
    }
}