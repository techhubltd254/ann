<?php

namespace App\Pipeline\Stages;

class ExperienceBuilder
{
    public function handle(array $context): array
    {
        $config = config('experience.pricing');

        $context['addon_options'] = $config['addons'] ?? [];
        $context['package_discounts'] = $config['package_discounts'] ?? [];
        $context['experience_types'] = config('experience.experience_types', []);

        // Calculate estimated total for the anchor item
        $entryFee = $context['anchor']->entry_fee ?? $config['default_entry_fee'] ?? 500;
        $context['estimated_total'] = $entryFee;

        return $context;
    }
}