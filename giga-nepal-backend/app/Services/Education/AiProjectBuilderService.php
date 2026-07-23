<?php

namespace App\Services\Education;

use App\Models\Education\EducationProject;
use App\Models\Education\BomLine;
use App\Models\Education\SensorKnowledge;
use App\Models\Marketplace\Product;
use App\Services\Product\MpnAutocompleteService;
use App\Services\Product\MpnNormalizationService;
use App\Services\Bom\BomComponentMatcher;

class AiProjectBuilderService
{
    public function __construct(
        private EducationProjectService $projectService,
        private MpnAutocompleteService $autocomplete,
        private MpnNormalizationService $normalization,
        private BomComponentMatcher $matcher,
    ) {}

    /**
     * Detect project intent from user prompt.
     */
    public function detectIntent(string $prompt): array
    {
        $lower = strtolower($prompt);

        // Detect controller
        $controller = null;
        if (str_contains($lower, 'esp32')) $controller = 'ESP32';
        elseif (str_contains($lower, 'arduino')) $controller = 'Arduino';
        elseif (str_contains($lower, 'raspberry pi') || str_contains($lower, 'rpi')) $controller = 'Raspberry Pi';
        elseif (str_contains($lower, 'stm32')) $controller = 'STM32';
        elseif (str_contains($lower, 'micro:bit')) $controller = 'Micro:bit';
        elseif (str_contains($lower, 'rp2040') || str_contains($lower, 'pico')) $controller = 'RP2040';
        elseif (str_contains($lower, 'esp8266')) $controller = 'ESP8266';

        // Detect skill level
        $skillLevel = 'beginner';
        if (str_contains($lower, 'advanced') || str_contains($lower, 'expert') || str_contains($lower, 'university')) $skillLevel = 'advanced';
        elseif (str_contains($lower, 'intermediate') || str_contains($lower, 'grade 10') || str_contains($lower, 'grade 11') || str_contains($lower, 'grade 12')) $skillLevel = 'intermediate';

        // Detect grade
        $gradeLevel = null;
        if (preg_match('/grade\s+(\d+)/i', $prompt, $m)) $gradeLevel = 'Grade ' . $m[1];

        // Detect budget
        $budget = null;
        if (preg_match('/below\s+(?:npr|usd|\$)\s*([\d,]+)/i', $prompt, $m)) {
            $budget = (float) str_replace(',', '', $m[1]);
        }

        // Detect region
        $region = null;
        if (str_contains($lower, 'nepal')) $region = 'Nepal';
        elseif (str_contains($lower, 'india')) $region = 'India';

        // Detect project type
        $projectType = 'generic';
        $projectTypes = [
            'robot' => ['robot', 'robotic', 'motor', 'wheels', 'chassis'],
            'iot' => ['iot', 'internet of things', 'smart', 'connected', 'wireless'],
            'weather' => ['weather', 'temperature', 'humidity', 'barometric'],
            'agriculture' => ['agriculture', 'irrigation', 'soil', 'farming', 'greenhouse'],
            'sensor' => ['sensor', 'monitor', 'detect', 'measure'],
            'automation' => ['automation', 'automate', 'control', 'smart home'],
            'security' => ['security', 'alarm', 'lock', 'rfid', 'attendance'],
            'gps' => ['gps', 'tracker', 'location', 'navigation'],
            'communication' => ['lora', 'bluetooth', 'wifi', 'gsm', 'nfc'],
            'energy' => ['solar', 'battery', 'power', 'energy', 'meter'],
        ];

        foreach ($projectTypes as $type => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) { $projectType = $type; break 2; }
            }
        }

        return [
            'controller' => $controller,
            'skill_level' => $skillLevel,
            'grade_level' => $gradeLevel,
            'budget' => $budget,
            'region' => $region,
            'project_type' => $projectType,
            'original_prompt' => $prompt,
        ];
    }

    /**
     * Find matching project from library or generate BOM.
     */
    public function buildProjectResponse(string $prompt): array
    {
        $intent = $this->detectIntent($prompt);

        // Try to find existing project
        $existingProject = $this->findMatchingProject($intent);

        if ($existingProject) {
            $bomData = $this->projectService->getProjectBomWithLivePricing($existingProject->id);
            return $this->formatProjectResponse($existingProject, $bomData, $intent, true);
        }

        // Generate BOM based on intent
        return $this->generateProjectFromIntent($intent);
    }

    /**
     * Find a matching project from the library.
     */
    private function findMatchingProject(array $intent): ?EducationProject
    {
        $q = EducationProject::published()->with(['bomLines.preferredProduct']);

        if ($intent['controller']) {
            $q->where('main_controller', $intent['controller']);
        }
        if ($intent['project_type'] !== 'generic') {
            $q->where('category', 'like', "%{$intent['project_type']}%");
        }
        if ($intent['skill_level']) {
            $q->where('skill_level', $intent['skill_level']);
        }

        return $q->orderByDesc('rating_avg')->orderByDesc('view_count')->first();
    }

    /**
     * Generate a project response from intent (no matching library project).
     */
    private function generateProjectFromIntent(array $intent): array
    {
        $controller = $intent['controller'] ?? 'ESP32';
        $projectType = $intent['project_type'];

        $bomSpec = $this->getBomSpecForProject($projectType, $controller);

        // Match BOM items against catalog
        $matchedItems = [];
        foreach ($bomSpec['items'] as $item) {
            $searchResult = $this->autocomplete->search($item['search_query'] ?? $item['name'], null, 3);
            $match = $searchResult['results'][0] ?? null;

            $matchedItems[] = [
                'name' => $item['name'],
                'role' => $item['role'],
                'quantity' => $item['quantity'],
                'required' => $item['required'] ?? true,
                'product_id' => $match['product_id'] ?? null,
                'product_name' => $match['name'] ?? null,
                'product_mpn' => $match['mpn'] ?? null,
                'product_sku' => $match['sku'] ?? null,
                'match_type' => $match ? 'catalog_match' : 'suggestion',
            ];
        }

        return [
            'type' => 'generated_project',
            'intent' => $intent,
            'project' => [
                'title' => $this->generateTitle($projectType, $controller, $intent),
                'controller' => $controller,
                'project_type' => $projectType,
                'skill_level' => $intent['skill_level'],
                'grade_level' => $intent['grade_level'],
                'summary' => $this->generateSummary($projectType, $controller, $intent),
                'difficulty' => ucfirst($intent['skill_level']),
                'estimated_duration' => $this->estimateDuration($projectType, $intent['skill_level']),
                'estimated_cost' => null,
            ],
            'bom' => $matchedItems,
            'build_guide' => $this->getBuildGuide($projectType, $controller),
            'code' => $this->getCodeSample($projectType, $controller),
            'lms' => ['enrollment_url' => null, 'course_id' => null],
            'actions' => [
                ['type' => 'add_bom_to_cart', 'label' => 'Add Complete BOM to Cart'],
                ['type' => 'create_rfq', 'label' => 'Create RFQ for Missing Items'],
                ['type' => 'save_project', 'label' => 'Save Project'],
                ['type' => 'download_bom', 'label' => 'Download BOM'],
                ['type' => 'download_code', 'label' => 'Download Code'],
            ],
            'source' => 'ai_generated',
            'verification_status' => 'ai_generated',
            'disclaimer' => 'This project was generated by AI. Verify component compatibility and safety before building.',
        ];
    }

    /**
     * Get BOM specification for a project type.
     */
    private function getBomSpecForProject(string $projectType, string $controller): array
    {
        $specs = [
            'robot' => [
                'items' => [
                    ['name' => "{$controller} Development Board", 'role' => 'Main Controller', 'quantity' => 1, 'search_query' => $controller, 'required' => true],
                    ['name' => 'L298N Motor Driver', 'role' => 'Motor Driver', 'quantity' => 1, 'search_query' => 'L298N motor driver', 'required' => true],
                    ['name' => 'DC Gear Motor 12V', 'role' => 'Motor', 'quantity' => 4, 'search_query' => '12V DC gear motor', 'required' => true],
                    ['name' => 'Robot Wheels', 'role' => 'Wheels', 'quantity' => 4, 'search_query' => 'robot wheel', 'required' => true],
                    ['name' => 'Robot Chassis Kit', 'role' => 'Chassis', 'quantity' => 1, 'search_query' => 'robot chassis', 'required' => true],
                    ['name' => 'Battery Pack 12V', 'role' => 'Power', 'quantity' => 1, 'search_query' => '12V battery pack', 'required' => true],
                    ['name' => 'Jumper Wires', 'role' => 'Wiring', 'quantity' => 1, 'search_query' => 'jumper wire', 'required' => true],
                    ['name' => 'HC-SR04 Ultrasonic Sensor', 'role' => 'Sensor', 'quantity' => 1, 'search_query' => 'HC-SR04', 'required' => false],
                ],
            ],
            'iot' => [
                'items' => [
                    ['name' => "{$controller} Development Board", 'role' => 'Main Controller', 'quantity' => 1, 'search_query' => $controller, 'required' => true],
                    ['name' => 'Breadboard', 'role' => 'Prototyping', 'quantity' => 1, 'search_query' => 'breadboard', 'required' => true],
                    ['name' => 'Jumper Wires', 'role' => 'Wiring', 'quantity' => 1, 'search_query' => 'jumper wire', 'required' => true],
                    ['name' => 'LED', 'role' => 'Output', 'quantity' => 5, 'search_query' => 'LED', 'required' => true],
                    ['name' => 'Resistor Kit', 'role' => 'Passive', 'quantity' => 1, 'search_query' => 'resistor kit', 'required' => true],
                    ['name' => 'Power Supply', 'role' => 'Power', 'quantity' => 1, 'search_query' => '5V power supply', 'required' => true],
                ],
            ],
            'weather' => [
                'items' => [
                    ['name' => "{$controller} Development Board", 'role' => 'Main Controller', 'quantity' => 1, 'search_query' => $controller, 'required' => true],
                    ['name' => 'DHT22 Temperature Humidity Sensor', 'role' => 'Sensor', 'quantity' => 1, 'search_query' => 'DHT22', 'required' => true],
                    ['name' => 'BMP280 Pressure Sensor', 'role' => 'Sensor', 'quantity' => 1, 'search_query' => 'BMP280', 'required' => false],
                    ['name' => 'OLED Display 128x64', 'role' => 'Display', 'quantity' => 1, 'search_query' => 'OLED 128x64', 'required' => true],
                    ['name' => 'Breadboard', 'role' => 'Prototyping', 'quantity' => 1, 'search_query' => 'breadboard', 'required' => true],
                    ['name' => 'Jumper Wires', 'role' => 'Wiring', 'quantity' => 1, 'search_query' => 'jumper wire', 'required' => true],
                ],
            ],
            'agriculture' => [
                'items' => [
                    ['name' => "{$controller} Development Board", 'role' => 'Main Controller', 'quantity' => 1, 'search_query' => $controller, 'required' => true],
                    ['name' => 'Soil Moisture Sensor', 'role' => 'Sensor', 'quantity' => 2, 'search_query' => 'soil moisture sensor', 'required' => true],
                    ['name' => 'Relay Module', 'role' => 'Actuator', 'quantity' => 1, 'search_query' => 'relay module', 'required' => true],
                    ['name' => 'Mini Water Pump', 'role' => 'Actuator', 'quantity' => 1, 'search_query' => 'mini water pump', 'required' => true],
                    ['name' => 'OLED Display', 'role' => 'Display', 'quantity' => 1, 'search_query' => 'OLED display', 'required' => false],
                    ['name' => 'Power Supply 12V', 'role' => 'Power', 'quantity' => 1, 'search_query' => '12V power supply', 'required' => true],
                ],
            ],
            'security' => [
                'items' => [
                    ['name' => "{$controller} Development Board", 'role' => 'Main Controller', 'quantity' => 1, 'search_query' => $controller, 'required' => true],
                    ['name' => 'RC522 RFID Module', 'role' => 'Reader', 'quantity' => 1, 'search_query' => 'RC522 RFID', 'required' => true],
                    ['name' => 'RFID Cards', 'role' => 'Credential', 'quantity' => 5, 'search_query' => 'RFID card', 'required' => true],
                    ['name' => 'Servo Motor SG90', 'role' => 'Actuator', 'quantity' => 1, 'search_query' => 'SG90 servo', 'required' => false],
                    ['name' => 'Buzzer', 'role' => 'Alert', 'quantity' => 1, 'search_query' => 'buzzer', 'required' => true],
                    ['name' => 'LED', 'role' => 'Indicator', 'quantity' => 3, 'search_query' => 'LED', 'required' => true],
                ],
            ],
        ];

        return $specs[$projectType] ?? $specs['iot'];
    }

    private function generateTitle(string $type, string $controller, array $intent): string
    {
        $grade = $intent['grade_level'] ? " for {$intent['grade_level']}" : '';
        return ucfirst($type) . " Project with {$controller}{$grade}";
    }

    private function generateSummary(string $type, string $controller, array $intent): string
    {
        return "Build a {$type} project using {$controller}. This project teaches fundamental electronics and programming concepts.";
    }

    private function estimateDuration(string $type, string $skillLevel): string
    {
        return match($skillLevel) {
            'beginner' => '2-4 hours',
            'intermediate' => '4-8 hours',
            'advanced' => '8-16 hours',
            default => '4-8 hours',
        };
    }

    private function getBuildGuide(string $type, string $controller): array
    {
        return [
            'steps' => [
                ['title' => 'Gather Components', 'description' => 'Collect all BOM items and verify completeness.'],
                ['title' => 'Prepare Controller', 'description' => "Set up {$controller} development environment."],
                ['title' => 'Wire Components', 'description' => 'Connect sensors and actuators according to wiring diagram.'],
                ['title' => 'Upload Code', 'description' => 'Flash the provided code to the controller.'],
                ['title' => 'Test', 'description' => 'Power on and verify all functions work correctly.'],
            ],
        ];
    }

    private function getCodeSample(string $type, string $controller): array
    {
        return [
            'title' => "{$controller} {$type} Code",
            'language' => 'arduino',
            'board' => $controller,
            'source_code' => "// {$controller} {$type} project\n// Upload this code to your {$controller}\n\nvoid setup() {\n  Serial.begin(115200);\n  // Initialize components\n}\n\nvoid loop() {\n  // Main program loop\n}",
            'verification_status' => 'unverified',
        ];
    }

    private function formatProjectResponse(EducationProject $project, array $bomData, array $intent, bool $fromLibrary): array
    {
        return [
            'type' => 'library_project',
            'intent' => $intent,
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'slug' => $project->slug,
                'controller' => $project->main_controller,
                'project_type' => $project->category,
                'skill_level' => $project->skill_level,
                'grade_level' => $project->grade_level,
                'summary' => $project->summary,
                'difficulty' => $project->difficulty_label,
                'estimated_duration' => $project->duration_label,
                'estimated_cost' => $bomData['total_cost'],
                'currency' => $bomData['currency'],
                'coverage_pct' => $bomData['coverage_pct'],
            ],
            'bom' => $bomData['lines'],
            'bom_summary' => [
                'total_lines' => $bomData['total_lines'],
                'required_lines' => $bomData['required_lines'],
                'total_cost' => $bomData['total_cost'],
                'required_cost' => $bomData['required_cost'],
                'currency' => $bomData['currency'],
            ],
            'code' => $this->projectService->getProjectCode($project->id),
            'build_guide' => [
                'wiring' => $project->wiring_instructions,
                'assembly' => $project->assembly_steps,
                'testing' => $project->testing_procedure,
                'troubleshooting' => $project->troubleshooting,
            ],
            'lms' => [
                'course_id' => $project->lms_course_id,
                'enrollment_url' => $project->course ? "/lms/courses/{$project->course->id}" : null,
            ],
            'actions' => [
                ['type' => 'add_bom_to_cart', 'label' => 'Add Complete BOM to Cart'],
                ['type' => 'create_rfq', 'label' => 'Create RFQ for Missing Items'],
                ['type' => 'save_project', 'label' => 'Save Project'],
                ['type' => 'download_bom', 'label' => 'Download BOM'],
                ['type' => 'download_code', 'label' => 'Download Code'],
                ['type' => 'enroll_course', 'label' => 'Enroll in Course', 'enabled' => $project->lms_course_id !== null],
            ],
            'source' => 'library_project',
            'verification_status' => $project->verification_status,
        ];
    }
}
