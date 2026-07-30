<?php

namespace Database\Seeders;

use App\Enums\IntegrityDocumentStatus;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Demo data for the Academic Integrity Suite. Not called from DatabaseSeeder
 * (per docs/deploy-to-cpanel.md's warning against blanket reseeds on a live
 * DB) — run explicitly: php artisan db:seed --class=IntegrityDemoSeeder
 */
class IntegrityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $trainer = Role::firstOrCreate(['name' => 'TRAINER']);

        $instructor = User::factory()->create(['name' => 'Demo Instructor']);
        $instructor->assignRole($trainer);

        $flagged = IntegrityDocument::factory()
            ->for($instructor, 'instructor')
            ->create(['title' => 'Suspicious Essay Submission', 'status' => IntegrityDocumentStatus::COMPLETE]);
        IntegrityReport::factory()->flagged()->for($flagged, 'document')->create();

        $cleared = IntegrityDocument::factory()
            ->for($instructor, 'instructor')
            ->create(['title' => 'Reviewed and Cleared Essay', 'status' => IntegrityDocumentStatus::COMPLETE]);
        IntegrityReport::factory()->for($cleared, 'document')->create([
            'ai_probability' => 20,
            'flagged' => false,
        ]);

        IntegrityDocument::factory()->pending()->for($instructor, 'instructor')
            ->create(['title' => 'Awaiting Analysis']);

        IntegrityDocument::factory()->failed()->for($instructor, 'instructor')
            ->create(['title' => 'Scanned PDF — Extraction Failed']);
    }
}
