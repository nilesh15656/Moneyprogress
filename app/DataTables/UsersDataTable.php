<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UsersDataTable extends DataTable
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
            ->filterColumn('status', function($query, $status) {
                $query->where('is_active',$status);
            })
            ->addColumn('user Id', function($data) {
                return $data->reference;
            })
            ->addColumn('mobile', function($data) {
                if(\Auth::user()->hasRole('admin') || \Auth::user()->hasRole('superadmin')){
                    return $data->mobile;
                }

                if(session()->has('teams_type')){
                    if(session()->get('teams_type') == 'teams' && $data->parent_id == \Auth::user()->id){
                        return $data->mobile;
                    }
                }
                return Str::mask($data->mobile, '*', 3);
            })
            ->addColumn('joining date', function($data) {
                return Carbon::parse($data->created_at)->format('d/m/Y');
            })
            ->addColumn('status', function($data) {
                $badge = 'danger'; $status = 'In Active';
                if ($data->is_active) {
                    $badge = 'success';
                    $status = 'Active';
                }
                return '<label class="badge badge-gradient-'.$badge.'"><span class="text-dark h6 m-1">'.$status.'</span></label>';
            })
            ->addColumn('rank', function($data) {
                return $data->packageIn->name??'';
            })
            ->addColumn('level', function($data) {
                return $data->levelIn->name??'';
            })
            ->addColumn('action', function($data) {
                $html = '<div class="d-flex justify-content-between flex-nowrap">';
                if(!$data->is_active){
                    $html .= '
                      <button title="Active ID using ePin" type="button" onclick="active('.$data->id.')" class="btn btn-gradient-success btn-rounded btn-icon btn-sm">
                        <i class="mdi mdi-account-check menu-icon"></i>
                      </button>';
                }
                if(\Auth::user()->hasRole('user') || \Auth::user()->hasRole('admin') || \Auth::user()->hasRole('superadmin')){
                    $html .= '
                      <a title="View Teams" href="'.route('teams.view',$data->id).'" class="btn btn-gradient-primary btn-rounded btn-icon btn-sm m-l-2">
                        <i class="mdi mdi-account menu-icon"></i>
                      </a>';
                    $html .= '
                      <button title="View" type="button" onclick="view('.$data->id.')" class="btn btn-gradient-info btn-rounded btn-icon btn-sm">
                        <i class="mdi mdi-eye menu-icon"></i>
                      </button>';
                }
                $html .= '</div>';

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
    public function query(User $model)
    {
        $data = Request()->all();
        $userId = \Auth::user()->id??NULL;
        if(session()->has('user_view_id')){
            $userId = session()->get('user_view_id');
        }else{
            if(\Auth::user()->hasRole('admin') || \Auth::user()->hasRole('superadmin')){
                $userId = NULL;
            }
        }
        $type = 'teams';
        if(session()->has('teams_type')){
            $type = session()->get('teams_type');
            if($type == 'teams'){
                // $model = $model->where('parent_id', $userId);
            }elseif($type == 'referrers'){
                $model = $model->where('referrer_id', $userId);
            }
        }
        // if(isset($data['rank_id'])){
        //     $model = $model->where('referrer_package_id', $data['rank_id']);
        // }
        if(isset($data['startdate']) && isset($data['enddate'])){
            $model = $model->whereDate('created_at','>=',$data['startdate'])
                ->whereDate('created_at','<=',$data['enddate']);
        }

        if($type == 'teams'){
            $level_id1 = $model->where('parent_id', $userId)->pluck('id')->toArray();
            $level_id2 = $model->whereIn('parent_id', $level_id1)->pluck('id')->toArray();
            $level_id3 = $model->whereIn('parent_id', $level_id2)->pluck('id')->toArray();
        }else{
            return $model->newQuery();
        }

        if(isset($data['level_id']) && $data['level_id'] == 1){
            $model = $model->whereIn('id', $level_id1);
        }elseif(isset($data['level_id']) && $data['level_id'] == 2){
            $model = $model->whereIn('id', $level_id2);
        }elseif(isset($data['level_id']) && $data['level_id'] == 3){
            $model = $model->whereIn('id', $level_id3);
        }else{
            $model = $model->where('parent_id', $userId);
        }
        return $model->whereNotIn('id', [1,2])->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('users-table')
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
            Column::make('user Id'),
            Column::make('name'),
            Column::make('mobile'),
            Column::make('joining date'),
            Column::make('status'),
            // Column::make('level'),
            // Column::make('rank'),
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
        return 'Users_' . date('YmdHis');
    }
}
