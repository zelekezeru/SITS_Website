<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class IntegrityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        SpatieRole::findOrCreate($roleName);
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function instructor(): User
    {
        return $this->userWithRole('TRAINER');
    }

    private function student(): User
    {
        return $this->userWithRole('STUDENT');
    }

    private function campusAdmin(): User
    {
        return $this->userWithRole('ADMIN');
    }

    // ----- Student is blocked everywhere -----------------------------------

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('integrity.dashboard'))->assertRedirect(route('login'));
    }

    public function test_student_gets_403_on_the_dashboard(): void
    {
        $this->actingAs($this->student())->get(route('integrity.dashboard'))->assertForbidden();
    }

    public function test_student_gets_403_on_history(): void
    {
        $this->actingAs($this->student())->get(route('integrity.history'))->assertForbidden();
    }

    public function test_student_gets_403_on_document_store(): void
    {
        $this->actingAs($this->student())
            ->post(route('integrity.documents.store'), ['title' => 'x', 'text' => 'some text'])
            ->assertForbidden();
    }

    public function test_student_gets_403_on_document_show(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);

        $this->actingAs($this->student())
            ->get(route('integrity.documents.show', $document))
            ->assertForbidden();
    }

    public function test_student_gets_403_on_reanalyze(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);

        $this->actingAs($this->student())
            ->post(route('integrity.documents.reanalyze', $document))
            ->assertForbidden();
    }

    public function test_student_gets_403_on_plagiarism_run(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);

        $this->actingAs($this->student())
            ->post(route('integrity.documents.plagiarism', $document))
            ->assertForbidden();
    }

    public function test_student_gets_403_on_writing_tool_run(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);

        $this->actingAs($this->student())
            ->post(route('integrity.documents.tools.run', [$document, 'summary']))
            ->assertForbidden();
    }

    public function test_student_gets_403_on_review_update(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);
        $report = IntegrityReport::factory()->for($document, 'document')->create();

        $this->actingAs($this->student())
            ->patch(route('integrity.reports.review', $report), ['action' => 'start'])
            ->assertForbidden();
    }

    public function test_student_gets_403_on_export_pdf(): void
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $this->instructor()->id]);

        $this->actingAs($this->student())
            ->get(route('integrity.documents.export.pdf', $document))
            ->assertForbidden();
    }

    // ----- Ownership scoping between instructors ----------------------------

    public function test_instructor_can_view_their_own_document(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->get(route('integrity.documents.show', $document))
            ->assertOk();
    }

    public function test_instructor_a_cannot_open_instructor_bs_document(): void
    {
        $instructorA = $this->instructor();
        $instructorB = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructorB->id]);

        $this->actingAs($instructorA)
            ->get(route('integrity.documents.show', $document))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_instructors_document(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($this->campusAdmin())
            ->get(route('integrity.documents.show', $document))
            ->assertOk();
    }

    // ----- Quota -------------------------------------------------------------

    public function test_quota_exceeded_returns_429_with_a_friendly_message(): void
    {
        config(['integrity.daily_quota' => 2]);
        $instructor = $this->instructor();

        IntegrityDocument::factory()->count(2)->create([
            'instructor_id' => $instructor->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->postJson(route('integrity.documents.store'), ['title' => 'One more', 'text' => str_repeat('word ', 200)]);

        $response->assertStatus(429);
        $response->assertJsonFragment(['message' => "You've reached your daily analysis quota (2/day). Please try again tomorrow."]);
    }

    public function test_quota_is_scoped_per_instructor(): void
    {
        config(['integrity.daily_quota' => 1]);
        $instructorA = $this->instructor();
        $instructorB = $this->instructor();

        IntegrityDocument::factory()->create(['instructor_id' => $instructorA->id, 'created_at' => now()]);

        // A is at quota, B is not — B's request must not be blocked by A's usage.
        $response = $this->actingAs($instructorB)
            ->post(route('integrity.documents.store'), ['title' => 'Fresh', 'text' => str_repeat('word ', 200)]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
    }
}
