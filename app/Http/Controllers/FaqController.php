<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Access;
use App\Models\Role;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use App\Mail\AddUserMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\Faq;

class FaqController extends Controller {
    
    function FaqData(){
        $faq_array = array();
        $faqs= Faq::all();
        foreach($faqs as $faq){            
          array_push($faq_array, ['Que'=>$faq->Question ,'Ans'=>$faq->Answer]);    
        }
       return $faq_array; 
    }

}
