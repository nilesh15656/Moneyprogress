<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
use App\Models\BankDetail;

class BankDetailsDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('user id', function($data) {
                return $data->user->reference;
            })
            ->addColumn('bank name', function($data) {
                return $data->bank_name;
            })
            ->addColumn('status', function($data) {
                return ucfirst($data->status);
            })
            ->addColumn('created at', function($data) {
                return Carbon::parse($data->created_at)->format('d/m/Y');
            })
            ->addColumn('action', function($data) {
                $html = '<div class="d-flex justify-content-between flex-nowrap">
                  <button type="button" class="btn btn-gradient-primary btn-rounded btn-icon btn-sm" onclick="view(this)" data-id="'.$data->id.'">
                    <i class="mdi mdi-eye menu-icon"></i>
                  </button>';

                return $html;
            });
            // ->addColumn('action', 'users.action');
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(BankDetail $model)
    {
        if(\Session::has('type')){
            $model = $model->where('status', \Session::get('type'));
        }
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('banks-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        Button::make('create'),
                        Button::make('export'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('user id'),
            Column::make('bank name'),
            Column::make('status'),
            Column::make('created at'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  // ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Teams_' . date('YmdHis');
    }
}
