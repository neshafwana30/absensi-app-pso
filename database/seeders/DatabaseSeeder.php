<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Role;
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
    }
}