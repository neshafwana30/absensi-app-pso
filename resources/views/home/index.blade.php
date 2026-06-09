@extends('layouts.home')

@section('content')
<div class="container py-5">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
                <img src="{{ asset('images/banner1.jpeg') }}"
                     class="img-fluid w-100"
                     alt="Banner Informasi Karyawan"
                     style="max-height: 320px; object-fit: cover;">
            </div>
        </div>
    </div>

    <livewire:user-stats />

    <div class="row mt-4">

        <div class="col-md-8 mb-4">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    Daftar Absensi Hari Ini
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($attendances as $attendance)
                            <a href="{{ route('home.show', $attendance->id) }}"
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3 px-4 border-0 border-bottom">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold text-dark fs-5">{{ $attendance->title }}</div>
                                    <p class="mb-0 text-muted small mt-1">{{ $attendance->description }}</p>
                                </div>
                                @include('partials.attendance-badges')
                            </a>
                        @empty
                            <div class="text-center py-5">
                                <p class="text-muted mb-0 fw-medium">Tidak ada jadwal absensi untuk posisi jabatan Anda hari ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 rounded-lg">
                <div class="card-header bg-white fw-bold py-3 border-bottom">
                    Informasi Karyawan
                </div>
                <div class="card-body py-4 px-4">
                    <div class="mb-3 border-bottom pb-2">
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Nama Lengkap</small>
                        <span class="text-dark fw-semibold fs-6">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="mb-3 border-bottom pb-2">
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Alamat Email</small>
                        <span class="text-dark">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="mb-3 border-bottom pb-2">
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">No. Telepon</small>
                        <span class="text-dark">{{ auth()->user()->phone ?? '-' }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Bergabung Sejak</small>
                        <span class="text-dark small">{{ auth()->user()->created_at->diffForHumans() }} ({{ auth()->user()->created_at->format('d M Y') }})</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
