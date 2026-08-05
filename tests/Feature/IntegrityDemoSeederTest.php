<?php

namespace Tests\Feature;

use App\Enums\IntegrityDocumentStatus;
use App\Enums\IntegrityReviewStatus;
use App\Models\IntegrityDocument;
use App\Models\User;
use Database\Seeders\IntegrityDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_one_admin_two_instructors_and_six_documents_across_statuses(): void
    {
        $this->seed(IntegrityDemoSeeder::class);

        $this->assertTrue(User::whereEmail('demo-admin@sits.edu.et')->first()->hasRole('ADMIN'));
        $this->assertTrue(User::whereEmail('demo-instructor-a@sits.edu.et')->first()->hasRole('TRAINER'));
        $this->assertTrue(User::whereEmail('demo-instructor-b@sits.edu.et')->first()->hasRole('TRAINER'));

        $this->assertSame(6, IntegrityDocument::count());
        $this->assertSame(4, IntegrityDocument::where('status', IntegrityDocumentStatus::COMPLETE)->count());
        $this->assertSame(1, IntegrityDocument::where('status', IntegrityDocumentStatus::PENDING)->count());
        $this->assertSame(1, IntegrityDocument::where('status', IntegrityDocumentStatus::FAILED)->count());

        $this->assertSame(1, IntegrityDocument::whereHas('report', fn ($q) => $q->where('review_status', IntegrityReviewStatus::CLEARED))->count());
        $this->assertSame(1, IntegrityDocument::whereHas('report', fn ($q) => $q->where('review_status', IntegrityReviewStatus::UPHELD))->count());
        $this->assertSame(1, IntegrityDocument::whereHas('report', fn ($q) => $q->where('flagged', true)->where('review_status', IntegrityReviewStatus::NONE))->count());
    }
}
