<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Presence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Fungsi Helper: Menghitung jarak antara 2 koordinat (Rumus Haversine)
     */
    private function isOutsideRadius($userLat, $userLng)
    {
        // Jika parameter tidak dikirim dari frontend, langsung tolak
        if (!$userLat || !$userLng) {
            return true; 
        }

        $centerLat = config('attendance.office_latitude');
        $centerLng = config('attendance.office_longitude');
        $maxRadius = config('attendance.allowed_radius_meters');

        $earthRadius = 6371000; // Radius bumi dalam satuan meter

        $latDelta = deg2rad($userLat - $centerLat);
        $lngDelta = deg2rad($userLng - $centerLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($centerLat)) * cos(deg2rad($userLat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c; // Hasil jarak dalam meter

        return $distance > $maxRadius;
    }

   

    /**
     * Tampilan Beranda Utama Karyawan
     */
    public function index()
    {
        $attendances = Attendance::all();
        $title = "Dashboard Karyawan";
        return view('home.index', compact('attendances', 'title'));
    }

/**
     * Menampilkan halaman detail sesi absensi (Historical Terbatasi Sejak Sesi Dibuat)
     */
    public function show(Attendance $attendance)
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
            ->where('is_accepted', true)
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

        $history = Presence::where('user_id', $userId)
            ->where('attendance_id', $attendance->id)
            ->orderBy('presence_date', 'desc')
            ->get();

        // 🎯 KUNCI UTAMA: Hitung selisih hari sejak sesi absensi diciptakan harian
        $attendanceCreatedAt = Carbon::parse($attendance->created_at)->startOfDay();
        $currentDay = Carbon::today();

        $diffInDays = $currentDay->diffInDays($attendanceCreatedAt);
        $limitDays = min($diffInDays + 1, 30); // Ambil nilai terkecil, maksimal mentok 30 hari

        $priodDate = [];
        for ($i = 0; $i < $limitDays; $i++) {
            $priodDate[] = now()->subDays($i)->toDateString();
        }

        $title = "Detail Absensi - " . $attendance->title;

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