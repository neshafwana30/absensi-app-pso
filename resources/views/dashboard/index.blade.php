@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="" method="GET" class="row g-2">
                <div class="col-md-5">
                    <select name="attendance_id" class="form-select shadow-sm" onchange="this.form.submit()">
                        @foreach($allAttendances as $a)
                            <option value="{{ $a->id }}" {{ (request('attendance_id', $allAttendances->first()->id ?? '') == $a->id) ? 'selected' : '' }}>
                                {{ $a->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <select name="position_id" class="form-select shadow-sm" onchange="this.form.submit()">
                        <option value="">Semua Jabatan</option>
                        @foreach($positions as $pos)
                            <option value="{{ $pos->id }}" {{ request('position_id') == $pos->id ? 'selected' : '' }}>
                                {{ $pos->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-info h-100">
                <div class="card-body">
                    <h6 class="text-info fw-bold text-uppercase mb-1">Total Karyawan</h6>
                    <h4 class="fw-bold mb-0">{{ $totalUsers }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-success h-100">
                <div class="card-body">
                    <h6 class="text-success fw-bold text-uppercase mb-1">Hadir</h6>
                    <h4 class="fw-bold mb-0">{{ $presentToday ?? 0 }}</h4> 
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-warning h-100">
                <div class="card-body">
                    <h6 class="text-warning fw-bold text-uppercase mb-1">Izin</h6>
                    <h4 class="fw-bold mb-0">{{ $permissionToday ?? 0 }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-start border-4 border-danger h-100">
                <div class="card-body">
                    <h6 class="text-danger fw-bold text-uppercase mb-1">Tidak Hadir</h6>
                    <h4 class="fw-bold mb-0">{{ $absentToday ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Tren Kehadiran (5 Hari Terakhir)</h6>
            <div style="height: 300px;"><canvas id="attendanceChart"></canvas></div>
        </div>
    </div>
</div>
<script>
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['4 Hari lalu', '3 Hari lalu', '2 Hari lalu', 'Kemarin', 'Hari ini'],
            datasets: [{
                label: 'Jumlah Hadir',
                data: {!! json_encode($chartData) !!},
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
</script>
@endsection
