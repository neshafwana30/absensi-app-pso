<div>
    @if (session()->has('success'))
        <script>
            window.dispatchEvent(new CustomEvent('showToast', {
                detail: {
                    success: true,
                    message: "{{ session('success') }}"
                }
            }));
        </script>
    @endif

    @if (session()->has('failed'))
        <script>
            window.dispatchEvent(new CustomEvent('showToast', {
                detail: {
                    success: false,
                    message: "{{ session('failed') }}"
                }
            }));
        </script>
    @endif

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }

        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }

        .calendar-link {
            text-decoration: none !important;
            color: inherit !important;
            display: block;
            height: 100%;
        }
    </style>

    <div class="p-3"
        style="background: #ffffff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #f1f5f9;">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0 text-capitalize" style="color: #1e293b; letter-spacing: -0.02em; font-size: 1.3rem;">
                {{ $monthName }}
            </h3>
            <div class="btn-group">
                <button wire:click="previousMonth" class="btn btn-sm btn-outline-secondary px-3"
                    style="border-radius: 10px 0 0 10px;">
                    <i class="align-middle">&lt;</i> Prev
                </button>
                <button wire:click="nextMonth" class="btn btn-sm btn-outline-secondary px-3"
                    style="border-radius: 0 10px 10px 0;">
                    Next &gt;
                </button>
            </div>
        </div>

        <div class="row g-1 text-center mb-1">
            <div class="col fw-bold text-danger small">Min</div>
            <div class="col fw-bold text-muted small">Sen</div>
            <div class="col fw-bold text-muted small">Sel</div>
            <div class="col fw-bold text-muted small">Rab</div>
            <div class="col fw-bold text-muted small">Kam</div>
            <div class="col fw-bold text-muted small">Jum</div>
            <div class="col fw-bold text-muted small">Sab</div>
        </div>

        @foreach ($calendarWeeks as $week)
            <div class="row g-1 mb-1">
                @foreach ($week as $day)
                    @php
                        $hasLocalHoliday = $day['holidays']->contains('type', 'local');
                        $hasApiHoliday = $day['holidays']->contains('type', 'api');
                        $localHolidayId = $hasLocalHoliday ? $day['holidays']->firstWhere('type', 'local')['id'] : null;

                        $cellBgColor = '#ffffff';

                        if ($day['holidays']->isNotEmpty()) {
                            if ($hasApiHoliday) {
                                $cellBgColor = 'rgba(254, 243, 199, 0.8)'; // API: Pink
                            } elseif ($hasLocalHoliday) {
                                $cellBgColor = 'rgba(254, 243, 199, 0.8)'; // Local: Kuning
                            }
                        } elseif (!$day['isCurrentMonth']) {
                            $cellBgColor = '#f8fafc';
                        } elseif ($day['isToday']) {
                            $cellBgColor = '#d6e8ff';
                        }
                    @endphp

                    <div class="col" style="min-height: 85px; transition: all 0.2s ease;">

                        @if ($hasLocalHoliday)
                            <a href="{{ route('holidays.edit', ['ids' => $localHolidayId]) }}" class="calendar-link"
                                style="cursor: pointer;" title="Klik area kotak ini untuk Edit Data Libur">
                        @endif

                        <div class="h-100 p-2 position-relative"
                            style="border-radius: 14px; 
                                    border: 1px solid {{ $day['isToday'] ? '#3b82f6' : '#f1f5f9' }}; 
                                    background-color: {{ $cellBgColor }};"
                            @if ($hasLocalHoliday) onmouseover="this.style.transform='scale(1.02)'; this.style.transition='all 0.1s ease';"
                                onmouseout="this.style.transform='scale(1)';" @endif>

                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold"
                                    style="color: {{ $day['isSunday'] || $day['hasApiHoliday'] ? '#ef4444' : '#4b5057' }}; 
                                            opacity: {{ $day['isCurrentMonth'] ? '1' : '0.3' }}; font-size: 0.9rem;">
                                    {{ $day['date']->day }}
                                </span>
                            </div>

                            <div class="mt-1 no-scrollbar" style="overflow-y: hidden; max-height: 55px;">
                                @foreach ($day['holidays'] as $holiday)
                                    <span class="d-block rounded-3"
                                        style="font-size: 12px; font-weight: 500; 
                                                color: #4b5057 !important;
                                                line-height: 1.2;
                                                white-space: normal !important;
                                                word-wrap: break-word !important;
                                                margin-bottom: 2px;">
                                        {{ $holiday['title'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        @if ($hasLocalHoliday)
                            </a>
                        @endif

                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
