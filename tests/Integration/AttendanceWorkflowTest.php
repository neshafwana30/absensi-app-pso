<?php

namespace Tests\Integration;

use App\Http\Livewire\PresenceForm;
use App\Models\Attendance;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire; // 🔥 Wajib di-import untuk testing komponen reaktif
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $attendance;
    protected $officeLat;
    protected $officeLng;
    protected $defaultDataState;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Suntik master data roles dan positions agar foreign key aman
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Ambil konfigurasi koordinat kantor harian (Paper Cup Darhus)
        $this->officeLat = config('attendance.office_latitude');
        $this->officeLng = config('attendance.office_longitude');

        // 2. Buat data sesi absensi langsung ke database dengan struktur kolom JSON `data`
        $this->attendance = Attendance::create([
            'id' => 1,
            'title' => 'Attendance Workflow Test',
            'description' => 'Testing attendance workflow',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'ATTENDANCE-INTEGRATION',
            'data' => json_encode([
                'is_start' => true,
                'is_end' => true,
                'is_using_qrcode' => true,
                'is_holiday_today' => false
            ])
        ]);

        // State default array parameter untuk perenderan halaman blade
        $this->defaultDataState = [
            'is_there_permission' => false,
            'is_has_enter_today' => false,
            'is_not_out_yet' => false,
            'is_permission_accepted' => false
        ];
    }

    /** @test */
    public function attendance_workflow_can_be_completed_successfully()
    {
        /*
        |--------------------------------------------------------------------------
        | STEP 1 - Admin creates attendance session
        |--------------------------------------------------------------------------
        | Sesi absensi otomatis dibuat melalui method setUp() di atas.
        */
        $admin = User::factory()->create([
            'role_id' => 1,
            'position_id' => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | STEP 2 - Employee performs check in (Menggunakan Livewire + Geofencing GPS)
        |--------------------------------------------------------------------------
        */
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Manipulasi waktu ke jam 8 pagi (Waktu masuk yang valid)
        $this->travelTo(now()->setTime(8, 0, 0));

        $this->actingAs($employee);

        // Menjalankan testing langsung pada alur reaktif form komponen
        Livewire::test(PresenceForm::class, [
            'attendance' => $this->attendance, 
            'data' => $this->defaultDataState
        ])
        ->set('latitude', $this->officeLat) // Pastikan user mengirim GPS dalam radius kantor
        ->set('longitude', $this->officeLng)
        ->call('sendEnterPresenceUsingQRCode', 'ATTENDANCE-INTEGRATION')
        ->assertDispatchedBrowserEvent('showToast');

        // Pastikan satpam DB mencatat riwayat masuk harian dengan benar
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
        // Lompat waktu ke jam 4 sore (Waktu pulang yang valid)
        $this->travelTo(now()->setTime(16, 0, 0));

        // Mocking state perubahan seolah-olah user sudah absen masuk hari ini
        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        Livewire::test(PresenceForm::class, [
            'attendance' => $this->attendance, 
            'data' => $stateSudahMasuk
        ])
        ->set('latitude', $this->officeLat)
        ->set('longitude', $this->officeLng)
        ->call('sendOutPresenceUsingQRCode', 'ATTENDANCE-INTEGRATION')
        ->assertDispatchedBrowserEvent('showToast');

        // Validasi record data jam keluar terisi utuh di database
        $presence = Presence::query()
            ->where('user_id', $employee->id)
            ->where('attendance_id', $this->attendance->id)
            ->first();

        $this->assertNotNull($presence);
        $this->assertNotNull($presence->presence_out_time);

        /*
        |--------------------------------------------------------------------------
        | STEP 4 - Admin views attendance data (HTTP request normal via Controller)
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

        $finalPresence = Presence::where('user_id', $employee->id)
            ->where('attendance_id', $this->attendance->id)
            ->first();

        $this->assertNotNull($finalPresence->presence_enter_time);
        $this->assertNotNull($finalPresence->presence_out_time);
    }
}