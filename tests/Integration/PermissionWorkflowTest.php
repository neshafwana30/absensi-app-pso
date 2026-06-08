<?php

namespace Tests\Integration;

use App\Models\Attendance;
use App\Models\Permission;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->attendance = Attendance::create([
            'title' => 'Permission Integration Test',
            'description' => 'Testing workflow izin lengkap',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'PERMISSION-INTEGRATION'
        ]);

        $this->attendance->data = (object)[
            'is_start' => true,
            'is_end' => true,
            'is_using_qrcode' => true,
            'is_holiday_today' => false
        ];
    }

    /** @test */
    public function permission_workflow_can_be_completed_successfully()
    {
        /*
        |--------------------------------------------------------------------------
        | STEP 1 - Admin creates attendance session
        |--------------------------------------------------------------------------
        */

        $admin = User::factory()->create([
            'role_id' => 1,
            'position_id' => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 2 - Employee submits leave request
        |--------------------------------------------------------------------------
        */

        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        Permission::create([
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'title' => 'Sakit Demam',
            'description' => 'Tidak dapat masuk kerja karena sakit.',
            'permission_date' => now()->toDateString(),
            'is_accepted' => 0
        ]);

        $this->assertDatabaseHas('permissions', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'is_accepted' => 0
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 3 - Admin views permission list
        |--------------------------------------------------------------------------
        */

        $this->actingAs($admin)
            ->get(route('presences.permissions', $this->attendance->id))
            ->assertStatus(200);

        /*
        |--------------------------------------------------------------------------
        | STEP 4 - Admin approves permission
        |--------------------------------------------------------------------------
        */

        $this->actingAs($admin)
            ->post(route('presences.acceptPermission', $this->attendance->id), [
                'user_id' => (string) $employee->id,
                'permission_date' => now()->toDateString()
            ])
            ->assertStatus(302);

        /*
        |--------------------------------------------------------------------------
        | STEP 5 - Permission status updated
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('permissions', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'is_accepted' => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 6 - Presence automatically created
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
            'is_permission' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 7 - Admin can view attendance data
        |--------------------------------------------------------------------------
        */

        $this->actingAs($admin)
            ->get(route('presences.show', $this->attendance->id))
            ->assertStatus(200);

        $presence = Presence::where('user_id', $employee->id)
            ->where('attendance_id', $this->attendance->id)
            ->first();

        $this->assertNotNull($presence);
        $this->assertTrue((bool) $presence->is_permission);
    }
}