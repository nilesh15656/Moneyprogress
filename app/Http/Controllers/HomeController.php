<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use DB;
use App\Events\UpdateWallet;
use App\Mail\ContactUs;
use Validator;
use Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Dashboard';
        $this->headerIcon = 'mdi mdi-home';
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $counts = DB::table('users')
            ->select(
                DB::raw('COUNT(id) as total_teams'),
                DB::raw("SUM(CASE WHEN is_active = '0' THEN 1 ELSE 0 END) AS inactive"),
                DB::raw("SUM(CASE WHEN is_active = '1' THEN 1 ELSE 0 END) AS active"),
                DB::raw("SUM(CASE WHEN is_epin_requested = '1' THEN 1 ELSE 0 END) AS epin_requested"),
                DB::raw("SUM(Date(created_at) = CURDATE()) AS today_joined_users"),
                DB::raw("SUM(CASE WHEN package_id = '2' THEN 1 ELSE 0 END) AS silver"),
                DB::raw("SUM(CASE WHEN package_id = '3' THEN 1 ELSE 0 END) AS gold"),
                DB::raw("SUM(CASE WHEN package_id = '4' THEN 1 ELSE 0 END) AS diamond"),
                DB::raw("SUM(CASE WHEN package_id = '5' THEN 1 ELSE 0 END) AS platinum")
            )->when(auth()->user()->hasRole('user'), function ($q) {
                return $q->where(function($q){

                    $level_id1 = $q->clone()->where('parent_id', \Auth::id())->pluck('id')->toArray();
                    $level_id2 = $q->clone()->whereIn('parent_id', $level_id1)->pluck('id')->toArray();
                    $level_id3 = $q->clone()->whereIn('parent_id', $level_id2)->pluck('id')->toArray();
                
                    $q->where('referrer_id','=',\Auth::id());
                    $q->orWhereIn('id',array_merge($level_id1,$level_id2,$level_id3));
                });
            })
            ->whereNotIn('id',[1,2])
            ->get()
            ->toArray()[0];

        $wallet = DB::table('wallets')
            ->select(
                DB::raw("SUM(main) as main_wallet"),
                DB::raw("SUM(upgrade) as upgrade_wallet"),
                DB::raw("SUM(royalty) as royalty"),
                DB::raw("SUM(tds) as tds"),
                DB::raw("SUM(admin_charge) as admin_charge"),
            )->when(auth()->user()->hasRole('user'), function ($q) {
                return $q->where('wallets.user_id','=',\Auth::id());
            })
            ->get()
            ->toArray()[0];

        $epin = DB::table('epins')
            ->select(
                DB::raw("SUM(CASE WHEN used = '0' THEN 1 ELSE 0 END) AS epin_count"),
            )->when(auth()->user()->hasRole('user'), function ($q) {
                return $q->where('epins.user_id','=',\Auth::id());
            })
            ->where('epins.status','=','active')
            ->get()
            ->toArray()[0];

        $payment = DB::table('payments')
            ->select(
                DB::raw("SUM(CASE WHEN paid_for = 'withdraw' THEN 1 ELSE 0 END) AS withdraw_count"),
                DB::raw("SUM(CASE WHEN paid_for = 'epin' THEN 1 ELSE 0 END) AS epin"),
                DB::raw("SUM(CASE WHEN paid_for = 'verification' THEN 1 ELSE 0 END) AS id_verification_count"),
                DB::raw("SUM(tds) AS tds_count"),
            )->when(auth()->user()->hasRole('user'), function ($q) {
                return $q->where('payments.user_id','=',\Auth::id());
            })->where('status', 'pending')
            ->get()
            ->toArray()[0];

        $walletWithdraw = DB::table('payments')
            ->select(
                DB::raw("SUM(CASE WHEN paid_for = 'withdraw' THEN amount ELSE 0 END) AS pending_wallet_amount"),
            )->when(auth()->user()->hasRole('user'), function ($q) {
                return $q->where('payments.user_id','=',\Auth::id());
            })->where('status','=','pending')
            ->get()
            ->toArray()[0];
        $counts->main_wallet = (($wallet->main_wallet ?? 0) - ($walletWithdraw->pending_wallet_amount??0));
        $counts->upgrade_wallet = $wallet->upgrade_wallet ?? 0;
        $counts->royalty = $wallet->royalty ?? 0;
        $counts->epin_count = $epin->epin_count ?? 0;
        $counts->withdrawReq = $payment->withdraw_count ?? 0;
        $counts->epinReq = $payment->epin ?? 0;
        $counts->idVerReq = $payment->id_verification_count ?? 0;
        $counts->tds = $wallet->tds ?? 0;
        $counts->admin_charge = $wallet->admin_charge ?? 0;

        $this->counts = $counts;
        return view('home-dashboard', $this->data);
    }

    public function inquiry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        $attributeNames = array(
            'name' => 'name',
            'email' => 'email',
            'subject' => 'subject',
            'message' => 'Message'
        );

        $validator->setAttributeNames($attributeNames);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $mail_data = ['name' => $request->get('name'), 'email' => $request->get('email'), 'subject' => $request->get('subject'), 'msg' => $request->get('message')];
        \Mail::to("inquiry@moneyprogress.in")->send(new ContactUs($mail_data));
        Session::flash('title', 'Success!');
        Session::flash('message', 'Inquiry sent successfully.');
        Session::flash('alert-class', 'bg-success');
        return redirect()->back();

    }
}
