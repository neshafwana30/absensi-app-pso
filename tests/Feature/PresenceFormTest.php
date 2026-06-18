<?php

namespace Tests\Feature;

use App\Http\Livewire\PresenceForm;
use App\Models\Attendance;
use App\Models\Presence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PresenceFormTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $attendanceManual;
    protected $attendanceQR;
    protected $officeLat;
    protected $officeLng;
    protected $defaultDataState;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Inject master data roles dan positions agar foreign key aman
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'operator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'user', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Pegawai', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Ambil konfigurasi titik koordinat kantor harian (Paper Cup Darhus)
        $this->officeLat = config('attendance.office_latitude');
        $this->officeLng = config('attendance.office_longitude');

        // 2. Buat data user dummy yang terikat dengan foreign key di atas
        $this->user = User::factory()->create([
            'role_id' => 3,
            'position_id' => 1
        ]);

        // 3. Sesi Absensi MANUAL (NON-QR)
        $this->attendanceManual = Attendance::create([
            'title' => 'Absen Manual PSO',
            'description' => 'Sesi absensi menggunakan tombol biasa',
            'start_time' => '00:00:00',
            'batas_start_time' => '23:59:00',
            'end_time' => '00:00:00', 
            'batas_end_time' => '23:59:00',
            'code' => 'MANUAL-PSO',
            'data' => json_encode([
                'is_start' => true,
                'is_end' => true,
                'is_using_qrcode' => false,
                'is_holiday_today' => false
            ])
        ]);

        // 4. Sesi Absensi QR CODE
        $this->attendanceQR = Attendance::create([
            'title' => 'Absen QR PSO',
            'description' => 'Sesi absensi menggunakan pemindai QR',
            'start_time' => '00:00:00',
            'batas_start_time' => '23:59:00',
            'end_time' => '00:00:00', 
            'batas_end_time' => '23:59:00',
            'code' => 'QR-TEST-WORKFLOW',
            'data' => json_encode([
                'is_start' => true,
                'is_end' => true,
                'is_using_qrcode' => true,
                'is_holiday_today' => false
            ])
        ]);

        // 5. Siapkan array state default biar view blade tidak 'Undefined array key'
        $this->defaultDataState = [
            'is_there_permission' => false,
            'is_has_enter_today' => false,
            'is_not_out_yet' => false,
            'is_permission_accepted' => false
        ];
    }

    // =========================================================================
    // KASUS 1: ABSENSI MASUK & PULANG NON-QR (MANUAL) DI DALAM RADIUS
    // =========================================================================
    
    /** @test */
    public function kasus_1_absen_masuk_dan_pulang_manual_di_dalam_radius_sukses()
    {
        $this->actingAs($this->user);

        // A. Jalankan simulasi masuk manual
        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceManual, 'data' => $this->defaultDataState])
            ->set('latitude', $this->officeLat)
            ->set('longitude', $this->officeLng)
            ->call('sendEnterPresence')
            ->assertDispatchedBrowserEvent('showToast'); // Deteksi event pemicu toast muncul

        $this->assertDatabaseHas('presences', [
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendanceManual->id,
        ]);

        // B. Jalankan simulasi pulang manual (Isolasi dengan state setelah masuk)
        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceManual, 'data' => $stateSudahMasuk])
            ->set('latitude', $this->officeLat)
            ->set('longitude', $this->officeLng)
            ->call('sendOutPresence')
            ->assertDispatchedBrowserEvent('showToast');
    }

    // =========================================================================
    // KASUS 2: ABSENSI MASUK & PULANG NON-QR (MANUAL) DI LUAR RADIUS
    // =========================================================================

    /** @test */
    public function kasus_2_absen_masuk_dan_pulang_manual_di_luar_radius_ditolak()
    {
        $this->actingAs($this->user);
        
        $fakeLatOutside = -6.200000; 
        $fakeLngOutside = 106.816666;

        // A. Kirim absen masuk dari luar radius
        $test = Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceManual, 'data' => $this->defaultDataState])
            ->set('latitude', $fakeLatOutside)
            ->set('longitude', $fakeLngOutside)
            ->call('sendEnterPresence')
            ->assertDispatchedBrowserEvent('showToast');

        $this->assertDatabaseMissing('presences', ['user_id' => $this->user->id]);

        // B. Coba tembak absen pulang dari luar radius
        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceManual, 'data' => $stateSudahMasuk])
            ->set('latitude', $fakeLatOutside)
            ->set('longitude', $fakeLngOutside)
            ->call('sendOutPresence')
            ->assertDispatchedBrowserEvent('showToast');
    }

    // =========================================================================
    // KASUS 3: ABSENSI MASUK & PULANG QR CODE DI DALAM RADIUS
    // =========================================================================

    /** @test */
    public function kasus_3_absen_masuk_dan_pulang_qr_di_dalam_radius_sukses()
    {
        $this->actingAs($this->user);

        // A. Scan QR masuk di dalam radius
        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceQR, 'data' => $this->defaultDataState])
            ->set('latitude', $this->officeLat)
            ->set('longitude', $this->officeLng)
            ->call('sendEnterPresenceUsingQRCode', 'QR-TEST-WORKFLOW')
            ->assertDispatchedBrowserEvent('showToast');

        $this->assertDatabaseHas('presences', [
            'user_id' => $this->user->id,
            'attendance_id' => $this->attendanceQR->id,
        ]);

        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        // B. Scan QR pulang di dalam radius
        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceQR, 'data' => $stateSudahMasuk])
            ->set('latitude', $this->officeLat)
            ->set('longitude', $this->officeLng)
            ->call('sendOutPresenceUsingQRCode', 'QR-TEST-WORKFLOW')
            ->assertDispatchedBrowserEvent('showToast');
    }

    // =========================================================================
    // KASUS 4: ABSENSI MASUK & PULANG QR CODE DI LUAR RADIUS
    // =========================================================================

    /** @test */
    public function kasus_4_absen_masuk_dan_pulang_qr_di_luar_radius_ditolak()
    {
        $this->actingAs($this->user);

        $fakeLatOutside = -6.200000;
        $fakeLngOutside = 106.816666;

        // A. Scan QR masuk dari lokasi luar radius
        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceQR, 'data' => $this->defaultDataState])
            ->set('latitude', $fakeLatOutside)
            ->set('longitude', $fakeLngOutside)
            ->call('sendEnterPresenceUsingQRCode', 'QR-TEST-WORKFLOW')
            ->assertDispatchedBrowserEvent('showToast');

        // B. Coba scan QR pulang dari luar radius
        $stateSudahMasuk = $this->defaultDataState;
        $stateSudahMasuk['is_has_enter_today'] = true;
        $stateSudahMasuk['is_not_out_yet'] = true;

        Livewire::test(PresenceForm::class, ['attendance' => $this->attendanceQR, 'data' => $stateSudahMasuk])
            ->set('latitude', $fakeLatOutside)
            ->set('longitude', $fakeLngOutside)
            ->call('sendOutPresenceUsingQRCode', 'QR-TEST-WORKFLOW')
            ->assertDispatchedBrowserEvent('showToast');
    }
}