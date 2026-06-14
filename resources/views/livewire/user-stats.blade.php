<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100" style="background-color: rgba(212, 239, 223, 0.7); border-left: 5px solid #27ae60 !important;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 text-muted fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Hadir Bulan Ini</p>
                    <h3 class="fw-bold mb-0 text-success" style="font-size: 1.75rem;">{{ $totalHadir }} Hari</h3>
                </div>
                <div class="fs-1 text-success opacity-75">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100" style="background-color: rgba(252, 243, 207, 0.7); border-left: 5px solid #f1c40f !important;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 text-muted fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Izin / Sakit</p>
                    <h3 class="fw-bold mb-0 text-warning" style="font-size: 1.75rem; color: #b7950b !important;">{{ $totalIzin }} Hari</h3>
                </div>
                <div class="fs-1 text-warning opacity-75" style="color: #b7950b !important;">
                    <i class="bi bi-envelope-paper-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0 h-100" style="background-color: rgba(fadbd8, 0.7); background-color: rgba(242, 215, 213, 0.7); border-left: 5px solid #e74c3c !important;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 text-muted fw-semibold text-uppercase" style="font-size: 0.8rem; letter-spacing: 0.5px;">Tidak Hadir (Alpa)</p>
                    <h3 class="fw-bold mb-0 text-danger" style="font-size: 1.75rem;">{{ $totalTidakHadir }} Hari</h3>
                </div>
                <div class="fs-1 text-danger opacity-75">
                    <i class="bi bi-calendar-x-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>
