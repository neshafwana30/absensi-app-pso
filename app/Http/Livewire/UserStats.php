<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Presence;

class UserStats extends Component
{
    public $totalHadir = 0;
    public $totalIzin = 0;
    public $totalTerlambat = 0;

    // Fungsi ini otomatis jalan pertama kali saat komponen dimuat
    public function mount()
    {
        $this->fetchStats();
    }

    public function fetchStats()
    {
        $userId = auth()->user()->id;
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // 1. Hitung total Hadir bulan ini
        $this->totalHadir = Presence::where('user_id', $userId)
                        ->whereMonth('presence_date', $currentMonth)
                        ->whereYear('presence_date', $currentYear)
                        ->whereNotNull('presence_enter_time')
                        ->where('is_permission', false)
                        ->count();

        // 2. Hitung total Izin / Sakit bulan ini
        $this->totalIzin = Presence::where('user_id', $userId)
                        ->whereMonth('presence_date', $currentMonth)
                        ->whereYear('presence_date', $currentYear)
                        ->where('is_permission', true)
                        ->count();

        // 3. Hitung total Terlambat (Batas jam masuk contoh: 08:00:00)
        $this->totalTerlambat = Presence::where('user_id', $userId)
                            ->whereMonth('presence_date', $currentMonth)
                            ->whereYear('presence_date', $currentYear)
                            ->whereTime('presence_enter_time', '>', '08:00:00')
                            ->where('is_permission', false)
                            ->count();
    }

    public function render()
    {
        return view('livewire.user-stats');
    }
}
