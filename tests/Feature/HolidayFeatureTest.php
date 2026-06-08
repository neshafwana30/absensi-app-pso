<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Holiday;
use App\Http\Livewire\HolidayCalendar;
use App\Http\Livewire\HolidayEditForm;
use App\Http\Livewire\HolidayCreateForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HolidayFeatureTest extends TestCase
{
    use RefreshDatabase; // Mengosongkan DB virtual setiap test biar steril

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 🔥 CONTEK TRICK KELOMPOKMU: Suntik Data Master Roles langsung ke DB virtual
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. 🔥 Suntik Data Master Positions langsung ke DB virtual biar gak NOT NULL violation
        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai "Biasa"', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Direktur', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Operator', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Sekarang bikin user admin dummy pake data master yang valid di atas!
        $this->admin = User::factory()->create([
            'role_id' => 1,     
            'position_id' => 1, 
        ]);
    }

    /** @test */
    public function halaman_kalender_bisa_diakses_oleh_admin()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('holidays.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('holiday-calendar');
    }

    // /** @test */
    // public function komponen_kalender_berhasil_load_bulan_aktif()
    // {
    //     // Buat data libur dummy di database dengan deskripsi agar lolos not null
    //     Holiday::create([
    //         'title' => 'Libur Dummy Bub',
    //         'description' => 'Testing manual input',
    //         'holiday_date' => date('Y-m-d')
    //     ]);

    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayCalendar::class)
    //         ->assertSet('currentYear', date('Y'))
    //         ->assertSet('currentMonth', date('m'))
    //         ->assertSee('Libur Dummy Bub');
    // }

    // /** @test */
    // public function form_tambah_bisa_menyimpan_banyak_data_libur_sekaligus()
    // {
    //     // Hitung tanggal otomatis untuk 1 bulan dan 2 bulan ke depan
    //     $bulanDepan = date('Y-m-d', strtotime('+1 month'));
    //     $duaBulanLagi = date('Y-m-d', strtotime('+2 months'));

    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayCreateForm::class)
    //         ->set('holidays', [
    //             [
    //                 'title' => 'Libur Bulan Depan (Form)',
    //                 'description' => 'Dibuat simulasi dari form tambah',
    //                 'holiday_date' => $bulanDepan
    //             ],
    //             [
    //                 'title' => 'Libur Dua Bulan Lagi (Form)',
    //                 'description' => 'Dibuat simulasi dari form tambah juga',
    //                 'holiday_date' => $duaBulanLagi
    //             ]
    //         ])
    //         ->call('saveHolidays')
    //         ->assertRedirect(route('holidays.index'));

    //     // Pastikan satpam DB mencatat kedua data masa depan ini masuk dengan sukses
    //     $this->assertDatabaseHas('holidays', [
    //         'title' => 'Libur Bulan Depan (Form)',
    //         'holiday_date' => $bulanDepan
    //     ]);
        
    //     $this->assertDatabaseHas('holidays', [
    //         'title' => 'Libur Dua Bulan Lagi (Form)',
    //         'holiday_date' => $duaBulanLagi
    //     ]);
    // }

    // /** @test */
    // public function form_edit_bisa_menghapus_data_libur_manual()
    // {
    //     $holiday = Holiday::create([
    //         'title' => 'Data Malas Kerja',
    //         'description' => 'Mau dihapus',
    //         'holiday_date' => date('Y-m-d', strtotime('+5 days'))
    //     ]);

    //     // Bungkus ke dalam Eloquent Collection seperti kiriman controller asli
    //     $collection = Holiday::where('id', $holiday->id)->get();

    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayEditForm::class, ['holidays' => $collection])
    //         ->call('deleteHoliday')
    //         ->assertRedirect(route('holidays.index'));

    //     $this->assertDatabaseMissing('holidays', [
    //         'id' => $holiday->id
    //     ]);
    // }

    // /** @test */
    // public function form_edit_bisa_mengubah_data_libur_yang_sudah_ada()
    // {
    //     // 1. Buat data libur lama dulu di database
    //     $holiday = Holiday::create([
    //         'title' => 'Libur Lama Bub',
    //         'description' => 'Deskripsi lama',
    //         'holiday_date' => date('Y-m-d', strtotime('+3 days'))
    //     ]);

    //     // Bungkus ke dalam Eloquent Collection seperti kiriman asli dari controller
    //     $collection = Holiday::where('id', $holiday->id)->get();

    //     // 2. Jalankan test Livewire Form Edit buat ngubah judul & deskripsinya
    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayEditForm::class, ['holidays' => $collection])
    //         ->set('holidays.0.title', 'Libur Baru Dong Anjay')
    //         ->set('holidays.0.description', 'Deskripsi sudah diupdate!')
    //         // 🔥 FIX: Ganti updateHolidays jadi saveHolidays sesuai nama method di backend kamu!
    //         ->call('saveHolidays') 
    //         ->assertRedirect(route('holidays.index'));

    //     // 3. BUKTIKAN: Pastikan data lama udah berganti dengan data baru di database!
    //     $this->assertDatabaseHas('holidays', [
    //         'id' => $holiday->id,
    //         'title' => 'Libur Baru Dong Anjay',
    //         'description' => 'Deskripsi sudah diupdate!'
    //     ]);

    //     // Pastikan data yang lama banget udah ilang/terganti
    //     $this->assertDatabaseMissing('holidays', [
    //         'title' => 'Libur Lama Bub'
    //     ]);
    // }

    // /** @test */
    // public function tombol_navigasi_prev_dan_next_kalender_berhasil_mengubah_bulan()
    // {
    //     // Ambil bulan dan tahun aktif saat ini sebagai baseline
    //     $currentMonth = (int) date('m');
    //     $currentYear = (int) date('Y');

    //     // Hitung bulan sebelumnya secara manual untuk ekspektasi test
    //     $expectedPrevMonth = $currentMonth - 1;
    //     $expectedPrevYear = $currentYear;
    //     if ($expectedPrevMonth === 0) {
    //         $expectedPrevMonth = 12;
    //         $expectedPrevYear = $currentYear - 1;
    //     }

    //     // Jalankan simulasi klik tombol di Livewire
    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayCalendar::class)
    //         // 1. Test Tombol Prev (Mundur 1 Bulan)
    //         ->call('previousMonth')
    //         ->assertSet('currentMonth', $expectedPrevMonth)
    //         ->assertSet('currentYear', $expectedPrevYear)
            
    //         // 2. Test Tombol Next (Maju balik ke bulan awal)
    //         ->call('nextMonth')
    //         ->assertSet('currentMonth', $currentMonth)
    //         ->assertSet('currentYear', $currentYear);
    // }

    // /** @test */
    // public function tombol_navigasi_bisa_pindah_bulan_dan_menampilkan_data_libur_yang_sesuai()
    // {
    //     // 1. Jalankan simulasi form tambah buat ngisi data bulan depan & 2 bulan lagi ke DB
    //     $bulanDepan = date('Y-m-d', strtotime('+1 month'));
    //     $duaBulanLagi = date('Y-m-d', strtotime('+2 months'));

    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayCreateForm::class)
    //         ->set('holidays', [
    //             [
    //                 'title' => 'Libur Bulan Depan (Form)',
    //                 'description' => 'Testing navigasi next 1x',
    //                 'holiday_date' => $bulanDepan
    //             ],
    //             [
    //                 'title' => 'Libur Dua Bulan Lagi (Form)',
    //                 'description' => 'Testing navigasi next 2x',
    //                 'holiday_date' => $duaBulanLagi
    //             ]
    //         ])
    //         ->call('saveHolidays');

    //     // 2. Sekarang kita uji komponen Kalendernya!
    //     Livewire::actingAs($this->admin)
    //         ->test(HolidayCalendar::class)
    //         // Di awal (bulan aktif saat ini), pastikan belum ngeliat data bulan depan
    //         ->assertDontSee('Libur Bulan Depan (Form)')
            
    //         // 🔥 AKSI 1: Klik Next (Maju ke bulan depan)
    //         ->call('nextMonth')
    //         // BUKTIKAN: Kalender beneran nampilin datanya setelah di-next!
    //         ->assertSee('Libur Bulan Depan (Form)')
    //         ->assertDontSee('Libur Dua Bulan Lagi (Form)')
            
    //         // 🔥 AKSI 2: Klik Next Lagi (Maju ke 2 bulan ke depan)
    //         ->call('nextMonth')
    //         // BUKTIKAN: Data 2 bulan lagi beneran nongol, dan data bulan depan ke-hide!
    //         ->assertSee('Libur Dua Bulan Lagi (Form)')
    //         ->assertDontSee('Libur Bulan Depan (Form)')

    //         // 🔥 AKSI 3: Klik Prev (Mundur lagi ke 1 bulan ke depan)
    //         ->call('previousMonth')
    //         // BUKTIKAN: Kalender pinter balik nampilin data bulan depan lagi!
    //         ->assertSee('Libur Bulan Depan (Form)')
    //         ->assertDontSee('Libur Dua Bulan Lagi (Form)');
    // }
}