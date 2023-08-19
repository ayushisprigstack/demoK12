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
use App\Models\Avtar;
use Illuminate\Support\Facades\Log;
class StaffMemberController extends Controller {

 function allUser($sid, $searchkey, $skey, $sflag)
    {
        if ($searchkey == 'null') {
            $user = User::where('school_id', $sid)->whereIn('access_type', [1, 2, 3, 4, 8])->orderByDesc('id')->get();
        } else {
            $user = User::where('school_id', $sid)
                ->whereIn('access_type', [1, 2, 3, 4, 8])
                ->where(function ($query) use ($searchkey) {
                    $query->where('first_name', 'LIKE', "%$searchkey%")
                        ->orWhere('last_name', 'LIKE', "%$searchkey%")
                        ->orWhere('email', 'LIKE', "%$searchkey%");
                })
                ->orderByDesc('id')
                ->get();
        }
        $array_allUser = array();
        foreach ($user as $userdata) {
            $avtar = Avtar::where('id', $userdata['avtar'])->first();
            if (isset($avtar)) {
                $avtars = $avtar->avtar;
            } else {
                $avtars = null;
            }
            $useraccesstype = $userdata['access_type'] ?? 'null';
            $Access = Access::where('id', $useraccesstype)->first();
            $AccessType = $Access['access_type'] ?? 'null';
            $Schoolid = $userdata['school_id'] ?? 'null';
            $name = $userdata['first_name'] . ' ' . $userdata['last_name'] ?? 'null';
            $email = $userdata['email'] ?? 'null';
            $id = $userdata['id'] ?? 'null';
            $status = $userdata['status'] ?? 'null';

            if ($status == 'Approve') {
                $statusFlag = ' ';
            } else {
                $statusFlag = 'lowOpacityColor';
            }
            array_push($array_allUser, [
                "status" => $status,
                "Acess" => $AccessType,
                "id" => $id,
                "school_id" => $Schoolid,
                "name" => $name,
                "email" => $email,
                "statusFlag" => $statusFlag,
                "avatar" => $avtars
            ]);
        }
        $array_allUser = collect($array_allUser);
        if ($skey == 1) {
            $array_allUser = $sflag == 'desc' ? $array_allUser->sortByDesc('name') : $array_allUser->sortBy('name');
        } elseif ($skey == 2) {
            $array_allUser = $sflag == 'desc' ? $array_allUser->sortByDesc('Acess') : $array_allUser->sortBy('Acess');
        } elseif ($skey == 3) {
            $array_allUser = $sflag == 'desc' ? $array_allUser->sortByDesc('email') : $array_allUser->sortBy('email');
        }else{
            $array_allUser = $sflag == 'desc' ? $array_allUser->sortByDesc('id') : $array_allUser->sortBy('id');
        }
        $final = $array_allUser->values();
        $Access = Access::whereNotIn('ID', [5, 6, 7])->get();


        return Response::json([
            'status' => "success",
            'msg' => $final,
            'access' => $Access
        ]);
    }
    function updateUserData($uid) {
        $data = User::where('ID', $uid)->get();
        return Response::json(array(
                    'status' => "success",
                    'msg' => $data
        ));
    }

    function deleteUser($id, $flag) {
        if ($flag == 1) {
            $updateUser = User::where('ID', $id)->update(['status' => 'Approve']);
        } else {
            $updateUser = User::where('ID', $id)->update(['status' => 'Reject']);
        }

        return Response::json(array(
                    'status' => "success",
        ));
    }

//
    function allAccess() {
        $Access = Access::whereNotIn('ID', [5, 6, 7])->get();
        return Response::json(array(
                    'status' => "success",
                    'Access' => $Access,
        ));
    }

    // new code 
    function addUpdateUser(Request $request) {
        $requstedEmailDomain = substr(strrchr($request->input('email'), "@"), 1);        
        $checkUserDomain = User::where('school_id', $request->input('schoolId'))->where('access_type', 1)->whereNull('copy_access_type')->orderBy('created_at', 'asc')->first();         
        $availableEmailDomain = substr(strrchr($checkUserDomain->email, "@"), 1);
       
        if ($availableEmailDomain == $requstedEmailDomain) {
            if ($request->input('id') == 0) {
                $user = new User;
                $user->first_name = $request->input('firstname');
                $user->last_name = $request->input('lastname');
                $user->access_type = $request->input('access');
                $user->school_id = $request->input('schoolId');
                $user->email = $request->input('email');
                $user->status = 'Approve';

                $checkuseremail = User::where('email', $request->input('email'))->first();

                if (isset($checkuseremail)) {
                    return Response::json(array(
                                'status' => "error",
                                'msg' => 'email already exists'
                    ));
                } else {
                    $usedAvatars = User::where('school_id', $request->input('schoolId'))->whereNotNull('avtar')->distinct()->pluck('avtar')->all();
                    $availableAvatars = Avtar::whereNotIn('ID', $usedAvatars)->pluck('ID')->all();                    
                    $allAvtars = Avtar::all();
                    if (!empty($availableAvatars)) {

                        $avatarId = $availableAvatars[0]; 
                        $avatar = Avtar::find($avatarId);
                        $user->avtar = $avatar->id;
                    } else {
                        $avatarId = $allAvtars[0]; 
                        $avatar = Avtar::find($avatarId);           
                        $user->avtar = $avatar[0]->id;
                    }

                    $user->save();

                    $schoolData = School::where('ID', $user->school_id)->select('name')->first();
                    $accessType = Access::where('ID', $user->access_type)->select('access_type')->first();
                    $data = ['name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email,
                        'school_name' => $schoolData->name,
                        'access_type' => $accessType->access_type
                    ];
                   
try {
                                Mail::to($user->email)->send(new AddUserMailer($data));
                            } catch (\Exception $e) {
                                Log::error("Mail sending failed: " . $e->getMessage());
                            }
                    return Response::json(array(
                                'status' => "success",
                    ));
                }
            } else {

                $checkemail = User::where('email', $request->input('email'))->first();

                if (isset($checkemail)) {
                    if ($checkemail->id == $request->input('id')) {
                        $currentEmail = User::where('ID', $request->input('id'))->first();
                        User::where('email', $currentEmail->email)->update(['first_name' => $request->input('firstname'), 'last_name' => $request->input('lastname'), 'access_type' => $request->input('access'), 'email' => $request->input('email')]);

                        return Response::json(array(
                                    'status' => "success",
                        ));
                    } else {
                        return Response::json(array(
                                    'status' => "error",
                                    'msg' => 'email already exists'
                        ));
                    }
                } else {
                    $currentEmail = User::where('ID', $request->input('id'))->first();
                    User::where('email', $currentEmail->email)->update(['first_name' => $request->input('firstname'), 'last_name' => $request->input('lastname'), 'access_type' => $request->input('access'), 'email' => $request->input('email')]);

                    return Response::json(array(
                                'status' => "success",
                    ));
                }
            }
        } else {
            return Response::json(array(
                        'status' => "error",
                        'msg' => 'Invalid Domain'
            ));
        }
    }
    
    function takeAccesstoken($userid){
        $get = User::where('id',$userid)->first();
        return $get;
    }

}
