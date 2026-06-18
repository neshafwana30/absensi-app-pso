<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Presence;
use App\Models\Attendance; // Wajib import model Attendance
use Carbon\Carbon;

class PresenceSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua data pegawai
        $users = User::all();

        // Ambil absensi pertama yang ada di database (misal: Absen Kantor)
        $attendance = Attendance::first();

        // Cek darurat: kalau absennya belum ada, hentikan seeder biar nggak error
        if (!$attendance) {
            $this->command->info('Data Attendance (Absen) belum ada! Buat dulu lewat aplikasi atau AttendanceSeeder.');
            return;
        }

        // Putar waktu dari 6 hari lalu sampai hari ini (total 7 hari)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            foreach ($users as $user) {
                // Beri peluang 90% pegawai ini absen hari itu (10% alpa/tidak ada data)
                if (rand(1, 100) <= 90) {
                    
                    // Beri peluang 15% dari yang absen tersebut statusnya Izin/Sakit, sisanya Hadir
                    $isPermission = rand(1, 100) <= 15;

                    Presence::create([
                        'user_id' => $user->id,
                        'attendance_id' => $attendance->id, // <--- INI TAMBAHAN WAJIBNYA
                        'is_permission' => $isPermission,
                        'presence_date' => $date->format('Y-m-d'), 
                        'presence_enter_time' => '08:00:00',
                        'presence_out_time' => '17:00:00',
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }
    }
}