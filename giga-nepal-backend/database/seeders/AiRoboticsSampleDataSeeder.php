<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiRoboticsSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedManufacturers();
        $this->seedRobotModels();
        $this->seedAiModels();
        $this->seedLearningPaths();
        $this->seedEvents();
        $this->seedArticles();
        $this->seedIntegrators();
        $this->seedInstitutionalPackages();
    }

    private function seedManufacturers(): void
    {
        $manufacturers = [
            ['name' => 'Boston Dynamics', 'slug' => 'boston-dynamics', 'country' => 'USA', 'description' => 'Pioneer in dynamic robots that navigate the real world. Creators of Spot, Atlas, and Stretch.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.bostondynamics.com'],
            ['name' => 'Universal Robots', 'slug' => 'universal-robots', 'country' => 'Denmark', 'description' => 'World leader in collaborative robots (cobots). UR cobots are used in 50,000+ installations worldwide.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.universal-robots.com'],
            ['name' => 'NVIDIA', 'slug' => 'nvidia', 'country' => 'USA', 'description' => 'Leading AI computing platform. Jetson series for edge AI, Isaac for robot simulation, Omniverse for digital twins.', 'is_ai_hardware_manufacturer' => true, 'is_software_provider' => true, 'website_url' => 'https://www.nvidia.com'],
            ['name' => 'Raspberry Pi Foundation', 'slug' => 'raspberry-pi', 'country' => 'UK', 'description' => 'Affordable computing for education and prototyping. Raspberry Pi 5, Compute Module, and AI Kit.', 'is_ai_hardware_manufacturer' => true, 'website_url' => 'https://www.raspberrypi.com'],
            ['name' => 'DJI', 'slug' => 'dji', 'country' => 'China', 'description' => 'World leader in drone technology. Matrice, Mavic, and Agras series for commercial and industrial use.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.dji.com'],
            ['name' => 'ABB Robotics', 'slug' => 'abb-robotics', 'country' => 'Switzerland', 'description' => 'Industrial robots and automation. IRB series, YuMi collaborative robot, and FlexPicker.', 'is_robot_manufacturer' => true, 'website_url' => 'https://new.abb.com/products/robotics'],
            ['name' => 'Fetch Robotics (Zebra)', 'slug' => 'fetch-robotics', 'country' => 'USA', 'description' => 'Autonomous mobile robots for warehouse logistics. FetchCart, Fetch500, and PalletTransporter.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.fetchrobotics.com'],
            ['name' => 'Intel', 'slug' => 'intel', 'country' => 'USA', 'description' => 'AI processors and edge computing. Intel NCS, Movidius, and Arc GPUs for AI inference.', 'is_ai_hardware_manufacturer' => true, 'website_url' => 'https://www.intel.com'],
            ['name' => 'Google DeepMind', 'slug' => 'google-deepmind', 'country' => 'UK', 'description' => 'AI research lab. Gemini, AlphaFold, RT-2 for robotics, and TensorFlow/JAX frameworks.', 'is_software_provider' => true, 'website_url' => 'https://deepmind.google'],
            ['name' => 'OpenAI', 'slug' => 'openai', 'country' => 'USA', 'description' => 'AI research company. GPT-4, DALL-E, Whisper, and robotics research through partnerships.', 'is_software_provider' => true, 'website_url' => 'https://openai.com'],
            ['name' => 'KUKA', 'slug' => 'kuka', 'country' => 'Germany', 'description' => 'Industrial robot manufacturer. KR AGILUS, LBR iiwa collaborative robot, and KR QUANTEC.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.kuka.com'],
            ['name' => 'FANUC', 'slug' => 'fanuc', 'country' => 'Japan', 'description' => 'World\'s largest industrial robot manufacturer. R-30iB Plus controller, M-10iD, and CRX collaborative robots.', 'is_robot_manufacturer' => true, 'website_url' => 'https://www.fanucamerica.com'],
        ];

        foreach ($manufacturers as $m) {
            DB::table('ai_robotics_manufacturers')->updateOrInsert(
                ['slug' => $m['slug']],
                $m + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedRobotModels(): void
    {
        $nvidiaId = DB::table('ai_robotics_manufacturers')->where('slug', 'nvidia')->value('id');
        $rpiId = DB::table('ai_robotics_manufacturers')->where('slug', 'raspberry-pi')->value('id');
        $bostonId = DB::table('ai_robotics_manufacturers')->where('slug', 'boston-dynamics')->value('id');
        $urId = DB::table('ai_robotics_manufacturers')->where('slug', 'universal-robots')->value('id');
        $djiId = DB::table('ai_robotics_manufacturers')->where('slug', 'dji')->value('id');
        $intelId = DB::table('ai_robotics_manufacturers')->where('slug', 'intel')->value('id');

        $armType = DB::table('robot_types')->where('slug', 'robotic-arm')->value('id');
        $amrType = DB::table('robot_types')->where('slug', 'amr')->value('id');
        $droneType = DB::table('robot_types')->where('slug', 'drone')->value('id');
        $eduType = DB::table('robot_types')->where('slug', 'educational')->value('id');
        $chassisType = DB::table('robot_types')->where('slug', 'robot-chassis')->value('id');

        $manufacturingApp = DB::table('robot_applications')->where('slug', 'manufacturing')->value('id');
        $warehouseApp = DB::table('robot_applications')->where('slug', 'warehouse-logistics')->value('id');
        $eduApp = DB::table('robot_applications')->where('slug', 'education')->value('id');
        $researchApp = DB::table('robot_applications')->where('slug', 'research')->value('id');

        $robots = [
            [
                'name' => 'NVIDIA Jetson AGX Orin Developer Kit',
                'slug' => 'nvidia-jetson-agx-orin',
                'model_number' => 'Jetson-AGX-Orin-64GB',
                'manufacturer_id' => $nvidiaId,
                'robot_type_id' => $chassisType,
                'description' => 'The most powerful edge AI development kit. 275 TOPS of AI performance with 12-core Arm Cortex-A78AE CPU and 2048-core NVIDIA Ampere GPU.',
                'short_description' => '275 TOPS edge AI development kit for robotics and autonomous machines',
                'compute_platform' => 'NVIDIA Ampere GPU + Arm Cortex-A78AE',
                'ai_accelerator' => '2048-core GPU, 64 Tensor Cores',
                'operating_system' => 'JetPack SDK (Ubuntu 20.04)',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'C++', 'CUDA', 'TensorRT'],
                'sdk_available' => true,
                'api_available' => true,
                'simulation_support' => true,
                'indoor_outdoor' => 'both',
                'global_price' => 1999.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['CSI Camera', 'USB Camera', 'IMU', 'LiDAR support'],
            ],
            [
                'name' => 'NVIDIA Jetson Orin Nano',
                'slug' => 'nvidia-jetson-orin-nano',
                'model_number' => 'Jetson-Orin-Nano-8GB',
                'manufacturer_id' => $nvidiaId,
                'robot_type_id' => $chassisType,
                'description' => 'Entry-level edge AI with 40 TOPS. Perfect for AI-powered robots, drones, and smart devices.',
                'short_description' => '40 TOPS entry-level edge AI for robots and drones',
                'compute_platform' => 'Arm Cortex-A78AE + NVIDIA GPU',
                'ai_accelerator' => '1024-core GPU, 32 Tensor Cores',
                'operating_system' => 'JetPack SDK',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'C++', 'CUDA'],
                'sdk_available' => true,
                'indoor_outdoor' => 'both',
                'global_price' => 499.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['CSI Camera', 'IMU', 'GPIO'],
            ],
            [
                'name' => 'Raspberry Pi 5 AI Kit',
                'slug' => 'raspberry-pi-5-ai-kit',
                'model_number' => 'RPI5-AI-KIT-16GB',
                'manufacturer_id' => $rpiId,
                'robot_type_id' => $eduType,
                'description' => 'Raspberry Pi 5 with M.2 HAT+ and AI Camera. 8GB RAM, quad-core Arm Cortex-A76, perfect for learning AI and robotics.',
                'short_description' => 'Affordable AI development kit for education and prototyping',
                'compute_platform' => 'Arm Cortex-A76 quad-core 2.4GHz',
                'ai_accelerator' => 'AI Camera (Hailo-8L)',
                'operating_system' => 'Raspberry Pi OS (Linux)',
                'ros2_support' => true,
                'ros_support' => false,
                'programming_languages' => ['Python', 'C++', 'Scratch', 'JavaScript'],
                'sdk_available' => true,
                'indoor_outdoor' => 'indoor',
                'global_price' => 120.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['AI Camera', 'GPIO', 'I2C', 'SPI'],
            ],
            [
                'name' => 'UR5e Collaborative Robot',
                'slug' => 'ur5e-cobot',
                'model_number' => 'UR5e',
                'manufacturer_id' => $urId,
                'robot_type_id' => $armType,
                'description' => 'Flexible collaborative robot with 5kg payload and 850mm reach. Easy to program, safe for human-robot collaboration.',
                'short_description' => '5kg payload collaborative robot for flexible manufacturing',
                'payload_kg' => 5.00,
                'reach_mm' => 850.00,
                'degrees_of_freedom' => 6,
                'weight_kg' => 20.60,
                'speed_mps' => 1.00,
                'compute_platform' => 'UR+ Controller',
                'operating_system' => 'UR OS',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'URScript', 'C++'],
                'sdk_available' => true,
                'api_available' => true,
                'indoor_outdoor' => 'indoor',
                'ip_rating' => 'IP54',
                'global_price' => 35000.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['Force/Torque Sensor', 'Safety Scanner', 'Camera Mount'],
            ],
            [
                'name' => 'Spot Robot',
                'slug' => 'boston-dynamics-spot',
                'model_number' => 'Spot',
                'manufacturer_id' => $bostonId,
                'robot_type_id' => $amrType,
                'description' => 'Agile mobile robot for industrial inspection, construction monitoring, and research. 360° perception, autonomous navigation.',
                'short_description' => 'Agile quadruped robot for inspection and research',
                'payload_kg' => 14.00,
                'weight_kg' => 32.50,
                'speed_mps' => 1.60,
                'battery_runtime_min' => 90,
                'charging_time_min' => 60,
                'compute_platform' => 'NVIDIA Jetson',
                'operating_system' => 'SpotOS',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'C++'],
                'sdk_available' => true,
                'api_available' => true,
                'simulation_support' => true,
                'indoor_outdoor' => 'both',
                'ip_rating' => 'IP54',
                'global_price' => 74500.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['360° Perception', 'Stereo Cameras', 'IMU', 'LIDAR'],
                'camera_system' => '5 stereo pairs + 1 front-facing',
                'documentation_url' => 'https://dev.bostondynamics.com',
            ],
            [
                'name' => 'DJI Matrice 350 RTK',
                'slug' => 'dji-matrice-350-rtk',
                'model_number' => 'M350-RTK',
                'manufacturer_id' => $djiId,
                'robot_type_id' => $droneType,
                'description' => 'Enterprise drone for mapping, inspection, and public safety. IP55 rated, 55-minute flight time, dual operator support.',
                'short_description' => 'Enterprise drone for mapping and industrial inspection',
                'weight_kg' => 6.47,
                'speed_mps' => 23.00,
                'battery_runtime_min' => 55,
                'charging_time_min' => 70,
                'compute_platform' => 'DJI Flight Controller',
                'operating_system' => 'DJI Pilot 2',
                'ros2_support' => false,
                'programming_languages' => ['Python', 'DJI SDK'],
                'sdk_available' => true,
                'api_available' => true,
                'indoor_outdoor' => 'outdoor',
                'ip_rating' => 'IP55',
                'global_price' => 11000.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'sensors' => ['RTK GNSS', 'FPV Camera', 'Laser Rangefinder', '红外传感'],
                'camera_system' => 'Zenmuse H30T / L2 / P1 support',
            ],
            [
                'name' => 'Intel RealSense D455',
                'slug' => 'intel-realsense-d455',
                'model_number' => 'D455',
                'manufacturer_id' => $intelId,
                'robot_type_id' => $chassisType,
                'description' => 'Stereo depth camera for robotics and AI. 6m range, 120° FOV, RGB-D for navigation and obstacle avoidance.',
                'short_description' => 'Stereo depth camera for robot navigation and perception',
                'compute_platform' => 'USB 3.0 Host',
                'operating_system' => 'Cross-platform SDK',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'C++', 'C#'],
                'sdk_available' => true,
                'indoor_outdoor' => 'indoor',
                'global_price' => 349.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => false,
                'sensors' => ['Stereo Depth', 'RGB Camera', 'IMU'],
                'camera_system' => 'RGB + Depth 1280x720',
            ],
            [
                'name' => 'NVIDIA Isaac Sim',
                'slug' => 'nvidia-isaac-sim',
                'model_number' => 'Isaac-Sim-4.0',
                'manufacturer_id' => $nvidiaId,
                'robot_type_id' => $amrType,
                'description' => 'Robot simulation platform powered by Omniverse. Physically accurate simulation, synthetic data generation, reinforcement learning.',
                'short_description' => 'Robot simulation and digital twin platform',
                'compute_platform' => 'NVIDIA RTX GPU required',
                'operating_system' => 'Ubuntu 20.04/22.04',
                'ros2_support' => true,
                'ros_support' => true,
                'programming_languages' => ['Python', 'C++', 'USD', 'Omniverse'],
                'sdk_available' => true,
                'api_available' => true,
                'simulation_support' => true,
                'digital_twin_support' => true,
                'indoor_outdoor' => 'both',
                'global_price' => 0.00,
                'currency' => 'USD',
                'is_active' => true,
                'is_featured' => true,
                'documentation_url' => 'https://developer.nvidia.com/isaac-sim',
            ],
        ];

        foreach ($robots as $robot) {
            // JSON-encode array fields for PostgreSQL
            foreach (['sensors', 'programming_languages', 'certifications', 'safety_features', 'images', 'videos', 'seo_meta'] as $field) {
                if (isset($robot[$field]) && is_array($robot[$field])) {
                    $robot[$field] = json_encode($robot[$field]);
                }
            }
            DB::table('robot_models')->updateOrInsert(
                ['slug' => $robot['slug']],
                $robot + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedAiModels(): void
    {
        $models = [
            [
                'name' => 'YOLOv8',
                'slug' => 'yolov8',
                'provider' => 'Ultralytics',
                'model_type' => 'vision',
                'supported_tasks' => ['Object Detection', 'Image Segmentation', 'Pose Estimation', 'Classification'],
                'license_type' => 'open_source',
                'license_name' => 'AGPL-3.0',
                'input_types' => ['Image', 'Video'],
                'output_types' => ['Bounding Boxes', 'Masks', 'Keypoints', 'Labels'],
                'hardware_requirements' => ['NVIDIA GPU (recommended)', 'CPU (slow)'],
                'supported_accelerators' => ['NVIDIA TensorRT', 'ONNX Runtime', 'OpenVINO'],
                'edge_compatible' => true,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Object detection for pick-and-place', 'Quality inspection', 'Safety monitoring'],
                'cv_use_cases' => ['Real-time object detection', 'Video analytics', 'Medical imaging'],
                'documentation_url' => 'https://docs.ultralytics.com',
                'github_url' => 'https://github.com/ultralytics/ultralytics',
                'description' => 'State-of-the-art object detection model. Fast, accurate, and easy to deploy on edge devices.',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Segment Anything (SAM)',
                'slug' => 'segment-anything',
                'provider' => 'Meta AI',
                'model_type' => 'vision',
                'supported_tasks' => ['Image Segmentation', 'Object Segmentation'],
                'license_type' => 'open_source',
                'license_name' => 'Apache 2.0',
                'input_types' => ['Image', 'Point Prompt', 'Box Prompt'],
                'output_types' => ['Masks', 'Segmentation Maps'],
                'hardware_requirements' => ['NVIDIA GPU'],
                'supported_accelerators' => ['NVIDIA TensorRT'],
                'edge_compatible' => true,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Grasp planning', 'Scene understanding', 'Manipulation'],
                'cv_use_cases' => ['Image editing', 'Medical imaging', 'Autonomous driving'],
                'documentation_url' => 'https://segment-anything.com',
                'github_url' => 'https://github.com/facebookresearch/segment-anything',
                'description' => 'Foundation model for image segmentation. Segment any object in any image with minimal prompting.',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Whisper',
                'slug' => 'whisper',
                'provider' => 'OpenAI',
                'model_type' => 'speech',
                'supported_tasks' => ['Speech Recognition', 'Translation', 'Language Identification'],
                'license_type' => 'open_source',
                'license_name' => 'MIT',
                'input_types' => ['Audio'],
                'output_types' => ['Text', 'Translations'],
                'hardware_requirements' => ['NVIDIA GPU (recommended)', 'CPU (slow)'],
                'supported_accelerators' => ['NVIDIA CUDA'],
                'edge_compatible' => true,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Voice commands for robots', 'Transcription of field recordings'],
                'nlp_use_cases' => ['Speech-to-text', 'Real-time translation', 'Meeting transcription'],
                'documentation_url' => 'https://github.com/openai/whisper',
                'github_url' => 'https://github.com/openai/whisper',
                'description' => 'General-purpose speech recognition model. Multilingual, accurate, and fast.',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Stable Diffusion XL',
                'slug' => 'stable-diffusion-xl',
                'provider' => 'Stability AI',
                'model_type' => 'generative',
                'supported_tasks' => ['Text-to-Image', 'Image-to-Image', 'Inpainting'],
                'license_type' => 'open_source',
                'license_name' => 'Stability AI Community License',
                'input_types' => ['Text', 'Image'],
                'output_types' => ['Image'],
                'hardware_requirements' => ['NVIDIA GPU with 8GB+ VRAM'],
                'supported_accelerators' => ['NVIDIA TensorRT', 'ONNX Runtime'],
                'edge_compatible' => false,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Synthetic training data generation', 'Scene visualization'],
                'cv_use_cases' => ['Image generation', 'Design automation', 'Content creation'],
                'documentation_url' => 'https://stability.ai/stable-diffusion',
                'github_url' => 'https://github.com/Stability-AI/generative-models',
                'description' => 'High-quality text-to-image generation model. Open-source alternative to DALL-E.',
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'RT-2 (Robotic Transformer)',
                'slug' => 'rt-2',
                'provider' => 'Google DeepMind',
                'model_type' => 'reinforcement',
                'supported_tasks' => ['Robot Control', 'Visual Question Answering', 'Object Manipulation'],
                'license_type' => 'proprietary',
                'license_name' => 'Research License',
                'input_types' => ['Image', 'Text Instruction'],
                'output_types' => ['Robot Actions', 'Text'],
                'hardware_requirements' => ['Google TPU or NVIDIA GPU'],
                'supported_accelerators' => ['Google TPU', 'NVIDIA GPU'],
                'edge_compatible' => false,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Pick-and-place', 'Object sorting', 'Instruction following'],
                'cv_use_cases' => ['Visual understanding', 'Scene comprehension'],
                'documentation_url' => 'https://robotics-transformer2.github.io',
                'description' => 'Vision-language-action model that directly outputs robot actions from images and text instructions.',
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'LLaMA 3',
                'slug' => 'llama-3',
                'provider' => 'Meta AI',
                'model_type' => 'nlp',
                'supported_tasks' => ['Text Generation', 'Question Answering', 'Summarization', 'Translation'],
                'license_type' => 'open_source',
                'license_name' => 'Llama 3 Community License',
                'input_types' => ['Text'],
                'output_types' => ['Text'],
                'hardware_requirements' => ['NVIDIA GPU (8B: 16GB VRAM, 70B: 2x A100)'],
                'supported_accelerators' => ['NVIDIA CUDA', 'Apple Metal'],
                'edge_compatible' => true,
                'cloud_compatible' => true,
                'robotics_use_cases' => ['Natural language robot control', 'Technical documentation Q&A'],
                'nlp_use_cases' => ['Conversational AI', 'Content generation', 'Code generation'],
                'documentation_url' => 'https://llama.meta.com',
                'github_url' => 'https://github.com/meta-llama/llama3',
                'description' => 'State-of-the-art open-source large language model. Available in 8B, 70B, and 405B parameter sizes.',
                'is_active' => true,
                'is_featured' => true,
            ],
        ];

        foreach ($models as $model) {
            // JSON-encode array fields for PostgreSQL
            foreach (['supported_tasks', 'input_types', 'output_types', 'hardware_requirements', 'supported_accelerators', 'robotics_use_cases', 'cv_use_cases', 'nlp_use_cases', 'seo_meta'] as $field) {
                if (isset($model[$field]) && is_array($model[$field])) {
                    $model[$field] = json_encode($model[$field]);
                }
            }
            DB::table('ai_models')->updateOrInsert(
                ['slug' => $model['slug']],
                $model + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedLearningPaths(): void
    {
        $paths = [
            ['name' => 'Introduction to AI & Robotics', 'slug' => 'intro-ai-robotics', 'level' => 'beginner', 'description' => 'Start your journey into AI and robotics. Learn the fundamentals of electronics, programming, and robot construction.', 'estimated_hours' => 40, 'is_active' => true, 'is_featured' => true],
            ['name' => 'Computer Vision Mastery', 'slug' => 'computer-vision-mastery', 'level' => 'intermediate', 'description' => 'Master computer vision with OpenCV, YOLO, and depth cameras. Build real-time detection and tracking systems.', 'estimated_hours' => 60, 'is_active' => true, 'is_featured' => true],
            ['name' => 'ROS 2 for Robotics', 'slug' => 'ros2-for-robotics', 'level' => 'intermediate', 'description' => 'Learn Robot Operating System 2 for building autonomous robots. Topics include navigation, manipulation, and simulation.', 'estimated_hours' => 80, 'is_active' => true, 'is_featured' => true],
            ['name' => 'Edge AI Deployment', 'slug' => 'edge-ai-deployment', 'level' => 'advanced', 'description' => 'Deploy AI models on edge devices. TensorRT optimization, ONNX conversion, and real-time inference on Jetson and Raspberry Pi.', 'estimated_hours' => 50, 'is_active' => true, 'is_featured' => true],
            ['name' => 'Industrial Robotics Programming', 'slug' => 'industrial-robotics', 'level' => 'advanced', 'description' => 'Program industrial robots (UR, KUKA, FANUC). Safety systems, motion planning, and production integration.', 'estimated_hours' => 70, 'is_active' => true, 'is_featured' => false],
        ];

        foreach ($paths as $path) {
            DB::table('learning_paths')->updateOrInsert(
                ['slug' => $path['slug']],
                $path + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedEvents(): void
    {
        $events = [
            ['name' => 'NeoGiga AI & Robotics Summit 2026', 'slug' => 'ai-robotics-summit-2026', 'event_type' => 'conference', 'description' => 'Annual conference bringing together AI researchers, robot manufacturers, and engineering professionals. Keynotes, workshops, and live demos.', 'location' => 'Kathmandu, Nepal', 'location_type' => 'hybrid', 'starts_at' => '2026-11-15 09:00:00', 'ends_at' => '2026-11-17 17:00:00', 'ticket_price' => 150.00, 'max_attendees' => 500, 'is_active' => true, 'is_featured' => true],
            ['name' => 'Hands-On ROS 2 Workshop', 'slug' => 'ros2-workshop-2026', 'event_type' => 'workshop', 'description' => 'Two-day intensive workshop on ROS 2 fundamentals. Build and program a robot from scratch. Bring your laptop.', 'location' => 'Online (Zoom)', 'location_type' => 'online', 'starts_at' => '2026-08-20 10:00:00', 'ends_at' => '2026-08-21 16:00:00', 'ticket_price' => 50.00, 'max_attendees' => 100, 'is_active' => true, 'is_featured' => true],
            ['name' => 'Student Robotics Competition', 'slug' => 'student-robotics-competition-2026', 'event_type' => 'competition', 'description' => 'Annual robotics competition for university and college students. Build autonomous robots to complete challenges.', 'location' => 'NeoGiga Lab, Kathmandu', 'location_type' => 'offline', 'starts_at' => '2026-10-05 08:00:00', 'ends_at' => '2026-10-06 18:00:00', 'ticket_price' => 0.00, 'max_attendees' => 200, 'is_active' => true, 'is_featured' => true],
        ];

        foreach ($events as $event) {
            DB::table('ai_robotics_events')->updateOrInsert(
                ['slug' => $event['slug']],
                $event + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedArticles(): void
    {
        $articles = [
            ['title' => 'NVIDIA Jetson Orin Nano: The Future of Edge AI', 'slug' => 'nvidia-jetson-orin-nano-review', 'article_type' => 'product_launch', 'excerpt' => 'NVIDIA\'s new Jetson Orin Nano delivers 40 TOPS of AI performance at just $499, making edge AI accessible for everyone.', 'body' => "NVIDIA has unveiled the Jetson Orin Nano, a compact yet powerful AI computing module that delivers 40 TOPS of AI performance at an incredible $499 price point.\n\nThe Jetson Orin Nano features a 6-core Arm Cortex-A78AE CPU and a 1024-core NVIDIA Ampere GPU with 32 Tensor Cores. This combination enables real-time AI inference for applications ranging from autonomous robots to smart cameras.\n\nKey features include support for multiple AI frameworks (TensorRT, PyTorch, TensorFlow), hardware-accelerated video encoding/decoding, and a rich set of I/O interfaces for sensors and actuators.\n\nFor robotics developers, the Jetson Orin Nano offers an ideal balance of performance, power efficiency, and cost. It can run multiple AI models simultaneously while consuming just 7-15 watts of power.", 'status' => 'published', 'published_at' => '2026-07-20 10:00:00', 'is_featured' => true],
            ['title' => 'Building Your First Robot with Raspberry Pi 5', 'slug' => 'build-first-robot-raspberry-pi-5', 'article_type' => 'case_study', 'excerpt' => 'Step-by-step guide to building an autonomous line-following robot using Raspberry Pi 5 and the AI Kit.', 'body' => "In this guide, we'll walk you through building your first autonomous robot using the Raspberry Pi 5 and the new AI Kit.\n\n**What You'll Need:**\n- Raspberry Pi 5 (8GB)\n- AI Camera Module\n- Robot chassis kit\n- Motors and motor driver\n- Battery pack\n\n**Step 1: Assemble the Hardware**\nStart by assembling the robot chassis. Connect the motors to the motor driver, and wire everything to the Raspberry Pi GPIO pins.\n\n**Step 2: Install the Software**\nFlash Raspberry Pi OS to your SD card. Install the AI Kit drivers and the YOLOv8 model.\n\n**Step 3: Program the Robot**\nWrite a Python script that reads the camera feed and uses YOLOv8 to detect objects. Use the detection results to control the motors.\n\n**Step 4: Test and Iterate**\nUpload your code, test in the real world, and refine your algorithm.\n\nTotal build time: 4-6 hours. Cost: Under $200.", 'status' => 'published', 'published_at' => '2026-07-22 14:00:00', 'is_featured' => true],
            ['title' => 'Industrial Robotics Trends in South Asia 2026', 'slug' => 'industrial-robotics-south-asia-2026', 'article_type' => 'research', 'excerpt' => 'Analysis of industrial robotics adoption trends in Nepal, India, Bangladesh, and Sri Lanka.', 'body' => "The industrial robotics market in South Asia is experiencing rapid growth, driven by manufacturing modernization and labor shortages.\n\n**Key Findings:**\n\n1. **India** leads the region with 6,000+ industrial robot installations annually, primarily in automotive and electronics manufacturing.\n\n2. **Nepal** is emerging as a robotics education hub, with 15+ universities offering robotics programs.\n\n3. **Bangladesh** is adopting robots in garment manufacturing, with 500+ installations expected by 2027.\n\n4. **Sri Lanka** focuses on agricultural and service robots for tourism.\n\n**Market Drivers:**\n- Rising labor costs\n- Quality consistency requirements\n- Government automation incentives\n- Growing engineering talent pool\n\n**Challenges:**\n- High initial investment\n- Skills gap in robot programming\n- Infrastructure requirements\n\nThe region is projected to see 25% annual growth in robot installations through 2030.", 'status' => 'published', 'published_at' => '2026-07-24 09:00:00', 'is_featured' => false],
        ];

        foreach ($articles as $article) {
            DB::table('ai_robotics_articles')->updateOrInsert(
                ['slug' => $article['slug']],
                $article + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedIntegrators(): void
    {
        $integrators = [
            ['name' => 'NeoGiga Integration Services', 'slug' => 'neogiga-integration', 'country' => 'Nepal', 'description' => 'Official NeoGiga robotics integration arm. Specializes in laboratory setup, training, and custom robot solutions for South Asian institutions.', 'services' => ['Lab Setup', 'Training', 'Custom Solutions', 'Maintenance', 'Consulting'], 'regions_served' => ['Nepal', 'India', 'Bangladesh', 'Sri Lanka'], 'is_active' => true],
            ['name' => 'RoboTech Solutions', 'slug' => 'robotech-solutions', 'country' => 'India', 'description' => 'Industrial automation and robotics integration company. 10+ years of experience in manufacturing automation.', 'services' => ['Industrial Automation', 'Cobot Integration', 'Vision Systems', 'Conveyor Systems'], 'regions_served' => ['India', 'Nepal', 'Bangladesh'], 'is_active' => true],
        ];

        foreach ($integrators as $intg) {
            // JSON-encode array fields for PostgreSQL
            foreach (['services', 'regions_served', 'certifications', 'seo_meta'] as $field) {
                if (isset($intg[$field]) && is_array($intg[$field])) {
                    $intg[$field] = json_encode($intg[$field]);
                }
            }
            DB::table('ai_robotics_integrators')->updateOrInsert(
                ['slug' => $intg['slug']],
                $intg + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedInstitutionalPackages(): void
    {
        $packages = [
            ['name' => 'School Robotics Starter Lab', 'slug' => 'school-robotics-starter', 'target_institution' => 'school', 'description' => 'Complete robotics lab for schools. Includes 20 robot kits, teacher training, curriculum, and 1-year support.', 'short_description' => 'Everything a school needs to start teaching robotics', 'base_price' => 15000.00, 'currency' => 'USD', 'includes' => ['20x Robot Kits', 'Teacher Training (2 days)', 'Curriculum Materials', '1-Year Support', 'Online Resources'], 'is_active' => true, 'is_featured' => true],
            ['name' => 'AI & Computer Vision Lab', 'slug' => 'ai-computer-vision-lab', 'target_institution' => 'university', 'description' => 'Advanced AI lab with NVIDIA Jetson development kits, cameras, and computing infrastructure for computer vision research.', 'short_description' => 'NVIDIA-powered AI lab for computer vision research', 'base_price' => 50000.00, 'currency' => 'USD', 'includes' => ['10x Jetson AGX Orin', '10x AI Cameras', 'GPU Workstation', 'Software Licenses', 'Installation', 'Training'], 'is_active' => true, 'is_featured' => true],
            ['name' => 'ROS 2 Research Lab', 'slug' => 'ros2-research-lab', 'target_institution' => 'university', 'description' => 'Complete ROS 2 research infrastructure with robots, sensors, and simulation software.', 'short_description' => 'Full ROS 2 research infrastructure with robots and simulation', 'base_price' => 75000.00, 'currency' => 'USD', 'includes' => ['5x Research Robots', 'ROS 2 Training', 'Isaac Sim Licenses', 'Motion Capture System', 'Installation'], 'is_active' => true, 'is_featured' => true],
        ];

        foreach ($packages as $pkg) {
            // JSON-encode array fields for PostgreSQL
            foreach (['equipment_list', 'includes', 'seo_meta'] as $field) {
                if (isset($pkg[$field]) && is_array($pkg[$field])) {
                    $pkg[$field] = json_encode($pkg[$field]);
                }
            }
            DB::table('institutional_packages')->updateOrInsert(
                ['slug' => $pkg['slug']],
                $pkg + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
