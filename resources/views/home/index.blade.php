@extends('layouts.home')

@section('content')
<div class="container py-5">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 overflow-hidden">
                <img src="{{ asset('images/banner3.jpeg') }}"
                     class="img-fluid w-100"
                     alt="Banner Informasi Karyawan"
                     style="max-height: 260px; object-fit: cover;">
            </div>
        </div>
    </div>

    <livewire:user-stats />

    <div class="row mt-4">

        <div class="col-md-4 mb-4">
            <div class="card bg-white">
                <div class="card-header bg-white fw-bold py-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="text-dark fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Profil Karyawan</span>
                    <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill small" style="font-size: 0.75rem; font-weight: 600;">
                        Active
                    </span>
                </div>
                <div class="card-body py-4 px-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm"
                             style="width: 60px; height: 60px; font-size: 1.3rem; background: linear-gradient(135deg, #3498db, #2980b9);">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="ms-3">
                            <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.3px;">{{ auth()->user()->name }}</h5>
                            <span class="badge bg-light text-muted border mt-1" style="font-size: 0.75rem;">{{ auth()->user()->position->name ?? 'Staff Pegawai' }}</span>
                        </div>
                    </div>

                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Alamat Email</small>
                        <span class="text-dark d-flex align-items-center"><i class="bi bi-envelope me-2 text-muted"></i>{{ auth()->user()->email }}</span>
                    </div>

                    <div class="mb-4 pb-2">
                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">No. Telepon</small>
                        <span class="text-dark d-flex align-items-center"><i class="bi bi-telephone me-2 text-muted"></i>{{ auth()->user()->phone ?? '-' }}</span>
                    </div>

                    <div class="bg-light p-3 rounded-3">
                        <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Bergabung Sejak</small>
                        <span class="text-secondary small d-flex align-items-center">
                            <i class="bi bi-calendar3 me-2 text-primary"></i>{{ auth()->user()->created_at->format('d M Y') }} ({{ auth()->user()->created_at->diffForHumans() }})
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card h-100 bg-white">
                <div class="card-header bg-white fw-bold py-3 border-bottom text-dark">
                    <i class="bi bi-clipboard-check me-2 text-success"></i>Daftar Absensi Hari Ini
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom-lg overflow-hidden">
                        @forelse ($attendances as $attendance)
                            <a href="{{ route('home.show', $attendance->id) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3 px-4 border-0 border-bottom transition-all"
                               style="background-color: #fdfefe;">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold text-dark fs-5" style="letter-spacing: -0.2px;">{{ $attendance->title }}</div>
                                    <p class="mb-0 text-muted small mt-1 d-flex align-items-center"><i class="bi bi-info-circle me-1.5"></i>{{ $attendance->description }}</p>
                                </div>
                                <div class="mt-1">
                                    @include('partials.attendance-badges')
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-5 my-4">
                                <div class="fs-1 text-muted opacity-25 mb-2">
                                    <i class="bi bi-calendar-x"></i>
                                </div>
                                <p class="text-muted mb-0 fw-medium">Tidak ada jadwal absensi untuk posisi jabatan Anda hari ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
