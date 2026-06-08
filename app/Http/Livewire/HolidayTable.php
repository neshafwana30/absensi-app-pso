<?php

namespace App\Http\Livewire;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Rules\{Rule, RuleActions};
use PowerComponents\LivewirePowerGrid\Traits\ActionButton;
use PowerComponents\LivewirePowerGrid\{Button, Column, Exportable, Footer, Header, PowerGrid, PowerGridComponent, PowerGridEloquent};

final class HolidayTable extends PowerGridComponent
{
    use ActionButton;

    // Table sort field (Disamakan polanya dengan EmployeeTable biar default urutannya jelas)
    public string $sortField = 'holidays.created_at';
    public string $sortDirection = 'desc';

    protected function getListeners()
    {
        return array_merge(
            parent::getListeners(),
            [
                'bulkCheckedDelete',
                'bulkCheckedEdit'
            ]
        );
    }

    public function header(): array
    {
        return [
            Button::add('bulk-checked')
                ->caption(__('Hapus'))
                ->class('btn btn-danger border-0')
                ->emit('bulkCheckedDelete', []),
            Button::add('bulk-edit-checked')
                ->caption(__('Edit'))
                ->class('btn btn-success border-0')
                ->emit('bulkCheckedEdit', []),
        ];
    }

    public function bulkCheckedDelete()
    {
        if (auth()->check()) {
            $ids = $this->checkedValues();

            if (!$ids)
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => 'Pilih data yang ingin dihapus terlebih dahulu.']);

            try {
                Holiday::whereIn('id', $ids)->delete();
                $this->dispatchBrowserEvent('showToast', ['success' => true, 'message' => 'Data hari libur berhasil dihapus.']);
            } catch (\Illuminate\Database\QueryException $ex) {
                $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => 'Data gagal dihapus, kemungkinan ada data lain yang menggunakan data tersebut.']);
            }
        }
    }

    public function bulkCheckedEdit()
    {
        if (auth()->check()) {
            $ids = $this->checkedValues();

            if (!$ids)
                return $this->dispatchBrowserEvent('showToast', ['success' => false, 'message' => 'Pilih data yang ingin diedit terlebih dahulu.']);

            $ids = join('-', $ids);
            return $this->dispatchBrowserEvent('redirect', ['url' => route('holidays.edit', ['ids' => $ids])]);
        }
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        // Berikan select spesifik agar PostgreSQL tidak bingung saat sorting id
        return Holiday::query()->select('holidays.*');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function addColumns(): PowerGridEloquent
    {
        return PowerGrid::eloquent()
            ->addColumn('id')
            ->addColumn('title')
            ->addColumn('description')
            ->addColumn('holiday_date')
            ->addColumn('holiday_date_formatted', fn (Holiday $model) => Carbon::parse($model->holiday_date)->format('d/m/Y'))
            ->addColumn('created_at')
            ->addColumn('created_at_formatted', fn (Holiday $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'));
    }

    public function columns(): array
    {
        return [
            // Parameter ke-3 wajib diarahkan langsung ke table.kolom database ('holidays.xxx') agar bisa di-sort
            Column::make('ID', 'id', 'holidays.id')
                ->searchable()
                ->sortable(),

            Column::make('Nama Hari Libur', 'title', 'holidays.title')
                ->searchable()
                ->makeInputText('title')
                ->sortable(),

            Column::make('Keterangan', 'description', 'holidays.description')
                ->searchable()
                ->makeInputText('description')
                ->sortable(),

            Column::make('Tanggal Libur', 'holiday_date', 'holidays.holiday_date')
                ->hidden(),

            Column::make('Tanggal Libur', 'holiday_date_formatted', 'holidays.holiday_date')
                ->makeInputDatePicker()
                ->searchable()
                ->sortable(),

            Column::make('Created at', 'created_at', 'holidays.created_at')
                ->hidden(),

            Column::make('Created at', 'created_at_formatted', 'holidays.created_at')
                ->makeInputDatePicker()
                ->searchable()
        ];
    }
}