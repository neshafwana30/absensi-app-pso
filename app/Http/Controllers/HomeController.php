<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Presence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * 1. FUNGSI ABSEN MASUK (Strict Range Waktu Berulang Harian)
     */
    public function sendEnterPresenceUsingQRCode(Request $request)
    {
        // Cari sesi absensi berdasarkan code QR yang di-scan
        $attendance = Attendance::where('code', $request->code)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Kode QR Tidak Valid'], 400);
        }

        $now = Carbon::now();
        $todayStr = $now->toDateString();

        // 🎯 FIX UTAMA: Gabungkan tanggal hari ini dengan jam dari database agar perbandingan 100% akurat
        $startTime = Carbon::parse($todayStr . ' ' . $attendance->start_time);
        $batasStartTime = Carbon::parse($todayStr . ' ' . $attendance->batas_start_time);

        // Validasi 2: Kunci range waktu masuk
        if (!$now->between($startTime, $batasStartTime)) {
            return response()->json([
                'message' => 'Waktu absensi masuk belum dimulai atau sudah ditutup! (Range: ' . $attendance->start_time . ' - ' . $attendance->batas_start_time . ')'
            ], 400);
        }

        // Validasi 3: Pastikan user belum absen masuk hari ini
        $alreadyCheckedIn = Presence::where('user_id', auth()->id())
            ->where('attendance_id', $attendance->id)
            ->where('presence_date', $todayStr)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json(['message' => 'Anda sudah melakukan absen masuk hari ini!'], 400);
        }

        // Simpan data ke database
        Presence::create([
            'user_id' => auth()->id(),
            'attendance_id' => $attendance->id,
            'presence_date' => $todayStr,
            'presence_enter_time' => $now->toTimeString(),
            'presence_out_time' => null
        ]);

        return response()->json(['message' => 'Absen masuk berhasil!'], 200);
    }

    /**
     * 2. FUNGSI ABSEN PULANG (Strict Range Waktu Berulang Harian)
     */
    public function sendOutPresenceUsingQRCode(Request $request)
    {
        if ($request->code === 'QR-DEMO-BYPASS') {
            $presence = Presence::where('user_id', auth()->id())
                ->where('presence_date', now()->toDateString())
                ->first();

            if ($presence) {
                $presence->update(['presence_out_time' => now()->toTimeString()]);
                return response()->json(['message' => 'Bypass absen pulang sukses!'], 200);
            }
            return response()->json(['message' => 'Data absen masuk hari ini tidak ditemukan.'], 400);
        }

        $attendance = Attendance::where('code', $request->code)->first();

        if (!$attendance) {
            return response()->json(['message' => 'Kode QR Tidak Valid'], 400);
        }

        $now = Carbon::now();
        $todayStr = $now->toDateString();

        // 🎯 FIX UTAMA: Gabungkan tanggal hari ini dengan jam dari database agar perbandingan 100% akurat
        $endTime = Carbon::parse($todayStr . ' ' . $attendance->end_time);
        $batasEndTime = Carbon::parse($todayStr . ' ' . $attendance->batas_end_time);

        // Validasi 2: Kunci range waktu pulang
        if (!$now->between($endTime, $batasEndTime)) {
            return response()->json([
                'message' => 'Waktu absensi pulang belum dimulai atau sudah ditutup! (Range: ' . $attendance->end_time . ' - ' . $attendance->batas_end_time . ')'
            ], 400);
        }

        // Validasi 3: Pastikan karyawan sudah absen masuk terlebih dahulu
        $presence = Presence::where('user_id', auth()->id())
            ->where('attendance_id', $attendance->id)
            ->where('presence_date', $todayStr)
            ->first();

        if (!$presence) {
            return response()->json(['message' => 'Anda belum melakukan absen masuk hari ini!'], 400);
        }

        // Validasi 4: Pastikan belum pernah absen pulang hari ini
        if ($presence->presence_out_time !== null) {
            return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini!'], 400);
        }

        $presence->update([
            'presence_out_time' => $now->toTimeString()
        ]);

        return response()->json(['message' => 'Absen pulang berhasil!'], 200);
    }

    /**
     * Tampilan Beranda Utama Karyawan
     */
    public function index()
    {
        // 1. Ambil data sesi absensi harian seperti semula
        $attendances = Attendance::all();

        // 2. 🎯 FIX UTAMA: Definisikan variabel $title yang dicari oleh layout master HTML kamu
        $title = "Dashboard Karyawan";

        // Lempar kedua variabel tersebut ke view menggunakan compact
        return view('home.index', compact('attendances', 'title'));
    }

    /**
     * Menampilkan halaman detail sesi absensi (Tempat scan QR, info libur, & histori 30 hari)
     */
    public function show(Attendance $attendance)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        // 1. Ambil data riwayat kehadiran user untuk sesi ini pada hari berjalan
        $presence = Presence::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->where('presence_date', $today)
            ->first();

        // 2. Cek status perizinan hari ini untuk sesi absensi terkait
        $isTherePermission = \App\Models\Permission::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->where('permission_date', $today)
            ->where('is_accepted', true)
            ->exists();

        // 3. Menyusun array $data sesuai dengan properti yang dicari oleh View kelompokmu
        $data = [
            'is_there_permission' => $isTherePermission,
            'is_has_enter_today'  => $presence && $presence->presence_enter_time !== null,
            'is_not_out_yet'      => $presence && $presence->presence_out_time === null,
            'is_permission_accepted' => \App\Models\Permission::where('user_id', $userId)
                ->where('attendance_id', $attendance->id)
                ->where('permission_date', $today)
                ->where('is_accepted', true)
                ->exists(),
        ];

        // 4. Generate data histori 30 hari terakhir untuk tabel log bawah
        $history = Presence::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->orderBy('presence_date', 'desc')
            ->get();

        $priodDate = [];
        for ($i = 0; $i < 30; $i++) {
            $priodDate[] = now()->subDays($i)->toDateString();
        }

        // 5. 🎯 FIX UTAMA: Definisikan variabel title untuk tab browser layout master
        $title = "Detail Absensi - " . $attendance->title;

        // 6. Ambil data libur nasional (dari DB local / fallback API)
        $holiday = \App\Models\Holiday::where('holiday_date', $today)->first();
        if (!$holiday) {
            try {
                $currentYear = date('Y');
                $currentMonth = date('m');
                $response = \Illuminate\Support\Facades\Http::timeout(3)
                    ->get("https://api-hari-libur.vercel.app/api?year={$currentYear}&month={$currentMonth}");

                if ($response->successful()) {
                    $apiData = $response->json()['data'] ?? [];
                    $todayHoliday = collect($apiData)->firstWhere('date', $today);
                    if ($todayHoliday) {
                        $holiday = (object)[
                            'title' => $todayHoliday['description'],
                            'holiday_date' => $todayHoliday['date']
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Gagal cek API libur di halaman detail: " . $e->getMessage());
            }
        }

        // 7. Masukkan 'title' ke dalam compact()
        return view('home.show', compact('attendance', 'presence', 'holiday', 'data', 'priodDate', 'history', 'title'));
    }

    public function permission(Attendance $attendance)
    {
        $userId = auth()->id();
        $today = now()->toDateString();

        $presence = Presence::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->where('presence_date', $today)
            ->first();

        $isTherePermission = \App\Models\Permission::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->where('permission_date', $today)
            ->exists();

        $data = [
            'is_there_permission' => $isTherePermission,
            'is_has_enter_today'  => $presence && $presence->presence_enter_time !== null,
            'is_not_out_yet'      => $presence && $presence->presence_out_time === null,
            'is_permission_accepted' => \App\Models\Permission::where('user_id', $userId)
                ->where('attendance_id', $attendance->id)
                ->where('permission_date', $today)
                ->where('is_accepted', true)
                ->exists(),
        ];

        $title = "Izin - " . $attendance->title;

        return view('home.permission', compact('attendance', 'data', 'title'));
    }
}
