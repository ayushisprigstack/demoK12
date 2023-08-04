<?php
namespace App;
use App\Models\ErrorLog;


class Helper{
   public static function Parseerror($errorfrom,$errormsg,$uid,$priority){
        $error = new ErrorLog();
        $error->user_id = $uid;
        $error->error_from = $errorfrom;
        $error->error_msg = $errormsg;
        $error->priority = $priority;
        $error->created_date = now()->format('Y-m-d');
        $error->save();
        
        $utility = new \App\Utility;
        $toEmail = 'ayushi.sprigstack@outlook.com';
        $sub =   'Welcome to gocoworq!';
        $body = 'There is high priority errors.';
//        $body = file_get_contents(resource_path('views/mails/newSpace_new.blade.php'));
//        $body = str_replace("~spacename~", request('spacename'), $body);
//        $body = str_replace("~cityname~", $cityName->name, $body);
//        $body = str_replace("~regcode~", $randomid, $body);
           $utility->sendEmail_New($toEmail, $sub, $body);       

    }
}