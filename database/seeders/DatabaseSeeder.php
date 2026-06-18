<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Role;
use App\Models\Attendance; // Wajib ditambahkan agar bisa memanggil tabel attendances
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(PositionSeeder::class);

        // 1. Pembuatan Akun Admin Utama
        \App\Models\User::factory()->create([
            'name' => 'Muhammad Pauzi (Admin)',
            'email' => 'admin@gmail.com',
            'role_id' => Role::where('name', 'admin')->first('id'),
            'position_id' => Position::where('name', 'Operator')->first('id'),
        ]);

        // 2. Pembuatan Akun Operator
        \App\Models\User::factory(1)->create([
            'role_id' => Role::where('name', 'operator')->first('id'),
            'position_id' => Position::where('name', 'Operator')->first('id'),
        ]);

        // 3. Pembuatan Akun Pegawai (Dibagi rata ke semua jabatan)
        $semuaJabatan = Position::all();
        
        foreach ($semuaJabatan as $jabatan) {
            \App\Models\User::factory(3)->create([
                'role_id' => Role::where('name', 'user')->first('id'), // user === employee
                'position_id' => $jabatan->id
            ]);
        }

        // 4. Pembuatan Master Absensi Pertama (Wajib agar PresenceSeeder jalan)
        $attendance = Attendance::create([
            'title' => 'Absen Kantor Reguler',
            'description' => 'Absensi harian wajib karyawan',
            'start_time' => '06:00',
            'batas_start_time' => '09:00',
            'end_time' => '16:00',
            'batas_end_time' => '19:00',
        ]);

        // Kaitkan absen ini ke semua jabatan yang ada (biar semua user bisa lihat absennya)
        $attendance->positions()->attach($semuaJabatan->pluck('id'));

        // 5. Jalankan Presence Seeder untuk membuat riwayat absen dummy 7 hari lalu
        $this->call(PresenceSeeder::class);
    }
}