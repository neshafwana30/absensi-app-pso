<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\User;
use App\Models\Presence;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // Tambahkan Request $request untuk menangkap filter dari URL
    public function index(Request $request) 
    {
        $today = Carbon::today();
        
        // Menangkap pilihan dropdown user (bisa null jika memilih "Semua Jabatan")
        $selectedPosition = $request->get('position_id');

        // 1. Ambil semua list jabatan untuk di-looping di Dropdown HTML
        $positions = Position::all();
        $positionCount = $positions->count();

        // 2. Hitung Total User (Berdasarkan filter jika ada)
        $userQuery = User::query();
        if ($selectedPosition) {
            $userQuery->where('position_id', $selectedPosition);
        }
        $totalUsers = $userQuery->count();

        // 3. DATA REAL-TIME HARI INI
        $presentQuery = Presence::whereDate('created_at', $today)->where('is_permission', false);
        $permissionQuery = Presence::whereDate('created_at', $today)->where('is_permission', true);

        // Jika filter jabatan aktif, saring absensi berdasarkan relasi tabel User-nya
        if ($selectedPosition) {
            $presentQuery->whereHas('user', function($q) use ($selectedPosition) {
                $q->where('position_id', $selectedPosition);
            });
            $permissionQuery->whereHas('user', function($q) use ($selectedPosition) {
                $q->where('position_id', $selectedPosition);
            });
        }

        $presentToday = $presentQuery->count();
        $permissionToday = $permissionQuery->count();
        
        // Hitung Alpa
        $absentToday = $totalUsers - ($presentToday + $permissionToday);
        $absentToday = $absentToday < 0 ? 0 : $absentToday;

        // 4. DATA GRAFIK (7 HARI TERAKHIR)
        $chartLabels = [];
        $chartData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartLabels[] = $date->translatedFormat('l'); 

            $dailyQuery = Presence::whereDate('created_at', $date)->where('is_permission', false);
            
            // Filter grafik berdasarkan jabatan juga
            if ($selectedPosition) {
                $dailyQuery->whereHas('user', function($q) use ($selectedPosition) {
                    $q->where('position_id', $selectedPosition);
                });
            }
            $chartData[] = $dailyQuery->count();
        }

        return view('dashboard.index', [
            "title" => "Dashboard",
            "positionCount" => $positionCount,
            "userCount" => $totalUsers,
            "presentToday" => $presentToday,
            "permissionToday" => $permissionToday,
            "absentToday" => $absentToday,
            "chartLabels" => $chartLabels,
            "chartData" => $chartData,
            "positions" => $positions, // Mengirim data jabatan ke view
            "selectedPosition" => $selectedPosition // Menyimpan state pilihan terakhir
        ]);
    }
}