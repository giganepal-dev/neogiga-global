<?php

namespace App\Services\Bom;

use App\Models\Bom\BomProject;

class BomProjectService
{
    public function publicQuery()
    {
        return BomProject::query()
            ->where('is_public', true)
            ->where('status', 'published')
            ->with(['items' => fn ($items) => $items->publiclyAvailable()])
            ->latest();
    }

    public function publicBySlug(string $slug): BomProject
    {
        return $this->publicQuery()->where('slug', $slug)->firstOrFail();
    }
}
