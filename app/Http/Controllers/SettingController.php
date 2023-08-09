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
class SettingController extends Controller
{

    function allMembers($sid,$uid)
    {
        $getUser = User::where('id', $uid)->first();
        $logodata = Logo::where('School_ID', $sid)->first();
        if (isset($logodata)) {
            $logo = $logodata->Logo_Path;
        } else {
            $logo = null;
        }
        $backtoAdmin = LoginAsSchoolAdminLog::where('LoginSchoolID',$sid)->first();
       
        if(isset($backtoAdmin)){          
           $data =  User::where('id', $backtoAdmin->K12ID)->first();
        }else{
            $data = NULL;
        }
                
                return response()->json([
                    'status' => "success",
                    'logo' => $logo,
                    'user' => $getUser,
                    'BackToadminData'=>$data
        ]);
    }

    function additionalSetting(Request $request)
 {
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
        $school = School::where('ID',$schoolId)->first();       
        if($addupdateId == 0){
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
            }
        }

        $logoisset = Logo::where('School_ID', $schoolId)->where('Logo_Path', $request->input('UploadLogo'))->first();       
        if (!isset($logoisset)) {
            $check = Logo::where('School_ID', $schoolId)->first();
            if (isset($check)) {
                Logo::where('School_ID', $schoolId)->update(['Logo_Path' => $uploadLogo]);
            } else {
                $logo = new Logo;
                $logo->School_ID = $schoolId;
                $logo->Logo_Path = $uploadLogo;
                $logo->save();
            }
        }
        return Response::json(array('status' => "success"));
    }

    function GetAllNotifications($sid, $flag)
    {
        $copyTicketArray = array();
        $sendInventoryArray = array();
        $incomingBatchesArray = array();
        $outgoingBatchesArray = array();
        $InvoicesArray = array();
        $notificationArray = array();
        $SignupUsersArray = array();
        $AdminSideInsuranceArray = array();
        $SchoolSideInsuranceArray = array();
        
        $getTicket = TicketCcSetting::where('School_ID', $sid)->get();
        foreach ($getTicket as $ticketData) {
            $ticketuser = User::where('id', $ticketData->UserID)->first();
            array_push($copyTicketArray, ['emailid' => $ticketuser->id, 'email' => $ticketuser->email]);
        }

        $getInventory = InventoryCcSetting::where('School_ID', $sid)->get();
        foreach ($getInventory as $inventoryData) {
            $inventoryuser = User::where('id', $inventoryData->UserID)->first();
            array_push($sendInventoryArray, ['emailid' => $inventoryuser->id, 'email' => $inventoryuser->email]);
        }

        if ($sid == 0) {
            $getMails = IncomingOutgoingBatchNotification::where('School_ID', $sid)
                ->orWhereNull('School_ID')
                ->get();
        } else {
            $getMails = IncomingOutgoingBatchNotification::where('School_ID', $sid)->get();
        }
        foreach ($getMails as $mail) {
            $batchuser = User::where('id', $mail->UserID)->first();

            if ($mail->BatchType == 2) {
                array_push($outgoingBatchesArray, ['emailid' => $batchuser->id, 'email' => $batchuser->email]);
            } else {
                array_push($incomingBatchesArray, ['emailid' => $batchuser->id, 'email' => $batchuser->email]);
            }
        }

        $getInvoice = InvoiceCcSetting::where('School_ID', $sid)->get();
        foreach ($getInvoice as $invoicedata) {
            $invoiceuser = User::where('id', $invoicedata->UserID)->first();
            array_push($InvoicesArray, ['emailid' => $invoiceuser->id, 'email' => $invoiceuser->email]);
        }

        if ($sid == 0) {
            $getSignup = SignUpCcSetting::where('School_ID', $sid)
                ->orWhereNull('School_ID')
                ->get();
        } else {
            $getSignup = SignUpCcSetting::where('School_ID', $sid)->get();
        }
        foreach ($getSignup as $signupdata) {
            $signupuser = User::where('id', $signupdata->UserID)->first();
            array_push($SignupUsersArray, ['emailid' => $signupuser->id, 'email' => $signupuser->email]);
        }
        
        $getadminSideInsuranceStaffs = AdminCorporateStaffCcSetting::all();
        foreach($getadminSideInsuranceStaffs  as  $getadminSideInsuranceStaffdata){
            $staffuser = User::where('id', $getadminSideInsuranceStaffdata->UserID)->first();
            array_push($AdminSideInsuranceArray, ['emailid' => $staffuser->id, 'email' => $staffuser->email]);
        }
        
        $getSchoolParentalCoverageCcs =  SchoolParentalCoverageCcSetting::where('SchoolID',$sid)->get();
         foreach($getSchoolParentalCoverageCcs  as  $getSchoolParentalCoverageData){
            $schoolcoverageuser = User::where('id', $getSchoolParentalCoverageData->UserID)->first();
            array_push($SchoolSideInsuranceArray, ['emailid' => $schoolcoverageuser->id, 'email' => $schoolcoverageuser->email]);
        }

        if ($flag == 1) {
            $notificationArray[] = [
                'Id' => 1,
                'Name' => 'Copy Ticket Notification Emails',
                'emails' => $copyTicketArray
            ];

            $notificationArray[] = [
                'Id' => 2,
                'Name' => 'Send Inventory Restock Notification Emails',
                'emails' => $sendInventoryArray
            ];
            $notificationArray[] = [
                'Id' => 4,
                'Name' => 'Outgoing Batches Notification Emails',
                'emails' => $outgoingBatchesArray
            ];

            $notificationArray[] = [
                'Id' => 5,
                'Name' => 'Invoice Notification Emails',
                'emails' => $InvoicesArray
            ];
             $notificationArray[] = [
                'Id' => 8,
                'Name' => 'Parental Coverage Notification Emails',
                'emails' => $SchoolSideInsuranceArray
            ];
        } else {
            $notificationArray[] = [
                'Id' => 3,
                'Name' => 'Incoming Batches Notification Emails',
                'emails' => $incomingBatchesArray
            ];
            $notificationArray[] = [
                'Id' => 6,
                'Name' => 'Signup Users Notification Emails',
                'emails' => $SignupUsersArray
            ];
            $notificationArray[] = [
                'Id' => 7,
                'Name' => 'Insurance Plan Notification Emails',
                'emails' => $AdminSideInsuranceArray
            ];
        }

        return Response::json(['msg' => $notificationArray]);
    }

    function GetEmailsbyId($sid, $id, $skey)
    {
        $query = User::where('school_id', $sid);
        if ($id == 1) {
            $getTicketIDs = TicketCcSetting::where('School_ID', $sid)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = $query->whereNotIn('id', $getTicketIDs)->get(['id', 'email']);
            } else {
                $UserData = $query->whereNotIn('id', $getTicketIDs)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if ($id == 2) {
            $getinventoryIDs = InventoryCcSetting::where('School_ID', $sid)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = $query->whereNotIn('id', $getinventoryIDs)->get(['id', 'email']);
            } else {
                $UserData = $query->whereNotIn('id', $getinventoryIDs)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if ($id == 3) {
            $batchIDs = IncomingOutgoingBatchNotification::where('School_ID', $sid)->where('BatchType', 1)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = User::whereIn('access_type', [5, 6])->whereNotIn('id', $batchIDs)->get(['id', 'email']);
            } else {
                $UserData = User::whereIn('access_type', [5, 6])->whereNotIn('id', $batchIDs)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if ($id == 4) {
            $batchIDs = IncomingOutgoingBatchNotification::where('School_ID', $sid)->where('BatchType', 2)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = $query->whereNotIn('id', $batchIDs)->get(['id', 'email']);
            } else {
                $UserData = $query->whereNotIn('id', $batchIDs)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if ($id == 5) {
            $InvoiceIDs = InvoiceCcSetting::where('School_ID', $sid)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = $query->whereNotIn('id', $InvoiceIDs)->get(['id', 'email']);
            } else {
                $UserData = $query->whereNotIn('id', $InvoiceIDs)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if ($id == 6) {
            $signup = SignUpCcSetting::where('School_ID', $sid)->pluck('UserID');
            if ($skey == 'null') {
                $UserData = User::whereIn('access_type', [5])->whereNotIn('id', $signup)->get(['id', 'email']);
            } else {
                $UserData = User::whereIn('access_type', [5])->whereNotIn('id', $signup)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if($id == 7){
            $corporateStaff = AdminCorporateStaffCcSetting::pluck('UserID');
            if($skey == 'null'){
                $UserData = $query->whereNotIn('id',$corporateStaff)->get(['id', 'email']);
            }else{
                 $UserData = $query->whereNotIn('id',$corporateStaff)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        } else if($id == 8){
            $schoolParentalcoverage = SchoolParentalCoverageCcSetting::where('SchoolID', $sid)->pluck('UserID');
            if($skey == 'null'){
                $UserData = $query->whereNotIn('id',$schoolParentalcoverage)->get(['id', 'email']);
            }else{
                 $UserData = $query->whereNotIn('id',$schoolParentalcoverage)->where('email', 'like', '%' . $skey . '%')->get(['id', 'email']);
            }
        }
        
        return Response::json(['msg' => $UserData]) ?? null;
    }

function SaveEmails(Request $request)
    {
        $flag = $request->input('Flag');
        $emails = $request->input('Emails');
        if ($flag == 1) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingTicketCcSetting = TicketCcSetting::where('UserID', $user->id)->first();
                    if (!$existingTicketCcSetting) {
                        $ticketccsetting = new TicketCcSetting();
                        $ticketccsetting->School_ID = $user->school_id;
                        $ticketccsetting->UserID = $user->id;
                        $ticketccsetting->save();
                    }
                }
            }
        } else if ($flag == 2) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingInventoryCcSetting = InventoryCcSetting::where('UserID', $user->id)->first();
                    if (!$existingInventoryCcSetting) {
                        $inventoryccsetting = new InventoryCcSetting();
                        $inventoryccsetting->School_ID = $user->school_id;
                        $inventoryccsetting->UserID = $user->id;
                        $inventoryccsetting->save();
                    }
                }
            }
        } else if ($flag == 3) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingincomingCcSetting = IncomingOutgoingBatchNotification::where('UserID', $user->id)->where('BatchType', 1)->first();
                    if (!$existingincomingCcSetting) {
                        $incomingccsetting = new IncomingOutgoingBatchNotification();
                        $incomingccsetting->BatchType = 1;
                        $incomingccsetting->UserID = $user->id;
                        $incomingccsetting->save();
                    }
                }
            }
        } else if ($flag == 4) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existoutgoingCcSetting = IncomingOutgoingBatchNotification::where('UserID', $user->id)->where('BatchType', 2)->first();
                    if (!$existoutgoingCcSetting) {
                        $outgoingccsetting = new IncomingOutgoingBatchNotification();
                        $outgoingccsetting->School_ID = $user->school_id;
                        $outgoingccsetting->BatchType = 2;
                        $outgoingccsetting->UserID = $user->id;
                        $outgoingccsetting->save();
                    }
                }
            }
        } else if ($flag == 5) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existinginvoiceCcSetting = InvoiceCcSetting::where('UserID', $user->id)->first();
                    if (!$existinginvoiceCcSetting) {
                        $invoiceccsetting = new InvoiceCcSetting();
                        $invoiceccsetting->School_ID = $user->school_id;
                        $invoiceccsetting->UserID = $user->id;
                        $invoiceccsetting->save();
                    }
                }
            }
        } else if ($flag == 6) {
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingsignupCcSetting = SignUpCcSetting::where('UserID', $user->id)->first();
                    if (!$existingsignupCcSetting) {
                        $signupccsetting = new SignUpCcSetting();
                        $signupccsetting->UserID = $user->id;
                        $signupccsetting->save();
                    }
                }
            }
        } else if($flag == 7){
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingadminStaffCcSetting = AdminCorporateStaffCcSetting::where('UserID', $user->id)->first();
                    if (!$existingadminStaffCcSetting) {
                        $corporatestaffccsetting = new AdminCorporateStaffCcSetting();
                        $corporatestaffccsetting->UserID = $user->id;
                        $corporatestaffccsetting->save();
                    }
                }
            }
        } else if($flag == 8){
            foreach ($emails as $email) {
                $user = User::where('id', $email['id'])->first();
                if ($user) {
                    $existingSchoolParentalCCcSetting = SchoolParentalCoverageCcSetting::where('UserID', $user->id)->first();
                    if (!$existingSchoolParentalCCcSetting) {
                        $corporatestaffccsetting = new SchoolParentalCoverageCcSetting();
                        $corporatestaffccsetting->UserID = $user->id;
                        $corporatestaffccsetting->SchoolID = $user->school_id;
                        $corporatestaffccsetting->save();
                    }
                }
            }
        }
        return "success";
    }

    function deleteEmail($id, $flag)
    {
        if ($flag == 1) {
            $ticketccsetting = TicketCcSetting::where('UserID', $id)->forcedelete();
        } else if ($flag == 2) {
            $ticketccsetting = InventoryCcSetting::where('UserID', $id)->forcedelete();
        } else if ($flag == 3) {
            $ticketccsetting = IncomingOutgoingBatchNotification::where('UserID', $id)->where('BatchType', 1)->forcedelete();
        } else if ($flag == 4) {
            $ticketccsetting = IncomingOutgoingBatchNotification::where('UserID', $id)->where('BatchType', 2)->forcedelete();
        } else if ($flag == 5) {
            $ticketccsetting = InvoiceCcSetting::where('UserID', $id)->forcedelete();
        } else if($flag == 6){
            $ticketccsetting = SignUpCcSetting::where('UserID', $id)->forcedelete();
        }else if($flag == 7){
            $ticketccsetting = AdminCorporateStaffCcSetting::where('UserID', $id)->forcedelete();
        }else if($flag == 8){
            $ticketccsetting = SchoolParentalCoverageCcSetting::where('UserID', $id)->forcedelete();
        }
        return "success";
    }

}
