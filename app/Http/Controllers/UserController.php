<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankDetail;
use Validator;
use Session;
use Carbon\Carbon;
use App\Models\User;
use App\DataTables\AllUsersDataTable;
use App\DataTables\UsersDataTable;
use App\DataTables\EpinRequestsDataTable;
use App\DataTables\AuditsDataTable;
use App\DataTables\BankDetailsDataTable;
use App\DataTables\BankDetailsUnApproveDataTable;
use App\DataTables\PaymentsDataTable;
use App\DataTables\WalletDataTable;
use App\DataTables\EbooksDataTable;
use App\DataTables\BankPassbookDataTable;
use Illuminate\Support\Facades\Hash;
use App\Models\Payments;
use App\Models\Epin;
use App\Events\UpdateWallet;
use App\Events\BulkEpin;
use App\Models\Wallet;
use App\Models\Package;
use App\Models\Level;
use App\Models\Ebook;
use App\Models\Marketplace;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Mail\BankOtp;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'User Profile';
        $this->headerIcon = 'mdi mdi-account';
    }

    public function index()
    {
        $row = User::where('id', \Auth::user()->id)->first();
        $this->row = $row;
        return view('user.profile', $this->data);
    }

    public function allUserIndex(AllUsersDataTable $dataTable)
    {
        if(auth()->user()->hasRole('user')){
            abort(404);
        }
        if(session()->has('user_view_id')){
            \Session::forget('user_view_id');
        }
        \Session::put('teams_type','all');
        $levels = Level::all();
        $ranks = Package::where('id','!=',1)->get();
        $row = User::find(\Auth::user()->parent_id);
        $this->pageTitle = 'All User';
        $this->headerIcon = 'mdi mdi-account';
        $this->levels = $levels;
        $this->ranks = $ranks;
        $this->row = $row;
        return $dataTable->render('user.all-user', $this->data);
    }

    public function edit()
    {
        $row = User::where('id', \Auth::user()->id)->first();
        $this->row = $row;
        return view('user.edit-profile', $this->data);
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $request->id,
                'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust file types and size as needed
            ]);
            $user = User::find($request->id);
            if ($user) {
                $user->name = $request->name??NULL;
                $user->mobile = $request->mobile??NULL;
                $user->address = $request->address??NULL;

                if ($request->hasFile('profile')) {
                    $files = $request->file('profile')->getClientOriginalName ();
                    // Get Filename
                    $filename = pathinfo($files, PATHINFO_FILENAME);
                    // Get just Extension
                    $extension = $request->file('profile')->getClientOriginalExtension();
                    // Filename To store
                    $image = $filename. '_'. time().'.'.$extension;
                    // Upload Image
                    $path = $request->file('profile')->storeAs('public/image/profile', $image);
                    $user->profile = $image;
                }
                $user->save();
                Session::flash('title', 'Success!');
                Session::flash('message', 'Profile saved successfully.');
                Session::flash('alert-class', 'bg-success');
                return redirect()->back();
            }
        } catch (Exception $e) {
        }
        Session::flash('title', 'Error!');
        Session::flash('title', 'Error!');
        Session::flash('message', 'Something went wrong.');
        Session::flash('alert-class', 'bg-dander');
        return redirect()->back();

    }

    public function changePassword()
    {
        return view('user.change-password',$this->data);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'sometimes|required',
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required',
        ]);
        $attributeNames = array(
            'old_password' => 'Old Password',
            'password' => 'Password',
            'password_confirmation' => 'Confirm Password'
        );

        $validator->setAttributeNames($attributeNames);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::find(\Auth::user()->id);

        if (isset($request->old_password) && Hash::check($request->old_password, $user->password)) 
        {
            $user->password = Hash::make($request->password);
            $user->save();

            Session::flash('title', 'Success!');
            Session::flash('message', 'Password changed successfully.');
            Session::flash('alert-class', 'bg-success');
            return redirect()->back();
        }else{
            Session::flash('title', 'Error!');
            Session::flash('message', 'Old Password is invalid.');
            Session::flash('alert-class', 'bg-danger');
            return redirect()->back();
        }

    }

    public function bankDetail()
    {
        $rows = BankDetail::where('user_id', \Auth::user()->id)->first();
        $this->rows = $rows;
        $this->pageTitle = 'Bank';
        $this->headerIcon = 'mdi mdi-bank';
        return view('bank.add', $this->data);
    }
    
    public function bankDetailSaveUpdate(Request $request)
    {
        $rule = [
            'bank_name' => 'required',
            'bank_type' => 'required',
            'bank_ac_number' => 'required|numeric',
            'bank_ifsc' => 'required',
            'bank_holder_name' => 'required',
            'cheque_image' => 'mimes:pdf,png,svg,jpeg,jpg',
            'pancard_image' => 'mimes:pdf,png,svg,jpeg,jpg',
        ];
        if (empty($request->bank_id)) {
            $rule['cheque_image'] = 'required|mimes:pdf,png,svg,jpeg,jpg';
        }

        $validator = Validator::make($request->all(), $rule);

        $attributeNames = array(
            'bank_name' => 'Bank Name',
            'bank_type' => 'Bank Type',
            'bank_ac_number' => 'Account Number',
            'bank_ifsc' => 'IFSC Code',
            'bank_holder_name' => 'Hoslder Name',
            'cheque_image' => 'Cancelled Chque',
            'pancard_image' => 'Pancard Chque',
        );
        $validator->setAttributeNames($attributeNames);

        if($validator->fails())
        {
           return response()->json([
                'title' => 'Error',
                'status' => 'error',
                'message' => $validator->errors()->all(),
                'position' => 'top-right'
            ]);
        }

        $user = Auth()->user();
        if(is_null($request->otp_status)){
            $otp = mt_rand(100000, 999999);
            $user->update(['otp' => $otp]);
            \Mail::to($user->email)->send(new BankOtp(['otp' => $otp]));
            return response()->json([
                'title' => 'OTP Sent',
                'status' => 'warning',
                'message' => 'An OTP has been sent to your registered email address',
                'position' => 'top-right'
            ]);
        }

        if($request->otp != $user->otp){
            return response()->json([
                'title' => 'Error',
                'status' => 'error',
                'message' => 'OTP entered is incorrect',
                'position' => 'top-right'
            ]);
        }

        $user->update(['otp_at' => Carbon::now(), 'otp' => NULL, ]);

        $pancard = $cheque = NULL;
        if ($request->hasFile('cheque_image')) {
            $file = $request->file('cheque_image')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('cheque_image')->getClientOriginalExtension();
            // Filename To store
            $cheque = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('cheque_image')->storeAs('public/image', $cheque);
        }
        if ($request->hasFile('pancard_image')) {
            $file = $request->file('pancard_image')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('pancard_image')->getClientOriginalExtension();
            // Filename To store
            $pancard = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('pancard_image')->storeAs('public/image', $pancard);
        }

        $save = BankDetail::updateOrCreate(
            [
                'id' => $request->bank_id
            ],
            [
                'user_id' => \Auth::user()->id,
                'bank_name' => $request->bank_name,
                'bank_type' => $request->bank_type,
                'bank_ac_number' => $request->bank_ac_number,
                'bank_ifsc' => $request->bank_ifsc,
                'bank_holder_name' => $request->bank_holder_name,
            ]);

        if ($save) {
            if (!is_null($cheque)) {
                $save->cheque_image = $cheque;
                $save->save();
            }
            if (!is_null($pancard)) {
                $save->pancard_image = $pancard;
                $save->save();
            }
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Bank Detail saved successfull.',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! Sometimes went wrong.',
            'position' => 'top-right',
        ]);

    }

    public function bankDetailDelete(Request $request)
    {
        $delete = BankDetail::find($request->id)->delete();

        if ($delete) {
            Session::flash('title', 'Success!');
            Session::flash('message', 'Bank Detail deleted successfully.');
            Session::flash('alert-class', 'bg-success');
            return redirect('bank-detail');
        }
        Session::flash('title', 'Error!');
        Session::flash('title', 'Error!');
        Session::flash('message', 'Something went wrong.');
        Session::flash('alert-class', 'bg-dander');
        return redirect()->back();

    }

    public function bankDetailIndex(Request $request, BankDetailsDataTable $dataTable)
    {
        $type = $request->type;
        $query = \DB::table('bank_detail');
        \Session::put('type',$type);
        $this->pageTitle = 'Bank';
        $this->headerIcon = 'mdi mdi-bank';
        return $dataTable->render('bank.index',$this->data);
    }

    public function teamIndex(UsersDataTable $dataTable)
    {
        if(session()->has('user_view_id')){
            \Session::forget('user_view_id');
        }
        \Session::put('teams_type','teams');
        $levels = Level::all();
        $ranks = Package::where('id','!=',1)->get();
        $row = User::find(\Auth::user()->parent_id);
        $this->pageTitle = 'Teams';
        $this->headerIcon = 'mdi mdi-account-multiple';
        $this->levels = $levels;
        $this->ranks = $ranks;
        $this->row = $row;
        return $dataTable->render('user.team-index', $this->data);
    }

    public function myReferrerIndex(UsersDataTable $dataTable)
    {
        if(session()->has('user_view_id')){
            \Session::forget('user_view_id');
        }
        \Session::put('teams_type','referrers');
        $levels = Level::all();
        $ranks = Package::where('id','!=',1)->get();
        $this->pageTitle = 'My Referrers';
        $this->headerIcon = 'mdi mdi-account-multiple';
        $this->levels = $levels;
        $this->ranks = $ranks;
        return $dataTable->render('user.team-index', $this->data);
    }

    public function epinIndex(EpinRequestsDataTable $dataTable)
    {
        $this->pageTitle = 'Epins';
        $this->headerIcon = 'mdi mdi-blur-radial';
        return $dataTable->render('epins.index',$this->data);
    }

    public function audits(AuditsDataTable $dataTable)
    {
        $this->pageTitle = 'Audits';
        $this->headerIcon = 'mdi mdi-history';
        return $dataTable->render('audits.index',$this->data);
    }

    public function idVerificationIndex()
    {
        $this->pageTitle = 'Id verification';
        $this->headerIcon = 'mdi mdi-account-check';
        return view('id-verification-index',$this->data);
    }

    public function bankDetailView(Request $request)
    {
        $row = BankDetail::find($request->id);
        $this->row = $row;
        return view('bank.view',$this->data);
    }

    public function bankDetailStatusUpdate(Request $request)
    {
        $bank = BankDetail::find($request->id);
        $status = 'unapprove';
        if($request->status == 'unapprove'){
            $status = 'approve';
        }
        $bank->status = $status;
        $bank->save();
        return response()->json(['status'=>'success','message'=>'Status updated successfully!']);
    }

    public function idActive(Request $request)
    {
        $row = User::find($request->id);
        // Payment getway Pending
        return view('user.user-id-active', compact('row'));
    }

    public function viewTeams(Request $request,UsersDataTable $dataTable)
    {
        if(\Auth::user()->hasRole('user') || \Auth::user()->hasRole('admin') || \Auth::user()->hasRole('superadmin')){
            \Session::put('user_view_id',$request->id);
        }
        \Session::put('teams_type','teams');
        $row = User::find($request->id);
        $col = '3';
        $levels = Level::all();
        $ranks = Package::where('id','!=',1)->get();
        $this->pageTitle = $row->name.'\'s Teams';
        $this->headerIcon = 'mdi mdi-account-multiple';
        $this->levels = $levels;
        $this->ranks = $ranks;
        $this->row = $row;
        $this->col = $col;
        return $dataTable->render('user.team-index', $this->data);
    }

    public function idVerificationModal()
    {
        return view('user.id-verification-modal')->render();
    }

    public function idVerificationReceiptUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|mimes:png,svg,jpeg,jpg'
        ]);
        if ($validator->fails()) {
           return response()->json([
                'title' => 'Error',
                'status' => 'error',
                'message' => $validator->errors()->all(),
                'position' => 'top-right'
            ]);
        }
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('receipt')->getClientOriginalExtension();
            // Filename To store
            $receipt = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('receipt')->storeAs('public/image', $receipt);
        }else{
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'The Receipt required',
                'position' => 'top-right',
            ]);
        }
        $payment = Payments::updateOrCreate([
                'user_id'=>\Auth::user()->id,
                'paid_type'=>'manually',
                'paid_for'=>'verification',
            ],[
                'user_id'=>\Auth::user()->id,
                'amount'=>200,
                'paid_type'=>'manually',
                'paid_for'=>'verification',
                'requested_at'=>now(),
                'receipt'=>$receipt,
            ]);
        if ($payment) {
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Receipt uploaded successfully.',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! sometimes went wrong.',
            'position' => 'top-right',
        ]);
    }

    public function idVerificationRequests(PaymentsDataTable $dataTable)
    {
        $this->pageTitle = ' ID Verification Requests';
        $this->headerIcon = 'mdi mdi-account-check';
        Session()->put('request_type','verification');
        return $dataTable->render('user.id-verification-request',$this->data);
    }

    public function idVerificationRequestView(Request $request)
    {
        $row = Payments::find($request->id);
        $wallet = [];
        if($row->paid_for == 'withdraw' || $row->paid_for == 'upgrade_wallet'){
            $wallet = Wallet::where('user_id', $row->user_id)->first();
        }
        return view('user.id-verification-request-view',compact('row', 'wallet'));
    }

    public function idVerificationRequestAction(Request $request)
    {
        $payment = Payments::find($request->id);
        $status = 'pending';
        if($request->status == 'pending'){
            $status = 'active';
        }
        $payment->approved_at = now();
        $payment->approved_by = \Auth::user()->id;
        $payment->status = $status;
        $payment->save();

        $user = $payment->user;
        $referrer = $user->referrer;

        if ($referrer !== NULL && $user->is_active == 0) {

            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~START~~~~~~~~~~~~~~~~~~~~');
            if (isset($user->referrer_id)) {
                //Update parentID
                \Log::channel('wallet-log')->info('New User active ::user_id '.$user->id);
                \Log::channel('wallet-log')->info('---::sponser_id '.$user->referrer_id);
                $parent_id = checkLastUser($referrer);
                $user->parent_id = $parent_id;
                $parent = User::find($parent_id);
                $user->referrer_level_id = $parent->level_id??NULL;
                $user->referrer_package_id = ($parent->package_id??0)+1;
                try {
                    \Log::channel('wallet-log')->info('---update::parent_id '.$user->parent_id);
                    \Log::channel('wallet-log')->info('---update::referrer_level_id '.$user->referrer_level_id);
                    \Log::channel('wallet-log')->info('---update::referrer_package_id '.$user->referrer_package_id);
                } catch (Exception $e) {
                    \Log::channel('wallet-log')->info($e);
                }
            }

            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~~~Update Wallet');
            event (new UpdateWallet($user));
            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~~~END~~~~~~~~~~~~~~~~~~~~');
        }
        
        $user->is_active = 1;
        $user->active_at = Carbon::now();
        $user->active_by = \Auth::user()->id;
        $user->save();
        return response()->json([
            'title' => 'Success !',
            'status' => 'success',
            'message' => 'Status updated successfully!',
            'position' => 'top-right',
        ]);
    }

    public function epinRequestModal()
    {
        return view('epins.request-modal')->render();
    }

    public function epinRequestStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epin_count' => 'required|min:1',
            'receipt' => 'required|mimes:png,svg,jpeg,jpg'
        ]);
        if ($validator->fails()) {
           return response()->json([
                'title' => 'Error',
                'status' => 'error',
                'message' => $validator->errors()->all(),
                'position' => 'top-right'
            ]);
        }
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($file, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('receipt')->getClientOriginalExtension();
            // Filename To store
            $receipt = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('receipt')->storeAs('public/image', $receipt);
        }else{
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'The Receipt required',
                'position' => 'top-right',
            ]);
        }
        $payment = Payments::create([
            'user_id'=>\Auth::user()->id,
            'amount'=>$request->epin_count*100,
            'paid_type'=>'manually',
            'paid_for'=>'epin',
            'requested_at'=>now(),
            'receipt'=>$receipt,
        ]);
        if ($payment) {
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Receipt uploaded successfully.',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! sometimes went wrong.',
            'position' => 'top-right',
        ]);
    }

    public function epinRequests(PaymentsDataTable $dataTable)
    {
        $this->pageTitle = 'Epin Verification Requests';
        $this->headerIcon = 'mdi mdi-blur-radial';
        Session()->put('request_type','epin');
        return $dataTable->render('epins.verification-request',$this->data);
    }

    public function epinVerificationRequestView(Request $request)
    {
        $row = Payments::find($request->id);
        return view('epins.verification-request-view',compact('row'));
    }

    public function epinVerificationRequestAction(Request $request)
    {
        $payment = Payments::find($request->id);
        $status = 'pending';
        if($request->status == 'pending'){
            $status = 'active';
        }
        $payment->approved_at = now();
        $payment->approved_by = \Auth::user()->id;
        $payment->status = $status;
        $payment->save();

        if ($payment->status == 'active') {
            event (new BulkEpin($payment));
        }
        return response()->json([
            'title' => 'Success !',
            'status' => 'success',
            'message' => 'Status updated successfully!',
            'position' => 'top-right',
        ]);
    }

    public function activeIdUsingEpin(Request $request)
    {
        $epin = Epin::where('pin', $request->epin)->first();
        if(!$epin){
            return response()->json([
                'title' => 'Warning !',
                'status' => 'info',
                'message' => 'Oops! Looks like Epin not exist',
                'position' => 'top-right',
            ]);
        }
        if($epin->used == 1){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Epin aleady used',
                'position' => 'top-right',
            ]);
        }
        $epin->used = 1;
        $epin->save();
        
        $user = User::find($request->id);
        $referrer = $user->referrer;
        if ($referrer !== NULL && $user->is_active == 0) {

            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~START~~~~~~~~~~~~~~~~~~~~');
            if (isset($user->referrer_id)) {
                //Update parentID
                \Log::channel('wallet-log')->info('New User active ::user_id '.$user->id);
                \Log::channel('wallet-log')->info('---::sponser_id '.$user->referrer_id);
                $parent_id = checkLastUser($referrer);
                $user->parent_id = $parent_id;
                $parent = User::find($parent_id);
                $user->referrer_level_id = $parent->level_id??NULL;
                $user->referrer_package_id = ($parent->package_id??0)+1;
                try {
                    \Log::channel('wallet-log')->info('---update::parent_id '.$user->parent_id);
                    \Log::channel('wallet-log')->info('---update::referrer_level_id '.$user->referrer_level_id);
                    \Log::channel('wallet-log')->info('---update::referrer_package_id '.$user->referrer_package_id);
                } catch (Exception $e) {
                    \Log::channel('wallet-log')->info($e);
                }
            }

            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~~~Update Wallet');
            event (new UpdateWallet($user));
            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~~~END~~~~~~~~~~~~~~~~~~~~');
        }

        $user->is_active = 1;
        $user->active_at = Carbon::now();
        $user->active_by = \Auth::user()->id;
        $user->save();

        return response()->json([
            'title' => 'Success !',
            'status' => 'success',
            'message' => 'Status updated successfully!',
            'position' => 'top-right',
        ]);
    }

    public function wallet(WalletDataTable $dataTable)
    {
        $wallet = Wallet::where('user_id', \Auth::user()->id)->first();
        $this->wallet = $wallet;
        $this->pageTitle = 'Wallet History';
        $this->headerIcon = 'mdi mdi-history';

        $payments = Payments::where('user_id', \Auth::user()->id)->where('status','pending');

        $walletRequest = $payments->clone()->select(DB::raw('SUM(amount) as amounts'))->where('paid_for','withdraw')->get()->toArray()[0];
        $mainWallet = $wallet->main??0;
        if(isset($walletRequest['amounts']) && $walletRequest['amounts']){
            $mainWallet = $mainWallet-$walletRequest['amounts'];
        }

        $upgradeRequest = $payments->clone()->where('paid_for','upgrade_wallet')->first();

        $this->amount = $mainWallet;
        $this->upgade = $upgradeRequest;

        return $dataTable->render('user.wallet',$this->data);
    }

    public function walletWithdrawRequestModal(Request $request)
    {
        $payments = Payments::where('user_id', \Auth::user()->id)->where('status','pending');
        $wallet = Wallet::where('user_id', \Auth::user()->id)->first();

        $walletRequest = $payments->clone()->select(DB::raw('SUM(amount) as amounts'))->where('paid_for','withdraw')->get()->toArray()[0];
        $amount = $wallet->main??0;
        if(isset($walletRequest['amounts']) && $walletRequest['amounts']){
            $amount = $amount-$walletRequest['amounts'];
        }

        $upgade = $payments->clone()->where('paid_for','upgrade_wallet')->first();
        return view('user.wallet-withdraw-request-send', compact('amount','upgade'));
    }

    public function walletWithdrawRequestStore(Request $request)
    {
        if(empty($request->amount) || !is_numeric($request->amount)){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Please enter valid amount',
                'position' => 'top-right',
            ]);
        }
        $bank = BankDetail::where('user_id', \Auth::user()->id)->first();
        if(empty($bank)){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Please update your bank details first',
                'position' => 'top-right',
            ]);
        }

        // if($bank->status != 'approve'){
        //     return response()->json([
        //         'title' => 'Error !',
        //         'status' => 'error',
        //         'message' => 'Your bank details has not been approved by admin',
        //         'position' => 'top-right',
        //     ]);
        // }

        /*Re-Check amount*/
        $payments = Payments::where('user_id', \Auth::user()->id)->where('status','pending');
        $wallet = Wallet::where('user_id', \Auth::user()->id)->first();

        $walletRequest = $payments->clone()->where('paid_for','withdraw')->first();
        $mainWallet = $wallet->main??0;
        if($walletRequest){
            $mainWallet = $mainWallet-$walletRequest->amount;
        }

        if($request->amount > $mainWallet){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Please enter valid amount',
                'position' => 'top-right',
            ]);
        }
        /*Re-Check amount*/

        $payment = Payments::create([
            'user_id'=>\Auth::user()->id,
            // 'main_amount'=>$request->amount,
            // 'tds'=>$request->amount,
            // 'admin_charge'=>$request->amount,
            'amount'=>$request->amount,
            'main_amount'=>$request->amount,
            'paid_type'=>'manually',
            'paid_for'=>'withdraw',
            'requested_at'=>now(),
            'status'=>'pending',
        ]);
        if ($payment) {
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Withdraw request sent successfully!',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! sometimes went wrong.',
            'position' => 'top-right',
        ]);
    }

    public function viewUser(Request $request)
    {
        $row = User::where('id', $request->id)->first();
        return view('user.view', compact('row'));
    }

    public function eBooks(EbooksDataTable $dataTable)
    {
        if(auth()->user()->hasRole('user')){
            abort(404);
        }
        $this->pageTitle = 'Ebooks';
        $this->headerIcon = 'mdi mdi-book-open-variant';
        return $dataTable->render('eBooks.index',$this->data);
    }

    public function addEbook()
    {
        if(auth()->user()->hasRole('user')){
            abort(404);
        }
        $this->pageTitle = 'Add Ebook';
        $this->headerIcon = 'mdi mdi-book-open-variant';
        return view('eBooks.create',$this->data);
    }

    public function storeEbook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ebook_name' => 'required',
            'image' => 'required|mimes:png,svg,jpeg,jpg',
            'file' => 'required|mimes:pdf',
        ]);
        $attributeNames = array(
            'ebook_name' => 'Ebook name',
            'image' => 'Ebook Image',
            'file' => 'Ebook file',
        );

        $validator->setAttributeNames($attributeNames);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bookName = $request->ebook_name;
        $file = $request->file('file');
        $name = $file->hashName();
        $upload = Storage::disk('ebooks')->put("{$name}", file_get_contents($file));
        $image = $request->file('image');
        $image_name = $image->hashName();
        $image_upload = Storage::disk('ebooks')->put("{$image_name}", file_get_contents($image));
        $img = NULL;
        if ($request->hasFile('image')) {
            $files = $request->file('image')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($files, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('image')->getClientOriginalExtension();
            // Filename To store
            $img = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('image')->storeAs('public/image', $img);
        }

        Ebook::create([
            'name' => "{$bookName}",
            'image' => $img,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'path' => "ebooks/{$name}",
            'disk' => config('app.uploads.disk'),
            'file_hash' => hash_file(
                config('app.uploads.hash'),
                Storage::disk('ebooks')->path("{$name}"),
            ),
            'collection' => '',
            'size' => $file->getSize(),
            'added_by' => \Auth::id(),
        ]);

        return redirect()->route('ebooks')->withSuccess('Ebook added successfully');
    }

    public function destoryEbook(Request $request)
    {
        $ebook = Ebook::find($request->id);
        if ($ebook) {
            if (Storage::disk('ebooks')->exists(basename($ebook->path))) {
                Storage::disk('ebooks')->delete(basename($ebook->path));
            }
            $ebook->delete();
            return redirect()->back()->withSuccess('Ebook deleted successfully');
        } else {
            return redirect()->back()->withErrors('Ebook not found');
        }
    }

    public function previewEbook()
    {
        $this->pageTitle = 'Ebooks';
        $this->headerIcon = 'mdi mdi-book-open-variant';
        $this->eBooks = Ebook::all();
        return view('eBooks.preview',$this->data);
    }

    public function viewEbook($id)
    {
        $ebook = Ebook::find($id);
        if(empty($ebook)){
            return redirect()->back()->withErrors('Ebook not found');
        }
        $this->pageTitle = $ebook->name;
        $this->headerIcon = 'mdi mdi-book-open-variant';

        if(!Storage::disk('ebooks')->exists(basename($ebook->path))){
            return redirect()->back()->withErrors('Ebook file not exist, Looks like file has been deleted');
        }
        $file = Storage::disk('ebooks')->get(basename($ebook->path));
        $this->fileBase64 = base64_encode($file);
        return view('eBooks.view',$this->data);
    }

    public function downloadEbook($id)
    {
        $ebook = Ebook::find($id);
        if ($ebook) {
            if (Storage::disk('ebooks')->exists(basename($ebook->path))) {
                return response()->download(Storage::disk('ebooks')->path(basename($ebook->path)));
            }
        }
        return redirect()->back()->withErrors('Ebook not found');
    }

    public function upgradeWalletAction(Request $request)
    {
        $user_id = \Auth::user()->id;
        if($request->id){
            $payment = Payments::find($request->id);
            $user_id = $payment->user_id??NULL;
        }
        if(is_null($user_id)){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Oops ! Request not found.',
                'position' => 'top-right',
            ]);
        }
        $wallet = Wallet::where('user_id', $user_id)->first();
        $payment = Payments::where('user_id', $user_id)->where('paid_for', 'upgrade_wallet')->where('status', 'pending')->first();

        if ($payment) {

            $newJoin = $user = User::find($user_id);

            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~START~~~~~~~~~~~~~~~~~~~~');
            /*Send amount 3 parents */
            // (C) Level 3
            if(isset($user->parent)){
                upgradeWallet($newJoin, $user = $user->parent, 1, $newJoin->package_id);
            }

            // (B) Level 2
            if(isset($user->parent)){
                upgradeWallet($newJoin, $user = $user->parent, 2, $newJoin->package_id);
            }

            // (A) Level 1
            if(isset($user->parent)){
                upgradeWallet($newJoin, $user = $user->parent, 3, $newJoin->package_id);
            }
            \Log::channel('wallet-log')->info('~~~~~~~~~~~~~~~~~END~~~~~~~~~~~~~~~~~~~~');

            $payment->upgrade_status = 'upgraded';
            $payment->status = 'active';
            $payment->approved_at = now();
            $payment->approved_by = \Auth::user()->id;
            $payment->save();

            $wallet->upgrade = 0;
            $wallet->save();
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Wallet upgraded successfully!',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! Your wallet aleady upgraded.',
            'position' => 'top-right',
        ]);
    }

    public function bankPassbook(BankPassbookDataTable $dataTable)
    {
        $this->wallet = Wallet::where('user_id', \Auth::user()->id)->first();
        $this->pageTitle = 'Bank Passbook';
        $this->headerIcon = 'mdi mdi-book-open-variant';


        $walletRequest = Payments::where('user_id', \Auth::user()->id)->where('paid_for','withdraw_wallet')->where('status','pending')->first();
        $upgade = 0;
        $amount = $wallet->upgrade??0;
        if($walletRequest){
            $amount = $amount-$walletRequest->amount;
            $upgade = $walletRequest->amount;
        }

        $this->amount = $amount;
        $this->upgade = $upgade;
        return $dataTable->render('bank.passbook',$this->data);
    }

    public function walletWithdrawRequest(PaymentsDataTable $dataTable)
    {
        $this->pageTitle = 'Wallet withdraw requests';
        $this->headerIcon = 'mdi mdi-wallet';
        Session()->put('request_type','withdraw');
        return $dataTable->render('user.wallet-withdraw-request',$this->data);
    }

    public function withdrawRequestView(Request $request)
    {
        $row = Payments::find($request->id);
        $wallet = [];
        if($row->paid_for == 'withdraw'){
            $wallet = Wallet::where('user_id', $row->user_id)->first();
        }
        return view('user.wallet-withdraw-request-modal',compact('row', 'wallet'));
    }

    public function withdrawRequestAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'tds' => "required_if:status,==,approve"
        ]);
        if ($validator->fails()) {
           return response()->json([
                'title' => 'Error',
                'status' => 'error',
                'message' => $validator->errors()->all(),
                'position' => 'top-right'
            ]);
        }

        $payment = Payments::find($request->id);
        if($payment){

            $wallet = $payment->user->wallet??NULL;

            $tdsAmount = ($payment->amount*($request->tds??0)/100); 
            $adminCharge = ($payment->amount*5/100); // 5% 
            $json = [
                'amount' => $payment->amount,
                'wallet' => $wallet->main??NULL,
                'tds' => $request->tds,
                'tds_amount' => $tdsAmount,
                'admin_charge' => $adminCharge,
            ];

            $payment->description = $request->desc??'';
            $payment->tds = $request->tds??NULL;
            $payment->tds_amount = $request->tds;
            $payment->main_Amount = $payment->amount;
            $payment->amount = $payment->amount;
            $payment->approved_at = now();
            $payment->approved_by = \Auth::user()->id;
            $payment->extra_json = json_encode($json);

            $wallet = $payment->user->wallet??NULL;
            if ($wallet) {
                if ($wallet && $payment->status == 'approve' && $request->status != 'pending') {
                    // revert amount
                    $wallet->main = ($wallet->main??0)+$payment->main_Amount;
                    $wallet->withdraw = ($wallet->withdraw??0)-$payment->main_Amount;
                    $wallet->tds = ($wallet->tds??0)-$tdsAmount;
                    $wallet->admin_charge = ($wallet->admin_charge??0)-$adminCharge;
                    $wallet->save();
                }elseif ($wallet && $request->status == 'approve') {
                    // minus amount
                    $wallet->main = ($wallet->main??0)-$payment->main_Amount;
                    $wallet->withdraw = ($wallet->withdraw??0)+$payment->main_Amount;
                    $wallet->tds = ($wallet->tds??0)+$tdsAmount;
                    $wallet->admin_charge = ($wallet->admin_charge??0)+$adminCharge;
                    $wallet->save();
                }
            }
            $payment->status = $request->status;
            $payment->save();
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Status updated successfully!',
                'position' => 'top-right',
            ]);
        }else{
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Wallet request not found!',
                'position' => 'top-right',
            ]);
        }
        
    }

    public function walletUpragdeIndex(PaymentsDataTable $dataTable)
    {
        $this->pageTitle = 'Pending Upgrade Wallets';
        $this->headerIcon = 'mdi mdi-clock';
        Session()->put('request_type','upgrade_wallet');
        return $dataTable->render('user.pending-wallet-updrade',$this->data);
    }

    public function royaltyIncomeModal(Request $request)
    {
        $row = User::find($request->id);
        return view('royalty.modal', compact('row'));
    }

    public function royaltyIncomeStore(Request $request)
    {
        $amount = $request->amount??NULL;
        $id = $request->id??NULL;
        if(!is_numeric($amount)){
            return response()->json([
                'title' => 'Error !',
                'status' => 'error',
                'message' => 'Please enter valid amount!',
                'position' => 'top-right',
            ]);
        }
        $payment = Payments::create([
            'user_id'=>$id,
            'amount'=>$request->amount,
            'main_amount'=>$request->amount,
            'paid_type'=>'manually',
            'paid_for'=>'royalty',
            'requested_at'=>now(),
            'status'=>'approve',
            'approved_by' => \Auth::user()->id
        ]);
        if ($payment) {
            $wallet = $payment->user->wallet??NULL;
            if ($wallet) {
                // update wallet amount
                $wallet->main = ($wallet->main??0)+$payment->main_amount;
                $wallet->save();
            }
            return response()->json([
                'title' => 'Success !',
                'status' => 'success',
                'message' => 'Royalty amount added successfully!',
                'position' => 'top-right',
            ]);
        }

        return response()->json([
            'title' => 'Error !',
            'status' => 'error',
            'message' => 'Oops ! sometimes went wrong.',
            'position' => 'top-right',
        ]);
    }

    public function marketplace()
    {
        $this->pageTitle = 'Market Place';
        $this->headerIcon = 'mdi mdi-shopping';
        $this->marketplace = Marketplace::all();
        return view('marketplace.index',$this->data);
    }

    public function marketplaceCreate()
    {
        if(auth()->user()->hasRole('user')){
            abort(404);
        }
        $this->pageTitle = 'Add Item';
        $this->headerIcon = 'mdi mdi-shopping';
        return view('marketplace.create',$this->data);
    }

    public function marketplaceStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'link' => 'required|url',
            'image' => 'required|mimes:png,svg,jpeg,jpg',
        ]);
        $attributeNames = array(
            'name' => 'Itne name',
            'link' => 'Item link',
            'image' => 'Item Image',
        );

        $validator->setAttributeNames($attributeNames);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $image = NULL;
        if ($request->hasFile('image')) {
            $files = $request->file('image')->getClientOriginalName ();
            // Get Filename
            $filename = pathinfo($files, PATHINFO_FILENAME);
            // Get just Extension
            $extension = $request->file('image')->getClientOriginalExtension();
            // Filename To store
            $image = $filename. '_'. time().'.'.$extension;
            // Upload Image
            $path = $request->file('image')->storeAs('public/image/item', $image);
        }

        Marketplace::create([
            'name' => $request->name,
            'image' => $image,
            'link' => $request->link,
            'description' => $request->description??NULL,
            'added_by' => \Auth::id(),
        ]);

        return redirect()->route('marketplace')->withSuccess('Item added successfully');
    }

    public function marketplaceDelete(Request $request)
    {
        $item = Marketplace::find($request->id);
        if($item){
            $item->delete();
        }
        return response()->json([
            'title' => 'Success',
            'status' => 'success',
            'message' => 'Item has been deleted successfully',
            'position' => 'top-right'
        ]);
    }

    public function idVerificationDelete(Request $request)
    {
        $delete = Payments::find($request->id);
        
        if ($delete) {
            $delete->delete();
        }
        Session::flash('title', 'Success!');
        Session::flash('message', 'Record has been deleted successfully.');
        Session::flash('alert-class', 'bg-success');
        return redirect()->back();
    }

    public function userDelete(Request $request)
    {
        $delete = User::find($request->uid);
        
        if ($delete) {
            $delete->delete();
        }
        Session::flash('title', 'Success!');
        Session::flash('message', 'Record has been deleted successfully.');
        Session::flash('alert-class', 'bg-success');
        return redirect()->back();
    }
}
