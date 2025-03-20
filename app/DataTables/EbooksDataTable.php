<?php

namespace App\DataTables;

use App\Models\Ebook;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class EbooksDataTable extends DataTable
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
            ->editColumn('action', function($data) {
                $html = '';
                $html .= '<form onsubmit="return confirm(\'Are you sure want to delete this ebook ?\');" action="'. route('ebook.destroy') .'" method="POST" style="display: inline;" >';
                $html .= '<input type="hidden" name="_token" value="'.csrf_token().'">';
                $html .= '<input type="hidden" value="'.$data->id.'" name="id">';
                $html .= '<button title="Delete" type="submit" class="btn btn-danger btn-rounded btn-icon"><i class="mdi mdi-delete"></i></button>';
                $html .= '</form>';
                return $html;
            })
            ->rawColumns(['action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Ebook $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Ebook $model)
    {
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
                    ->setTableId('ebooks-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->buttons(
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
            Column::make('name'),
            Column::make('created_at'),
            Column::make('updated_at'),
            Column::computed('action')
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'Ebooks_' . date('YmdHis');
    }
}
