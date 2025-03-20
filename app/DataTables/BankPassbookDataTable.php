<?php

namespace App\DataTables;

use App\Models\Audit;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Payments;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class BankPassbookDataTable extends DataTable
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
            ->editColumn('user', function($data) {
                return $data->user->reference;
            })
            ->editColumn('amount', function($data) {
                if($data->paid_for == 'withdraw'){
                    return $data->amount.' rupees withdrawn';
                }else{
                    return $data->amount.' royalty income received';
                }
            })
            ->editColumn('tds', function($data) {
                if($data->tds){
                    return ($data->amount*$data->tds/100).' rupees';
                }
            })
            ->editColumn('charge', function($data) {
                $json = json_decode($data->extra_json)??[];
                if($data->tds && $json && isset($json->admin_charge)){
                    return $json->admin_charge.' rupees';
                }
            })
            ->editColumn('desc', function($data) {
                return $data->description;
            })
            ->addColumn('status', function($data) {
                return ucfirst($data->status);
            })
            ->editColumn('created_at', function($data) {
                return $data->created_at != null ? Carbon::parse($data->created_at)->format('d-m-Y H:i:s') : '';
            })
            ->rawColumns(['amount', 'status', 'desc']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Audit $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Payments $model)
    {
        if(\Auth::user()->hasRole('user')){
            $model = $model->where('user_id',\Auth::user()->id);
        }
        return $model->whereIn('paid_for',['withdraw', 'royalty'])->orderBy('created_at', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('wallet-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        // Button::make('create'),
                        // Button::make('export'),
                        Button::make('print'),
                        // Button::make('reset'),
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
        $user = 'user';
        if(Auth()->user()->hasRole('user')) {
            $user = false;
        }
        return [
            Column::make('user')->visible($user),
            Column::make('amount')->title('Transaction'),
            Column::make('status')->title('Status'),
            Column::make('tds')->title('TDS'),
            Column::make('charge')->title('Charge'),
            Column::make('desc')->title('Details'),
            Column::make('created_at')->title('DateTime'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Passbook_' . date('YmdHis');
    }
}
