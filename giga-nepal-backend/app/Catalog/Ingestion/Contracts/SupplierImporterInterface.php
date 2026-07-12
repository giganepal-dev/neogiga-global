<?php

namespace App\Catalog\Ingestion\Contracts;

interface SupplierImporterInterface
{
    /** @return iterable<string> */
    public function discover(array $definition, int $limit = 0): iterable;

    /** @return array<string, mixed> */
    public function parse(string $url, array $definition): array;
}
