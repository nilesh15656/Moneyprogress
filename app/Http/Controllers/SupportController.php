<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportMessage;
use Validator;
use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Support;
use App\DataTables\SupportsDataTable;
use Illuminate\Support\Facades\Hash;

class SupportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Contact Support';
        $this->headerIcon = 'mdi mdi-phone-in-talk';
    }

    public function index(SupportsDataTable $dataTable)
    {
        $this->row = Support::where('user_id', \Auth::user()->id)->first();
        return $dataTable->render('support.index', $this->data);
    }

    public function create()
    {
        return view('support.add')->render();
    }

    public function save(Request $request)
    {
        if(!isset($request->subject)){
            return response()->json(['status'=>'error','message'=>'The subject is required']);
        }
        if(!isset($request->description)){
            return response()->json(['status'=>'error','message'=>'The description is required']);
        }
        $save = Support::updateOrCreate(
            [
                'id' => $request->support_id
            ],
            [
                'user_id' => \Auth::user()->id,
                'subject' => $request->subject,
                'description' => $request->description,
            ]);

        if ($save) {
            return response()->json(['status'=>'success','message'=>'saved successfully']);
        }
        return response()->json(['status'=>'error','message'=>'Oops! Something went wrong']);
    }

    public function view(Request $request)
    {
        $row = Support::with('reply')->find($request->id);
        return view('support.view', compact('row'));
    }

    public function reply(Request $request)
    {
        if(!isset($request->reply)){
            return response()->json(['status'=>'error','message'=>'The reply is required']);
        }
        $sender = 'admin';
        if(\Auth::user()->hasRole('user')){
            $sender = 'user';
        }
        $save = SupportMessage::create(
            [
                'support_id' => $request->support_id,
                'sender_id' => \Auth::user()->id,
                'sender' => $sender,
                'message' => $request->reply,
            ]);

        if ($save) {
            return response()->json(['status'=>'success','message'=>'saved successfully']);
        }
        return response()->json(['status'=>'success','message'=>'Oops! Something went wrong']);
    }
}
