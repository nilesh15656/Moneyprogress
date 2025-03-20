<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\Epin;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class EpinRequestsDataTable extends DataTable
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
            ->addColumn('user_id', function($data) {
                return $data->user->name ?? '';
            })
            ->addColumn('epin', function($data) {
                return $data->pin ?? '';
            })
            ->addColumn('status', function($data) {
                $status = 'Un Used';
                $badge = 'danger';
                if ($data->used == '1') {
                    $badge = 'success';
                    $status = 'Used';
                }
                return '<label class="badge badge-gradient-'.$badge.'"><span class="text-dark h6 m-1">'.$status.'</span></label>';
            })
            ->addColumn('requested_at', function($data) {
                return $data->requested_at != null ? Carbon::parse($data->requested_at)->format('d/m/Y') : '';
            })
            ->addColumn('approved_at', function($data) {
                return $data->approved_at != null ? Carbon::parse($data->approved_at )->format('d/m/Y') : '';
            })
            ->rawColumns(['status']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\EpinRequest $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Epin $model)
    {
        if(\Auth::user()->hasRole('user')){
            $model = $model->where('user_id',\Auth::user()->id);
        }
        return $model->has('user')->with('user')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('epinrequests-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons(
                        // Button::make('create'),
                        // Button::make('export'),
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
            Column::make('user_id')->title('User\'s name'),
            Column::make('pin')->addClass('text-center'),
            Column::computed('status')->title('Status')->addClass('text-center'),
            Column::make('requested_at')->title('Requested at')->addClass('text-center'),
            Column::make('approved_at')->title('Approved at')->addClass('text-center'),
            // Column::make('created_at'),
            // Column::make('updated_at'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'EpinRequests_' . date('YmdHis');
    }
}
