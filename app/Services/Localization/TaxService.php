<?php

namespace App\Services\Localization;

use App\Models\Country;
use App\Models\TaxClass;
use App\Models\ImportDutyRule;

/**
 * Tax Calculation Service
 * 
 * Handles VAT, GST, sales tax, and import duty calculations.
 */
class TaxService
{
    /**
     * Calculate tax for a product sale.
     */
    public function calculateSalesTax(
        float $amount,
        int $countryId,
        ?int $categoryId = null,
        string $productType = 'physical',
        ?string $customerType = null
    ): float {
        $taxClass = $this->getApplicableTaxClass(
            $countryId,
            $categoryId,
            $productType,
            $customerType
        );

        if (!$taxClass) {
            return 0.0;
        }

        return $taxClass->calculateTax($amount);
    }

    /**
     * Get applicable tax class for a transaction.
     */
    protected function getApplicableTaxClass(
        int $countryId,
        ?int $categoryId,
        string $productType,
        ?string $customerType
    ): ?TaxClass {
        // Get active tax classes for country
        $taxClasses = TaxClass::active()
            ->where('country_id', $countryId)
            ->get();

        // Filter by category
        if ($categoryId !== null) {
            $taxClasses = $taxClasses->filter(function ($tc) use ($categoryId) {
                return $tc->appliesToCategory($categoryId);
            });
        }

        // Filter by product type
        $taxClasses = $taxClasses->filter(function ($tc) use ($productType) {
            return $tc->appliesToProductType($productType);
        });

        // Priority: customer-specific > reduced > standard
        if ($customerType === 'business' && $taxClasses->contains('code', 'ZERO')) {
            return $taxClasses->firstWhere('code', 'ZERO');
        }

        if ($taxClasses->contains('code', 'REDUCED')) {
            return $taxClasses->firstWhere('code', 'REDUCED');
        }

        return $taxClasses->firstWhere('code', 'STANDARD') 
            ?? $taxClasses->first();
    }

    /**
     * Calculate import duties and taxes for cross-border shipment.
     */
    public function calculateImportCosts(
        float $customsValue,
        string $destinationCountryCode,
        string $originCountryCode,
        string $hsCode,
        ?float $freightCost = null,
        ?float $insuranceCost = null
    ): array {
        $country = Country::findByCode($destinationCountryCode);
        
        if (!$country) {
            throw new \RuntimeException("Invalid destination country: {$destinationCountryCode}");
        }

        // CIF Value (Cost + Insurance + Freight)
        $cifValue = $customsValue 
            + ($freightCost ?? 0) 
            + ($insuranceCost ?? 0);

        // Find applicable import duty rule
        $dutyRule = ImportDutyRule::findForHsCode($country->id, $hsCode);

        if (!$dutyRule) {
            // No specific rule, use default rates
            $duty = $cifValue * ($country->default_import_duty_rate / 100);
            $vat = ($cifValue + $duty) * ($country->default_vat_rate / 100);
            
            return [
                'customs_value' => $customsValue,
                'freight' => $freightCost ?? 0,
                'insurance' => $insuranceCost ?? 0,
                'cif_value' => $cifValue,
                'duty_rate' => $country->default_import_duty_rate,
                'duty' => $duty,
                'vat_rate' => $country->default_vat_rate,
                'vat' => $vat,
                'excise' => 0,
                'total_taxes' => $duty + $vat,
                'landed_cost' => $cifValue + $duty + $vat,
            ];
        }

        // Use rule for calculation
        $calculation = $dutyRule->calculateTotalImportCost(
            $cifValue,
            $originCountryCode,
            $hsCode
        );

        return [
            'customs_value' => $customsValue,
            'freight' => $freightCost ?? 0,
            'insurance' => $insuranceCost ?? 0,
            'cif_value' => $cifValue,
            'duty_rate' => $dutyRule->duty_rate,
            'duty' => $calculation['duty'],
            'vat_rate' => $dutyRule->vat_rate ?? $country->default_vat_rate,
            'vat' => $calculation['vat'],
            'excise_rate' => $dutyRule->excise_rate,
            'excise' => $calculation['excise'],
            'total_taxes' => $calculation['duty'] + $calculation['vat'] + $calculation['excise'],
            'landed_cost' => $calculation['total'],
            'requires_certificate' => $dutyRule->requires_certificate,
            'required_certificates' => $dutyRule->required_certificates,
        ];
    }

    /**
     * Calculate reverse charge VAT for B2B intra-EU trade.
     */
    public function calculateReverseChargeVat(
        float $amount,
        string $supplierCountryCode,
        string $customerCountryCode
    ): array {
        $supplierCountry = Country::findByCode($supplierCountryCode);
        $customerCountry = Country::findByCode($customerCountryCode);

        // Check if both are EU countries
        $isIntraEu = $supplierCountry?->is_eu && $customerCountry?->is_eu;

        if (!$isIntraEu) {
            // Normal VAT applies
            $vatRate = $customerCountry?->default_vat_rate ?? 0;
            $vatAmount = $amount * ($vatRate / 100);

            return [
                'reverse_charge' => false,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'net_amount' => $amount,
                'gross_amount' => $amount + $vatAmount,
            ];
        }

        // Reverse charge applies - customer accounts for VAT
        return [
            'reverse_charge' => true,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'net_amount' => $amount,
            'gross_amount' => $amount,
            'note' => 'Reverse charge mechanism applies. Customer to account for VAT.',
        ];
    }

    /**
     * Validate VAT number format by country.
     */
    public function validateVatNumber(string $vatNumber, string $countryCode): bool
    {
        $vatNumber = strtoupper(trim($vatNumber));
        $countryCode = strtoupper($countryCode);

        // Check if VAT number starts with country code
        if (!str_starts_with($vatNumber, $countryCode)) {
            $vatNumber = $countryCode . $vatNumber;
        }

        // Basic format validation by country
        return match ($countryCode) {
            'DE' => (bool) preg_match('/^DE\d{9}$/', $vatNumber),
            'GB' => (bool) preg_match('/^GB(?:\d{3} \d{4} \d{2}|GD\d{3}|HA\d{3})$/', $vatNumber),
            'FR' => (bool) preg_match('/^FR[A-Z0-9]{2}\d{9}$/', $vatNumber),
            'IT' => (bool) preg_match('/^IT\d{11}$/', $vatNumber),
            'ES' => (bool) preg_match('/^ES[A-Z0-9]\d{7}[A-Z0-9]$/', $vatNumber),
            'NL' => (bool) preg_match('/^NL\d{9}B\d{2}$/', $vatNumber),
            'BE' => (bool) preg_match('/^BE0\d{9}$/', $vatNumber),
            'AT' => (bool) preg_match('/^ATU\d{8}$/', $vatNumber),
            'IE' => (bool) preg_match('/^IE\d{7}[A-Z]{1,2}$/', $vatNumber),
            default => true, // Accept if no specific rule
        };
    }

    /**
     * Verify VAT number via VIES API (EU only).
     */
    public function verifyVatNumberViaVies(string $vatNumber): array
    {
        $vatNumber = strtoupper(trim($vatNumber));
        
        if (strlen($vatNumber) < 3) {
            return [
                'valid' => false,
                'error' => 'Invalid VAT number format',
            ];
        }

        $countryCode = substr($vatNumber, 0, 2);
        $number = substr($vatNumber, 2);

        try {
            // VIES SOAP API
            $client = new \SoapClient('https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
            $result = $client->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $number,
            ]);

            return [
                'valid' => $result->valid ?? false,
                'name' => $result->name ?? null,
                'address' => $result->address ?? null,
                'request_date' => $result->requestDate ?? null,
            ];

        } catch (\Exception $e) {
            \Log::warning('VIES API error', [
                'vat_number' => $vatNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => null, // Unknown - API unavailable
                'error' => 'VAT verification service unavailable',
            ];
        }
    }

    /**
     * Get tax breakdown for invoice.
     */
    public function getTaxBreakdown(
        array $lineItems,
        int $countryId,
        ?string $customerVatNumber = null
    ): array {
        $subtotal = 0;
        $taxByRate = [];

        foreach ($lineItems as $item) {
            $amount = $item['amount'] ?? 0;
            $categoryId = $item['category_id'] ?? null;
            $productType = $item['product_type'] ?? 'physical';

            $subtotal += $amount;

            // Check if reverse charge applies
            if ($customerVatNumber && str_starts_with($customerVatNumber, 'DE')) {
                // Simplified - in reality check country match
                $taxRate = 0;
            } else {
                $tax = $this->calculateSalesTax($amount, $countryId, $categoryId, $productType);
                $taxRate = $this->getEffectiveTaxRate($countryId, $categoryId, $productType);
            }

            $rateKey = number_format($taxRate, 2);
            
            if (!isset($taxByRate[$rateKey])) {
                $taxByRate[$rateKey] = [
                    'rate' => $taxRate,
                    'taxable_amount' => 0,
                    'tax_amount' => 0,
                ];
            }

            $taxByRate[$rateKey]['taxable_amount'] += $amount;
            $taxByRate[$rateKey]['tax_amount'] += ($amount * ($taxRate / 100));
        }

        $totalTax = array_sum(array_column($taxByRate, 'tax_amount'));

        return [
            'subtotal' => $subtotal,
            'tax_breakdown' => array_values($taxByRate),
            'total_tax' => $totalTax,
            'total' => $subtotal + $totalTax,
        ];
    }

    /**
     * Get effective tax rate.
     */
    protected function getEffectiveTaxRate(
        int $countryId,
        ?int $categoryId,
        string $productType
    ): float {
        $taxClass = $this->getApplicableTaxClass($countryId, $categoryId, $productType, null);
        return $taxClass?->rate ?? 0;
    }
}
