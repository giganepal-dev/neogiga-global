<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class NeoGigaAiService
 * 
 * AI service for generating product summaries, BOM suggestions, 
 * compatible alternatives, cross-sell recommendations, and more.
 */
class NeoGigaAiService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.neoai.api_key');
        $this->apiUrl = config('services.neoai.api_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('services.neoai.model', 'gpt-4o-mini');
    }

    /**
     * Generate an AI-powered product summary.
     */
    public function generateProductSummary(array $product): string
    {
        $prompt = $this->buildProductSummaryPrompt($product);
        
        $response = $this->callAi($prompt);
        
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Generate BOM (Bill of Materials) suggestions for a product.
     */
    public function generateBomSuggestions(array $product): array
    {
        $prompt = $this->buildBomPrompt($product);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        // Parse JSON response
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse BOM JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Find compatible alternative products.
     */
    public function findCompatibleAlternatives(array $product, array $catalogProducts = []): array
    {
        $prompt = $this->buildAlternativesPrompt($product, $catalogProducts);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse alternatives JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate cross-sell recommendations.
     */
    public function generateCrossSellRecommendations(array $product): array
    {
        $prompt = $this->buildCrossSellPrompt($product);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse cross-sell JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate project ideas using the product.
     */
    public function generateProjectIdeas(array $product): array
    {
        $prompt = $this->buildProjectIdeasPrompt($product);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse project ideas JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate pinout diagram description.
     */
    public function generatePinoutDiagram(array $product): array
    {
        $prompt = $this->buildPinoutPrompt($product);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse pinout JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Generate wiring examples.
     */
    public function generateWiringExamples(array $product, string $targetBoard = 'Arduino Uno'): array
    {
        $prompt = $this->buildWiringPrompt($product, $targetBoard);
        
        $response = $this->callAi($prompt);
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to parse wiring JSON', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Answer questions about datasheets.
     */
    public function answerDatasheetQuestion(string $datasheetContent, string $question): string
    {
        $prompt = $this->buildDatasheetQaPrompt($datasheetContent, $question);
        
        $response = $this->callAi($prompt);
        
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Call the AI API.
     */
    protected function callAi(string $prompt, int $maxTokens = 2000): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert electronics engineer and technical writer specializing in embedded systems, IoT, robotics, and maker hardware. Provide clear, accurate, and practical information.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('AI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return ['choices' => []];
        } catch (\Exception $e) {
            Log::error('AI API error', ['error' => $e->getMessage()]);
            return ['choices' => []];
        }
    }

    /**
     * Build prompt for product summary generation.
     */
    protected function buildProductSummaryPrompt(array $product): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $description = $product['description'] ?? '';
        $specs = json_encode($product['technical_specifications'] ?? [], JSON_PRETTY_PRINT);
        $features = implode("\n", $product['features'] ?? []);
        
        return <<<PROMPT
Generate a concise, engaging product summary (150-200 words) for the following electronics component:

**Product Name:** {$name}

**Description:** {$description}

**Technical Specifications:**
{$specs}

**Key Features:**
{$features}

The summary should:
1. Start with a hook that highlights the main use case
2. Explain what the product is and its primary function
3. Mention key technical specifications that matter to engineers/makers
4. Highlight compatibility with popular platforms (Arduino, Raspberry Pi, ESP32, etc.)
5. End with typical applications or project ideas
6. Use clear, professional language suitable for both beginners and experienced engineers

Write in a friendly, informative tone that encourages exploration and creativity.
PROMPT;
    }

    /**
     * Build prompt for BOM suggestions.
     */
    protected function buildBomPrompt(array $product): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $category = $product['category']['name'] ?? 'Uncategorized';
        $specs = json_encode($product['technical_specifications'] ?? [], JSON_PRETTY_PRINT);
        
        return <<<PROMPT
For the product "{$name}" in category "{$category}", suggest a complete Bill of Materials (BOM) for building a typical project with this component.

Specifications:
{$specs}

Return a JSON array of suggested components with this exact structure:
[
  {
    "component_name": "Component name",
    "category": "Component category",
    "quantity": 1,
    "purpose": "Why this component is needed",
    "alternatives": ["Alternative 1", "Alternative 2"]
  }
]

Include:
- The main product itself
- Required supporting components (resistors, capacitors, etc.)
- Connection hardware (wires, connectors, breadboard)
- Power supply requirements
- Optional enhancement components
- Tools that might be needed

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Build prompt for finding alternatives.
     */
    protected function buildAlternativesPrompt(array $product, array $catalogProducts): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $mpn = $product['mpn'] ?? '';
        $specs = json_encode($product['technical_specifications'] ?? [], JSON_PRETTY_PRINT);
        
        $catalogSample = array_slice($catalogProducts, 0, 20);
        $catalogJson = json_encode($catalogSample, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Find compatible alternative products for: "{$name}" (MPN: {$mpn})

Product Specifications:
{$specs}

From this catalog sample:
{$catalogJson}

Identify products that could serve as alternatives based on:
1. Similar functionality
2. Compatible specifications
3. Same form factor or pinout
4. Compatible voltage/current requirements
5. Support for same platforms

Return a JSON array with this structure:
[
  {
    "product_name": "Alternative product name",
    "mpn": "Manufacturer Part Number",
    "similarity_score": 0.95,
    "key_differences": ["Difference 1", "Difference 2"],
    "when_to_choose": "When you need X instead of Y"
  }
]

Return ONLY valid JSON, no additional text. If no good alternatives exist, return an empty array.
PROMPT;
    }

    /**
     * Build prompt for cross-sell recommendations.
     */
    protected function buildCrossSellPrompt(array $product): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $category = $product['category']['name'] ?? 'Uncategorized';
        $compatibleBoards = json_encode($product['compatible_boards'] ?? [], JSON_PRETTY_PRINT);
        
        return <<<PROMPT
For customers viewing "{$name}" in the "{$category}" category, recommend complementary products they might want to purchase together.

Compatible Boards/Platforms:
{$compatibleBoards}

Suggest products in these categories:
1. Essential accessories (cables, mounts, cases)
2. Compatible breakout boards or shields
3. Sensors or modules that work well together
4. Development boards if not already included
5. Tools and testing equipment
6. Learning resources or kits

Return a JSON array with this structure:
[
  {
    "product_type": "Accessory/Sensor/Tool/etc.",
    "recommendation": "Product name or type",
    "reason": "Why this pairs well",
    "priority": "high/medium/low"
  }
]

Return 5-8 recommendations sorted by priority. Return ONLY valid JSON.
PROMPT;
    }

    /**
     * Build prompt for project ideas.
     */
    protected function buildProjectIdeasPrompt(array $product): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $description = $product['description'] ?? '';
        $category = $product['category']['name'] ?? 'Uncategorized';
        $features = implode(", ", $product['features'] ?? []);
        
        return <<<PROMPT
Generate creative project ideas using the component: "{$name}"

Category: {$category}
Description: {$description}
Features: {$features}

Create 5 project ideas ranging from beginner to advanced difficulty. For each project include:
- Project name
- Difficulty level (Beginner/Intermediate/Advanced)
- Estimated time to complete
- Brief description of what it does
- Additional components needed
- Learning outcomes

Return a JSON array with this exact structure:
[
  {
    "project_name": "Project Name",
    "difficulty": "Beginner/Intermediate/Advanced",
    "estimated_time": "2-4 hours",
    "description": "What the project does",
    "additional_components": ["Component 1", "Component 2"],
    "learning_outcomes": ["Skill 1", "Skill 2"],
    "tags": ["IoT", "Robotics", "Home Automation"]
  }
]

Make projects practical, educational, and inspiring. Include diverse applications like IoT, robotics, home automation, wearables, etc.

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Build prompt for pinout diagram.
     */
    protected function buildPinoutPrompt(array $product): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $specs = json_encode($product['technical_specifications'] ?? [], JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Generate a detailed pinout description for: "{$name}"

Specifications:
{$specs}

Create a structured pinout diagram description that includes:
- Pin number/name
- Pin type (Power, Ground, Digital I/O, Analog Input, Communication, etc.)
- Voltage levels
- Function description
- Usage notes/warnings

Return a JSON array with this exact structure:
[
  {
    "pin_number": "1 or PIN_NAME",
    "pin_type": "Power/Ground/Digital/Analog/Communication",
    "voltage": "3.3V/5V/GND/etc.",
    "function": "What this pin does",
    "notes": "Important usage notes",
    "arduino_pin": "Corresponding Arduino pin if applicable"
  }
]

Include all pins, power connections, ground pins, communication interfaces (I2C, SPI, UART), and any special function pins.

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Build prompt for wiring examples.
     */
    protected function buildWiringPrompt(array $product, string $targetBoard): string
    {
        $name = $product['name'] ?? 'Unknown Product';
        $specs = json_encode($product['technical_specifications'] ?? [], JSON_PRETTY_PRINT);
        
        return <<<PROMPT
Provide detailed wiring instructions to connect "{$name}" to a {$targetBoard}.

Specifications:
{$specs}

Create step-by-step wiring instructions with:
- Clear connection list (which pin to which)
- Wire color recommendations
- Any required external components (pull-up resistors, etc.)
- Safety warnings
- Verification steps

Return a JSON array with this exact structure:
[
  {
    "component_pin": "Pin on the component",
    "board_pin": "Pin on {$targetBoard}",
    "wire_color": "Recommended wire color",
    "notes": "Any special notes",
    "requires_resistor": false,
    "resistor_value": null
  }
]

Also include a "warnings" array and "verification_steps" array in the response:
{
  "connections": [...],
  "warnings": ["Warning 1", "Warning 2"],
  "verification_steps": ["Step 1", "Step 2"]
}

Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Build prompt for datasheet Q&A.
     */
    protected function buildDatasheetQaPrompt(string $datasheetContent, string $question): string
    {
        $truncatedContent = substr($datasheetContent, 0, 10000); // Limit context
        
        return <<<PROMPT
Based on the following datasheet content, answer the user's question accurately and concisely.

**Datasheet Content:**
{$truncatedContent}

**User Question:** {$question}

Provide a clear, direct answer. If the information is not in the datasheet, say so. Include relevant quotes or section references when helpful.

If the question involves technical specifications, provide exact values with units. If it involves usage, provide practical guidance.

Answer:
PROMPT;
    }
}
