<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\User;
use App\Models\Presence;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request) 
    {
        $today = Carbon::today();
        
        // Data untuk Dropdown
        $allAttendances = Attendance::all();
        $positions = Position::all();
        
        // Filter ID
        $attendanceId = $request->input('attendance_id');
        $positionId = $request->input('position_id');

        // Force default ke absen pertama jika belum ada filter
        if (!$attendanceId && $allAttendances->isNotEmpty()) {
            $attendanceId = $allAttendances->first()->id;
        }

        // Query Kehadiran
        $presentQuery = Presence::whereDate('created_at', $today)->where('is_permission', false);
        $permissionQuery = Presence::whereDate('created_at', $today)->where('is_permission', true);

        // Filter berdasar Absensi yang dipilih
        if ($attendanceId) {
            $presentQuery->where('attendance_id', $attendanceId);
            $permissionQuery->where('attendance_id', $attendanceId);
        }

        // Filter Jabatan
        if ($positionId) {
            $presentQuery->whereHas('user', fn($q) => $q->where('position_id', $positionId));
            $permissionQuery->whereHas('user', fn($q) => $q->where('position_id', $positionId));
        }

        $presentToday = $presentQuery->count();
        $permissionToday = $permissionQuery->count();
        $totalUsers = $positionId ? User::where('position_id', $positionId)->count() : User::count();
        $absentToday = max(0, $totalUsers - ($presentToday + $permissionToday));

        // Grafik (5 Hari)
        $chartData = [];
        for ($i = 4; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Presence::whereDate('created_at', $date)
                ->where('attendance_id', $attendanceId ?? 0)
                ->where('is_permission', false)
                ->count();
            $chartData[] = max(0, $count);
        }

        // Definisikan variabel title untuk layout induk
        $title = "Dashboard Absensi";

        return view('dashboard.index', compact(
            'title', 'allAttendances', 'positions', 'attendanceId', 'positionId', 
            'presentToday', 'permissionToday', 'absentToday', 'totalUsers', 'chartData'
        ));
    }
}