<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Position;
use App\Models\Presence;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\TestCase;

class DashboardUserTest extends TestCase
{
    use RefreshDatabase;

    protected $employee;
    protected $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Suntik data master roles & positions agar tidak melanggar foreign key
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Buat akun karyawan tiruan untuk simulasi login dashboard
        $this->employee = User::create([
            'id' => 99,
            'name' => 'Hul Developer',
            'email' => 'hul@pso.com',
            'password' => bcrypt('password'),
            'role_id' => 3,
            'position_id' => 1,
            'phone' => '08123456789'
        ]);

        // 3. Buat satu sesi absensi aktif di database testing
        $this->attendance = Attendance::create([
            'id' => 1,
            'title' => 'Sesi Absensi Utama',
            'description' => 'Sesi Absensi Kantor Pagi',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'QR-DASHBOARD-TEST'
        ]);
    }

    /** @test */
    public function test_employee_dashboard_can_correctly_calculate_and_show_statistics()
    {
        // (Bagian travelTo dan suntik data Presence/Permission biarkan tetap utuh seperti kemarin)
        $fixedDate = Carbon::now()->startOfMonth()->addDays(4);
        $this->travelTo($fixedDate);

        // DATA SIMULASI:
        // Buat riwayat 2 Hari Hadir di bulan berjalan
        Presence::create([
            'user_id' => $this->employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => Carbon::now()->startOfMonth()->toDateString(),
            'presence_enter_time' => '08:00:00',
            'presence_out_time' => '16:00:00'
        ]);

        Presence::create([
            'user_id' => $this->employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => Carbon::now()->startOfMonth()->addDay()->toDateString(),
            'presence_enter_time' => '08:05:00',
            'presence_out_time' => '16:10:00'
        ]);

        // Menambahkan data izin lengkap agar lolos skema database kelompok
        Permission::create([
            'user_id' => $this->employee->id,
            'attendance_id' => $this->attendance->id,
            'title' => 'Surat Izin Sakit',
            'description' => 'Demam tinggi, butuh istirahat sebelum sidang.',
            'permission_date' => Carbon::now()->startOfMonth()->addDays(2)->toDateString(),
            'permission_reason' => 'Sakit Persiapan Sidang',
            'is_accepted' => true
        ]);

        // Kirim variabel $title tiruan agar partials 'attendance-badges' tidak eror
        $title = $this->attendance->title;

        // 🎯 TRIK PENYELAMAT: Bagikan variabel $title secara global khusus untuk sesi render test ini
        view()->share('title', $title);

        // EKSEKUSI: Langsung login dan panggil rute beranda tanpa trik global share lagi
        $response = $this->actingAs($this->employee)
            ->get(route('home.index'));

        // VALIDASI:
        $response->assertStatus(200);

        $response->assertSee('2 Hari');
        $response->assertSee('1 Hari');

        $response->assertSee('Hul Developer');
        $response->assertSee('hul@pso.com');

        // Kembalikan waktu testing ke semula
        $this->travelBack();
    }
}
