<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup data dasar agar foreign key database kelompokmu terpenuhi
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai "Biasa"', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Operator', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->attendance = Attendance::create([
            'title' => 'Sesi Absen Security Test',
            'description' => 'Untuk uji coba keamanan celah rute',
            'start_time' => '07:00:00',
            'batas_start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'batas_end_time' => '17:00:00',
            'code' => 'SECURE-ROUTE-TEST'
        ]);

        $this->attendance->data = (object)[
            'is_start' => true,
            'is_end' => false, // Set false: pura-puranya belum jam pulang (tombol sembunyi)
            'is_using_qrcode' => true,
            'is_holiday_today' => false
        ];
    }

//    /** 1. Test Kasus Paksa: Karyawan nembak URL absen pulang padahal di web tombolnya gak muncul (Jam 10 Pagi) */
//     public function test_employee_cannot_force_check_out_if_not_the_time_yet()
//     {
//         $employee = User::factory()->create(['role_id' => 3, 'position_id' => 1]);

//         // Sudah absen masuk jam 8
//         $presence = Presence::create([
//             'user_id' => $employee->id,
//             'attendance_id' => $this->attendance->id,
//             'presence_date' => now()->toDateString(),
//             'presence_enter_time' => '08:00:00',
//             'presence_out_time' => null
//         ]);

//         // Lompat ke jam 10 pagi, lalu coba tembak endpoint POST secara ilegal
//         $this->travelTo(now()->setTime(10, 0, 0));

//         $response = $this->actingAs($employee)->post(route('home.sendOutPresenceUsingQRCode'), [
//             'code' => 'SECURE-ROUTE-TEST'
//         ]);

//         // Memastikan kolom presence_out_time di database tetap NULL (tidak bocor/jebol)
//         $this->assertDatabaseHas('presences', [
//             'id' => $presence->id,
//             'presence_out_time' => null
//         ]);
//     }

    // /** 2. Test Security: Hacker / Guest (Belum Login) Dilarang Keras Masuk ke Halaman Mana Pun */
    // public function test_guest_is_redirected_to_login_page()
    // {
    //     // Coba buka dashboard admin tanpa login
    //     $response1 = $this->get('/dashboard');
    //     $response1->assertRedirect(route('auth.login'));

    //     // Coba buka home karyawan tanpa login
    //     $response2 = $this->get(route('home.index'));
    //     $response2->assertRedirect(route('auth.login'));
    // }

    /** 3. Test Security: Karyawan Biasa Nekat Tembak Route POST Approve Izin Milik Admin */
    public function test_employee_cannot_force_approve_leave_route()
    {
        $employee = User::factory()->create(['role_id' => 3, 'position_id' => 1]);

        // Karyawan mencoba mengirim request POST approval atas namanya sendiri ke controller admin
        $response = $this->actingAs($employee)->post("/presences/{$this->attendance->id}/acceptPermission", [
            'user_id' => $employee->id,
            'permission_date' => now()->toDateString()
        ]);

        // Harus mental (status 302 redirect kembali karena dihadang middleware role)
        $response->assertStatus(302);
    }
}