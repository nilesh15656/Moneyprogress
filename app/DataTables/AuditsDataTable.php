<?php

namespace App\DataTables;

use App\Models\Audit;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class AuditsDataTable extends DataTable
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
            ->editColumn('event', function($data) {
                $modified = $data->getModified();
                $user_name = $data->user->name ?? "Deactivated User";
                $user_name = ucfirst($user_name);

                switch ($modified) {
                    case array_key_exists('email_verified_at', $modified):
                        $event = $user_name.' verified email';
                        break;

                    case ($data->event == 'created'):
                        $user_name = $modified['name']['new'] ?? 'User'; 
                        $event = $user_name.' registered';
                        break;

                    case ($data->auditable_type == 'App\Models\Wallet'):
                        $event = '';
                        $event .= '<ul>';
                        foreach ($modified as $name => $value) {
                            $event .= '<li>';
                            $event .= ucfirst($name). ' Wallet\'s amount has been changed to '.$value['new'].' from '.$value['old'].' for '.$user_name;
                            $event .= '</li>';
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
        return $model->with('user')->orderBy('created_at', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('audits-table')
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
            Column::make('id')->addClass('text-center'),
            Column::make('event'),
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
        return 'Audits_' . date('YmdHis');
    }
}
