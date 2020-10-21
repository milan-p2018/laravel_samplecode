<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Auth;
use Closure;
use App\User;
use App\WorkerData;
use Carbon\Carbon;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    //Function to check the api token is expired or not
    public function handle($request, Closure $next, ...$guards)
    {
        $status = 401;
        if($request->is('api*')) {
            //to check the header content for language
            $locale = $request->headers->has('Content-Language') ? $request->header('Content-Language') : 'de';
            app()->setLocale($locale);
            if(!empty($request->bearerToken('Authorization'))) {
                $user = User::where('api_token', $request->bearerToken('Authorization'))->first();
                if($user) {
                    if((Carbon::parse($user->expired_at))->gte(Carbon::now())) {
                        return $next($request);
                    } else {
                        $user->expired_at = null;
                        $user->api_token = null;
                        $user->save(); 
                    }
                } 
                $error['error'] = [\Lang::get('lang.token-expired-msg')];
                $err_response  = [
                    'success' => false,
                    'errors' => $error
                ];
                return response()->json($err_response, $status); 
            } else {
                $error['authorization token'] = [\Lang::get('lang.token-not-provided-msg')];
                $err_response  = [
                    'success' => false,
                    'errors' => $error
                ];
                return response()->json($err_response, $status);
            }
        } else {
            if (!Auth::check()) {
                return redirect(route('login'));
            }
            else if(!empty(Auth::user()->email_verified_at)) {
                if(!User::find(Auth::id())->workerData()->exists() && !$request->is('profil/update-profile') && !$request->is('profil/store')) {
                    return redirect(route('profil.update-profile'));
                }
            }
            return $next($request);
        }
    }
}
