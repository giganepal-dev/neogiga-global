<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\DB;

/**
 * Race-safe sequential document numbers (PO/RFQ/QUO/…). Uses a row lock so
 * concurrent requests never receive the same number.
 */
class DocumentNumberService
{
    public function next(string $key, string $defaultPrefix = '', int $padding = 5): string
    {
        return DB::transaction(function () use ($key, $defaultPrefix, $padding) {
            $seq = DB::table('document_number_sequences')->where('key', $key)->lockForUpdate()->first();

            if (!$seq) {
                DB::table('document_number_sequences')->insert([
                    'key' => $key,
                    'prefix' => $defaultPrefix,
                    'next_number' => 1,
                    'padding' => $padding,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $seq = DB::table('document_number_sequences')->where('key', $key)->lockForUpdate()->first();
            }

            $number = (int) $seq->next_number;

            DB::table('document_number_sequences')->where('id', $seq->id)->update([
                'next_number' => $number + 1,
                'updated_at' => now(),
            ]);

            $prefix = $seq->prefix !== '' ? $seq->prefix : $defaultPrefix;

            return $prefix . str_pad((string) $number, (int) $seq->padding, '0', STR_PAD_LEFT);
        });
    }
}
