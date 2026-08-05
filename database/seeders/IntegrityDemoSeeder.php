<?php

namespace Database\Seeders;

use App\Enums\IntegrityReviewStatus;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Demo data for the Academic Integrity Suite: 1 admin, 2 instructors, 6
 * documents spanning every status/review state so the dashboard, history,
 * and review workflow all demo immediately. Not called from DatabaseSeeder
 * (per docs/deploy-to-cpanel.md's warning against blanket reseeds on a live
 * DB) — run explicitly: php artisan db:seed --class=IntegrityDemoSeeder
 */
class IntegrityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $trainerRole = Role::firstOrCreate(['name' => 'TRAINER']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);

        $admin = User::factory()->create(['name' => 'Demo Admin', 'email' => 'demo-admin@sits.edu.et']);
        $admin->assignRole($adminRole);

        $instructorA = User::factory()->create(['name' => 'Demo Instructor A', 'email' => 'demo-instructor-a@sits.edu.et']);
        $instructorA->assignRole($trainerRole);

        $instructorB = User::factory()->create(['name' => 'Demo Instructor B', 'email' => 'demo-instructor-b@sits.edu.et']);
        $instructorB->assignRole($trainerRole);

        // 1. Flagged, awaiting review.
        $flagged = IntegrityDocument::factory()->for($instructorA, 'instructor')
            ->create(['title' => 'Suspicious Essay Submission']);
        IntegrityReport::factory()->flagged()->for($flagged, 'document')->create();

        // 2. Flagged, reviewed and cleared (feeds baseline recalibration).
        $cleared = IntegrityDocument::factory()->for($instructorA, 'instructor')
            ->create(['title' => 'Reviewed and Cleared Essay — ESL Phrasing']);
        IntegrityReport::factory()->flagged()->for($cleared, 'document')->create([
            'review_status' => IntegrityReviewStatus::CLEARED,
            'reviewed_by' => $instructorA->id,
            'reviewed_at' => now()->subDay(),
            'review_notes' => 'Discussed with the student — non-native phrasing, not AI-generated.',
        ]);

        // 3. Flagged, reviewed and upheld.
        $upheld = IntegrityDocument::factory()->for($instructorB, 'instructor')
            ->create(['title' => 'Reviewed and Upheld Submission']);
        IntegrityReport::factory()->flagged()->for($upheld, 'document')->create([
            'review_status' => IntegrityReviewStatus::UPHELD,
            'reviewed_by' => $instructorB->id,
            'reviewed_at' => now()->subDays(2),
            'review_notes' => 'Student confirmed use of an AI tool without disclosure.',
        ]);

        // 4. Complete, not flagged (clean submission).
        $clean = IntegrityDocument::factory()->for($instructorB, 'instructor')
            ->create(['title' => 'Original Reflection Paper']);
        IntegrityReport::factory()->for($clean, 'document')->create([
            'ai_probability' => 14,
            'confidence' => 'high',
            'flagged' => false,
        ]);

        // 5. Pending (queued, not yet analyzed).
        IntegrityDocument::factory()->pending()->for($instructorA, 'instructor')
            ->create(['title' => 'Awaiting Analysis']);

        // 6. Failed (extraction failure).
        IntegrityDocument::factory()->failed()->for($instructorB, 'instructor')
            ->create(['title' => 'Scanned PDF — Extraction Failed']);
    }
}
