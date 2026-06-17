<?php

namespace Tests\Feature;

use App\Http\Livewire\AttendanceCreateForm;
use App\Models\Attendance;
use App\Models\Position;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Memastikan Admin bisa membuat absensi dinamis (seperti Absen Padel)
     * melalui komponen Livewire dan tersimpan dengan struktur baru di database.
     */
    public function test_admin_can_create_dynamic_attendance()
    {
        // Buat role admin dan user dummy untuk login
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Buat master posisi/jabatan
        $position = Position::create(['name' => 'Pegawai "Biasa"']);

        // Jalankan simulasi test pada komponen Livewire form create
        Livewire::actingAs($admin)
            ->test(AttendanceCreateForm::class)
            ->set('attendance.title', 'Absen Padel Bersama')
            ->set('attendance.description', 'Kategori absen khusus untuk main padel berkali-kali')
            ->set('attendance.start_time', '07:00')
            ->set('attendance.batas_start_time', '09:00')
            ->set('attendance.end_time', '16:00')
            ->set('attendance.batas_end_time', '17:00')
            ->set('position_ids', [$position->id => $position->id])
            ->call('save')
            ->assertRedirect(route('attendances.index'));

        // Pastikan data tersimpan di database dengan benar
        $this->assertDatabaseHas('attendances', [
            'title' => 'Absen Padel Bersama'
        ]);
    }

    /**
     * Test 2: Memastikan Dashboard Controller sukses memproses filter ganda
     * (Absen & Jabatan) dan mengamankan nilai "Tidak Hadir" agar tidak minus.
     */
    public function test_dashboard_filter_logic_and_prevent_negative_values()
    {
        // Buat role admin dan user dummy
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Buat satu master data absensi
        $attendance = Attendance::create([
            'title' => 'Absen Kantor Reguler',
            'description' => 'Digunakan harian',
            'start_time' => '07:00',
            'batas_start_time' => '08:00',
            'end_time' => '16:00',
            'batas_end_time' => '17:00'
        ]);

        // Hitung index halaman dashboard dengan parameter filter
        $response = $this->actingAs($admin)
            ->get(route('dashboard.index', [
                'attendance_id' => $attendance->id,
                'position_id' => ''
            ]));

        $response->assertStatus(200);

        // Validasi: Pastikan data hitungan absentToday yang dilempar ke view bernilai minimal 0 (tidak minus)
        $response->assertViewHas('absentToday', function ($absentToday) {
            return $absentToday >= 0;
        });

        // Validasi: Pastikan data chartData terisi array 5 hari dan tidak kosong
        $response->assertViewHas('chartData', function ($chartData) {
            return is_array($chartData) && count($chartData) === 5;
        });
    }

    /**
     * Test 3: Memastikan halaman beranda pegawai otomatis merender status "Tutup"
     * ketika waktu saat ini berada di luar batas toleransi jam absen masuk.
     */
    public function test_employee_sees_closed_status_outside_attendance_hours()
    {
        // Buat role user/pegawai biasa
        $userRole = Role::create(['name' => 'user']);
        $employee = User::factory()->create(['role_id' => $userRole->id]);

        // Buat data absensi dengan range waktu spesifik
        $attendance = Attendance::create([
            'title' => 'Absen Padel Sore',
            'description' => 'Khusus olahraga sore',
            'start_time' => '15:00',
            'batas_start_time' => '16:00',
            'end_time' => '18:00',
            'batas_end_time' => '19:00'
        ]);

        // Manipulasi waktu sistem ke jam 21:00 malam (sudah lewat batas absen masuk)
        Carbon::setTestNow(Carbon::createFromTime(21, 0, 0));

        // Akses halaman beranda (sesuaikan nama route halaman utama pegawai kamu, misal 'home' atau 'index')
        $response = $this->actingAs($employee)->get(route('home'));

        $response->assertStatus(200);

        // Pastikan teks "Tutup" tampil di halaman web pegawai karena sudah telat
        $response->assertSee('Tutup');

        // Kembalikan waktu sistem ke kondisi aslinya (wajib agar tidak merusak test lain)
        Carbon::setTestNow();
    }
}