<?php
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Payments;
use App\Models\Commission;
use App\Models\Wallet;
use App\Models\Package;

if (!function_exists('checkLastUser'))
{
  function checkLastUser($user)
  {
    $child = $user->child;
    \Log::channel('wallet-log')->info('------------::Child Count '.$child->count());
    if($child->count() < 5){
      \Log::channel('wallet-log')->info('------------::returnP '.$user->id);
      return $user->id;
    }
    return checkLastUserLoop($child);
  }
}

if (!function_exists('checkLastUserLoop'))
{
  function checkLastUserLoop($child, $collection='', $array = [])
  {
    foreach ($child as $key => $ch) {
      $collection = empty($collection) ? $ch->child : $collection->merge($ch->child);
      $array[$ch->id] = $ch->child->count();
      if($ch->child->count() == 0){
        break;
      }
    }

    \Log::channel('wallet-log')->info('------------::Child array ');

    \Log::channel('wallet-log')->info($array);
    if(count($array) && MIN($array) < 5){
      \Log::channel('wallet-log')->info('------------::returnP '.array_search(MIN($array),$array));
      return $parent_id = array_search(MIN($array),$array);
    }
    return checkLastUserLoop($collection);
  }
}

if (!function_exists('checkLastUserOldd'))
{
  function checkLastUserOldd($parent_id)
  {
      \Log::info('checkLastUser Helper ::P '.$parent_id);
      $child = User::where('parent_id',$parent_id)->get();
      \Log::info('------------::child Count '.$child->count());
      if($child->count() < 5){
        \Log::info('------------::return '.$parent_id);
        return $parent_id;
      }
      return checkLastUserLoopOldd($child->pluck('id')->toArray());
  }
}

if (!function_exists('checkLastUserLoopOldd'))
{
  function checkLastUserLoopOldd($ids = array())
  {
    \Log::info('------------::ids ');
    \Log::info($ids);
    $iids = [];
    foreach ($ids as $key => $parent_id) {
      \Log::info('checkLastUser Helper ::P '.$parent_id);
      $child = User::where('parent_id',$parent_id)->get();
      \Log::info('------------::child Count '.$child->count());
      if($child->count() < 5){
        \Log::info('------------::return '.$parent_id);
        return $parent_id;
      }
      $iids = array_merge($iids,$child->pluck('id')->toArray());
    }
    return checkLastUserLoop($iids);
  }
}

if (!function_exists('updateWallet'))
{
  function updateWallet($newJoin, $user, $level_id, $package_id = 1)
  {
    // basic
    $commission = Commission::query()
        ->where('level_id', $level_id)
        ->where('package_id', $package_id)
        ->latest()
        ->first();

    $deductions = json_decode($commission->deductions??[]);

    if(isset($deductions->upgrade) && isset($deductions->main)){

        $wallet = Wallet::where('user_id', $user->id)->first();
        $myWallet = Wallet::updateOrCreate([
            'user_id' => $user->id,
        ],[
            'upgrade' => ($wallet->upgrade??0)+$deductions->upgrade,
            'main' => ($wallet->main??0)+$deductions->main,
            'referrer_id' => $newJoin->id??NULL,  // for audits only
        ]);

        \Log::channel('wallet-log')->info('---update upgrade amount with { '.$deductions->upgrade.' } changed { '.($wallet->upgrade??0).' } to { '.$myWallet->upgrade.' }');
        \Log::channel('wallet-log')->info('---main amount with { '.$deductions->main.' } changed { '.($wallet->main??0).' } to { '.$myWallet->main.' }');

        /* Upgrade package */
        $package_id = $user->package_id+1;
        $package = Package::find($package_id);
        if($package && $myWallet->upgrade == $package->basic_amount){
            $payment = Payments::create([
                'user_id'=>$user->id,
                'package_id'=>$package->id,
                'upgrade_status'=>'waiting',
                'amount'=>$myWallet->upgrade,
                'paid_type'=>'auto-system',
                'paid_for'=>'upgrade_wallet',
                'requested_at'=>now(),
                'status'=>'pending'
            ]);
            $user->package_id = $package->id;
            $user->save();
            // $myWallet->upgrade = 0;
            // $myWallet->save();
            \Log::channel('wallet-log')->info('---Upgrade wallet ID ('.$user->id.') amount { '.$myWallet->upgrade.' } package_id '.$package->id.' : payment_id '.$payment->id);
        }
    }
  }
}

if (!function_exists('upgradeWallet'))
{
  function upgradeWallet($newJoin, $user, $level_id, $package_id = 1)
  {
    $commission = Commission::query()
        ->where('level_id', $level_id)
        ->where('package_id', $package_id)
        ->latest()
        ->first();

    $deductions = json_decode($commission->deductions??[]);

    if(isset($deductions->upgrade) && isset($deductions->main)){

        $wallet = Wallet::where('user_id', $user->id)->first(); 
        $userReffererCount = $user->referrals->where('is_active',1)->count();
        if($userReffererCount >= 5){
          $myWallet = Wallet::updateOrCreate([
              'user_id' => $user->id,
          ],[
              'upgrade' => ($wallet->upgrade??0)+$deductions->upgrade,
              'main' => ($wallet->main??0)+$deductions->main,
              'referrer_id' => $newJoin->id??NULL,  // for audits only
          ]);
        }else{
          $myWallet = Wallet::updateOrCreate([
              'user_id' => $user->id,
          ],[
              'upgrade' => ($wallet->upgrade??0)+$deductions->upgrade,
              // 'main' => ($wallet->main??0)+$deductions->main,
              'referrer_id' => $newJoin->id??NULL,  // for audits only
          ]);
          /*Parent*/
          if($user->parent){
            $walletParent = Wallet::where('user_id', $user->parent->id)->first();
            if($walletParent){
              $myParentWallet = Wallet::updateOrCreate([
                  'user_id' => $user->parent->id,
              ],[
                  // 'upgrade' => ($wallet->upgrade??0)+$deductions->upgrade,
                  'main' => ($walletParent->main??0)+$deductions->main,
                  'referrer_id' => $newJoin->id??NULL,  // for audits only
              ]);
            }
          }
        }

        \Log::channel('wallet-log')->info('---upgrade amount with { '.$deductions->upgrade.' } changed { '.($wallet->upgrade??0).' } to { '.$myWallet->upgrade.' } he Refferer of Count: '.$userReffererCount);
        \Log::channel('wallet-log')->info('---main amount with { '.$deductions->main.' } changed { '.($wallet->main??0).' } to { '.$myWallet->main.' }');

        /* Upgrade package */
        /* Check next package amount to uprdade current package */
        $package_id = $user->package_id+1;
        $package = Package::find($package_id);
        if($package && $myWallet->upgrade == $package->amount){
            $payment = Payments::create([
                'user_id'=>$user->id,
                'package_id'=>$package->id,
                'upgrade_status'=>'waiting',
                'amount'=>$wallet->upgrade,
                'paid_type'=>'auto-system',
                'paid_for'=>'upgrade_wallet',
                'requested_at'=>now(),
                'status'=>'pending',
            ]);
            $user->package_id = $package->id;
            $user->package_at = now();
            $user->save();
            $myWallet->upgrade = 0;
            $myWallet->save();
        }
    }
  }
}

if (!function_exists('profile'))
{
    /**
     * Get profile photo of user
     */
    function profile($userId)
    {
        $userData = User::findOrFail($userId);
        $name = substr($userData->name, 0, 2);
        $img = public_path() . '/storage/image/profile/' . $userData->profile;
        if (file_exists($img) && $userData->profile) {
            return url('storage/image/profile/' . $userData->profile);
        } else {
            return "https://ui-avatars.com/api/?name=" . $name . "&color=7F9CF5&background=EBF4FF";
        }
    }
}
?>