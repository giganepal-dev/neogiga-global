<?php

namespace App\Services;

use App\Models\AiBomBuild;
use App\Models\LmsProject;
use App\Models\LmsProductLink;
use Illuminate\Support\Collection;

class LmsMatcherService
{
    protected array $projectRules = [
        '4wd robot' => '4wd-robot-car-with-esp32',
        'arduino robot' => 'arduino-robot-beginner',
        'smart home' => 'iot-smart-home-system',
        'weather station' => 'iot-weather-station',
        'solar' => 'solar-power-monitoring',
        'ev' => 'ev-battery-monitor',
    ];

    public function matchForBom(AiBomBuild $bomBuild): ?LmsProject
    {
        $goalLower = strtolower($bomBuild->user_goal);
        
        foreach ($this->projectRules as $keyword => $slug) {
            if (str_contains($goalLower, $keyword)) {
                $project = LmsProject::where('slug', $slug)->first();
                
                if ($project) {
                    $this->linkProductsToProject($bomBuild, $project);
                    return $project;
                }
            }
        }
        
        return null;
    }

    public function matchForProduct(int $productId): Collection
    {
        $links = LmsProductLink::where('product_id', $productId)
            ->with(['project', 'lesson'])
            ->get();
            
        return $links;
    }

    public function getProjectsByCategory(string $categorySlug): Collection
    {
        return LmsProject::whereHas('categories', function ($query) use ($categorySlug) {
            $query->where('slug', $categorySlug);
        })
        ->with(['lessons', 'components'])
        ->orderBy('difficulty_level')
        ->get();
    }

    protected function linkProductsToProject(AiBomBuild $bomBuild, LmsProject $project): void
    {
        foreach ($bomBuild->items as $item) {
            if ($item->product_id) {
                LmsProductLink::firstOrCreate(
                    [
                        'lms_project_id' => $project->id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'is_required' => true,
                        'sort_order' => $item->sort_order,
                        'notes' => $item->reason,
                    ]
                );
            }
        }
    }

    public function getCodeSamplesForProject(LmsProject $project): Collection
    {
        return $project->codeSamples()->orderBy('sort_order')->get();
    }

    public function getComponentsForProject(LmsProject $project): Collection
    {
        return $project->components()
            ->with(['product', 'product.category'])
            ->orderBy('sort_order')
            ->get();
    }
}
