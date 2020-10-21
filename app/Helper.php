<?php

namespace App\Helpers;
use phpseclib\Crypt\RSA;
use FFMpeg;
use Carbon\Carbon;
use Auth;
use Config;
class Helper
{
    //to encrypt the data for api responses
    public static function cryptoJsAesEncrypt($passphrase, $value){
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx.$passphrase.$salt, true);
            $salted .= $dx;
        }
        //generate the key and iv        
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);
        //encrypt the data using key and iv using aes-256-cbs algorithm
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return json_encode($data);
    }

    //to encrypt the passpharase for api response
    public static function encrypt($value, $public_key) {
        $rsa = new RSA();
        $rsa->loadKey(base64_decode($public_key)); 
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        return $rsa->encrypt($value);
    }

    //to decrypt the data for api responses
    public static function cryptoJsAesDecrypt($passphrase, $jsonString){
        $jsondata = json_decode($jsonString, true);
        try {
            $salt = hex2bin($jsondata["s"]);
            $iv  = hex2bin($jsondata["iv"]);
        } catch(Exception $e) { return null; }
        $ct = base64_decode($jsondata["ct"]);
        $concatedPassphrase = $passphrase.$salt;
        $md5 = array();
        $md5[0] = md5($concatedPassphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
            $result .= $md5[$i];
        }
        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        return json_decode($data, true);
    }

    //function to get the chart data
    public static function getChartData($start, $end) {
        $random_numbers = [];
        for( $i = 0; $i <= 49; $i++) {
            $random_numbers[] = rand($start, $end);
        }
        return $random_numbers;
    }

    //function to generate the thubnail
    public static function getThumbnailForExerciseVideo($name, $path) {
        $ffmpeg = \FFMpeg\FFMpeg::create([
                    'ffmpeg.binaries'  => env('FFMPEG_PATH'),
                    'ffprobe.binaries' => env('FFPROBE_PATH') 
        ]);
        $video = $ffmpeg->open(\URL::asset('storage' . $path));
        $frame = $video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5));
        $frame->save('storage/exercises/'.$name .'.png');
        return '/exercises/'.$name. '.png';
    }

    //function to generate the random password which containt uppercase, lowercase, number and special characters
    public static function generateStrongRandomPassword($len = 8) {
        //enforce min length 8
        if($len < 8)
            $len = 8;

        //define character libraries - remove ambiguous characters like iIl|1 0oO
        $sets = array();
        $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '1234567890';
        $sets[]  = '~!@#$%^&*(){}[],./?';

        $password = '';
        
        //append a character from each set - gets first 4 characters
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }

        //use all characters to fill up to $len
        while(strlen($password) < $len) {
            //get a random set
            $randomSet = $sets[array_rand($sets)];
            
            //add a random char from the random set
            $password .= $randomSet[array_rand(str_split($randomSet))]; 
        }
        
        //shuffle the password string before returning!
        return str_shuffle($password);
    }
	
    //function to send the array of permission
    public static function checkUserHasPermission($module_id, $sub_module_id, $permission) {
        $permission_array = [];
        //To check the user is logged in or not
        if(Auth::check()) {
            //If user has the permission and assigned role
            $permissions = Auth::user()->role_permission;
            if(!empty($permissions) && !$permissions->Permissions->isEmpty()) {
                $permission_array = $permissions->Permissions->toArray();
                //Filter the array to get the required module data and it's permission
                $module_permission = array_filter($permission_array, function ($var) use ($module_id, $sub_module_id, $permission) {
                        // If the specific module [$module_id] has the specific permission [$permission] then return that module
                        if($var['module_id'] == $module_id && $var['sub_module_id'] == $sub_module_id) {
                            return ($var[$permission] == 1);
                        }
                    });
                // If module permission found
                if(!empty($module_permission)) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function getOrganizationsProcessStatus($status, $organization_type, $flow_type) {
        $parallel_status_array_owner = Config::get('globalConstants.owner_organization_parallel_status_array_flow_type_'.$flow_type);
        $parallel_status_array_connected = Config::get('globalConstants.connected_organization_parallel_status_array_flow_type_'.$flow_type);
        $data = array();

        if ($organization_type == 'owner') {
            foreach ($parallel_status_array_owner as $key => $value) {
                if (isset($value[$status])) {
                    $data['owner_organization_process_status'] = $status;
                    $data['connected_organization_process_status'] = $value[$status];
                }
            }
        } else {
            foreach ($parallel_status_array_connected as $key => $value) {
                if (isset($value[$status])) {
                    $data['connected_organization_process_status'] = $status;
                    $data['owner_organization_process_status'] = $value[$status];
                }
            }
        }
        return $data;
    }
}