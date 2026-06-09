<?php

namespace Tests\Integration;

use App\Models\Attendance;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
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
            'title' => 'Attendance Workflow Test',
            'description' => 'Testing attendance workflow',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'ATTENDANCE-INTEGRATION'
        ]);

        $this->attendance->data = (object)[
            'is_start' => true,
            'is_end' => true,
            'is_using_qrcode' => true,
            'is_holiday_today' => false
        ];
    }

    /** @test */
    public function attendance_workflow_can_be_completed_successfully()
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
        | STEP 2 - Employee performs check in
        |--------------------------------------------------------------------------
        */

        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        $this->travelTo(now()->setTime(8, 0, 0));

        $this->actingAs($employee)
            ->post(route('home.sendEnterPresenceUsingQRCode'), [
                'code' => 'ATTENDANCE-INTEGRATION'
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString()
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 3 - Employee performs check out
        |--------------------------------------------------------------------------
        */

        $this->travelTo(now()->setTime(16, 0, 0));

        $this->actingAs($employee)
            ->post(route('home.sendOutPresenceUsingQRCode'), [
                'code' => 'ATTENDANCE-INTEGRATION'
            ])
            ->assertStatus(200);

        $presence = Presence::query()
            ->where('user_id', $employee->id)
            ->where('attendance_id', $this->attendance->id)
            ->first();

        $this->assertNotNull($presence);
        $this->assertNotNull($presence->presence_out_time);

        /*
        |--------------------------------------------------------------------------
        | STEP 4 - Admin views attendance data
        |--------------------------------------------------------------------------
        */

        $this->actingAs($admin)
            ->get(route('presences.show', $this->attendance->id))
            ->assertStatus(200);

        /*
        |--------------------------------------------------------------------------
        | STEP 5 - Admin can see employee attendance record
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString()
        ]);

        $presence = Presence::where('user_id', $employee->id)
            ->where('attendance_id', $this->attendance->id)
            ->first();

        $this->assertNotNull($presence);
        $this->assertNotNull($presence->presence_enter_time);
        $this->assertNotNull($presence->presence_out_time);
    }
}