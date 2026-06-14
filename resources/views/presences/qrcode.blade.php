@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-6">
            {{-- CARD CONTAINER QR CODE --}}
            <div class="card mb-3 shadow-sm border-0" style="max-width: min-content; border-radius: 1rem; overflow: hidden;">
                <div class="card-body bg-white p-4 text-center">
                    {{-- 🎯 KLIK GAMBAR UNTUK FULL SCREEN --}}
                    <div class="qrcode-wrapper position-relative cursor-pointer" onclick="toggleFullScreen()"
                        title="Klik untuk Full Screen">
                        <img src="{{ $qrcode }}" alt="QR Code Absensi" id="qrcode" class="img-fluid transition-all"
                            style="max-width: 280px; border-radius: 0.5rem;">

                        {{-- Overlay hint haluss --}}
                        <div
                            class="qrcode-overlay small text-white position-absolute top-50 start-50 translate-middle bg-dark bg-opacity-75 px-3 py-2 rounded-pill opacity-0 transition-all">
                            <i class="bi bi-fullscreen"></i> Klik Full Screen
                        </div>
                    </div>
                </div>
            </div>

            {{-- AREA TOMBOL UTAMA --}}
            <div class="d-flex gap-2 mb-3">
                <a href="{{ route('presences.qrcode.download-pdf', ['code' => $code]) }}"
                    class="btn btn-primary fw-bold px-4 py-2" style="border-radius: 0.5rem;">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>

                {{-- Ganti area Form Tombol Regenerate kamu dengan ini --}}
                <form action="{{ route('presences.qrcode.regenerate', $attendance->id) }}" method="POST"
                    onsubmit="return confirm('Apakah Anda yakin ingin memperbarui QR Code ini? Sesi scan lama otomatis tidak berlaku.')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-danger fw-bold px-4 py-2" style="border-radius: 0.5rem;">
                        <i class="bi bi-arrow-clockwise"></i> Regenerate QR Code
                    </button>
                </form>
            </div>

            <div class="alert alert-light border-0 shadow-sm p-3" style="border-radius: 0.75rem;">
                <small class="text-muted d-block">Untuk mendownload QrCode SVG (agar bisa diedit) silahkan klik kanan/klik
                    kiri pada gambar qrcode, lalu download.</small>
            </div>
        </div>
    </div>

    {{-- 🛠️ CSS UTK INTERAKSI HOVER DAN FULL SCREEN --}}
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .qrcode-wrapper:hover .qrcode-overlay {
            opacity: 1 !important;
        }

        .qrcode-wrapper:hover #qrcode {
            transform: scale(1.02);
            filter: brightness(0.9);
        }

        /* Style Khusus Saat Elemen Masuk Mode Full Screen Browser */
        :fullscreen {
            background-color: #ffffff !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }

        :fullscreen #qrcode {
            /* 🎯 KUNCI UTAMA: Memaksa gambar mengabaikan max-width 280px lama */
            max-width: 85vh !important;
            width: 85vh !important;
            height: 85vh !important;
            object-fit: contain !important;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.08);
            background: white;
            padding: 40px;
            border-radius: 2rem;
            transition: none !important;
            /* Matikan efek scale hover pas fullscreen biar stabil */
            transform: none !important;
        }

        :fullscreen .qrcode-overlay {
            display: none !important;
            /* Sembunyikan text hint pas full screen */
        }
    </style>

    {{-- ⚡ JAVASCRIPT FULL SCREEN LOGIC --}}
    <script>
        function toggleFullScreen() {
            // Ambil wrapper pembungkus QR Code-nya
            const element = document.querySelector('.qrcode-wrapper');

            if (!document.fullscreenElement) {
                // Jika belum full screen, paksa masuk ke mode full screen
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    /* Safari */
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) {
                    /* IE11 */
                    element.msRequestFullscreen();
                }
            } else {
                // Jika diklik lagi pas full screen, maka keluar dari mode full screen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
    </script>
@endsection
