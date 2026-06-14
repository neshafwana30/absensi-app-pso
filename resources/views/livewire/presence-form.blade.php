<div>
    @if ($holiday)
        <div class="alert alert-success">
            <small class="fw-bold">Hari ini adalah hari libur.</small>
        </div>
    @else
        {{-- KONDISI 1: JIKA ABSENSI MENGGUNAKAN QRCODE --}}
        @if ($attendance->data->is_using_qrcode && !$data['is_there_permission'])

            {{-- Jika belum absen masuk dan sesi masuk sudah dimulai --}}
            @if ($attendance->data->is_start && !$data['is_has_enter_today'])
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100 mb-2" data-bs-toggle="modal"
                    data-bs-target="#qrcode-scanner-modal" data-is-enter="1">Scan QRCode Masuk</button>
                <a href="{{ route('home.permission', $attendance->id) }}"
                    class="btn btn-info px-3 py-2 btn-sm fw-bold d-block w-100">Izin</a>
            @endif

            {{-- Jika sudah absen masuk dan sesi pulang sudah dimulai --}}
            @if ($attendance->data->is_end && $data['is_has_enter_today'] && $data['is_not_out_yet'])
                <button class="btn btn-primary px-3 py-2 btn-sm fw-bold d-block w-100" data-bs-toggle="modal"
                    data-bs-target="#qrcode-scanner-modal" data-is-enter="0">Scan QRCode Pulang</button>
            @endif

            {{-- KONDISI 2: JIKA ABSENSI MANUAL / TOMBOL BIASA --}}
        @elseif (!$attendance->data->is_using_qrcode && !$data['is_there_permission'])
            {{-- Tombol Absen Masuk Manual --}}
            @if ($attendance->data->is_start && !$data['is_has_enter_today'])
                <button class="btn btn-success px-3 py-2 btn-sm fw-bold d-block w-100 mb-2"
                    wire:click="sendEnterPresence" wire:loading.attr="disabled">Klik Untuk Absen Masuk</button>
                <a href="{{ route('home.permission', $attendance->id) }}"
                    class="btn btn-info px-3 py-2 btn-sm fw-bold d-block w-100">Izin</a>
            @endif

            {{-- Tombol Absen Pulang Manual --}}
            @if ($attendance->data->is_end && $data['is_has_enter_today'] && $data['is_not_out_yet'])
                <button class="btn btn-success px-3 py-2 btn-sm fw-bold d-block w-100" wire:click="sendOutPresence"
                    wire:loading.attr="disabled">Klik Untuk Absen Pulang</button>
            @endif

        @endif

        {{-- NOTIFIKASI / STATUS ABSENSI --}}
        @if ($data['is_has_enter_today'] && $data['is_not_out_yet'])
            <div class="alert alert-success mt-2">
                <small class="d-block fw-bold text-success">Anda sudah berhasil mengirim absensi masuk.</small>
            </div>
        @endif

        @if ($data['is_has_enter_today'] && !$data['is_not_out_yet'])
            <div class="alert alert-success mt-2">
                <small class="d-block fw-bold text-success">Anda sudah melakukan absen masuk dan absen pulang.</small>
            </div>
        @endif

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

    {{-- 🌟 MODAL SCANNER QR CODE MODERN & SECURE (STRICT CAMERA ONLY) 🌟 --}}
    <div class="modal fade" id="qrcode-scanner-modal" tabindex="-1" aria-hidden="true" wire:ignore>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                        <h5 class="modal-title fw-bold fs-5 m-0" style="letter-spacing: -0.3px;">Pemindai QR Absensi
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <p class="text-muted small text-center mb-3">Posisikan kode QR perusahaan tepat berada di dalam
                        kotak kamera pemindai harian.</p>

                    {{-- Container Scanner dengan Style Custom Overrides --}}
                    <div class="position-relative bg-white shadow-sm border p-2"
                        style="border-radius: 1rem; overflow: hidden;">
                        <div id="reader" style="width: 100%;"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light pt-0 pb-4 px-4 d-flex justify-content-center">
                    <button type="button" class="btn btn-secondary border-0 px-4 py-2 fw-bold shadow-sm"
                        data-bs-dismiss="modal" style="border-radius: 0.75rem; font-size: 0.875rem;">Batalkan
                        Absen</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- STYLE CUSTOM UNTUK MERAPIKAN DESIGN INTERNAL HTML5-QRCODE --}}
<style>
    #reader {
        border: none !important;
    }

    #reader __video {
        object-fit: cover !important;
        border-radius: 0.75rem !important;
    }

    #reader button {
        background-color: #0d6efd !important;
        color: white !important;
        border: none !important;
        padding: 0.5rem 1.25rem !important;
        font-weight: 600 !important;
        border-radius: 0.5rem !important;
        font-size: 0.875rem !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }

    #reader button:hover {
        background-color: #0b5ed7 !important;
    }

    #reader select {
        padding: 0.375rem 1.75rem 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        border-radius: 0.5rem !important;
        border: 1px solid #ced4da !important;
    }
</style>

@push('script')
    <script src="{{ asset('html5-qrcode/html5-qrcode.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        @this.set('latitude', position.coords.latitude);
                        @this.set('longitude', position.coords.longitude);
                    },
                    function(error) {
                        console.error('GPS belum diizinkan oleh browser:', error);
                    }, {
                        enableHighAccuracy: true
                    }
                );
            }
        });

        const QRCodeScannerModal = document.getElementById("qrcode-scanner-modal");
        let html5QrcodeScanner = null;

        if (QRCodeScannerModal) {
            QRCodeScannerModal.addEventListener("shown.bs.modal", (event) => {
                const isEnter = event.relatedTarget.dataset.isEnter == "1";

                function onScanSuccess(code) {
                    html5QrcodeScanner.clear();

                    if (isEnter) {
                        @this.call('sendEnterPresenceUsingQRCode', code);
                    } else {
                        @this.call('sendOutPresenceUsingQRCode', code);
                    }

                    const modal = bootstrap.Modal.getInstance(QRCodeScannerModal);
                    if (modal) modal.hide();
                }

                document.getElementById("reader").innerHTML = "";

                // 🎯 SAKTI: Mengunci konfigurasi hanya menerima inputan real-time dari modul KAMERA FISIK
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader", {
                        fps: 15,
                        qrbox: {
                            width: 220,
                            height: 220
                        },
                        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
                        rememberLastUsedCamera: true,
                        // Kunci tipe scan: HANYA MENGIZINKAN SCAN_TYPE_CAMERA
                        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                    },
                    false
                );

                html5QrcodeScanner.render(onScanSuccess);
            });

            QRCodeScannerModal.addEventListener("hidden.bs.modal", () => {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.clear();
                }
            });
        }
    </script>
@endpush
