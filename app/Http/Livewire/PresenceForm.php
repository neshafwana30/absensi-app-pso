<?php

namespace App\Http\Livewire;

use App\Models\Attendance;
use App\Models\Presence;
use Livewire\Component;

class PresenceForm extends Component
{
    public Attendance $attendance;
    public $holiday;
    public $data;
    
    // 🎯 Properti Penampung Koordinat dari Frontend
    public $latitude;
    public $longitude;

    public function mount(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Fungsi Helper: Menghitung jarak radius (Haversine Formula)
     */
    private function isOutsideRadius()
    {
        // Tolak jika device gagal mengirim koordinat GPS
        if (!$this->latitude || !$this->longitude) {
            return true;
        }

        $centerLat = config('attendance.office_latitude');
        $centerLng = config('attendance.office_longitude');
        $maxRadius = config('attendance.allowed_radius_meters');

        $earthRadius = 6371000; // Satuan Meter

        $latDelta = deg2rad($this->latitude - $centerLat);
        $lngDelta = deg2rad($this->longitude - $centerLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($centerLat)) * cos(deg2rad($this->latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance > $maxRadius;
    }

    public function sendEnterPresence()
    {
        // 🚨 VALIDASI GEOPOSITION RADIUS MASUK
        if ($this->isOutsideRadius()) {
            return $this->dispatchBrowserEvent('showToast', [
                'success' => false, 
                'message' => "Absen masuk gagal! Anda berada di luar radius lokasi kantor yang diizinkan."
            ]);
        }

        if ($this->attendance->data->is_start && !$this->attendance->data->is_using_qrcode) {
            Presence::create([
                "user_id" => auth()->user()->id,
                "attendance_id" => $this->attendance->id,
                "presence_date" => now()->toDateString(),
                "presence_enter_time" => now()->toTimeString(),
                "presence_out_time" => null
            ]);

            $this->data['is_has_enter_today'] = true;
            $this->data['is_not_out_yet'] = true;

            return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Kehadiran atas nama '" . auth()->user()->name . "' berhasil dikirim."]);
        }
    }

    public function sendOutPresence()
    {
        // 🚨 VALIDASI GEOPOSITION RADIUS PULANG
        if ($this->isOutsideRadius()) {
            return $this->dispatchBrowserEvent('showToast', [
                'success' => false, 
                'message' => "Absen pulang gagal! Anda berada di luar radius lokasi kantor yang diizinkan."
            ]);
        }

        if (!$this->attendance->data->is_end && $this->attendance->data->is_using_qrcode)
            return false;

        $presence = Presence::query()
            ->where('user_id', auth()->user()->id)
            ->where('attendance_id', $this->attendance->id)
            ->where('presence_date', now()->toDateString())
            ->where('presence_out_time', null)
            ->first();

        if (!$presence)
            return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Terjadi masalah pada saat melakukan absensi."]);

        $this->data['is_not_out_yet'] = false;
        $presence->update(['presence_out_time' => now()->toTimeString()]);
        return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Atas nama '" . auth()->user()->name . "' berhasil melakukan absensi pulang."]);
    }

    public function render()
    {
        return view('livewire.presence-form');
    }
}