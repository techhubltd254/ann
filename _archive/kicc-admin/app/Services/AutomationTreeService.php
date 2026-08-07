<?php

namespace App\Services;

class AutomationTreeService
{
    /**
     * Hierarchical automation tree — each sector is independent but linked under platform.
     * Platform → Sector → County → Action
     */
    public function getTree(): array
    {
        return [
            'name' => 'KICC Platform',
            'type' => 'platform',
            'children' => [
                [
                    'name' => 'County Content Pipeline',
                    'file' => 'county-content-pipeline.json',
                    'type' => 'sector',
                    'schedule' => 'Weekly (every 7 days)',
                    'description' => 'Auto-fetch county tourism data, generate SEO-optimized content, push to platform',
                    'children' => [
                        ['name' => 'Trigger: Scheduled Scan', 'type' => 'trigger', 'icon' => 'clock'],
                        ['name' => 'Fetch County Content (API)', 'type' => 'action', 'icon' => 'download'],
                        ['name' => 'Generate SEO Metadata', 'type' => 'action', 'icon' => 'search'],
                        ['name' => 'Update Platform Content', 'type' => 'action', 'icon' => 'upload'],
                        ['name' => 'Notify County Admin', 'type' => 'action', 'icon' => 'bell'],
                    ],
                ],
                [
                    'name' => 'International Trade Promotion',
                    'file' => 'international-trade-promotion.json',
                    'type' => 'sector',
                    'schedule' => 'Weekly (Sunday 08:00)',
                    'description' => 'Scan new county products, generate multilingual trade listings, push to export partners',
                    'children' => [
                        ['name' => 'Schedule: Weekly Export Push', 'type' => 'trigger', 'icon' => 'clock'],
                        ['name' => 'Fetch New County Products', 'type' => 'action', 'icon' => 'package'],
                        ['name' => 'Generate Trade Listing (EN/FR/AR)', 'type' => 'action', 'icon' => 'globe'],
                        ['name' => 'Send to Export Partners', 'type' => 'action', 'icon' => 'send'],
                        ['name' => 'Log Promotion to Dashboard', 'type' => 'action', 'icon' => 'bar-chart'],
                    ],
                ],
                [
                    'name' => 'Sector-by-Sector Pipeline',
                    'file' => 'sbs-pipeline.json',
                    'type' => 'sector',
                    'schedule' => 'Continuous (event-driven)',
                    'description' => 'Per-sector image analysis, video generation, content refresh — each sector runs independently',
                    'children' => [
                        ['name' => 'Trigger: Sector Update Event', 'type' => 'trigger', 'icon' => 'zap'],
                        ['name' => 'Run Sector Image Analyzer', 'type' => 'action', 'icon' => 'image'],
                        ['name' => 'Generate Sector Showcase Video', 'type' => 'action', 'icon' => 'video'],
                        ['name' => 'SEO Refresh for Sector', 'type' => 'action', 'icon' => 'refresh-cw'],
                        ['name' => 'Push to Screen Pipeline', 'type' => 'action', 'icon' => 'monitor'],
                    ],
                ],
                [
                    'name' => 'Agentic Loop (AI Automation)',
                    'file' => 'agentic-loop',
                    'type' => 'sector',
                    'schedule' => 'Every 15 min',
                    'description' => 'Observe platform signals → LLM decide → Execute actions (SEO, content, alerts)',
                    'children' => [
                        ['name' => 'Observer: Collect Platform Signals', 'type' => 'process', 'icon' => 'eye'],
                        ['name' => 'Decider: LLM Evaluation', 'type' => 'process', 'icon' => 'cpu'],
                        ['name' => 'SEO Content Refresh', 'type' => 'action', 'icon' => 'file-text'],
                        ['name' => 'Recommendation Update', 'type' => 'action', 'icon' => 'star'],
                        ['name' => 'Admin Alert on Stuck Payments', 'type' => 'action', 'icon' => 'alert-triangle'],
                    ],
                ],
            ],
        ];
    }
}