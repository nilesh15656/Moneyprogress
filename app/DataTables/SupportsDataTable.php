<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\Support;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class SupportsDataTable extends DataTable
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
            ->addColumn('user', function($data) {
                return $data->user->reference;
            })
            ->addColumn('subject', function($data) {
                return $data->subject;
            })
            ->addColumn('description', function($data) {
                return $data->description;
            })
            ->addColumn('created at', function($data) {
                return Carbon::parse($data->created_at)->format('d/m/Y');
            })
            ->addColumn('action', function($data) {
                $html = '<div class="d-flex justify-content-between flex-nowrap">
                  <button type="button" class="btn btn-gradient-primary btn-rounded btn-icon btn-sm" onclick="view('.$data->id.')" data-id="'.$data->id.'">
                    <i class="mdi mdi-eye menu-icon"></i>
                  </button>';

                return $html;
            })
            ->rawColumns(['status','action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Support $model)
    {
        $data = Request()->all();
        if(\Auth::user()->hasRole('user')){
            $model = $model->where('user_id', \Auth::user()->id);
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
                    ->setTableId('supports-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        Button::make('create'),
                        // Button::make('export'),
                        // Button::make('print'),
                        // Button::make('reset'),
                        // Button::make('reload')
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
            Column::make('user'),
            Column::make('subject'),
            Column::make('description'),
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
        return 'Supports_' . date('YmdHis');
    }
}
