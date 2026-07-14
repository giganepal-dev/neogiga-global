<?php

namespace App\Services\CatalogImport\Elecforest;

class ElecforestManufacturerResolver
{
    /** @param array<string, mixed> $record @return array{id:?int,name:?string,mpn:?string,confidence:float,verified:bool} */
    public function resolve(array $record): array
    {
        return ['id' => null, 'name' => null, 'mpn' => null, 'confidence' => 0.0, 'verified' => false];
    }
}
