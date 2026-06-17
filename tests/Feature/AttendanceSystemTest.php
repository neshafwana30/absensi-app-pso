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
     * Test 1: Memastikan Admin bisa membuat absensi dinamis
     */
    public function test_admin_can_create_dynamic_attendance()
    {
        $position = Position::create(['name' => 'Pegawai "Biasa"']);
        $adminRole = Role::create(['name' => 'admin']);
        
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'position_id' => $position->id
        ]);

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

        $this->assertDatabaseHas('attendances', [
            'title' => 'Absen Padel Bersama'
        ]);
    }

    /**
     * Test 2: Memastikan Dashboard Controller sukses memproses filter ganda
     */
    public function test_dashboard_filter_logic_and_prevent_negative_values()
    {
        $position = Position::create(['name' => 'Admin HR']);
        $adminRole = Role::create(['name' => 'admin']);
        
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'position_id' => $position->id
        ]);

        $attendance = Attendance::create([
            'title' => 'Absen Kantor Reguler',
            'description' => 'Digunakan harian',
            'start_time' => '07:00',
            'batas_start_time' => '08:00',
            'end_time' => '16:00',
            'batas_end_time' => '17:00'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('dashboard.index', [
                'attendance_id' => $attendance->id,
                'position_id' => ''
            ]));

        $response->assertStatus(200);

        $response->assertViewHas('absentToday', function ($absentToday) {
            return $absentToday >= 0;
        });

        $response->assertViewHas('chartData', function ($chartData) {
            return is_array($chartData) && count($chartData) === 5;
        });
    }

    /**
     * Test 3: Memastikan halaman beranda pegawai otomatis merender status "Tutup"
     */
    public function test_employee_sees_closed_status_outside_attendance_hours()
    {
        $position = Position::create(['name' => 'Pegawai "Biasa"']);
        $userRole = Role::create(['name' => 'user']);
        
        $employee = User::factory()->create([
            'role_id' => $userRole->id,
            'position_id' => $position->id
        ]);

        $attendance = Attendance::create([
            'title' => 'Absen Padel Sore',
            'description' => 'Khusus olahraga sore',
            'start_time' => '15:00',
            'batas_start_time' => '16:00',
            'end_time' => '18:00',
            'batas_end_time' => '19:00'
        ]);

        // KUNCI PERBAIKAN: Hubungkan absensi dengan posisi pegawai
        $attendance->positions()->attach($position->id);

        Carbon::setTestNow(Carbon::createFromTime(21, 0, 0));

        $response = $this->actingAs($employee)->get(route('home.index'));

        $response->assertStatus(200);
        $response->assertSee('Tutup');

        Carbon::setTestNow();
    }
}