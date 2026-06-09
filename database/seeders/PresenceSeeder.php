<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Presence;
use Carbon\Carbon;

class PresenceSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua data pegawai
        $users = User::all();

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
                        'is_permission' => $isPermission,
                        // Asumsi tabelmu butuh kolom tanggal, format: YYYY-MM-DD
                        'presence_date' => $date->format('Y-m-d'), 
                        'presence_enter_time' => '08:00:00', // Sesuaikan jika ada kolom jam masuk
                        'presence_out_time' => '17:00:00',   // Sesuaikan jika ada kolom jam keluar
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }
    }
}