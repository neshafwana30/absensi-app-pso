<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class HolidayCalendar extends Component
{
    public $currentYear;
    public $currentMonth;
    public $calendarWeeks = [];
    public $monthName;

    public function mount()
    {
        $this->currentYear = date('Y');
        $this->currentMonth = date('m');
        $this->generateCalendar();

        // 🔥 Cek apakah ada session flash lama dari halaman edit, kalau ada langsung oper ke Toast
        if (session()->has('success')) {
            $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => session('success')]);
        }
        if (session()->has('failed')) {
            $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => session('failed')]);
        }
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->generateCalendar();
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->generateCalendar();
    }

    // 🔥 METHOD UNTUK AKSI KLIK: Melempar toast konfirmasi sebelum dipindahkan, atau langsung redirect aman
    public function alertBeforeEdit($id, $title)
    {
        // Kirim toast pemberitahuan ke browser dulu kalau data siap diedit
        $this->dispatchBrowserEvent('showToast', [
            'success' => true, 
            'message' => "Membuka data libur: \"{$title}\"..."
        ]);

        // Beri jeda sekilas biar toast-nya kelihatan dulu sebelum loncat halaman
        return redirect()->to(route('holidays.edit', ['ids' => $id]));
    }

    public function generateCalendar()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $this->monthName = $date->translatedFormat('F Y');

        $localHolidays = Holiday::whereYear('holiday_date', $this->currentYear)
            ->whereMonth('holiday_date', $this->currentMonth)
            ->where('description', 'NOT LIKE', '%API%')
            ->get()
            ->groupBy('holiday_date');

        $apiHolidays = collect();
        try {
            $response = Http::timeout(5)->get("https://api-hari-libur.vercel.app/api?year={$this->currentYear}&month={$this->currentMonth}");
            if ($response->successful()) {
                $apiData = $response->json()['data'] ?? [];
                $apiHolidays = collect($apiData)->groupBy('date');
            }
        } catch (\Exception $e) {
            \Log::error("Gagal load API di Kalender: " . $e->getMessage());
        }

        $startOfCalendar = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfCalendar = $date->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $weeks = [];
        $currentDay = $startOfCalendar->copy();

        while ($currentDay->lessThanOrEqualTo($endOfCalendar)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateString = $currentDay->format('Y-m-d');
                $dayHolidays = collect();

                if (isset($apiHolidays[$dateString])) {
                    foreach ($apiHolidays[$dateString] as $h) {
                        $dayHolidays->push([
                            'title' => trim(str_replace('Cuti Bersama', '', $h['description'])),
                            'type' => 'api'
                        ]);
                    }
                }

                if (isset($localHolidays[$dateString])) {
                    foreach ($localHolidays[$dateString] as $h) {
                        $titleText = $h->title;
                        $isDuplicate = $dayHolidays->contains(fn($item) => strtolower(trim($item['title'])) === strtolower(trim($titleText)));

                        if (!$isDuplicate) {
                            $dayHolidays->push([
                                'id' => $h->id,
                                'title' => $titleText,
                                'type' => 'local'
                            ]);
                        }
                    }
                }

                $week[] = [
                    'date' => $currentDay->copy(),
                    'isCurrentMonth' => $currentDay->month == $this->currentMonth,
                    'isToday' => $currentDay->isToday(),
                    'isSunday' => $currentDay->dayOfWeek === Carbon::SUNDAY,
                    'hasApiHoliday' => $dayHolidays->contains('type', 'api'),
                    'holidays' => $dayHolidays
                ];
                $currentDay->addDay();
            }
            $weeks[] = $week;
        }

        $this->calendarWeeks = $weeks;
    }

    public function render()
    {
        return view('livewire.holiday-calendar');
    }
}