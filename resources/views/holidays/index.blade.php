@extends('layouts.app')

@section('buttons')
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('holidays.create') }}" class="btn btn-sm btn-primary px-3 py-2"
            style="border-radius: 10px; font-weight: 600;">
            <span data-feather="plus-circle" class="align-text-bottom me-1"></span>
            Tambah Data Hari Libur
        </a>
    </div>
@endsection

@section('content')
    <livewire:holiday-calendar />
@endsection

@push('script')
    @livewireScripts
@endpush
