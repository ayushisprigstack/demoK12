<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminSetting;
use App\Models\InventoryCcSetting;
use App\Models\TicketCcSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\School;
use App\Models\Domain;
use App\Models\MenuAccess;
use App\Models\Menu;
use App\Mail\RegisterMailer;
use App\Mail\SameDomainSchoolRegisterMailer;
use App\Mail\SignUpMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\Access;
use App\Mail\NewDomainSchoolAddMailer;
use Illuminate\Support\Str;
use App\Models\Avtar;
use App\Models\SignUpCcSetting;
use ReCaptcha\ReCaptcha;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;

class LoginController extends Controller {

    function Register(Request $request) {
        $user = new User;
        $userName = $request->input('name'); // no use
        $userEmail = $request->input('email');
        $userGoogleId = $request->input('googleId');
        $userMicrosoftId = $request->input('microsoftId');
        $userAccessToken = $request->input('accessToken');
        $flag = $request->input('flag');
        $requstedEmailDomain = substr(strrchr($userEmail, "@"), 1);
        $checkDomain = Domain::where('Name', $requstedEmailDomain)->first();
        $checkusersavedemail = User::where('email', $request->input('email'))->first();
        if (isset($checkusersavedemail->copy_access_type)) {
            User::where('email', $request->input('email'))->update(['access_type' => $checkusersavedemail->copy_access_type, 'copy_access_type' => $checkusersavedemail->NULL,'school_id'=> 0]);
        }

        if ($checkDomain->Status == 'active') {
            $usersavedemail = User::where('email', $request->input('email'))->first();

            if (isset($usersavedemail)) {
                if ($usersavedemail->status == 'Approve') {

                    if ($flag == 1) {
                        $updatedLoginDetail = User::where('email', $usersavedemail->email)->update(['remember_token' => $userAccessToken, 'google_id' => $userGoogleId]);
                    } else {
                        $updatedLoginDetail = User::where('email', $usersavedemail->email)->update(['remember_token' => $userAccessToken, 'microsoft_id' => $userMicrosoftId]);
                    }

                    $menuAcess = MenuAccess::where('Access_type', $usersavedemail->access_type)->where('Status', 'Active')->get();
                    $menuID = $menuAcess->pluck('Menu')->all();
                    $flag = $usersavedemail->access_type;
                    $Menu = Menu::whereIN('ID', $menuID)->get();
                    $updatedData = User::where('email', $request->input('email'))->first();
                    $SchoolDetails = School::where('ID', $usersavedemail->school_id)->first();

                    return Response::json(array(
                                'status' => "success",
                                'msg' => $updatedData,
                                'menu' => $Menu,
                                'schoolDetails'=>$SchoolDetails
                    ));
                } else {

                    return Response::json(array(
                                'status' => "error",
                                'response' => ' Your account has been deactivated by the administrator. Please contact our support team for further assistance',
                                'flag' => 2
                    ));
                }
            } else {
                return Response::json(array(
                            'status' => "error",
                            'response' => 'Please sign up to create an account and access our services. Click on the Sign Up button above to get started. If you already have an account, make sure you enter the correct credentials to log in. For any assistance, feel free to contact our support team.',
                            'flag' => 1
                ));
            }
        } else {
            return Response::json(array(
                        'status' => "error",
                        'response' => 'Domain of the email you entered is not currently supported or is not active. Please make sure you have entered the correct email address with a valid domain. Please contact our support team for further assistance.',
                        'flag' => 2
            ));
        }
       
    }
   
    function addUsers(Request $request) {
        $firstname = $request->input('FirstName');
        $lastname = $request->input('lastname');
        $email = $request->input('email');
        $schoolname = $request->input('schoolname');
        $requstedEmailDomain = substr(strrchr($email, "@"), 1);
        $domainalldata = Domain::all();
        $status = 'false';
        $schoolID = '';
        $recaptcha = new ReCaptcha(env('RECAPTCHA_SECRET_KEY'));
        $response = $request->input('Captcha');
        $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            //email domain is match with db domain
            foreach ($domainalldata as $data) {
                $dataEmailDomain = $data->Name;
                if ($requstedEmailDomain == $dataEmailDomain && $data->Status == 'active') {
                    $status = 'true';
                }
            }
        
        $userdata = User::with('school')->where('email', $email)->first();
       
        if (isset($userdata)) {           
            return Response::json(array(
                        'response' => 'Already register',
                        'msg' => 'The email you are trying to Sign in with has already  added under the staffmembers of ' .($userdata->school->name ?? 'k12 admin') .' ',
                        'status' => 'error',
            ));
        } else {
            if ($status == 'true') {

                //now check user table                 
                $domainCheck = User::where('status', 'Approve')->where('email', 'LIKE', '%@' . $requstedEmailDomain)->first();
                if (isset($domainCheck)) {

                    return Response::json(array(
                                'response' => 'Same Domain School Already register',
                                'msg' => 'School with this domain is already register you want to register new one ?',
                                'status' => 'error',
                                'flag' => 1
                    ));
                } else {
                    $schooldata = School::where('Name', $schoolname)->first();
                    if (isset($schooldata)) {
                        return Response::json(array(
                                    'response' => 'school exsist',
                                    'msg' => 'This school is already registered with us.',
                                    'status' => 'error',
                        ));
                    } else {

                        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                        $school = new School;
                        $school->name = $schoolname;
                        $school->status = 'Active';
                        $school->schoolNumber = $randomString;
                        $school->location = 1;
                        $school->save();
                    }
                    $user = new User;
                    $user->first_name = $firstname;
                    $user->last_name = $lastname;
                    $user->email = $email;
                    $user->school_id = $school->id;
                    $user->access_type = 1;
                    $user->status = 'Approve';
                    $allAvtars = Avtar::all();
                    $avatarId = $allAvtars[0];
                    $avatar = Avtar::find($avatarId);
                    $user->avtar = $avatar[0]->id;
                    $user->save();                            

                    //mail send
                    $schoolname = School::where('ID', $user->school_id)->select('name')->first();
                    $data = [
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'school_name' => $schoolname,
                        'domain' => $requstedEmailDomain
                    ];
                    $schoolAdmin = User::where('school_id', $school->id)->where('access_type', 1)->first();

                    try {
                        Mail::to($user->email)->send(new RegisterMailer($data, 'emails.registerMail'));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }

                    $ccRecipients = NotificationEventsLog::where('EventID', 6)->pluck('UserID')->all();
                    if (isset($ccRecipients)) {
                        foreach ($ccRecipients as $recipent) {
                            $staffmember = User::where('id', $recipent)->first();
                            $data = [
                                'name' => $staffmember->first_name . '' . $staffmember->last_name,
                                'school_name' => $schoolname->name,
                                'domain' => $requstedEmailDomain
                            ];
                            try {
                                Mail::to($staffmember->email)->send(new SignUpMailer($data, 'emails.signUpMail'));
                            } catch (\Exception $e) {
                                Log::error("Mail sending failed: " . $e->getMessage());
                            }
                        }
                    }

                    return Response::json(array(
                                'response' => 'Approve',
                                'msg' => 'You are successfully signed up and your account is approved, you can go ahead and sign-in with the same email address',
                                'status' => 'success',
                    ));
                }
            } else {
                $user = new User;
                $user->first_name = $firstname;
                $user->last_name = $lastname;
                $user->email = $email;
                $user->status = 'Reject';
                $user->save();

                $checkDomain = Domain::where('Name', $requstedEmailDomain)->first();
                if (isset($checkDomain)) {
                    Domain::where('Name', $requstedEmailDomain)->update(['Status' => 'deactive']);
                } else {
                    $domain = new Domain;
                    $domain->Name = $requstedEmailDomain;
                    $domain->Status = 'deactive';
                    $domain->save();
                }
                $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $school = new School;
                $school->name = $schoolname;
                $school->status = 'Deactive';
                $school->schoolNumber = $randomString;
                $school->location = 1;
                $school->save();

                $data = [
                    'name' => $firstname . '' . $lastname,
                    'email' => $email,
                    'school_name' => $schoolname,
                    'domain' => $requstedEmailDomain,

                ];

                $mainUser = 'Info@k12techrepairs.com';

                try {
                    Mail::to($mainUser)->send(new RegisterMailer($data, 'emails.newSchoolWithoutDomainAdd'));
                } catch (\Exception $e) {
                    Log::error("Mail sending failed: " . $e->getMessage());
                }



                $ccRecipients = NotificationEventsLog::where('EventID', 6)->pluck('UserID')->all();
                foreach ($ccRecipients as $recipent) {
                    $staffmember = User::where('id', $recipent)->first();
                    $data = [
                        'name' => $staffmember->first_name . '' . $staffmember->last_name,
                        'school_name' => $schoolname,
                        'domain' => $requstedEmailDomain
                    ];
                    try {
                        Mail::to($staffmember->email)->send(new SignUpMailer($data, 'emails.signUpMail'));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
                }
                return Response::json(array(
                            'response' => 'Reject',
                            'msg' => 'You are successfully signed up, system administrator will contact you soon to get your account approved!',
                            'status' => 'error'
                ));
            }
        }
        } else {
            return Response::json(array(
                        'response' => 'Reject',
                        'msg' => 'Invalid Captcha!',
                        'status' => 'error'
            ));
    }
    }
    
    function sameDomainSchoolContinueClick(Request $request) {
        $firstname = $request->input('FirstName');
        $lastname = $request->input('lastname');
        $email = $request->input('email');
        $schoolname = $request->input('schoolname');
        $requstedEmailDomain = substr(strrchr($email, "@"), 1);
        $domainCheckInUser = User::where('status', 'Approve')->where('email', 'LIKE', '%@' . $requstedEmailDomain)->first();

        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $school = new School;
        $school->name = $schoolname;
        $school->status = 'Deactive';
        $school->schoolNumber = $randomString;
        $school->location = 1;
        $school->save();

        $user = new User;
        $user->first_name = $firstname;
        $user->last_name = $lastname;
        $user->email = $email;
        $user->school_id = $school->id;
        $user->access_type = 1;
        $user->status = 'Reject';
        $allAvtars = Avtar::all();
        $avatarId = $allAvtars[0];
        $avatar = Avtar::find($avatarId);
        $user->avtar = $avatar[0]->id;
        $user->save();
        $link = 'https://rocket.k12techrepairs.com/';
        $url = $link . 'confirm-school' . '/' . $school->id;
        $data = [
            'name' => $firstname . '' . $lastname,
            'school_name' => $schoolname,
            'domain' => $requstedEmailDomain,
            'email' => $email,
            'link' => $url,
        ];

        try {
            Mail::to($domainCheckInUser->id)->send(new SameDomainSchoolRegisterMailer($data, 'emails.sameDomainSchoolRegister'));
        } catch (\Exception $e) {
            Log::error("Mail sending failed: " . $e->getMessage());
        }

        return Response::json(array(
                    'link' => $url,
                    'status' => 'success',
                    'msg'=>'administrator contact you for further assistance'
        ));
    }

    function sameDomainSchoolConfirmClick(Request $request) {
        $schoolId = $request->input('schoolId');
        School::where('ID', $schoolId)->update(['status' => 'Active']);
        User::where('school_id', $schoolId)->update(['status' => 'Approve']);
        return 'success';
    }

       function menuAccess($uid)
{
    $userType = User::with('school')->where('id', $uid)->first();
    $masterInventoryFlag = 0;
    if(isset($userType->school->MasterInventory)){
        if($userType->school->MasterInventory == 1){
            $masterInventoryFlag = 1;
       }
    }
    
    $userType->schoolNum = $userType->school->schoolNumber ?? null;
        $menuAccess = MenuAccess::where('Access_type', $userType->access_type)
                ->where('Status', 'Active')
                ->orderBy('MenuOrderID')
                ->get();


    $menuID = $menuAccess->pluck('Menu')->all();
    $flag = ($userType->access_type == 5) ? 1 : 0;
    $menuData = Menu::whereIn('ID', $menuID)->get();

    $menuArray = [];
    $submenuIDs = [];
    foreach ($menuAccess as $access) {
         $submenuIds = preg_split('/,/', $access->SubMenuID, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($submenuIds as $submenuId) {
            $submenuIDs = array_merge($submenuIDs, explode(',', $submenuId));
        }
    }

    foreach ($menuAccess as $access) {
        if (!in_array($access->Menu, $submenuIDs)) {
            $submenuIds = preg_split('/,/', $access->SubMenuID, -1, PREG_SPLIT_NO_EMPTY);
            $submenu = [];

            foreach ($submenuIds as $submenuId) {
                $submenuItems = $menuData->whereIn('ID', explode(',', $submenuId))->all();

                foreach ($submenuItems as $submenuItem) {
                    $submenu[] = [
                        'ID' => $submenuItem->ID,
                        'Name' => $submenuItem->Name,
                        'Href' => $submenuItem->Href,
                        'Image' => $submenuItem->Image,
                        'HrefFlag' => $submenuItem->HrefFlag,
                        'SubMenuId' => $submenuItem->SubMenuId,
                        'Submenu' => []
                    ];
                }
            }

            $menuItem = $menuData->where('ID', $access->Menu)->first();

            if ($menuItem) {
                $menuItem->Submenu = $submenu;

                $menuArray[] = $menuItem;
            }
        }
    }
    usort($menuArray, function ($a, $b) {
        return $a['MenuOrderID'] - $b['MenuOrderID'];
    });
    return response()->json([
        'status' => 'success',
        'msg' => $menuArray,
        'flag' => $flag,
        'user' => $userType,
        'inventoryflag'=>$masterInventoryFlag
    ]);
}


}
