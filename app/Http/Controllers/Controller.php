<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Carbon\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * @var array
     */
    public $data = [];

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Put here if you wish to set a value that will be used by all controllers.
            $this->rank = $this->joiningDate = $this->code = $this->activatedDate = '';
            if(auth()->user()){
                try {
                    if(auth()->user()->package_id != 1){
                        $this->rank = auth()->user()->packageInformation->name ?? '';
                        $this->rank = strtolower($this->rank);
                    }
                    $this->joiningDate = auth()->user()->created_at->format('d/m/Y h:i A') ?? '';
                    $this->code = auth()->user()->is_active ? auth()->user()->referral_code : '';
                    $this->activatedDate = auth()->user()->active_at ? Carbon::parse(auth()->user()->active_at)->format('d/m/Y h:i A') : '';
                } catch (\Exception $e) {
                    
                }
            }
            return $next($request);
        });
    }
}
