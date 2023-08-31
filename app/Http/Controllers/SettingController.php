<?php

namespace App\Http\Controllers;

use App\Models\OperatingSystem;
use App\Models\Device;
use App\Models\User;
use App\Models\Logo;
use App\Models\AdminSetting;
use App\Models\TicketCcSetting;
use App\Models\InventoryCcSetting;
use App\Models\InvoiceCcSetting;
use App\Models\InventoryManagement;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Response;
use App\Mail\NewAdminMailer;
use App\Models\School;
use Illuminate\Support\Facades\Mail;
use App\Models\IncomingOutgoingBatchNotification;
use App\Models\SignUpCcSetting;
use App\Models\LoginAsSchoolAdminLog;
use App\Models\SchoolAddress;
use App\Models\Location;
use App\Models\AdminCorporateStaffCcSetting;
use App\Models\SchoolParentalCoverageCcSetting;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
use App\Models\Student;
class SettingController extends Controller
{
   

    function allMembers($sid, $uid) {
        $getUser = User::where('id', $uid)->first();
        $logodata = Logo::where('School_ID', $sid)->first();
        if (isset($logodata)) {
            $logo = $logodata->Logo_Path;
        } else {
            $logo = null;
        }
        $backtoAdmin = LoginAsSchoolAdminLog::where('LoginSchoolID', $sid)->first();

        if (isset($backtoAdmin)) {
            $data = User::where('id', $backtoAdmin->K12ID)->first();
        } else {
            $data = NULL;
        }

        return response()->json([
                    'status' => "success",
                    'logo' => $logo,
                    'user' => $getUser,
                    'BackToadminData' => $data
        ]);
    }

    function additionalSetting(Request $request) {
        $uploadLogo = $request->input('UploadLogo');
        $schoolId = $request->input('SchoolId');
        $streetLines = $request->input('StreetLines');
        $city = $request->input('City');
        $state = $request->input('State');
        $postalCode = $request->input('PostalCode');
        $countryCode = $request->input('CountryCode');
        $phoneNum = $request->input('PhoneNum');
        $addupdateId = $request->input('AddUpdateId');
        $schoolName = $request->input('SchoolName');
        $school = School::where('ID', $schoolId)->first();

        $check = Logo::where('School_ID', $schoolId)->first();       
        if (isset($check)) {
            Logo::where('School_ID', $schoolId)->update(['Logo_Path' => $uploadLogo]);
        } else {
            $logo = new Logo;
            $logo->School_ID = $schoolId;
            $logo->Logo_Path = $uploadLogo;
            $logo->save();
        }

        if ($addupdateId == 0) {
            $address = new SchoolAddress;
            $address->StreetLine = $streetLines;
            $address->City = $city;
            $address->StateOrProvinceCode = $state;
            $address->PostalCode = $postalCode;
            $address->CountryCode = $countryCode;
            $address->SchoolID = $schoolId;
            $address->PhoneNum = $phoneNum;
            $address->Location = $school->location;
            $address->save();
        } else {
            SchoolAddress::where('SchoolID', $schoolId)->update(['StreetLine' => $streetLines, 'City' => $city, 'StateOrProvinceCode' => $state, 'PostalCode' => $postalCode, 'PhoneNum' => $phoneNum]);
            $checkschool = School::whereNot('ID', $schoolId)->where('name', $schoolName)->first();
            if (isset($checkschool)) {
                return Response::json(array('status' => "error",
                            'msg' => 'School Name Already Exists.'
                ));
            } else {
                School::where('ID', $schoolId)->update(['name' => $schoolName]);
                return Response::json(array('status' => "success"));
            }
        }     
    }

    function GetAllNotifications($sid,$flag) {
//           $flag=1 for admin side
        $getEventsData = NotificationEventsLog::with('event', 'school', 'user')->where('SchoolID',$sid)->where('EventType',$flag)->get();
        $getEvents = NotificationEvents::where('Type',$flag)->get();
            foreach ($getEventsData as $data) {
            $data->eventName = $data->event->Events;
            $data->schoolName = $data->school->name ?? null;
            $data->userName = $data->user->first_name . ' ' . $data->user->last_name;
            $data->userEmail = $data->user->email;
            $data->makeHidden(['event', 'school', 'user', 'created_at', 'updated_at']);
        }

        $grouped = $getEventsData->groupBy('EventID');

        $transformed = $grouped->map(function ($events, $eventId) {
                    return [
                'Id' => $eventId,
                'Name' => $events->first()->eventName, // Assuming all events with the same ID have the same name
                'details' => $events
                    ];
                })->values();

        return response()->json(['msg' => $transformed,'events'=>$getEvents]);
        
        
    }
    
    function GetEmailsbyId($sid, $id, $skey) {
        $getEventsData = NotificationEventsLog::with('event', 'school', 'user')->where('SchoolID', $sid)->where('EventID', $id)->pluck('UserID');
        $alluser = User::whereNotIn('id', $getEventsData)->where('school_id', $sid)->get();
        $selecteduser = User::whereIn('id', $getEventsData)->where('school_id', $sid)->get();
        return Response::json(['alluser' => $alluser,
                    'selected' => $selecteduser]) ?? null;
    }

    function SaveEmails(Request $request)
    {
        $flag = $request->input('Flag');
        $emails = $request->input('Emails');
        $eventType = $request->input('EventType');
      
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $eventLog = NotificationEventsLog::where('UserID', $user->id)->where('EventID',$flag)->first();
                    if (!$eventLog) {
                        $ticketccsetting = new NotificationEventsLog();
                       
                        if($user->school_id == 0){                          
                            $SchoolID = NULL;
                        }else{
                            $SchoolID = $user->school_id;
                        }
                        $ticketccsetting->SchoolID =  $SchoolID;
                        $ticketccsetting->UserID = $user->id;
                        $ticketccsetting->EventID = $flag;
                         $ticketccsetting->EventType = $eventType;
                        $ticketccsetting->save();
                    }
                }
            }
         return "success";
        
    } 
     function deleteEmail($id, $flag)
    {         
       NotificationEventsLog::where('UserID',$id)->where('EventID',$flag)->forceDelete();       
       return "success";
    }
    

}
