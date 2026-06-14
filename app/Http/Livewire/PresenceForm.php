<?php

namespace App\Http\Livewire;

use App\Models\Attendance;
use App\Models\Presence;
use Carbon\Carbon;
use Livewire\Component;

class PresenceForm extends Component
{
    public Attendance $attendance;
    public $holiday;
    public $data;
    
    // Properti koordinat GPS
    public $latitude;
    public $longitude;

    public function mount(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    private function isOutsideRadius()
    {
        if (!$this->latitude || !$this->longitude) {
            return true;
        }

        $centerLat = config('attendance.office_latitude');
        $centerLng = config('attendance.office_longitude');
        $maxRadius = config('attendance.allowed_radius_meters');

        $earthRadius = 6371000;

        $latDelta = deg2rad($this->latitude - $centerLat);
        $lngDelta = deg2rad($this->longitude - $centerLng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($centerLat)) * cos(deg2rad($this->latitude)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance > $maxRadius;
    }

    // --- FUNGSI ABSEN MANUAL (AMBA / AMAN BANGET) ---
    
    public function sendEnterPresence()
    {
        if ($this->isOutsideRadius()) {
            return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Absen masuk gagal! Di luar radius."]);
        }

        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $startTime = Carbon::parse($todayStr . ' ' . $this->attendance->start_time);
        $batasStartTime = Carbon::parse($todayStr . ' ' . $this->attendance->batas_start_time);

        if ($now->between($startTime, $batasStartTime)) {
            Presence::create([
                "user_id" => auth()->id(),
                "attendance_id" => $this->attendance->id,
                "presence_date" => $todayStr,
                "presence_enter_time" => $now->toTimeString(),
                "presence_out_time" => null
            ]);

            $this->data['is_has_enter_today'] = true;
            $this->data['is_not_out_yet'] = true;

            return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Berhasil absen masuk."]);
        }
        return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Waktu absen ditutup."]);
    }

    public function sendOutPresence()
    {
        if ($this->isOutsideRadius()) {
            return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Absen pulang gagal! Di luar radius."]);
        }

        $now = Carbon::now();
        $todayStr = $now->toDateString();
        $endTime = Carbon::parse($todayStr . ' ' . $this->attendance->end_time);
        $batasEndTime = Carbon::parse($todayStr . ' ' . $this->attendance->batas_end_time);

        if (!$now->between($endTime, $batasEndTime)) {
            return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Belum masuk waktu absen pulang."]);
        }

        $presence = Presence::where('user_id', auth()->id())
            ->where('attendance_id', $this->attendance->id)
            ->where('presence_date', $todayStr)
            ->whereNull('presence_out_time')
            ->first();

        if (!$presence) {
            return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Terjadi masalah absensi."]);
        }

        $this->data['is_not_out_yet'] = false;
        $presence->update(['presence_out_time' => $now->toTimeString()]);
        
        return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Berhasil absen pulang."]);
    }

    /**
     * FUNGSI ABSEN MASUK VIA QR CODE (Bebas 500 Error)
     */
    public function sendEnterPresenceUsingQRCode($scannedCode = null)
    {
        try {
            if ($this->isOutsideRadius()) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Absen gagal! Di luar radius."]);
            }

            // Paksa jadi string biar tidak error kalau null
            $qrCode = trim((string) $scannedCode);

            if ((string) $this->attendance->code !== $qrCode) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Kode QR Tidak Valid!"]);
            }

            $now = Carbon::now();
            $todayStr = $now->toDateString();
            $startTime = Carbon::parse($todayStr . ' ' . $this->attendance->start_time);
            $batasStartTime = Carbon::parse($todayStr . ' ' . $this->attendance->batas_start_time);

            if (!$now->between($startTime, $batasStartTime)) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Waktu absen masuk ditutup."]);
            }

            if (Presence::where('user_id', auth()->id())->where('attendance_id', $this->attendance->id)->where('presence_date', $todayStr)->exists()) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Sudah absen masuk hari ini!"]);
            }

            Presence::create([
                'user_id' => auth()->id(),
                'attendance_id' => $this->attendance->id,
                'presence_date' => $todayStr,
                'presence_enter_time' => $now->toTimeString(),
                'presence_out_time' => null
            ]);

            $this->data['is_has_enter_today'] = true;
            $this->data['is_not_out_yet'] = true;

            return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Absen masuk via QR berhasil!"]);

        } catch (\Throwable $e) {
            // 🎯 JIKA ADA BUG, MUNCULKAN DI LAYAR BUKAN DI SERVER ERROR
            return $this->dispatchBrowserEvent('showToast', [
                'success' => false, 
                'message' => "Terciduk Bug: " . $e->getMessage() . " (Baris: " . $e->getLine() . ")"
            ]);
        }
    }

    /**
     * FUNGSI ABSEN PULANG VIA QR CODE (Bebas 500 Error)
     */
    public function sendOutPresenceUsingQRCode($scannedCode = null)
    {
        try {
            if ($this->isOutsideRadius()) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Absen pulang gagal! Di luar radius."]);
            }

            $qrCode = trim((string) $scannedCode);

            if ((string) $this->attendance->code !== $qrCode) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Kode QR Tidak Valid!"]);
            }

            $now = Carbon::now();
            $todayStr = $now->toDateString();
            $endTime = Carbon::parse($todayStr . ' ' . $this->attendance->end_time);
            $batasEndTime = Carbon::parse($todayStr . ' ' . $this->attendance->batas_end_time);

            if (!$now->between($endTime, $batasEndTime)) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Waktu absen pulang ditutup."]);
            }

            $presence = Presence::where('user_id', auth()->id())
                ->where('attendance_id', $this->attendance->id)
                ->where('presence_date', $todayStr)
                ->whereNull('presence_out_time')
                ->first();

            if (!$presence) {
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => "Data absen masuk tidak ditemukan."]);
            }

            $this->data['is_not_out_yet'] = false;
            $presence->update(['presence_out_time' => $now->toTimeString()]);

            return $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => "Absen pulang via QR berhasil!"]);

        } catch (\Throwable $e) {
            // 🎯 JIKA ADA BUG, MUNCULKAN DI LAYAR BUKAN DI SERVER ERROR
            return $this->dispatchBrowserEvent('showToast', [
                'success' => false, 
                'message' => "Terciduk Bug: " . $e->getMessage() . " (Baris: " . $e->getLine() . ")"
            ]);
        }
    }
    public function render()
    {
        return view('livewire.presence-form');
    }
}