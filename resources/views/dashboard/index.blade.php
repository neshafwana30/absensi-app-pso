@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <div class="row mb-4">
        <div class="col-md-4">
            <form action="" method="GET">
                <select name="position_id" class="form-select shadow-sm" onchange="this.form.submit()">
                    <option value="">-- Tampilkan Semua Jabatan --</option>
                    @if(isset($positions))
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" {{ (isset($selectedPosition) && $selectedPosition == $pos->id) ? 'selected' : '' }}>
                                {{ $pos->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-info h-100">
                <div class="card-body">
                    <h6 class="fs-6 fw-light text-info fw-bold text-uppercase mb-1">Data Jabatan</h6>
                    <h4 class="fw-bold mb-0">{{ $positionCount }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-primary h-100">
                <div class="card-body">
                    <h6 class="fs-6 fw-light text-primary fw-bold text-uppercase mb-1">Total Karyawan</h6>
                    <h4 class="fw-bold mb-0">{{ $userCount }}</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-success h-100">
                <div class="card-body">
                    <h6 class="fs-6 fw-light text-success fw-bold text-uppercase mb-1">Hadir Hari Ini</h6>
                    <h4 class="fw-bold mb-0">{{ $presentToday ?? 0 }}</h4> 
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-warning h-100">
                <div class="card-body">
                    <h6 class="fs-6 fw-light text-warning fw-bold text-uppercase mb-1">Izin / Sakit</h6>
                    <h4 class="fw-bold mb-0">{{ $permissionToday ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <h6 class="fs-6 fw-bold mb-3 text-secondary">Tren Kehadiran (7 Hari Terakhir)</h6>
                    <div style="height: 350px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = {!! json_encode($chartLabels ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']) !!};
        const dataValues = {!! json_encode($chartData ?? [0, 0, 0, 0, 0]) !!};

        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Hadir',
                    data: dataValues,
                    borderColor: '#198754', 
                    backgroundColor: 'rgba(25, 135, 84, 0.1)', 
                    borderWidth: 3,
                    pointBackgroundColor: '#198754',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    fill: true,
                    tension: 0.3 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false } 
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 } 
                    }
                }
            }
        });
    });
</script>
@endsection