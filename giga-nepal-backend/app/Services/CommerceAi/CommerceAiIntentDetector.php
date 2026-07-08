<?php

namespace App\Services\CommerceAi;

class CommerceAiIntentDetector
{
    public function detect(string $prompt): string
    {
        $text = mb_strtolower($prompt);

        return match (true) {
            str_contains($text, '4wd') || str_contains($text, 'robot car') => '4wd_robot_car',
            str_contains($text, 'irrigation') || str_contains($text, 'soil moisture') => 'smart_irrigation',
            str_contains($text, 'school') || str_contains($text, 'lab kit') => 'school_electronics_lab',
            str_contains($text, 'solar') || str_contains($text, 'backup') => 'solar_backup',
            str_contains($text, 'cctv') || str_contains($text, 'access control') => 'cctv_access_control',
            str_contains($text, 'esp32') || str_contains($text, 'arduino') => 'board_comparison',
            default => 'generic_project_bom',
        };
    }
}
