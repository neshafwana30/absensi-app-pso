<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Permission;
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

        // 1. Suntik Data Master Roles sesuai database asli kelompokmu
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Suntik Data Master Positions sesuai database asli kelompokmu
        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai "Biasa"', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Direktur', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Operator', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Membuat Sesi Absensi Utama menggunakan code tiruan agar lolos query
        $this->attendance = Attendance::create([
            'title' => 'Absensi Harian Pegawai',
            'description' => 'Sesi absensi kehadiran harian resmi',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'QR-TEST-WORKFLOW'
        ]);

        // Mocking property object pembantu sesuai if statement di controller
        $this->attendance->data = (object)[
            'is_start' => true,
            'is_end' => true,
            'is_using_qrcode' => true,
            'is_holiday_today' => false
        ];
    }

    /** 1. Test Karyawan Berhasil Absen Masuk Tepat Waktu (Jam 08:00 Pagi) */
    public function test_employee_can_check_in_on_time()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Mengatur waktu tiruan ke jam 08:00 pagi (Di dalam range masuk resmi)
        $this->travelTo(now()->setTime(8, 0, 0));

        // Menembak route dengan membawa parameter code yang valid
        $response = $this->actingAs($employee)->post(route('home.sendEnterPresenceUsingQRCode'), [
            'code' => 'QR-TEST-WORKFLOW'
        ]);

        // Memastikan data tersimpan dengan aman di tabel presences
        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
        ]);
    }

    /** 2. Test Karyawan Berhasil Absen Pulang Tepat Waktu (Jam 16:00 Sore) */
    public function test_employee_can_check_out_on_time()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Kondisi Awal: Sudah absen masuk pagi harinya
        $presence = Presence::create([
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
            'presence_enter_time' => '08:00:00',
            'presence_out_time' => null
        ]);

        // Melompat ke jam 16:00 sore
        $this->travelTo(now()->setTime(16, 0, 0));

        $response = $this->actingAs($employee)->post(route('home.sendOutPresenceUsingQRCode'), [
            'code' => 'QR-TEST-WORKFLOW'
        ]);

        // Memastikan record jam pulang terupdate dengan sukses
        $this->assertDatabaseHas('presences', [
            'id' => $presence->id,
            'presence_date' => now()->toDateString(),
        ]);
        $this->assertNotNull($presence->fresh()->presence_out_time);
    }

    /** 3. Test Kasus Gagal: Karyawan Mencoba Absen Masuk Tapi Sudah Telat (Jam 11:00 Siang) */
    public function test_employee_failed_to_check_in_after_deadline()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Dipercepat ke jam 11:00 siang
        $this->travelTo(now()->setTime(11, 0, 0));

        // Simulasikan state di mana is_start sudah bernilai false karena lewat jam
        $this->attendance->data->is_start = false;

        $response = $this->actingAs($employee)->post(route('home.sendEnterPresenceUsingQRCode'), [
            'code' => 'QR-TEST-WORKFLOW'
        ]);

        // Harus mengembalikan status error 400 Bad Request atau redirect gagal
        $response->assertStatus(400);

        // Memastikan database bersih, tidak kemasukan data ghoib
        $this->assertDatabaseMissing('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString()
        ]);
    }

    /** 4. Test Karyawan Mengajukan Izin dan Disetujui (Accepted) Oleh Admin */
    public function test_employee_apply_leave_and_approved_by_admin()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        $admin = User::factory()->create([
            'role_id' => 1,
            'position_id' => 1
        ]);

        // Membuat record izin dengan mengisi field title dan description agar lolos NOT NULL constraint
        $permission = Permission::create([
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'permission_date' => now()->toDateString(),
            'title' => 'Sakit Demam Tinggi',
            'description' => 'Izin tidak masuk kerja, surat dokter terlampir.',
            'is_accepted' => 0
        ]);

        // Admin melakukan approval via HTTP POST request dengan payload eksplisit
        $response = $this->actingAs($admin)
            ->post("/presences/{$this->attendance->id}/acceptPermission", [
                'user_id' => (string) $employee->id,
                'permission_date' => now()->toDateString()
            ]);

        // Memastikan status izin di database bener-bener berubah menjadi 1 (Accepted)
        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'is_accepted' => true
        ]);

        // Sistem harus otomatis membuat baris baru di tabel presences dengan flag is_permission
        $this->assertDatabaseHas('presences', [
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
            'is_permission' => true
        ]);
    }

    /** 5. Test Operator Dilarang Masuk ke Halaman Beranda Utama Milik Karyawan (Redirect Security) */
    public function test_operator_cannot_access_employee_attendance()
    {
        // Membuat user dengan role operator
        $operator = User::factory()->create([
            'role_id' => 2,
            'position_id' => 4
        ]);

        $response = $this->actingAs($operator)->get(route('home.index'));

        // Memastikan middleware menolak operator masuk lapak user dan me-redirect paksa keluar (status 302)
        $response->assertRedirect();
    }

    /** 6. Test Dashboard: Memastikan sistem akurat mendeteksi status karyawan yang sudah absen masuk tapi belum absen pulang */
    public function test_employee_dashboard_can_correctly_detect_attendance_status()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Kondisi: Karyawan sudah sukses absen masuk jam 8 pagi ini
        Presence::create([
            'user_id' => $employee->id,
            'attendance_id' => $this->attendance->id,
            'presence_date' => now()->toDateString(),
            'presence_enter_time' => '08:00:00',
            'presence_out_time' => null // Belum absen pulang
        ]);

        // Karyawan mengakses halaman detail informasi absensi (Fungsi show() di HomeController)
        $response = $this->actingAs($employee)->get("/absensi/{$this->attendance->id}");

        $response->assertStatus(200);

        // Memastikan data status yang dilempar ke View Blade nilainya benar dan akurat
        $response->assertViewHas('data', function ($data) {
            return $data['is_has_enter_today'] === true &&
                   $data['is_not_out_yet'] === true &&
                   $data['is_there_permission'] === false;
        });
    }

    /** 7. Test Dashboard: Memastikan sistem berhasil menampilkan info hari libur saat admin mengaktifkan status holiday */
    public function test_employee_dashboard_correctly_shows_holiday_announcement()
    {
        $employee = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // Masukkan data hari libur tiruan dengan menyertakan description agar lolos NOT NULL constraint
        DB::table('holidays')->insert([
            'id' => 99,
            'title' => 'Hari Libur Nasional',
            'description' => 'Libur resmi memperingati hari besar nasional.',
            'holiday_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Ubah mock state session absensi menjadi sedang libur hari ini
        $this->attendance->data->is_holiday_today = true;

        // Karyawan mengakses halaman depan absensi
        $response = $this->actingAs($employee)->get("/absensi/{$this->attendance->id}");

        $response->assertStatus(200);

        // Memastikan data holiday yang dilempar ke view tidak bernilai false, melainkan berisi data libur yang valid
        $response->assertViewHas('holiday', function ($holiday) {
            return $holiday !== false && $holiday->title === 'Hari Libur Nasional';
        });
    }
}
