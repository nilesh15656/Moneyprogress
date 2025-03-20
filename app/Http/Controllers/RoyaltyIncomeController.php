<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\RoyaltyIncome;
use App\Models\Level;
use App\DataTables\RoyaltyIncomeDataTable;
use Illuminate\Support\Facades\Hash;

class RoyaltyIncomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(RoyaltyIncomeDataTable $dataTable)
    {
        $this->row = RoyaltyIncome::where('user_id', \Auth::user()->id)->first();
        $this->pageTitle = 'Royalty Income';
        $this->headerIcon = 'mdi mdi-currency-inr';
        return $dataTable->render('royalty.index', $this->data);
    }

    public function add()
    {
        $levels = Level::all();
        return view('royalty.add',compact('levels'))->render();
    }

    public function search(Request $request)
    {
        $dateFrom = $request->from??NULL;
        $dateTo = $request->to??NULL;
        $level = $request->level??NULL;
        dd($request->all()); // continue working need to discuss for top silver users
        return view('support.view', compact('row'));
    }
}
