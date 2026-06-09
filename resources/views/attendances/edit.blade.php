@extends('layouts.app')

@section('buttons')
<div class="btn-toolbar mb-2 mb-md-0">
    <div>
        <a href="{{ route('attendances.index') }}" class="btn btn-sm btn-light">
            <span data-feather="arrow-left-circle" class="align-text-bottom"></span>
            Kembali
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-7 mb-4">
        <livewire:attendance-edit-form :attendance="$attendance" />
    </div>

    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 rounded-lg text-center p-4 bg-white">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-2">QRCode Absensi Hari Ini</h5>
                <p class="text-muted small mb-4">Gunakan kode ini agar dipindai oleh karyawan/user biasa melalui menu scan.</p>

                <div class="d-inline-block p-3 bg-light border rounded shadow-sm mb-3">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($attendance->code) }}"
                         alt="QRCode Absensi"
                         class="img-fluid"
                         style="width: 230px; height: 230px; object-fit: contain;">
                </div>

                <div class="mt-2">
                    <span class="badge bg-secondary px-3 py-2 fs-6">Raw Code: {{ $attendance->code }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
