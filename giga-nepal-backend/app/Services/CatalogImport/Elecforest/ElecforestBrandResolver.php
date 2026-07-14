<?php

namespace App\Services\CatalogImport\Elecforest;

class ElecforestBrandResolver
{
    /** @param array<string, mixed> $record @return array{id:?int,name:?string,confidence:float,verified:bool} */
    public function resolve(array $record): array
    {
        return ['id' => null, 'name' => null, 'confidence' => 0.0, 'verified' => false];
    }
}
