<div>
    @if ($holiday)
        <div class="alert alert-success">
            <small class="fw-bold">Hari ini adalah hari libur.</small>
        </div>
    @else
        {{-- Input Hidden untuk binding koordinat ke Livewire Component --}}
        <input type="hidden" wire:model="latitude" id="livewire-lat">
        <input type="hidden" wire:model="longitude" id="livewire-lng">

        @if (!$attendance->data->is_using_qrcode && !$data['is_there_permission'])

            @if ($attendance->data->is_start && !$data['is_has_enter_today'])
                {{-- Tombol Masuk memicu Javascript Geolocation terlebih dahulu sebelum wire:click --}}
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100 mb-2"
                    onclick="getLocationForLivewire('sendEnterPresence')" wire:loading.attr="disabled">Masuk</button>

                <a href="{{ route('home.permission', $attendance->id) }}"
                    class="btn btn-info px-3 py-2 btn-sm fw-bold d-block w-100">Izin</a>
            @endif

            @if ($data['is_has_enter_today'])
                <div class="alert alert-success">
                    <small class="d-block fw-bold text-success">Anda sudah berhasil mengirim absensi masuk.</small>
                </div>
            @endif

            @if ($attendance->data->is_end && $data['is_has_enter_today'] && $data['is_not_out_yet'])
                {{-- Tombol Pulang memicu Javascript Geolocation terlebih dahulu --}}
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100"
                    onclick="getLocationForLivewire('sendOutPresence')" wire:loading.attr="disabled">Pulang</button>
            @endif

            @if ($data['is_has_enter_today'] && !$data['is_not_out_yet'])
                <div class="alert alert-success">
                    <small class="d-block fw-bold text-success">Anda sudah melakukan absen masuk dan absen
                        pulang.</small>
                </div>
            @endif

            @if ($data['is_has_enter_today'] && !$attendance->data->is_end)
                <div class="alert alert-danger">
                    <small class="fw-bold">Belum saatnya melakukan absensi pulang.</small>
                </div>
            @endif
        @endif

        @if ($data['is_there_permission'] && !$data['is_permission_accepted'])
            <div class="alert alert-info">
                <small class="fw-bold">Permintaan izin sedang diproses (atau masih belum di terima).</small>
            </div>
        @endif

        @if ($data['is_there_permission'] && $data['is_permission_accepted'])
            <div class="alert alert-success">
                <small class="fw-bold">Permintaan izin sudah diterima.</small>
            </div>
        @endif

    @endif

    {{-- Script untuk mengisi data koordinat secara otomatis ke properti Livewire --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            getLocationForLivewire();
        });

        function getLocationForLivewire(targetMethod = null) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Set nilai ke input hidden
                        document.getElementById('livewire-lat').value = position.coords.latitude;
                        document.getElementById('livewire-lng').value = position.coords.longitude;

                        // Emit/set data langsung ke properti Livewire component
                        @this.set('latitude', position.coords.latitude);
                        @this.set('longitude', position.coords.longitude);

                        // Jika dipicu dari klik tombol, eksekusi method Livewire setelah koordinat siap
                        if (targetMethod) {
                            @this.call(targetMethod);
                        }
                    },
                    function(error) {
                        alert('Gagal mendapatkan lokasi. Pastikan GPS aktif dan izin lokasi diberikan.');
                    }, {
                        enableHighAccuracy: true
                    }
                );
            } else {
                alert("Browser Anda tidak mendukung deteksi lokasi.");
            }
        }
    </script>
</div>
