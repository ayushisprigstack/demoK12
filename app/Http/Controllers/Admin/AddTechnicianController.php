<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Personal;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketsAttachment;
use App\Models\TicketStatusLog;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\TicketIssue;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Models\PaymentLog;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Response;
use App\Models\ErrorLog;
use App\Helper;
use App\Models\Logo;
use App\Models\PartSKUs;
use App\Exceptions\InvalidOrderException;
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use App\Models\InvoiceLog;
use App\Models\K12User;
use App\Mail\AddK12UserMailer;
use App\Models\Access;
use Illuminate\Support\Facades\Mail;
use App\Models\Location;
use App\Models\TechnicianLocation;
use App\Models\MenuAccess;
use App\Models\Menu;
use App\Models\LoginAsSchoolAdminLog;
use Illuminate\Support\Facades\Log;
class AddTechnicianController extends Controller {

    function addUpdateK12User(Request $request) {
        try {
            if ($request->input('id') !== 0) {
                $k12userID = $request->input('id');
                $userFirstName = $request->input('firstname');
                $userLastName = $request->input('lastname');
                $userAccessType = $request->input('access');
                $useremail = $request->input('email');
                $location = $request->input('location');
                $checkemail = K12User::where('email', $useremail)->first();

                if (isset($checkemail) && $checkemail->ID !== $k12userID) {
                    return Response::json(array(
                                'status' => "error",
                                'msg' => 'Email Already Exists'
                    ));
                }

                $currentEmail = K12User::where('ID', $k12userID)->first();
                $currentUser = User::where('email', $currentEmail->email)->where('school_id', 0)->first();
                User::where('email', $currentEmail->email)
                        ->where('school_id', 0)
                        ->update([
                            'first_name' => $userFirstName,
                            'last_name' => $userLastName,
                            'access_type' => $userAccessType,
                            'email' => $useremail
                ]);

                K12User::where('ID', $k12userID)
                        ->update([
                            'first_name' => $userFirstName,
                            'last_name' => $userLastName,
                            'access_type' => $userAccessType,
                            'email' => $useremail
                ]);

                foreach ($location as $locationdata) {
                    if ($locationdata['flag'] == 1) {
                        TechnicianLocation::updateOrCreate([
                            'Technician' => $currentUser->id,
                            'Location' => $locationdata['id']
                        ]);
                    } else {
                        TechnicianLocation::where('Technician', $currentUser->id)
                                ->where('Location', $locationdata['id'])
                                ->forceDelete();
                    }
                }

                return Response::json(array(
                            'status' => "success",
                ));
            } else {
                $K12user = new K12User;
                $K12user->first_name = $request->input('firstname');
                $K12user->last_name = $request->input('lastname');
                $K12user->access_type = $request->input('access');
                $K12user->email = $request->input('email');
                $K12user->status = 'Approve';

                if (K12User::where('email', $request->input('email'))->exists()) {
                    return Response::json(array(
                                'status' => "error",
                                'msg' => 'Email Already Exists'
                    ));
                }

                $K12user->save();

                $user = new User;
                $user->first_name = $request->input('firstname');
                $user->last_name = $request->input('lastname');
                $user->access_type = $request->input('access');
                $user->email = $request->input('email');
                $user->status = 'Approve';
                $user->school_id = 0;
                $user->save();

                foreach ($request->input('location') as $locationData) {
                    TechnicianLocation::updateOrCreate([
                        'Technician' => $user->id,
                        'Location' => $locationData['id']
                    ]);
                }

                $accessType = Access::where('ID', $K12user->access_type)->select('access_type')->first();

                $data = [
                    'name' => $K12user->first_name . ' ' . $K12user->last_name,
                    'email' => $K12user->email,
                    'accessType' => $accessType->access_type
                ];
                    try {
                        Mail::to($K12user->email)->send(new AddK12UserMailer($data));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
               

                return Response::json(array(
                            'status' => "success",
                ));
            }
        } catch (\Throwable $th) {
            return Response::json(array(
                        'status' => "Error",
            ));
        }
    }

    function allK12User($skey, $sortbykey, $sortbyflag) {
        if ($skey == 'null') {
            $k12users = K12User::query();
        } else {
            $k12users = K12User::where(function ($query) use ($skey) {
                        $query->where('first_name', 'LIKE', "%$skey%")
                                ->orWhere('last_name', 'LIKE', "%$skey%")
                                ->orWhere('email', 'LIKE', "%$skey%");
                    });
        }

        if ($sortbykey == 1) {
            $k12users = $k12users->orderByRaw("CONCAT(first_name, ' ', last_name) $sortbyflag");
        } elseif ($sortbykey == 2) {
            $k12users = $k12users->orderBy('access_type', $sortbyflag);
        } elseif ($sortbykey == 3) {
            $k12users = $k12users->orderBy('email', $sortbyflag);
        } else {
            $k12users = $k12users->orderBy('ID', $sortbyflag);
        }

        $k12users = $k12users->get();
        $accesses = Access::whereNotIn('ID', [1, 2, 3, 4, 7])->pluck('access_type', 'ID');
        $Access = Access::whereNotIn('ID', [1, 2, 3, 4, 7])->get();

        foreach ($k12users as $user) {
            $user->accessname = $accesses[$user->access_type] ?? null;
            $user->flag = $user->status == 'Approve' ? ' ' : 'lowOpacityColor';
            $userDetails = User::where('email', $user->email)->where('access_type', $user->access_type)->first();
            $user->userid = $userDetails->id ?? null;
            if ($user->access_type == 6) {
                if ($userDetails) {
                    $locations = TechnicianLocation::where('Technician', $userDetails->id)->get();
                    $locationNames = [];

                    foreach ($locations as $location) {
                        $locationData = Location::where('ID', $location->Location)->first();
                        if ($locationData) {
                            $locationNames[] = $locationData->Location;
                        }
                    }
                    $user->locationName = implode(', ', $locationNames);
                } else {
                    $user->locationName = null;
                }
            } else {
                if ($userDetails) {
                    $location = Location::where('ID', $userDetails->id)->first();
                    $user->locationName = $location ? $location->Location : null;
                } else {
                    $user->locationName = null;
                }
            }
        }
        $k12users->makeHidden(['created_at', 'updated_at', 'deleted_at']);
        return Response::json([
                    'status' => "success",
                    'msg' => $k12users,
                    'access' => $Access
        ]);
    }

    function K12UserData($kuid) {
        $data = K12User::where('ID', $kuid)->first();
        $userDetails = User::where('email', $data->email)->where('access_type', $data->access_type)->first();

        if ($userDetails) {
            if ($userDetails->access_type == 6) {
                $locations = TechnicianLocation::where('Technician', $userDetails->id)->get();
                $locationNames = [];

                foreach ($locations as $location) {
                    $locationData = Location::where('ID', $location->Location)->first();
                    if ($locationData) {
                        $locationNames[] = ['name' => $locationData->Location, 'id' => $locationData->ID];
                    }
                }

                $data->locationName = !empty($locationNames) ? $locationNames : null;
            } else {
                $location = Location::where('ID', $userDetails->id)->first();
                $data->locationName = $location ? [['name' => $location->Location, 'id' => $location->ID]] : null;
            }
        } else {
            $data->locationName = null;
        }

        return response()->json([
                    'status' => "success",
                    'msg' => $data
        ]);
    }

    function deleteK12User($kuid, $flag) {
        $currentEmail = K12User::where('ID', $kuid)->first();
        if ($flag == 1) {
            $updateUser = K12User::where('ID', $kuid)->update(['status' => 'Approve']);
            User::where('email', $currentEmail->email)->where('school_id', 0)->update(['status' => 'Approve']);
        } else {
            $updateUser = K12User::where('ID', $kuid)->update(['status' => 'Reject']);
            User::where('email', $currentEmail->email)->where('school_id', 0)->update(['status' => 'Reject']);
        }

        return Response::json(array(
                    'status' => "success",
        ));
    }

    function setK12LoginasSchoolLogin($actualuid, $uid, $flag) {
        if ($flag == 1) {
            $k12Data = User::with('school')->where('id', $actualuid)->first();
            $userData = User::with('school')->where('id', $uid)->first();

            $log = new LoginAsSchoolAdminLog();
            $log->K12ID = $k12Data->id;
            $log->LoginSchoolID = $userData->school_id;
            $log->save();

            if ($k12Data->access_type == 6) {
                User::where('id', $k12Data->id)->update(['access_type' => 7, 'copy_access_type' => 6, 'school_id' => $userData->school_id]);
            } else {
                User::where('id', $k12Data->id)->update(['access_type' => 1, 'copy_access_type' => 5, 'school_id' => $userData->school_id]);
            }

            return Response::json(array(
                        'status' => "success",
                        'k12data' => $k12Data,
                        'schooldata' => $userData
            ));
        } else {
            $k12Data = User::with('school')->where('id', $actualuid)->first();

            if ($k12Data->access_type == 7) {
                $updatedUser = User::where('id', $k12Data->id)->where('email', $k12Data->email)->update(['access_type' => 6, 'copy_access_type' => NULL, 'school_id' => 0]);
            } else {
                $updatedUser = User::where('id', $k12Data->id)->where('email', $k12Data->email)->update(['access_type' => 5, 'copy_access_type' => NULL, 'school_id' => 0]);
            }

            LoginAsSchoolAdminLog::where('K12ID', $k12Data->id)->forceDelete();

            return Response::json(array(
                        'status' => "success",
                        'k12data' => $k12Data,
            ));
        }
    }

    function allLocation() {
        $get = Location::where('Status', 'Active')->orderByDesc('ID')->get();
        return $get;
    }

}
