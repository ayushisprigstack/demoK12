<?php
namespace App;
use App\Models\ErrorLog;


class Helper{
   public static function Parseerror($errorfrom,$errormsg,$priority){
        $error = new ErrorLog();       
        $error->error_from = $errorfrom;
        $error->error_msg = $errormsg;
        $error->priority = $priority;
        $error->created_date = now()->format('Y-m-d');
        $error->save();
    

    }
}
