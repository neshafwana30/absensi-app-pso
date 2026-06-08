<?php

namespace App\Http\Livewire;

use App\Http\Traits\useUniqueValidation;
use App\Models\Holiday;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class HolidayEditForm extends Component
{
    use useUniqueValidation;

    public $holidays;

    public function mount(Collection $holidays)
    {
        $this->holidays = [];
        foreach ($holidays as $holiday) {
            // $this->holidays[] = $holiday->toArray(); // jika menggunakan ini akan terjadi bandwith yang cukup besar
            $this->holidays[] = [
                'id' => $holiday->id,
                'title' => $holiday->title,
                'description' => $holiday->description,
                'holiday_date' => $holiday->holiday_date,
            ];
        }
    }

    public function saveHolidays()
    {
        $this->validate([
            'holidays.*.title' => 'required',
            'holidays.*.description' => 'required',
            'holidays.*.holiday_date' => 'required|date',
        ]);

        if (!$this->isUniqueOnLocal('holiday_date', $this->holidays)) {
            $this->dispatchBrowserEvent('livewire-scroll', ['top' => 0]);
            return session()->flash('failed', 'Pastikan tanggal hari libur tidak boleh sama dengan tanggal hari libur yang lain.');
        }

        $affected = 0;
        // alasan menggunakan create alih2 mengunakan ::insert adalah karena tidak looping untuk menambahkan created_at dan updated_at
        foreach ($this->holidays as $holiday) {
            $holidayBeforeUpdated = Holiday::find($holiday['id']);

            if (!$this->isUniqueOnDatabase($holidayBeforeUpdated, $holiday, 'holiday_date', Holiday::class)) {
                $this->dispatchBrowserEvent('livewire-scroll', ['top' => 0]);
                return session()->flash('failed', "Tanggal hari libur {$holiday['id']} sudah terdaftar. Silahkan masukan tanggal hari libur yang berbeda!");
            }

            $affected += $holidayBeforeUpdated->update([
                'title' => $holiday['title'],
                'description' => $holiday['description'],
                'holiday_date' => $holiday['holiday_date'],
            ]);
        }

        $message = $affected === 0 ?
            "Tidak ada data hari libur yang diubah." :
            "Ada $affected data hari libur yang berhasil diedit.";

        return redirect()->route('holidays.index')->with('success', $message);
    }

    /**
     * 🔥 METHOD BARU: Fungsi Hapus Data Hari Libur
     */
    public function deleteHoliday()
    {
        if (auth()->check()) {
            try {
                // Ekstrak semua ID dari data array holidays yang sedang diedit
                $ids = collect($this->holidays)->pluck('id')->filter()->toArray();

                if (!empty($ids)) {
                    // Hapus data langsung dari database
                    Holiday::whereIn('id', $ids)->delete();

                    // Mental balik ke halaman utama kalender, otomatis memicu Toast Sukses HTML kemarin
                    return redirect()->route('holidays.index')->with('success', 'Data hari libur berhasil dihapus!');
                }

                $this->dispatchBrowserEvent('livewire-scroll', ['top' => 0]);
                return session()->flash('failed', 'Gagal mendeteksi ID data yang ingin dihapus.');

            } catch (\Exception $e) {
                $this->dispatchBrowserEvent('livewire-scroll', ['top' => 0]);
                return session()->flash('failed', 'Gagal menghapus data: ' . $e->getMessage());
            }
        }
    }

    public function render()
    {
        return view('livewire.holiday-edit-form');
    }
}