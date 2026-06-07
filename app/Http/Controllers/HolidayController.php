<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    public function index()
    {
        $currentYear = date('Y');

        try {
            // 1. Ambil data dari API
            $response = Http::timeout(10)->get("https://api-hari-libur.vercel.app/api?year={$currentYear}");

            if ($response->successful()) {
                $apiData = $response->json()['data'] ?? [];

                if (is_array($apiData)) {
                    foreach ($apiData as $index => $item) {
                        
                        // 2. Pastikan item memiliki format valid dari API
                        if (is_array($item) && isset($item['date']) && isset($item['description'])) {
                            
                            // Gunakan updateOrCreate agar data tidak duplikat saat halaman di-refresh
                            Holiday::updateOrCreate(
                                ['holiday_date' => $item['date']], // Unik berdasarkan tanggal
                                [
                                    // FIX: ID menggunakan integer murni (10000+) agar sorting di PowerGrid aktif
                                    'id' => 10000 + $index, 
                                    'title' => $item['description'],
                                    'description' => 'Hari Libur Nasional Otomatis (API)'
                                ]
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Gagal sinkronisasi otomatis hari libur: " . $e->getMessage());
        }

        return view('holidays.index', [
            "title" => "Hari Libur"
        ]);
    }

    public function create()
    {
        return view('holidays.create', [
            "title" => "Tambah Data Hari Libur"
        ]);
    }

    public function edit()
    {
        $ids = request('ids');
        if (!$ids) return redirect()->back();
        $ids = explode('-', $ids);

        $holidays = Holiday::query()->whereIn('id', $ids)->get();

        return view('holidays.edit', [
            "title" => "Edit Data Hari Libur",
            "holidays" => $holidays
        ]);
    }

    public function destroy($id)
    {
        try {
            $holiday = Holiday::findOrFail($id);
            $holiday->delete();

            // Balik ke halaman kalender utama dengan membawa pesan sukses
            return redirect()
                ->route('holidays.index')
                ->with('success', 'Data hari libur berhasil dihapus!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('failed', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}