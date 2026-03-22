<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_admin_cannot_disable_their_own_account(): void
    {
        $admin = Admin::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        $admin->forceFill([
            'is_active' => false,
        ]);

        try {
            $admin->save();

            $this->fail('Expected the signed-in admin to remain active.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'You cannot disable the admin account that is currently signed in.',
                $exception->errors()['is_active'][0],
            );
        }

        $this->assertTrue((bool) $admin->fresh()?->is_active);
    }

    public function test_signed_in_admin_cannot_delete_their_own_account(): void
    {
        $admin = Admin::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        try {
            $admin->delete();

            $this->fail('Expected the signed-in admin record to be preserved.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'You cannot delete the admin account that is currently signed in.',
                $exception->errors()['admin'][0],
            );
        }

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
        ]);
    }

    public function test_last_active_admin_cannot_be_deleted_by_another_operator(): void
    {
        $targetAdmin = Admin::factory()->create([
            'is_active' => true,
        ]);

        $operator = Admin::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($operator, 'admin');

        try {
            $targetAdmin->delete();

            $this->fail('Expected the last active admin to be preserved.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'At least one active admin account must remain available.',
                $exception->errors()['admin'][0],
            );
        }

        $this->assertDatabaseHas('admins', [
            'id' => $targetAdmin->id,
        ]);
    }

    public function test_prune_command_is_not_scheduled_automatically(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command)
            ->filter()
            ->values();

        $this->assertFalse(
            $commands->contains(fn (string $command): bool => str_contains($command, 'status:prune-check-runs')),
            'The prune command should not run automatically while data preservation is enabled.',
        );
    }
}
