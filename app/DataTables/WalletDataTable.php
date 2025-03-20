<?php

namespace App\DataTables;

use App\Models\Audit;
use App\Models\Wallet;
use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class WalletDataTable extends DataTable
{
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $i = 1;
        return datatables()
            ->eloquent($query)
            ->editColumn('id', function($data) use (&$i){
                return $i++;
            })
            ->editColumn('event', function($data) {
                $modified = $data->getModified();

                switch ($modified) {
                    case ($data->auditable_type == 'App\Models\Wallet'):
                        $event = '';
                        $event .= '<ul>';
                        foreach ($modified as $name => $value) {
                            if(in_array($name,['user_id','tds','admin_charge'])){
                                continue;
                            }
                            if($name == 'referrer_id'){
                                $event .= '<li>';
                                $user = User::find($value['new']);
                                $event .= 'The '.($user->reference??"Deleted (#".$value['new'].")").' ID has been activated';
                                $event .= '</li>';
                            }else{
                                $event .= '<li>';
                                $event .= ucfirst($name). ' Wallet\'s amount has been changed to '.$value['new'].' from '.($value['old']??0);
                                $event .= '</li>';
                            }
                        }
                        $event .= '</ul>';
                        break;
                    
                    default:
                        $event = '';
                        break;
                }
                return $event;
            })
            ->editColumn('created_at', function($data) {
                return $data->created_at != null ? Carbon::parse($data->created_at)->format('d/m/Y H:i:s') : '';
            })
            ->rawColumns(['event']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Audit $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Audit $model)
    {
        if(\Auth::user()->hasRole('user')){
            $wallet_id = Wallet::where('user_id',\Auth::user()->id)->first();
            $model = $model->where('auditable_id',($wallet_id->id??0));
        }
        return $model->where('auditable_type','App\Models\Wallet')->orderBy('created_at', 'desc')->newQuery();
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
                    ->dom('Bfrtip')
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
        return [
            Column::make('id')->title('#')->addClass('text-center'),
            Column::make('event')->title('Details'),
            Column::make('created_at')->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Wallet_' . date('YmdHis');
    }
}
