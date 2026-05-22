<?php

namespace Tests\Feature;

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementDataUnitTest extends TestCase
{
    use RefreshDatabase;

    /** 1. Unit Test: Memastikan pembuatan data Jabatan (Position) baru berfungsi dengan benar */
    public function test_position_can_be_instantiated_with_correct_attributes()
    {
        // 1. Membuat data jabatan tiruan di memori
        $positionData = [
            'name' => 'Data Scientist Specialist'
        ];

        // 2. Eksekusi pembuatan data menggunakan Model Position
        $position = Position::create($positionData);

        // 3. Assertions: Cek apakah data objek yang dibuat sesuai dan tersimpan di database testing
        $this->assertInstanceOf(Position::class, $position);
        $this->assertEquals('Data Scientist Specialist', $position->name);
        
        $this->assertDatabaseHas('positions', [
            'name' => 'Data Scientist Specialist'
        ]);
    }

    /** 2. Unit Test: Memastikan pembuatan data Karyawan (User) baru terikat dengan Role & Position yang tepat */
    public function test_new_employee_can_be_created_with_assigned_role_and_position()
    {
        // 1. Buat data master pendukung terlebih dahulu di database testing
        $positionId = \Illuminate\Support\Facades\DB::table('positions')->insertGetId([
            'name' => 'Junior Developer',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $roleId = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'name' => 'user',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. Siapkan data karyawan baru
        $employeeData = [
            'name' => 'Nesha Agustina',
            'email' => 'nesha@its.ac.id',
            'password' => bcrypt('password123'),
            'role_id' => $roleId,
            'position_id' => $positionId
        ];

        // 3. Eksekusi pembuatan user baru lewat Model User
        $employee = User::create($employeeData);

        // 4. Assertions: Pastikan objek tercipta dengan atribut yang benar-benar cocok
        $this->assertInstanceOf(User::class, $employee);
        $this->assertEquals('Nesha Agustina', $employee->name);
        $this->assertEquals('nesha@its.ac.id', $employee->email);
        $this->assertEquals($roleId, $employee->role_id);
        $this->assertEquals($positionId, $employee->position_id);

        // 5. Cek apakah record-nya beneran masuk ke tabel users database testing
        $this->assertDatabaseHas('users', [
            'email' => 'nesha@its.ac.id',
            'role_id' => $roleId,
            'position_id' => $positionId
        ]);
    }
}