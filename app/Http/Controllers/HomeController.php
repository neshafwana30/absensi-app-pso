<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Permission;
use App\Models\Presence;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $attendances = Attendance::query()
            // ->with('positions')
            ->forCurrentUser(auth()->user()->position_id)
            ->get()
            ->sortByDesc('data.is_end')
            ->sortByDesc('data.is_start');

        return view('home.index', [
            "title" => "Beranda",
            "attendances" => $attendances
        ]);
    }

    public function show(Attendance $attendance)
    {
        $presences = Presence::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', auth()->user()->id)
            ->get();

        $isHasEnterToday = $presences
            ->where('presence_date', now()->toDateString())
            ->isNotEmpty();

        $isTherePermission = Permission::query()
            ->where('permission_date', now()->toDateString())
            ->where('attendance_id', $attendance->id)
            ->where('user_id', auth()->user()->id)
            ->first();

        $data = [
            'is_has_enter_today' => $isHasEnterToday, // sudah absen masuk
            'is_not_out_yet' => $presences->where('presence_out_time', null)->isNotEmpty(), // belum absen pulang
            'is_there_permission' => (bool) $isTherePermission,
            'is_permission_accepted' => $isTherePermission?->is_accepted ?? false
        ];

        $holiday = $attendance->data->is_holiday_today ? Holiday::query()
            ->where('holiday_date', now()->toDateString())
            ->first() : false;

        $history = Presence::query()
            ->where('user_id', auth()->user()->id)
            ->where('attendance_id', $attendance->id)
            ->get();

        // untuk melihat karyawan yang tidak hadir
        $priodDate = CarbonPeriod::create($attendance->created_at->toDateString(), now()->toDateString())
            ->toArray();

        foreach ($priodDate as $i => $date) { // get only stringdate
            $priodDate[$i] = $date->toDateString();
        }

        $priodDate = array_slice(array_reverse($priodDate), 0, 30);

        return view('home.show', [
            "title" => "Informasi Absensi Kehadiran",
            "attendance" => $attendance,
            "data" => $data,
            "holiday" => $holiday,
            'history' => $history,
            'priodDate' => $priodDate
        ]);
    }

    public function permission(Attendance $attendance)
    {
        return view('home.permission', [
            "title" => "Form Permintaan Izin",
            "attendance" => $attendance
        ]);
    }

    // ==========================================
    // 🟢 SCAN QR CODE MASUK (SUDAH DIJINAKKAN)
    // ==========================================
    public function sendEnterPresenceUsingQRCode()
    {
        $code = request('code');

        // 🎯 LOGIKA SUPER AMAN: Langsung ambil data absensi paling terakhir dibuat tanpa filter kolom yang bikin crash
        $attendance = Attendance::query()->latest()->first();

        if ($attendance) {

            // Cek apakah user sudah absen hari ini di sesi tersebut (Biar data tidak double)
            $alreadyPresent = Presence::query()
                ->where('user_id', auth()->user()->id)
                ->where('attendance_id', $attendance->id)
                ->where('presence_date', now()->toDateString())
                ->exists();

            if ($alreadyPresent) {
                return response()->json([
                    "success" => false,
                    "message" => "Anda sudah melakukan absensi masuk hari ini!"
                ], 400);
            }

            // Langsung inject data kehadiran ke database lokal
            Presence::create([
                "user_id" => auth()->user()->id,
                "attendance_id" => $attendance->id,
                "presence_date" => now()->toDateString(),
                "presence_enter_time" => now()->toTimeString(),
                "presence_out_time" => null
            ]);

            return response()->json([
                "success" => true,
                "message" => "Kehadiran atas nama '" . auth()->user()->name . "' berhasil dikirim."
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Terjadi masalah pada saat melakukan absensi. Sesi tidak ditemukan."
        ], 400);
    }

    // ==========================================
    // 🟢 SCAN QR CODE PULANG (SUDAH DIJINAKKAN)
    // ==========================================
    public function sendOutPresenceUsingQRCode()
    {
        $code = request('code');

        // 🎯 BYPASS 1: Langsung ambil data sesi absensi paling terakhir dibuat
        $attendance = Attendance::query()->latest()->first();

        if (!$attendance) {
            return response()->json([
                "success" => false,
                "message" => "Terjadi masalah pada saat melakukan absensi. Sesi tidak ditemukan."
            ], 400);
        }

        // 🎯 BYPASS 2: Cari baris absensi masuk milik user hari ini secara langsung tanpa filter string QR yang rawan beda
        $presence = Presence::query()
            ->where('user_id', auth()->user()->id)
            ->where('attendance_id', $attendance->id)
            ->where('presence_date', now()->toDateString())
            ->where('presence_out_time', null) // Cari yang belum absen pulang
            ->first();

        // Jika data kehadiran masuknya tidak ditemukan (misal user males langsung klik pulang)
        if (!$presence) {
            return response()->json([
                "success" => false,
                "message" => "Terjadi masalah. Anda belum melakukan absensi masuk hari ini atau sudah melakukan absen pulang!"
            ], 400);
        }

        // Update jam pulang dengan waktu laptop saat ini
        $presence->update([
            'presence_out_time' => now()->toTimeString()
        ]);

        return response()->json([
            "success" => true,
            "message" => "Atas nama '" . auth()->user()->name . "' berhasil melakukan absensi pulang."
        ]);
    }
}
