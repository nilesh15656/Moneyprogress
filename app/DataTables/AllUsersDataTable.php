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

class AllUsersDataTable extends DataTable
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
                // $query->where('is_active',$status);
            })
            ->addColumn('user', function($data) {
                return $data->reference;
            })
            ->addColumn('name', function($data) {
                return $data->name;
            })
            ->addColumn('sponsor', function($data) {
                return $data->referrer->reference??'';
            })
            ->addColumn('upline', function($data) {
                return $data->parent->reference??'';
            })
            ->addColumn('email', function($data) {
                return $data->email;
            })
            ->addColumn('joining_date', function($data) {
                return $data->created_at->format('d/m/Y h:i A') ?? '';
            })
            ->addColumn('active_date', function($data) {
                return optional(optional($data->activeReq())->created_at)->format('d/m/Y h:i A') ?? '';
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
            ->addColumn('status', function($data) {
                $badge = 'danger'; $status = 'In Active';
                if ($data->is_active) {
                    $badge = 'success';
                    $status = 'Active';
                }
                return '<label class="badge badge-gradient-'.$badge.'"><span class="text-dark h6 m-1">'.$status.'</span></label>';
            })
            ->addColumn('rank', function($data) {
                return $data->packageInformation->name??'';
            })
            ->addColumn('package_at', function($data) {
                if($data->package_at){
                    return Carbon::parse($data->package_at)->format('d/m/Y');
                }
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
                if((\Auth::user()->hasRole('admin') || \Auth::user()->hasRole('superadmin')) && $data->package_id > 1){
                    $html .= '
                      <button title="Add Royalty Income" type="button" onclick="royaltyIncome('.$data->id.')" class="btn btn-gradient-warning btn-rounded btn-icon btn-sm">
                        <i class="mdi mdi-currency-inr menu-icon"></i>
                      </button>';
                }
                if(!$data->is_active && \Auth::user()->hasRole('admin')){
                    $html .= '<form onsubmit="return confirm(\'Are you sure want to delete this Record ?\');" action="'. route('user.delete') .'" method="POST" style="display: inline;" >';
                    $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
                    $html .= '<input type="hidden" value="'.$data->id.'" name="uid">';
                    $html .= '<button type="submit" class="btn btn-gradient-danger btn-rounded btn-icon btn-sm m-l-2" title="Delete"><i class="mdi mdi-delete"></i></button>';
                    $html .= '</form>';
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
        $userId = NULL;
        if(session()->has('user_view_id')){
            $userId = session()->get('user_view_id');
        }
        if(isset($data['columns'][5]['search']['value'])){
            $status = $data['columns'][5]['search']['value'];
            $model = $model->where('is_active',$status);
        }
        if(isset($data['search']['value'])){
            $keyword = $data['search']['value'];
            $model = $model->where(function ($query) use ($keyword) {
                $query->orWhere('reference', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        /*Rank*/
        if(isset($data['rank_id'])){
            $model = $model->where('package_id', $data['rank_id']);
        }

        /*Joining Date*/
        if(isset($data['startdate']) && isset($data['enddate'])){
            $model = $model->whereDate('created_at','>=',$data['startdate'])
                ->whereDate('created_at','<=',$data['enddate']);
        }
        if($userId){
            $level_id1 = $model->where('parent_id', $userId)->pluck('id')->toArray();
            $level_id2 = $model->whereIn('parent_id', $level_id1)->pluck('id')->toArray();
            $level_id3 = $model->whereIn('parent_id', $level_id2)->pluck('id')->toArray();
            $model = $model->whereIn('id', array_merge($level_id1,$level_id2,$level_id3));
        }

        // if(isset($data['level_id']) && $data['level_id'] == 1){
        //     $model = $model->whereIn('id', $level_id1);
        // }elseif(isset($data['level_id']) && $data['level_id'] == 2){
        //     $model = $model->whereIn('id', $level_id2);
        // }elseif(isset($data['level_id']) && $data['level_id'] == 3){
        //     $model = $model->whereIn('id', $level_id3);
        // }

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
                    ->setTableId('all-users-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
                        // Button::make('create'),
                        Button::make('excel')->title('Download excel'),
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
            Column::make('name'),
            Column::make('mobile'),
            Column::make('email')->visible(false),
            Column::make('joining_date')->visible(false),
            Column::make('active_date')->visible(false),
            Column::make('rank'),
            Column::make('package_at')->title('Rank Date'),
            Column::make('status'),
            Column::make('sponsor')->visible(false),
            Column::make('upline')->visible(false),
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
        return 'All_Users_' . date('YmdHis');
    }
}
