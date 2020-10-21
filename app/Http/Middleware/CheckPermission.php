<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use App\OrganizationDistributor;
use Illuminate\Support\Facades\Auth;
use View;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $module_param)
    {
        $params = explode('__', $module_param);
        $module_id = $params[0];    // get the module_id
        $sub_module_id = $params[1];    // get the sub_module_id
        $module_name = Config::get('globalConstants.authorization_role_modules')[$params[0]];
        $access_permission = $params[2];    //Get the access permission
        $permissions = Auth::user()->role_permission;
        //If user has the permission and assigned role
        if(!empty($permissions) && !$permissions->Permissions->isEmpty()) {
            $permission_array = $permissions->Permissions->toArray();
            //Filter the array to get the required module data and it's permission
            $module_permission = array_filter($permission_array, function ($var) use ($module_id, $access_permission, $sub_module_id) {
                    //To check the function is used for multiple purpose or single
                    //['Multiple purpose then access_permission will be 'multi-purpose-func']
                    //['Single purpose then access_permission will be 'can_view, can_update, can_create, can_delete']
                    if($access_permission != 'multi-purpose-func') { 
                        // If the specific module [$module_id] has the specific permission [$access_permission] then return that module                
                        if($var['module_id'] == $module_id && $var['sub_module_id'] == $sub_module_id) {               
                            return ($var['can_'.$access_permission] == 1);
                        }
                    } else {  
                        // If access_permissuin is multiple purpose function then directly return the specific module
                        return ($var['module_id'] == $module_id && $var['sub_module_id'] == $sub_module_id);
                    }
                    
                });
            // If module permission found
            if(!empty($module_permission)) {
                $module_permission = array_shift($module_permission);
                $role_permission = array(
                    'can_view' => $module_permission['can_view'],
                    'can_create' => $module_permission['can_create'],
                    'can_update' => $module_permission['can_update'],
                    'can_delete' => $module_permission['can_delete'],
                );
                //Share the permission array to all the views
                View::share('permission_array', $role_permission);
                return $next($request);
            }
        }
        // Redirect to organization Dashboard page
        $message = \Lang::get('lang.restrict-permission-message');
        $request->session()->flash('alert-danger', $message);
        return redirect(route('organization.new'));
    }
}
