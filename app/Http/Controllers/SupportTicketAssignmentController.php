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
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Storage;
use App\Models\SupportTicketComments;
use App\Mail\SupportTicketNewCommentAddMailer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Mail\SupportTicketCreatedMailer;
use ReCaptcha\ReCaptcha;
use App\Mail\SupportTicketAssignMailer;
use App\Mail\SupportTicketClosedMailer;
use Carbon\Carbon;
use App\Models\Technology;
use App\Models\Maintenance;
use App\Models\Building;
use App\Models\SupportTicketAssignment;

class SupportTicketAssignmentController extends Controller {

    function getAllSupportTicketAssignment($sid, $skey) {
        $get = SupportTicketAssignment::with('building', 'assignTo.avtardata')
                ->where('school_id', $sid)
                ->when($skey != 'null', function ($query) use ($skey) {
                    $query->where(function ($query) use ($skey) {
                        $query->whereHas('building', function ($q) use ($skey) {
                            $q->where('Building', 'LIKE', "%$skey%");
                        })
                        ->orWhereHas('assignTo', function ($q) use ($skey) {
                            $q->where('first_name', 'LIKE', "%$skey%")
                            ->orWhere('last_name', 'LIKE', "%$skey%");
                        });
                    });
                })
                ->orderBy('ID', 'desc')
                ->get();

        $get->each(function ($data) {
            $data->buildingName = $data->building->Building ?? null;
            $data->assignToName = $data->assignTo->first_name . ' ' . $data->assignTo->last_name;
            $data->avtarPath = $data->assignTo->avtardata->avtar ?? null;
        });
        $get->makeHidden(['building', 'assignTo', 'created_at', 'updated_at', 'deleted_at']);

        return Response::json(array(
                    'status' => "success",
                    'msg' => $get));
    }

    function getSupportTicketAssignmentByID($id) {
        $get = SupportTicketAssignment::with('building', 'assignTo')->where('ID', $id)->first();

        $get->buildingName = $get->building->Building ?? null;
        $get->assignToName = $get->assignTo->first_name . ' ' . $get->assignTo->last_name;

        $get->makeHidden(['building', 'assignTo', 'created_at', 'updated_at', 'deleted_at']);
        return Response::json(array(
                    'status' => "success",
                    'msg' => $get));
    }

    function addUpdateSupportTicketAssignment(Request $request) {
        $schoolID = $request->input('LogInSchoolID');
        $buildingID = $request->input('BuildingId');
        $category = $request->input('CategoryId');
        $staffMemberID = $request->input('StaffMemberId');
        $assignmentID = $request->input('ID');
        $flag = $request->input('Flag');

        $existingAssignment = SupportTicketAssignment::where('building_id', $buildingID)
                ->where('category_id', $category)
                ->where('staffmember_id', $staffMemberID)
                ->first();

        if ($existingAssignment) {
            return Response::json(array(
                        'status' => "error",
                        'msg' => 'This combination already exists'));
        }
        if ($flag == 1) {
            $existingAssignmentonlybuilding = SupportTicketAssignment::where('building_id', $buildingID)
                    ->where('category_id', $category)
                    ->first();

            if ($existingAssignmentonlybuilding) {
                return Response::json(array(
                            'status' => "error",
                            'msg' => 'This combination already exists'));
            }

            $assignment = new SupportTicketAssignment;
            $assignment->building_id = $buildingID;
            $assignment->category_id = $category;
            $assignment->staffmember_id = $staffMemberID;
            $assignment->school_id = $schoolID;
            $assignment->save();
            return Response::json(array(
                        'status' => "success",
                        'msg' => 'TicketAssignment Success'));
        } else {
            SupportTicketAssignment::where('ID', $assignmentID)->update(['building_id' => $buildingID, 'category_id' => $category, 'staffmember_id' => $staffMemberID]);
            return Response::json(array(
                        'status' => "success",
                        'msg' => 'TicketAssignment Updated'));
        }
    }

    function getDeallocateBuildings($sid) {
        $getAllocatedBuildings = SupportTicketAssignment::where('school_id', $sid)->pluck('building_id')->all();
        $getBuilding = Building::whereNotIn('ID', $getAllocatedBuildings)->where('SchoolID', $sid)->get();
        return $getBuilding;
    }

}
