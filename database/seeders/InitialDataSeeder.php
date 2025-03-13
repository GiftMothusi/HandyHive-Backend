<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Seed the application's database with initial data.
     */
    public function run(): void
    {
        // Create services
        $this->createServices();

        // Create service providers
        $this->createServiceProviders();
    }

    /**
     * Create initial services.
     */
    private function createServices(): void
    {
        $services = [
            [
                'category' => 'Domestic Worker',
                'base_rate' => 25.00,
                'description' => 'Professional cleaning services for homes and apartments.',
                'requirements' => [
                    'experience' => '1+ years of experience',
                    'equipment' => ['cleaning supplies', 'vacuum cleaner'],
                    'certification' => [],
                ],
                'availability' => ['weekdays' => true, 'weekends' => true, 'holidays' => false],
                'duration' => ['minimum' => 2, 'maximum' => 8],
                'status' => 'active',
            ],
            [
                'category' => 'Gardener',
                'base_rate' => 30.00,
                'description' => 'Professional gardening services for homes and businesses.',
                'requirements' => [
                    'experience' => '1+ years of experience',
                    'equipment' => ['gardening tools', 'lawn mower'],
                    'certification' => [],
                ],
                'availability' => ['weekdays' => true, 'weekends' => true, 'holidays' => false],
                'duration' => ['minimum' => 2, 'maximum' => 8],
                'status' => 'active',
            ],
            [
                'category' => 'Chef',
                'base_rate' => 45.00,
                'description' => 'Professional cooking services for special events and meal preparation.',
                'requirements' => [
                    'experience' => '2+ years of experience',
                    'equipment' => ['cooking utensils'],
                    'certification' => ['food safety'],
                ],
                'availability' => ['weekdays' => true, 'weekends' => true, 'holidays' => true],
                'duration' => ['minimum' => 3, 'maximum' => 8],
                'status' => 'active',
            ],
            [
                'category' => 'Tutor',
                'base_rate' => 35.00,
                'description' => 'Professional tutoring services for students of all ages.',
                'requirements' => [
                    'experience' => '1+ years of experience',
                    'equipment' => ['teaching materials'],
                    'certification' => ['teaching qualification'],
                ],
                'availability' => ['weekdays' => true, 'weekends' => true, 'holidays' => false],
                'duration' => ['minimum' => 1, 'maximum' => 4],
                'status' => 'active',
            ],
            [
                'category' => 'Handyman',
                'base_rate' => 40.00,
                'description' => 'Professional handyman services for home repairs and maintenance.',
                'requirements' => [
                    'experience' => '2+ years of experience',
                    'equipment' => ['tools'],
                    'certification' => [],
                ],
                'availability' => ['weekdays' => true, 'weekends' => true, 'holidays' => false],
                'duration' => ['minimum' => 1, 'maximum' => 8],
                'status' => 'active',
            ],
        ];

        foreach ($services as $serviceData) {
            Service::create($serviceData);
        }
    }

    /**
     * Create initial service providers.
     */
    private function createServiceProviders(): void
    {
        $serviceProviders = [
            [
                'name' => 'Maria Johnson',
                'email' => 'maria.johnson@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456789',
                'category' => 'Domestic Worker',
                'description' => 'Experienced housekeeper with 5+ years of experience in cleaning, laundry, and organizing.',
                'hourly_rate' => 25.00,
                'rating' => 4.8,
                'availability' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'experience' => ['years' => 5, 'specialties' => ['Deep cleaning', 'Laundry', 'Organization']],
                'profile_image' => 'maria-johnson.jpg',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456790',
                'category' => 'Gardener',
                'description' => 'Professional gardener specializing in landscape design, plant care, and garden maintenance.',
                'hourly_rate' => 30.00,
                'rating' => 4.7,
                'availability' => ['Mon', 'Wed', 'Fri', 'Sat'],
                'experience' => ['years' => 7, 'specialties' => ['Landscape design', 'Plant care', 'Garden maintenance']],
                'profile_image' => 'john-smith.jpg',
            ],
            [
                'name' => 'Chef Antonio',
                'email' => 'chef.antonio@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456791',
                'category' => 'Chef',
                'description' => 'Culinary expert with experience in various cuisines. Available for meal prep and special events.',
                'hourly_rate' => 45.00,
                'rating' => 4.9,
                'availability' => ['Tue', 'Thu', 'Sat', 'Sun'],
                'experience' => ['years' => 10, 'specialties' => ['Italian cuisine', 'French cuisine', 'Special events']],
                'profile_image' => 'chef-antonio.jpg',
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'sarah.williams@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456792',
                'category' => 'Tutor',
                'description' => 'Certified teacher offering tutoring in mathematics, science, and English for all grade levels.',
                'hourly_rate' => 35.00,
                'rating' => 4.6,
                'availability' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'experience' => ['years' => 8, 'specialties' => ['Mathematics', 'Science', 'English']],
                'profile_image' => 'sarah-williams.jpg',
            ],
            [
                'name' => 'David Chen',
                'email' => 'david.chen@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456793',
                'category' => 'Domestic Worker',
                'description' => 'Reliable house cleaner with attention to detail and excellent references.',
                'hourly_rate' => 28.00,
                'rating' => 4.5,
                'availability' => ['Wed', 'Thu', 'Fri', 'Sat'],
                'experience' => ['years' => 3, 'specialties' => ['House cleaning', 'Organizing']],
                'profile_image' => 'david-chen.jpg',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@example.com',
                'password' => 'password123',
                'userType' => 'provider',
                'phone' => '+27123456794',
                'category' => 'Gardener',
                'description' => 'Experienced gardener specializing in organic gardening and sustainable practices.',
                'hourly_rate' => 32.00,
                'rating' => 4.7,
                'availability' => ['Mon', 'Tue', 'Sat', 'Sun'],
                'experience' => ['years' => 6, 'specialties' => ['Organic gardening', 'Sustainable practices']],
                'profile_image' => 'michael-brown.jpg',
            ],
        ];

        foreach ($serviceProviders as $providerData) {
            // Create user first
            $user = User::create([
                'name' => $providerData['name'],
                'email' => $providerData['email'],
                'password' => Hash::make($providerData['password']),
                'userType' => $providerData['userType'],
                'phone' => $providerData['phone'],
                'status' => 'active',
            ]);

            // Create service provider linked to user
            ServiceProvider::create([
                'user_id' => $user->id,
                'category' => $providerData['category'],
                'description' => $providerData['description'],
                'hourly_rate' => $providerData['hourly_rate'],
                'rating' => $providerData['rating'],
                'availability' => $providerData['availability'],
                'experience' => $providerData['experience'],
                'profile_image' => $providerData['profile_image'],
                'status' => 'active',
            ]);
        }

        // Create a client user
        User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password123'),
            'userType' => 'client',
            'phone' => '+27123456795',
            'status' => 'active',
        ]);
    }
}
