<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Log;

class CampaignContactXmlParser
{
    /**
     * Parse XML contact list into normalized rows.
     */
    public function parse(string $xmlContent): array
    {
        // Disable external entity loading for security
        libxml_disable_entity_loader(true);
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \InvalidArgumentException('Invalid XML: ' . ($errors[0]->message ?? 'Parse error'));
        }

        $rows = [];
        $contactNodes = $this->findContactNodes($xml);

        foreach ($contactNodes as $node) {
            $row = $this->extractContactData($node);
            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'format' => 'xml',
        ];
    }

    /**
     * Find contact nodes in XML structure.
     */
    private function findContactNodes(\SimpleXMLElement $xml): array
    {
        $nodes = [];

        // Try common contact node names
        $contactNames = ['contact', 'contacts', 'record', 'records', 'subscriber', 'subscribers', 'entry', 'entries'];

        foreach ($contactNames as $name) {
            $found = $xml->xpath("//{$name}");
            if ($found && count($found) > 0) {
                $nodes = array_merge($nodes, $found);
            }
        }

        // If no common names found, try child elements of root
        if (empty($nodes)) {
            foreach ($xml->children() as $child) {
                if ($child->count() > 0) { // Has child elements
                    $nodes[] = $child;
                }
            }
        }

        return $nodes;
    }

    /**
     * Extract contact data from a XML node.
     */
    private function extractContactData(\SimpleXMLElement $node): array
    {
        $row = [];

        // Try to get data from child elements
        foreach ($node->children() as $child) {
            $name = strtolower($child->getName());
            $value = trim((string) $child);

            if (!empty($value)) {
                $row[$name] = $value;
            }
        }

        // Also try to get data from attributes
        foreach ($node->attributes() as $name => $value) {
            $name = strtolower($name);
            if (!isset($row[$name]) && !empty($value)) {
                $row[$name] = (string) $value;
            }
        }

        // Normalize common field names
        $row = $this->normalizeFieldNames($row);

        return $row;
    }

    /**
     * Normalize field names to standard keys.
     */
    private function normalizeFieldNames(array $row): array
    {
        $normalized = [];

        $fieldMap = [
            'email' => ['email', 'emailaddress', 'email_address'],
            'first_name' => ['firstname', 'first_name', 'fname'],
            'last_name' => ['lastname', 'last_name', 'lname'],
            'full_name' => ['fullname', 'full_name', 'name'],
            'company' => ['company', 'companyname', 'company_name', 'organization'],
            'phone' => ['phone', 'phonenumber', 'phone_number', 'telephone'],
            'country' => ['country', 'countrycode', 'country_code'],
            'job_title' => ['jobtitle', 'job_title', 'title', 'position'],
        ];

        foreach ($row as $key => $value) {
            $found = false;
            foreach ($fieldMap as $canonical => $aliases) {
                if (in_array($key, $aliases, true)) {
                    $normalized[$canonical] = $value;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Preview XML structure for field mapping.
     */
    public function preview(string $xmlContent): array
    {
        libxml_disable_entity_loader(true);
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            return ['error' => 'Invalid XML'];
        }

        $contactNodes = $this->findContactNodes($xml);
        $sampleRow = !empty($contactNodes) ? $this->extractContactData($contactNodes[0]) : [];

        return [
            'total_nodes' => count($contactNodes),
            'available_fields' => array_keys($sampleRow),
            'sample_data' => $sampleRow,
            'structure' => $this->getStructure($xml),
        ];
    }

    /**
     * Get XML structure description.
     */
    private function getStructure(\SimpleXMLElement $xml): array
    {
        $structure = [];
        foreach ($xml->children() as $child) {
            $structure[] = [
                'name' => $child->getName(),
                'has_children' => $child->count() > 0,
                'attributes' => array_keys((array) $child->attributes()),
            ];
        }
        return array_slice($structure, 0, 10); // Limit to first 10 for preview
    }
}
