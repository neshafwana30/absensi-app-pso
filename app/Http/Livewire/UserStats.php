<?php

// 🎯 FIX NAMESPACE: Disamakan dengan yang dicari oleh sistem kelompokmu
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Presence;
use App\Models\Permission;
use Carbon\Carbon;

class UserStats extends Component
{
    public $totalHadir;
    public $totalIzin;
    public $totalTidakHadir;

    public function mount()
    {
        $userId = auth()->id();

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // 1. HITUNG TOTAL HADIR
        $this->totalHadir = Presence::where('user_id', $userId)
            ->whereBetween('presence_date', [$startOfMonth, $endOfMonth])
            ->count();

        // 2. HITUNG TOTAL IZIN
        $this->totalIzin = Permission::where('user_id', $userId)
            ->whereBetween('permission_date', [$startOfMonth, $endOfMonth])
            ->where('is_accepted', true)
            ->count();

        // 3. HITUNG TOTAL TIDAK HADIR (ALPA)
        $daysPassed = Carbon::now()->day;
        $calculatedAlpa = $daysPassed - ($this->totalHadir + $this->totalIzin);

        $this->totalTidakHadir = $calculatedAlpa < 0 ? 0 : $calculatedAlpa;
    }

    public function render()
    {
        return view('livewire.user-stats');
    }
}
