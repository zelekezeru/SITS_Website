<?php

namespace Tests\Feature;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Editing a payroll period's name, coverage and payment date. */
class PayrollPeriodEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::findOrCreate('President / Super Admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('manage payroll', 'web'));

        $this->admin = User::factory()->create([
            'is_approved' => true, 'is_active' => true, 'password_changed' => true,
        ]);
        $this->admin->assignRole($role);
    }

    private function period(string $status = 'open'): PayrollPeriod
    {
        return PayrollPeriod::create([
            'name' => 'March 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'status' => $status,
        ]);
    }

    public function test_period_month_and_payment_date_can_be_changed(): void
    {
        $period = $this->period();

        $this->actingAs($this->admin)
            ->put("/admin/payroll/periods/{$period->id}", [
                'name' => 'April 2026',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
                'payment_date' => '2026-05-02',
            ])
            ->assertRedirect();

        $period->refresh();
        $this->assertEquals('April 2026', $period->name);
        $this->assertEquals('2026-04-01', $period->start_date->toDateString());
        $this->assertEquals('2026-04-30', $period->end_date->toDateString());
        $this->assertEquals('2026-05-02', $period->payment_date->toDateString());
    }

    /**
     * The range must stay a full calendar month — scopeMonthly() filters on
     * start-day 1 / end-day 28–31, so a partial range vanishes from every
     * payroll, permission and config listing.
     */
    public function test_a_partial_month_range_is_rejected(): void
    {
        $period = $this->period();

        $this->actingAs($this->admin)
            ->put("/admin/payroll/periods/{$period->id}", [
                'name' => 'Mid March 2026',
                'start_date' => '2026-03-05',
                'end_date' => '2026-04-04',
            ])
            ->assertSessionHas('error');

        $this->assertEquals('2026-03-01', $period->fresh()->start_date->toDateString());
    }

    public function test_end_before_start_is_rejected(): void
    {
        $period = $this->period();

        $this->actingAs($this->admin)
            ->put("/admin/payroll/periods/{$period->id}", [
                'name' => 'March 2026',
                'start_date' => '2026-03-01',
                'end_date' => '2026-02-28',
            ])
            ->assertSessionHasErrors('end_date');
    }

    /** Two periods covering the same month would make the month ambiguous. */
    public function test_a_month_already_covered_is_rejected(): void
    {
        $march = $this->period();
        PayrollPeriod::create([
            'name' => 'April 2026',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'status' => 'open',
        ]);

        $this->actingAs($this->admin)
            ->put("/admin/payroll/periods/{$march->id}", [
                'name' => 'April 2026',
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-30',
            ])
            ->assertSessionHas('error');

        $this->assertEquals('2026-03-01', $march->fresh()->start_date->toDateString());
    }

    public function test_locked_and_paid_periods_cannot_be_edited(): void
    {
        foreach (['locked', 'paid'] as $status) {
            $period = PayrollPeriod::create([
                'name' => 'May 2026',
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
                'status' => $status,
            ]);

            $this->actingAs($this->admin)
                ->put("/admin/payroll/periods/{$period->id}", [
                    'name' => 'Changed',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-06-30',
                ])
                ->assertSessionHas('error');

            $this->assertEquals('May 2026', $period->fresh()->name);
        }
    }

    /** An edited period must still be found by the monthly scope. */
    public function test_an_edited_period_is_still_listed_by_the_monthly_scope(): void
    {
        $period = $this->period();

        $this->actingAs($this->admin)->put("/admin/payroll/periods/{$period->id}", [
            'name' => 'February 2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]);

        $this->assertTrue(
            PayrollPeriod::monthly()->whereKey($period->id)->exists(),
            'a February period ending on the 28th must still match scopeMonthly',
        );
    }
}
