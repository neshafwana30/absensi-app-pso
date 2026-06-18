<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm">
    <div class="position-sticky pt-3 px-2">
        <ul class="nav flex-column gap-1">
            
            @if (auth()->user()->isAdmin() or auth()->user()->isOperator())
            
            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('dashboard.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('dashboard.index') }}">
                    <span data-feather="home" class="align-text-bottom me-2"></span>
                    Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('positions.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('positions.index') }}">
                    <span data-feather="tag" class="align-text-bottom me-2"></span>
                    Jabatan / Posisi
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('employees.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('employees.index') }}">
                    <span data-feather="users" class="align-text-bottom me-2"></span>
                    Karyawan
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('holidays.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('holidays.index') }}">
                    <span data-feather="calendar" class="align-text-bottom me-2"></span>
                    Hari Libur
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('attendances.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('attendances.index') }}">
                    <span data-feather="clipboard" class="align-text-bottom me-2"></span>
                    Absensi
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link py-2 rounded {{ request()->routeIs('presences.*') ? 'active fw-bold' : 'text-dark' }}"
                   href="{{ route('presences.index') }}">
                    <span data-feather="file-text" class="align-text-bottom me-2"></span>
                    Data Kehadiran
                </a>
            </li>
            
            @endif
        </ul>

        <hr class="my-3 text-muted">

        <form action="{{ route('auth.logout') }}" method="post"
              onsubmit="return confirm('Apakah anda yakin ingin keluar?')">
            @method('DELETE')
            @csrf
            <button class="w-100 btn btn-sm btn-outline-danger border-0 fw-bold text-start px-3 py-2">
                <span data-feather="log-out" class="align-text-bottom me-2"></span>
                Keluar
            </button>
        </form>
    </div>
</nav>