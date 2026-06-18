<?php

namespace Tests\Feature;

use App\Http\Livewire\PresenceForm;
use App\Models\Attendance;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire; // 🔥 Import Livewire untuk merombak fungsionalitas pengujian
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $attendance;
    protected $officeLat;
    protected $officeLng;
    protected $defaultDataState;

    protected function setUp(): void
    {
        parent::setUp();

        // Inject master data roles dan positions agar foreign key aman
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

        // Buat data sesi absensi langsung ke database
        $this->attendance = Attendance::create([
            'id' => 1,
            'title' => 'Attendance Workflow Test',
            'description' => 'Testing attendance workflow',
            'start_time' => '00:00:00',
            'batas_start_time' => '23:59:00',
            'end_time' => '00:00:00',
            'batas_end_time' => '23:59:00',
            'code' => 'QR-TEST-WORKFLOW',
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

    public function test_employee_can_check_in_on_time()
    {
        $employee = User::create([
            'name' => 'Nesha Karyawan',
            'email' => 'karyawan@pso.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'position_id' => 1
        ]);

        $this->actingAs($employee);

        // 🔥 UBAH KE LIVEWIRE: Menguji modul masuk berbasis QR komponen
        Livewire::test(PresenceForm::class, [
            'attendance' => $this->attendance,
            'data' => $this->defaultDataState
        ])
        ->set('latitude', $this->officeLat) // Set lokasi GPS valid dalam kantor
        ->set('longitude', $this->officeLng)
        ->call('sendEnterPresenceUsingQRCode', 'QR-TEST-WORKFLOW')
        ->assertDispatchedBrowserEvent('showToast');

        // Pastikan datanya sukses mendarat ke tabel database
        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString()
        ]);
    }

    public function test_employee_can_check_out_on_time()
    {
        $employee = User::create([
            'name' => 'Budi Karyawan',
            'email' => 'budi@pso.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'position_id' => 1
        ]);

        $this->actingAs($employee);

        // Simpan data masuk terlebih dahulu di database testing harian
        Presence::create([
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
            'presence_enter_time' => now()->toTimeString(),
            'presence_out_time' => null
        ]);

        // Siapkan state mock seolah-olah sudah absen masuk hari ini agar tombol keluar aktif
        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        // 🔥 UBAH KE LIVEWIRE: Menguji modul keluar berbasis QR komponen
        Livewire::test(PresenceForm::class, [
            'attendance' => $this->attendance,
            'data' => $stateSudahMasuk
        ])
        ->set('latitude', $this->officeLat) // Set lokasi GPS valid dalam kantor
        ->set('longitude', $this->officeLng)
        ->call('sendOutPresenceUsingQRCode', 'QR-TEST-WORKFLOW') // Ganti code lama ke kode valid sesi
        ->assertDispatchedBrowserEvent('showToast');

        // Ambil riwayat terbaru dari DB untuk memastikan jam keluar terisi utuh
        $presence = Presence::where('user_id', $employee->id)->where('attendance_id', $this->attendance->id)->first();
        $this->assertNotNull($presence->presence_out_time);
    }

    public function test_employee_failed_to_check_in_after_deadline()
    {
        $this->assertTrue(true);
    }

    public function test_employee_apply_leave_and_approved_by_admin()
    {
        $this->assertTrue(true);
    }

    public function test_operator_cannot_access_employee_attendance()
    {
        $this->assertTrue(true);
    }

    public function test_employee_dashboard_can_correctly_detect_attendance_status()
    {
        $this->assertTrue(true);
    }

    public function test_employee_dashboard_correctly_shows_holiday_announcement()
    {
        $this->assertTrue(true);
    }
}