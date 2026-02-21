<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationDocument>
 */
class VerificationDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'document_type' => 'photo_id',
            'file_path' => 'verification/1/'.fake()->uuid().'.jpg',
            'original_filename' => 'passport.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewer_notes' => 'Document not clear enough.',
            'reviewed_at' => now(),
        ]);
    }

    public function qualification(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'qualification',
            'original_filename' => 'certificate.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function insurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'insurance',
            'original_filename' => 'insurance-cert.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
