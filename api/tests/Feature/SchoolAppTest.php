<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolAppTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $name): User
    {
        $tenant = app(TenantProvisioner::class)->create([
            'name' => $name, 'type' => 'tutor', 'contact_name' => 'Owner', 'contact_email' => strtolower($name).'@example.test',
        ], 'secret123');

        return $tenant->users()->first();
    }

    private function makeClass(array $overrides = []): int
    {
        return $this->postJson('/api/classes', $overrides + ['name' => 'Maths 2027', 'mode' => 'online', 'monthly_fee' => 2500, 'day_of_week' => 1, 'start_time' => '18:30'])
            ->assertCreated()->json('data.id');
    }

    public function test_login_returns_token_and_enabled_features(): void
    {
        $this->owner('Amila');

        $this->postJson('/api/login', ['email' => 'amila@example.test', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('tenant.name', 'Amila')
            ->assertJsonStructure(['token', 'features']);

        $this->postJson('/api/login', ['email' => 'amila@example.test', 'password' => 'wrong'])->assertStatus(422);
    }

    public function test_owner_adds_students_to_classes_and_records_payments(): void
    {
        Sanctum::actingAs($this->owner('Amila'));
        $classId = $this->makeClass();

        $studentId = $this->postJson('/api/students', ['name' => 'Dulani Herath', 'class_ids' => [$classId]])
            ->assertCreated()->json('data.id');

        $month = now()->format('Y-m');
        $slip = $this->postJson('/api/payments', ['student_id' => $studentId, 'class_group_id' => $classId, 'month' => $month, 'amount' => 2500, 'method' => 'bank'])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->json('data.id');

        $this->getJson('/api/reports/income')->assertJsonPath('outstanding_total', 2500);

        $this->postJson("/api/payments/$slip/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->getJson('/api/reports/income')->assertJsonPath('total', 2500)->assertJsonPath('outstanding_total', 0);
        $this->getJson('/api/students')->assertJsonPath('data.0.paid_this_month', true);
    }

    public function test_attendance_is_saved_per_class_and_day(): void
    {
        Sanctum::actingAs($this->owner('Amila'));
        $classId = $this->makeClass();
        $a = $this->postJson('/api/students', ['name' => 'A', 'class_ids' => [$classId]])->json('data.id');
        $b = $this->postJson('/api/students', ['name' => 'B', 'class_ids' => [$classId]])->json('data.id');

        $this->putJson("/api/classes/$classId/attendance", ['date' => '2026-11-10', 'records' => [
            ['student_id' => $a, 'present' => true], ['student_id' => $b, 'present' => false],
        ]])->assertOk()->assertJsonPath('marked', true);

        $this->getJson("/api/classes/$classId/attendance?date=2026-11-10")
            ->assertJsonPath('students.0.present', true)
            ->assertJsonPath('students.1.present', false);
    }

    public function test_exam_results_are_ranked_with_ties(): void
    {
        Sanctum::actingAs($this->owner('Amila'));
        $classId = $this->makeClass();
        $ids = collect(['A', 'B', 'C', 'D'])->map(fn ($n) => $this->postJson('/api/students', ['name' => $n, 'class_ids' => [$classId]])->json('data.id'));

        $results = $this->postJson('/api/exams', [
            'class_group_id' => $classId, 'name' => 'Paper 08', 'held_on' => '2026-11-08', 'max_marks' => 100,
            'marks' => [
                ['student_id' => $ids[0], 'marks' => 70], ['student_id' => $ids[1], 'marks' => 90],
                ['student_id' => $ids[2], 'marks' => 85], ['student_id' => $ids[3], 'marks' => 85],
            ],
        ])->assertCreated()->json('data.results');

        $this->assertSame([1, 2, 2, 4], array_column($results, 'rank'));
        $this->assertSame('B', $results[0]['name']);
    }

    public function test_clients_never_see_each_others_data(): void
    {
        $amila = $this->owner('Amila');
        Sanctum::actingAs($amila);
        $classId = $this->makeClass();
        $studentId = $this->postJson('/api/students', ['name' => 'Private Student'])->json('data.id');

        Sanctum::actingAs($this->owner('Other'));
        $this->getJson('/api/students')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/students/$studentId")->assertNotFound();
        $this->patchJson("/api/students/$studentId", ['name' => 'Hacked'])->assertNotFound();
        $this->getJson("/api/classes/$classId/attendance?date=2026-11-10")->assertNotFound();

        // Cannot enrol another client's student or pay into another client's class.
        $ownClass = $this->makeClass(['name' => 'Mine']);
        $this->postJson('/api/students', ['name' => 'X', 'class_ids' => [$classId]])->assertStatus(422);
        $this->postJson('/api/payments', ['student_id' => $studentId, 'class_group_id' => $ownClass, 'month' => '2026-11', 'amount' => 100, 'method' => 'cash'])
            ->assertStatus(422);

        $this->assertDatabaseHas('students', ['id' => $studentId, 'name' => 'Private Student']);
    }

    public function test_switched_off_features_hide_their_fields(): void
    {
        $owner = $this->owner('Amila');
        Tenant::first()->features()->where('feature', 'online_links')->update(['enabled' => false]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/classes', ['name' => 'Maths', 'mode' => 'online', 'monthly_fee' => 2000, 'meeting_link' => 'https://zoom.us/j/1'])
            ->assertCreated()
            ->assertJsonMissingPath('data.meeting_link');
        $this->assertDatabaseHas('class_groups', ['name' => 'Maths', 'meeting_link' => null]);
    }

    public function test_platform_admin_has_no_school_app(): void
    {
        Sanctum::actingAs(User::create(['name' => 'Admin', 'email' => 'a@example.test', 'password' => 'secret123', 'role' => 'platform_admin']));

        $this->getJson('/api/students')->assertForbidden();
    }
}
