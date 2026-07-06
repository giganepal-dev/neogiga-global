<?php

namespace App\Services;

use App\Models\Marketplace\Product;
use App\Models\AiBomBuild;
use App\Models\AiProductRecommendation;
use App\Models\LmsProject;
use Illuminate\Support\Collection;

class BomBuilderService
{
    protected array $componentRules = [
        '4wd robot car' => [
            [
                'name' => 'ESP32 Development Board',
                'category' => 'Microcontrollers',
                'quantity' => 1,
                'reason' => 'Main controller with WiFi/Bluetooth capability',
                'keywords' => ['esp32', 'microcontroller', 'wifi', 'bluetooth']
            ],
            [
                'name' => 'L298N Motor Driver',
                'category' => 'Motor Drivers',
                'quantity' => 1,
                'reason' => 'Controls 4 DC motors for 4WD movement',
                'keywords' => ['motor driver', 'l298n', 'dc motor']
            ],
            [
                'name' => '12V DC Gear Motor',
                'category' => 'Robot Motors',
                'quantity' => 4,
                'reason' => 'Provides torque and speed for each wheel',
                'keywords' => ['dc motor', 'gear motor', '12v']
            ],
            [
                'name' => '4WD Robot Chassis Kit',
                'category' => 'Robot Chassis',
                'quantity' => 1,
                'reason' => 'Frame and structure for the robot',
                'keywords' => ['chassis', '4wd', 'robot frame']
            ],
            [
                'name' => '18650 Li-ion Battery',
                'category' => 'Batteries',
                'quantity' => 4,
                'reason' => 'Power source for motors and electronics',
                'keywords' => ['battery', '18650', 'li-ion']
            ],
            [
                'name' => '2S BMS Board',
                'category' => 'BMS',
                'quantity' => 1,
                'reason' => 'Battery management and protection',
                'keywords' => ['bms', 'battery protection']
            ],
            [
                'name' => 'Ultrasonic Sensor HC-SR04',
                'category' => 'Sensors',
                'quantity' => 1,
                'reason' => 'Obstacle detection and avoidance',
                'keywords' => ['ultrasonic', 'sensor', 'hc-sr04']
            ],
            [
                'name' => 'Jumper Wire Set',
                'category' => 'Cables',
                'quantity' => 1,
                'reason' => 'Connections between components',
                'keywords' => ['jumper wires', 'cables']
            ],
            [
                'name' => 'Switch',
                'category' => 'Switches',
                'quantity' => 1,
                'reason' => 'Power on/off control',
                'keywords' => ['switch', 'power']
            ]
        ]
    ];

    public function buildFromGoal(string $goal): AiBomBuild
    {
        $goalLower = strtolower($goal);
        $components = $this->findMatchingComponents($goalLower);
        
        $bomBuild = AiBomBuild::create([
            'session_id' => null,
            'user_goal' => $goal,
            'total_estimated_price' => 0,
            'currency_code' => 'USD',
            'status' => 'draft'
        ]);

        foreach ($components as $component) {
            $product = $this->findProductByKeywords($component['keywords']);
            
            AiProductRecommendation::create([
                'ai_bom_build_id' => $bomBuild->id,
                'product_id' => $product?->id,
                'product_name_fallback' => $component['name'],
                'quantity' => $component['quantity'],
                'reason' => $component['reason'],
                'category_suggestion' => $component['category'],
                'estimated_price' => 0,
                'is_available' => $product !== null,
                'sort_order' => count($bomBuild->items) + 1
            ]);
        }

        $bomBuild->calculateTotal();
        
        return $bomBuild->fresh();
    }

    protected function findMatchingComponents(string $goal): array
    {
        foreach ($this->componentRules as $keyword => $components) {
            if (str_contains($goal, $keyword)) {
                return $components;
            }
        }
        
        return $this->componentRules['4wd robot car'] ?? [];
    }

    protected function findProductByKeywords(array $keywords): ?Product
    {
        $query = Product::query();
        
        foreach ($keywords as $index => $keyword) {
            if ($index === 0) {
                $query->where('name', 'LIKE', "%{$keyword}%");
            } else {
                $query->orWhere('name', 'LIKE', "%{$keyword}%")
                      ->orWhereHas('specs', function ($q) use ($keyword) {
                          $q->where('value', 'LIKE', "%{$keyword}%");
                      });
            }
        }
        
        return $query->first();
    }

    public function getLmsRecommendation(AiBomBuild $bomBuild): ?LmsProject
    {
        $goalLower = strtolower($bomBuild->user_goal);
        
        if (str_contains($goalLower, '4wd') || str_contains($goalLower, 'robot car')) {
            return LmsProject::where('slug', 'like', '%4wd-robot%')->first();
        }
        
        return null;
    }

    public function generateSampleCode(AiBomBuild $bomBuild): string
    {
        $goalLower = strtolower($bomBuild->user_goal);
        
        if (str_contains($goalLower, '4wd') || str_contains($goalLower, 'robot car')) {
            return $this->generate4wdRobotCode();
        }
        
        return '// Sample code not available for this project yet.';
    }

    protected function generate4wdRobotCode(): string
    {
        return <<<'CODE'
// ESP32 4WD Robot Car Control Code
// NeoGiga AI Commerce - Sample Code

#include <WiFi.h>

// Motor pins for L298N
const int IN1 = 27;
const int IN2 = 26;
const int IN3 = 25;
const int IN4 = 33;
const int ENA = 14;
const int ENB = 12;

// Ultrasonic sensor pins
const int TRIG_PIN = 5;
const int ECHO_PIN = 18;

void setup() {
  Serial.begin(115200);
  
  // Setup motor pins
  pinMode(IN1, OUTPUT);
  pinMode(IN2, OUTPUT);
  pinMode(IN3, OUTPUT);
  pinMode(IN4, OUTPUT);
  pinMode(ENA, OUTPUT);
  pinMode(ENB, OUTPUT);
  
  // Setup ultrasonic pins
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  
  Serial.println("NeoGiga 4WD Robot Ready!");
}

void loop() {
  long distance = readDistance();
  
  if (distance > 20) {
    moveForward();
  } else {
    stopMotors();
    delay(500);
    turnRight();
    delay(500);
  }
  
  delay(100);
}

long readDistance() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);
  
  long duration = pulseIn(ECHO_PIN, HIGH);
  return duration * 0.034 / 2;
}

void moveForward() {
  digitalWrite(IN1, HIGH);
  digitalWrite(IN2, LOW);
  digitalWrite(IN3, HIGH);
  digitalWrite(IN4, LOW);
  analogWrite(ENA, 200);
  analogWrite(ENB, 200);
}

void stopMotors() {
  digitalWrite(IN1, LOW);
  digitalWrite(IN2, LOW);
  digitalWrite(IN3, LOW);
  digitalWrite(IN4, LOW);
}

void turnRight() {
  digitalWrite(IN1, HIGH);
  digitalWrite(IN2, LOW);
  digitalWrite(IN3, LOW);
  digitalWrite(IN4, HIGH);
  analogWrite(ENA, 150);
  analogWrite(ENB, 150);
}
CODE;
    }
}
