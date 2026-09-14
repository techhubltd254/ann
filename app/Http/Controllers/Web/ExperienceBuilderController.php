<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Pipeline\PipelineManager;
use App\Models\CountyTourismAttraction;
use App\Models\Travel\Attraction;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\County;
use Illuminate\Http\Request;

class ExperienceBuilderController extends Controller
{
    public function plan(Request $request, string $type, $id)
    {
        $anchor = $this->resolveAnchor($type, $id);
        if (!$anchor) abort(404);

        $pipeline = app(PipelineManager::class);
        $context = $pipeline->run('context', $anchor, $request);

        return view('experience.plan', [
            'context' => $context,
            'anchor' => $anchor,
            'correlations' => $context['correlations'],
            'addonOptions' => $context['addon_options'] ?? [],
            'experienceTypes' => $context['experience_types'] ?? [],
            'estimatedTotal' => $context['estimated_total'] ?? 0,
        ]);
    }

    public function build(Request $request, string $type, $id)
    {
        $anchor = $this->resolveAnchor($type, $id);
        if (!$anchor) abort(404);

        $request->validate([
            'selections' => 'required|array|min:1',
            'days' => 'integer|min:1|max:14',
            'start_date' => 'date|after_or_equal:today',
            'phone' => 'nullable|string|max:30',
        ]);

        $pipeline = app(PipelineManager::class);
        $context = $pipeline->run('context', $anchor, $request);

        return redirect()->route('experience.receipt', [
            'type' => $type, 'id' => $id,
        ])->with('context', $context);
    }

    public function receipt(Request $request, string $type, $id)
    {
        $context = session('context');
        if (!$context) return redirect()->route('experience.plan', [$type, $id]);

        return view('experience.receipt', compact('context'));
    }

    public function itinerary(Request $request, string $type, $id)
    {
        $anchor = $this->resolveAnchor($type, $id);
        if (!$anchor) abort(404);

        $days = (int) $request->input('days', 3);
        $dest = optional($anchor->county)->name ?? $anchor->name ?? 'Kenya';

        return view('experience.itinerary', [
            'context' => ['days' => $days, 'anchor_name' => $anchor->name ?? '', 'county' => $anchor->county ?? null, 'anchor_type' => $type, 'anchor_id' => $id, 'correlations' => ['places_to_visit' => [], 'places_to_stay' => []]],
            'aiItinerary' => "Welcome to your {$days}-day {$dest} Experience!\n\nPlan your days exploring {$dest}'s top attractions, dining at local restaurants, and experiencing Kenyan hospitality.",
        ]);
    }

    protected function resolveAnchor(string $type, $id): mixed
    {
        return match ($type) {
            'attraction' => Attraction::with('county')->find($id) ?? CountyTourismAttraction::with('county')->find($id),
            'institution' => CountyInstitution::with('county')->find($id),
            'product' => Product::with('county')->find($id),
            'county' => County::find($id),
            default => null,
        };
    }
}