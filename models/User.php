<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Cmgmyr\Messenger\Traits\Messagable;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Storage;
use Helper;
use Auth;
use App\Organization;
use Illuminate\Support\Facades\Session;
use App\AuthorizationRole;

class User extends Authenticatable implements MustVerifyEmail
{
    use Messagable;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'secret', 'expired_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['profile_pic_url', 'encrypted_user_id', 'role_permission'];

    public function DistributorGroups()
    {
        return $this->hasMany('App\OrganizationDistributor');
    }
    public function WorkerData()
    {
        return $this->hasOne('App\WorkerData');
    }
    public function PatientData()
    {
        return $this->hasOne('App\PatientData');
    }
    public function Messages(){
        return $this->hasMany('Cmgmyr\Messenger\Models\Message');
    }
    public function WorkerSettings(){
        return $this->hasOne('App\WorkerSettings', 'user_id','id');
    }

    public function UserVerifiedOrganizations()
    {
        return $this->hasMany('App\Organization', 'owner') ->where('owner', \Auth::user()->id)->whereNotNull('verified_at')->orderBy('created_at', 'DESC');
    }

    public function UserHasOrganizations()
    {
        return $this->hasMany('App\Organization', 'owner') ->where('owner', \Auth::user()->id)->orderBy('created_at', 'DESC');
    }

    //function to override the reset-password  functionality
    public function sendPasswordResetNotification($token){
        $user = $this;
        $data = [
            'email' => $this->email,
            'reset_url' => route('password.reset', ['token' => $token, 'email' => $this->email]),
        ];
        $this->notify(new ResetPassword($data));
    }

    public function getprofilePicUrlAttribute()
    {
        return !empty($this->profile_pic) && Storage::disk('public')->exists($this->profile_pic) ?  url('/storage') . $this->profile_pic : NULL;
    }

    //apend the encrypted_document_id to document response
    public function getencryptedUserIdAttribute()
    {
        return encrypt($this->id);
    }

    //append the access permission with user modal
    public function getRolePermissionAttribute() {
        //Get current organization id
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $organizagtion_details = array();
        if(!empty($organization_id)) {
            $organizagtion_details = Organization::find($organization_id);
        }
        $user_roles = [];
        //If auth user is a patient then return empty array
        if(!empty(Auth::user()) && Auth::user()->type) {
            return $user_roles;
        }

        // To check in guard Api
        if(!empty(Auth::guard('api')->user()) && Auth::guard('api')->user()->type) {
            return $user_roles;
        }

        //If user has worker-data 
        if(!empty(Auth::user()) && !empty(Auth::user()->workerData())) {
            $role_name = '';
            //Bind the work_data_species_id to role 
            switch (Auth::user()->workerData()->get()[0]->worker_data_species_id) {
                case "lang_doc":
                    $role_name = 'doctor-role';
                    break;
                case "lang_physio":
                    $role_name = 'physiotherapist';
                    break;
                case "lang_assistant":
                    $role_name = 'medical-professional';
                    break;
                case "lang_it-staff":
                    $role_name = 'it-staff';
                    break;
            }

            //if the logged in user is the owner of current organization then user have the administrator role
            if(!empty($organizagtion_details) && $organizagtion_details->owner == Auth::user()->id) {
                $role_name = $role_name.'-owner';
            }
            //get the role details
            $user_roles = AuthorizationRole::with(['Permissions'])->where('name', $role_name)->first();
        }
        return $user_roles;
    }
}
