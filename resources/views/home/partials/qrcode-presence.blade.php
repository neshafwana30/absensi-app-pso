<div>
    @if ($holiday)
        <div class="alert alert-success">
            <small class="fw-bold">Hari ini adalah hari libur.</small>
        </div>
    @else
        {{-- KONDISI 1: JIKA ABSENSI MENGGUNAKAN QRCODE --}}
        @if ($attendance->data->is_using_qrcode && !$data['is_there_permission'])

            {{-- jika belum absen dan absen masuk sudah dimulai --}}
            @if ($attendance->data->is_start && !$data['is_has_enter_today'])
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100 mb-2" data-bs-toggle="modal"
                    data-bs-target="#qrcode-scanner-modal" data-is-enter="1">Scan QRCode Masuk</button>
                <a href="{{ route('home.permission', $attendance->id) }}"
                    class="btn btn-info px-3 py-2 btn-sm fw-bold d-block w-100">Izin</a>
            @endif

            {{-- jika absen pulang sudah dimulai, dan karyawan sudah absen masuk dan belum absen pulang --}}
            @if ($attendance->data->is_end && $data['is_has_enter_today'] && $data['is_not_out_yet'])
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100" data-bs-toggle="modal"
                    data-bs-target="#qrcode-scanner-modal" data-is-enter="0">Scan QRCode Pulang</button>
            @endif

            {{-- KONDISI 2: JIKA ABSENSI MANUAL (TANPA QRCODE / PAKAI TOMBOL BIASA) --}}
        @elseif (!$attendance->data->is_using_qrcode && !$data['is_there_permission'])
            {{-- Tombol Absen Masuk Manual --}}
            @if ($attendance->data->is_start && !$data['is_has_enter_today'])
                <button class="btn btn-success px-3 py-2 btn-sm fw-bold d-block w-100 mb-2"
                    wire:click="sendEnterPresence">Klik Untuk Absen Masuk</button>
                <a href="{{ route('home.permission', $attendance->id) }}"
                    class="btn btn-info px-3 py-2 btn-sm fw-bold d-block w-100">Izin</a>
            @endif

            {{-- Tombol Absen Pulang Manual --}}
            @if ($attendance->data->is_end && $data['is_has_enter_today'] && $data['is_not_out_yet'])
                <button class="btn btn-success px-3 py-2 btn-sm fw-bold d-block w-100" wire:click="sendOutPresence">Klik
                    Untuk Absen Pulang</button>
            @endif

        @endif

        {{-- 📢 NOTIFIKASI / ALERT STATUS ABSENSI --}}
        @if ($data['is_has_enter_today'] && $data['is_not_out_yet'])
            <div class="alert alert-success mt-2">
                <small class="d-block fw-bold text-success">Anda sudah berhasil mengirim absensi masuk.</small>
            </div>
        @endif

        {{-- sudah absen masuk dan absen pulang --}}
        @if ($data['is_has_enter_today'] && !$data['is_not_out_yet'])
            <div class="alert alert-success mt-2">
                <small class="d-block fw-bold text-success">Anda sudah melakukan absen masuk dan absen pulang.</small>
            </div>
        @endif

        {{-- jika sudah absen masuk dan belum saatnya absen pulang --}}
        @if ($data['is_has_enter_today'] && !$attendance->data->is_end)
            <div class="alert alert-danger mt-2">
                <small class="fw-bold">Belum saatnya melakukan absensi pulang.</small>
            </div>
        @endif

        @if ($data['is_there_permission'] && !$data['is_permission_accepted'])
            <div class="alert alert-info mt-2">
                <small class="fw-bold">Permintaan izin sedang diproses (atau masih belum di terima).</small>
            </div>
        @endif

        @if ($data['is_there_permission'] && $data['is_permission_accepted'])
            <div class="alert alert-success mt-2">
                <small class="fw-bold">Permintaan izin sudah diterima.</small>
            </div>
        @endif

    @endif

    {{-- MODAL SCANNER QR CODE (Wajib pakai wire:ignore biar kamera nggak nge-glitch) --}}
    <div class="modal fade" id="qrcode-scanner-modal" tabindex="-1" wire:ignore>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QRCode Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reader" style="width: 100%"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT PENGGANTI QRCODE.JS (FULL LIVEWIRE) --}}
@push('script')
    <script src="{{ asset('html5-qrcode/html5-qrcode.min.js') }}"></script>
    <script>
        // 1. Minta Izin GPS Agresif saat halaman dimuat
        document.addEventListener("DOMContentLoaded", function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Simpan koordinat langsung ke Livewire
                        @this.set('latitude', position.coords.latitude);
                        @this.set('longitude', position.coords.longitude);
                    },
                    function(error) {
                        console.error('GPS belum diizinkan:', error);
                    }, {
                        enableHighAccuracy: true
                    }
                );
            }
        });

        // 2. Logika Scanner Kamera yang nembak ke Livewire
        const QRCodeScannerModal = document.getElementById("qrcode-scanner-modal");
        let html5QrcodeScanner = null;

        if (QRCodeScannerModal) {
            QRCodeScannerModal.addEventListener("shown.bs.modal", (event) => {
                const isEnter = event.relatedTarget.dataset.isEnter == "1";

                function onScanSuccess(code) {
                    html5QrcodeScanner.clear();

                    // Eksekusi fungsi Livewire sesuai tombol yang diklik
                    if (isEnter) {
                        @this.call('sendEnterPresenceUsingQRCode', code);
                    } else {
                        @this.call('sendOutPresenceUsingQRCode', code);
                    }

                    // Tutup modal otomatis setelah scan sukses
                    const modal = bootstrap.Modal.getInstance(QRCodeScannerModal);
                    if (modal) modal.hide();
                }

                document.getElementById("reader").innerHTML = "";

                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader", {
                        fps: 10,
                        qrbox: {
                            width: 250,
                            height: 250
                        },
                        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
                    },
                    false
                );

                html5QrcodeScanner.render(onScanSuccess);
            });

            // Matikan kamera kalau user nutup modal secara manual
            QRCodeScannerModal.addEventListener("hidden.bs.modal", () => {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                }
            });
        }
    </script>
@endpush
