<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Epin;
use App\Models\Payments;

class PaymentsDataTable extends DataTable
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
            ->addColumn('amount', function($data) {
                return $data->amount ?? '';
            })
            ->addColumn('desc', function($data) {
                return $data->description ?? '';
            })
            ->addColumn('status', function($data) {
                $badge = 'danger';
                if ($data->status == 'active') {
                    $badge = 'success';
                }
                return '<label class="badge badge-gradient-'.$badge.'"><span class="text-dark h6 m-1">'.ucfirst($data->status).'</span></label>';
            })
            ->addColumn('requested_at', function($data) {
                return $data->requested_at != null ? Carbon::parse($data->requested_at)->format('d/m/Y') : '';
            })
            ->addColumn('action', function($data) {
                $html = '<div class="d-flex justify-content-between flex-nowrap">
                  <button type="button" class="btn btn-gradient-primary btn-rounded btn-icon btn-sm" onclick="view(this)" data-id="'.$data->id.'" title="View Request">
                    <i class="mdi mdi-eye menu-icon"></i>
                  </button>';
                    if(\Auth::user()->hasRole('admin', 'superadmin')){
                        $html .= '
                          <a title="View Teams" href="'.route('teams.view',$data->user->id).'" class="btn btn-gradient-warning btn-rounded btn-icon btn-sm m-l-2">
                            <i class="mdi mdi-account menu-icon"></i>
                          </a>';
                        if($data->paid_for == 'withdraw'){
                            $html .= '
                            <button type="button" class="btn btn-gradient-info btn-rounded btn-icon btn-sm" onclick="viewBank(this)" data-id="'.$data->user->bank->id.'" title="Viwe Bank">
                            <i class="mdi mdi-bank menu-icon"></i>
                            </button>';
                        }
                        if($data->paid_for == 'verification'){
                            $html .= '<form onsubmit="return confirm(\'Are you sure want to delete this Record ?\');" action="'. route('id.verification.delete') .'" method="POST" style="display: inline;" >';
                            $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
                            $html .= '<input type="hidden" value="'.$data->id.'" name="id">';
                            $html .= '<button type="submit" class="btn btn-gradient-danger btn-rounded btn-icon btn-sm m-l-2" title="Delete"><i class="mdi mdi-delete"></i></button>';
                            $html .= '</form>';
                        }
                    }
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['status','action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\EpinRequest $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Payments $model)
    {
        if(\Auth::user()->hasRole('user')){
            $model = $model->where('user_id',\Auth::user()->id);
        }
        if(session()->has('request_type')){
            $model = $model->where('paid_for',session()->get('request_type'));
        }
        return $model->where('status','pending')->has('user')->with('user')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('payreqs-table')
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
            Column::make('amount')->addClass('text-center'),
            Column::make('desc')->title('Details'),
            Column::computed('status')->title('Status')->addClass('text-center'),
            Column::make('requested_at')->title('Requested at')->addClass('text-center'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
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
        return 'payreqs-' . date('YmdHis');
    }
}
