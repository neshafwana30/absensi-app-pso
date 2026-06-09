<div class="row mb-4">
    <div class="col-md-4 mb-2">
        <div class="card shadow-sm border-0 bg-success text-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0 opacity-75" style="font-size: 14px;">Hadir Bulan Ini</p>
                    <h3 class="fw-bold mb-0">{{ $totalHadir }} Hari</h3>
                </div>
                <span class="fs-1 opacity-50"><i class="bi bi-check-circle"></i></span>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-2">
        <div class="card shadow-sm border-0 bg-warning text-dark">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0 opacity-75" style="font-size: 14px;">Izin / Sakit</p>
                    <h3 class="fw-bold mb-0">{{ $totalIzin }} Hari</h3>
                </div>
                <span class="fs-1 opacity-50"><i class="bi bi-envelope-open"></i></span>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-2">
        <div class="card shadow-sm border-0 bg-danger text-white">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0 opacity-75" style="font-size: 14px;">Terlambat</p>
                    <h3 class="fw-bold mb-0">{{ $totalTerlambat }} Kali</h3>
                </div>
                <span class="fs-1 opacity-50"><i class="bi bi-clock-history"></i></span>
            </div>
        </div>
    </div>
</div>
