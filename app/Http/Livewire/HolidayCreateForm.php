<?php

namespace App\Http\Livewire;

use App\Models\Holiday;
use Livewire\Component;

class HolidayCreateForm extends Component
{
    public $holidays;
    private $initialValue = ['title' => '', 'description' => '', 'holiday_date' => ''];

    public function mount()
    {
        $this->holidays = [$this->initialValue];
    }

    public function addHolidayInput(): void
    {
        $this->holidays[] = $this->initialValue;
    }

    public function removeHolidayInput(int $index): void
    {
        unset($this->holidays[$index]);
        $this->holidays = array_values($this->holidays);
    }

    public function saveHolidays()
    {
        // 1. Validasi format input array bertingkat
        $this->validate([
            'holidays.*.title' => 'required',
            'holidays.*.description' => 'required',
            'holidays.*.holiday_date' => 'required|date',
        ]);

        // 2. Loop dan simpan aman ke PostgreSQL dengan proteksi duplikat manual
        foreach ($this->holidays as $holiday) {
            
            $isExist = Holiday::where('holiday_date', $holiday['holiday_date'])->exists();
            if ($isExist) {
                $this->addError('holidays', 'Tanggal ' . $holiday['holiday_date'] . ' sudah terdaftar sebagai hari libur!');
                return;
            }

            Holiday::create([
                'title' => $holiday['title'],
                'description' => $holiday['description'],
                'holiday_date' => $holiday['holiday_date']
            ]);
        }

        // 🔥 FIX FINAL: Menggunakan cara redirect standar Livewire v2 tanpa 'navigate: true' biar ga memicu Error 500
        return redirect()->route('holidays.index')->with('success', 'Data hari libur berhasil ditambahkan.');
    }

    public function render()
    {
        return view('livewire.holiday-create-form');
    }
}