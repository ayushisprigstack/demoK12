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
use Illuminate\Support\Facades\Log;


class SupportTicketController extends Controller
{

    function getAllSupportTickets($skey, $sid, $uid, $flag, $sortkey, $sflag, $page, $limit)
    {
        $getUser = User::where('id', $uid)->first();
        $get = SupportTicket::with('user')
            ->where('Type', $flag)
            ->where('SchoolId', $sid)
            ->when($getUser->access_type != 1, function ($query) use ($uid) {
                $query->where('AssignedTo', $uid);
            });

        $openTicketsQuery = SupportTicket::with('user')
            ->where('Type', $flag)
            ->where('SchoolId', $sid)
            ->when($getUser->access_type != 1, function ($query) use ($uid) {
                $query->where('AssignedTo', $uid);
            })
            ->where('Status', 'Open');

        $closeTicketsQuery = SupportTicket::with('user')
            ->where('Type', $flag)
            ->where('SchoolId', $sid)
            ->when($getUser->access_type != 1, function ($query) use ($uid) {
                $query->where('AssignedTo', $uid);
            })
            ->where('Status', 'Closed');
        if ($skey != 'null') {
            $openTicketsQuery = $openTicketsQuery->where(function ($query) use ($skey) {
                $query->where('ID', 'LIKE', "%$skey%")
                    ->orWhere('Name', 'LIKE', "%$skey%")
                    ->orWhere('Role', 'LIKE', "%$skey%")
                    ->orWhere('Email', 'LIKE', "%$skey%")
                    ->orWhere('Title', 'LIKE', "%$skey%");
            });

            $closeTicketsQuery = $closeTicketsQuery->where(function ($query) use ($skey) {
                $query->where('ID', 'LIKE', "%$skey%")
                    ->orWhere('Name', 'LIKE', "%$skey%")
                    ->orWhere('Role', 'LIKE', "%$skey%")
                    ->orWhere('Email', 'LIKE', "%$skey%")
                    ->orWhere('Title', 'LIKE', "%$skey%");
            });
        }

        if ($sortkey == 1) {
            $openTicketsQuery = $sflag == 'as' ? $openTicketsQuery->orderBy('Name') : $openTicketsQuery->orderByDesc('Name');
            $closeTicketsQuery = $sflag == 'as' ? $closeTicketsQuery->orderBy('Name') : $closeTicketsQuery->orderByDesc('Name');
        } elseif ($sortkey == 2) {
            $openTicketsQuery = $sflag == 'as' ? $openTicketsQuery->orderBy('Email') : $openTicketsQuery->orderByDesc('Email');
            $closeTicketsQuery = $sflag == 'as' ? $closeTicketsQuery->orderBy('Email') : $closeTicketsQuery->orderByDesc('Email');
        } elseif ($sortkey == 3) {
            $openTicketsQuery = $sflag == 'as' ? $openTicketsQuery->orderBy('Title') : $openTicketsQuery->orderByDesc('Title');
            $closeTicketsQuery = $sflag == 'as' ? $closeTicketsQuery->orderBy('Title') : $closeTicketsQuery->orderByDesc('Title');
        } elseif ($sortkey == 4) {
            $openTicketsQuery = $sflag == 'as' ? $openTicketsQuery->orderBy('AssignedTo') : $openTicketsQuery->orderByDesc('AssignedTo');
            $closeTicketsQuery = $sflag == 'as' ? $closeTicketsQuery->orderBy('AssignedTo') : $closeTicketsQuery->orderByDesc('AssignedTo');
        } else {
            $openTicketsQuery->orderByDesc('ID');
            $closeTicketsQuery->orderByDesc('ID');
        }

        // Paginate Open Tickets
        $openTickets = $openTicketsQuery->paginate($limit, ['*'], 'open_tickets_page', $page);

        // Paginate Close Tickets
        $closeTickets = $closeTicketsQuery->paginate($limit, ['*'], 'close_tickets_page', $page);

        return response()->json([
            'status' => 'success',
            'OpenTickets' => $openTickets,
            'CloseTickets' => $closeTickets,
        ]);
    }

    function getSupportTicketDataByid($id)
    {
        $get = SupportTicket::where('ID', $id)->first();
        return Response::json(
            array(
                'status' => "success",
                'msg' => $get
            )
        );
    }

    function changeSupportTicketStatus(Request $request)
    {
        $flag = $request->input('Flag');
        if ($flag == 1) {
            SupportTicket::where('ID', $request->input('TicketId'))->update(['Status' => 'Closed']);
            $supportTicketData = SupportTicket::where('ID', $request->input('TicketId'))->first();
            $school = School::where('ID', $supportTicketData->SchoolId)->first();

            $frontendUrl = 'https://rocket.k12techrepairs.com/';
            $link = $frontendUrl . '/ticket/' . $school->schoolNumber . '/' . $supportTicketData->SupportTicketNum;
            $data = [
                'ticketNum' => $supportTicketData->SupportTicketNum,
                'ticketTitle' => $supportTicketData->Title,
                'link' => $link,
                'schoolName' => $school->name,
                'name' => $supportTicketData->Name,
                'ticketDescription' => $supportTicketData->Discription
            ];

            try {
                Mail::to($supportTicketData->Email)->send(new SupportTicketClosedMailer($data));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        } else {
            SupportTicket::where('ID', $request->input('TicketId'))->update(['Status' => 'Open']);
        }
        return "success";
    }
    function addCommentsonSupportTicket(Request $request)
    {
        $SchoolId = $request->input('SchoolID');
        $TicketNum = $request->input('TicketNum');
        $UserId = $request->input('UserEmail');
        $Comment = $request->input('Comment');
        $AssignStaffmember = $request->input('AssignStaffMember');
        $ByWhom = $request->input('Flag');
        $Document = $request->input('Document');

        try {
            $ticket = SupportTicket::where('SupportTicketNum', $TicketNum)->first();
            $user = User::where('id', $AssignStaffmember)->first() ?? null;
            $supportComment = new SupportTicketComments;
            $supportComment->SupportTicketID = $ticket->ID;
            $supportComment->SchoolID = $SchoolId;
            $supportComment->UserEmail = $UserId;
            $supportComment->Comments = $Comment;
            if ($ByWhom == 1) {
                $supportComment->Firstname = $user->first_name;
            } else {
                $supportComment->Firstname = $ticket->Name;
            }
            $supportComment->save();

            if ($Document !== null) {
                $imageData = base64_decode($Document);
                $filename = time() . '_' . rand(1000, 9999) . '.jpg'; // Example: 1629134523_1234.jpg
                $filePath = 'SupportTickets/' . $supportComment->id . '/' . $filename; // Store with the random filename
                Storage::disk('public')->put($filePath, $imageData);
                SupportTicketComments::where('ID', $supportComment->id)->update(['Img' => $filePath]);

            }

            //send mail
            //school admin 

            $School = School::where('ID', $SchoolId)->first();
            $userdata = User::where('school_id', $SchoolId)->where('access_type', 1)->first();
            $frontendUrl = 'https://rocket.k12techrepairs.com/';
            $link = $frontendUrl . '/ticket/' . $School->schoolNumber . '/' . $ticket->SupportTicketNum . '/' . $ticket->TicketGuID;
            $linkWithoutGUID = $frontendUrl . '/ticket/' . $School->schoolNumber . '/' . $ticket->SupportTicketNum;
            if ($ByWhom == 2) //3rd person
            {
                $data = [
                    'createdBy' => $ticket->Name,
                    'ticketNum' => $ticket->Title,
                    'comment' => $Comment,
                    'link' => $link,
                    'linkWithoutGUID' => $linkWithoutGUID,
                    'schoolName' => $School->name,
                    'name' => $userdata->first_name . ' ' . $userdata->last_name,
                ];
                //                Mail::to($ticket->Email)->send(new SupportTicketNewCommentAddMailer($data, 'emails.SupportTicketNewCommentAdd'));

                try {
                    Mail::to($userdata->email)->send(new SupportTicketNewCommentAddMailer($data, 'emails.SchoolTicketNewCommentAddForSchool'));
                } catch (\Exception $e) {
                    Log::error("Mail sending failed: " . $e->getMessage());
                }

            } else {
                $data = [
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'ticketNum' => $ticket->Title,
                    'comment' => $Comment,
                    'link' => $link,
                    'linkWithoutGUID' => $linkWithoutGUID,
                    'schoolName' => $School->name,
                    'createdBy' => $ticket->Name,
                ];
                //                Mail::to($user->email)->send(new SupportTicketNewCommentAddMailer($data, 'emails.SchoolTicketNewCommentAddForSchool'));

                try {
                    Mail::to($ticket->Email)->send(new SupportTicketNewCommentAddMailer($data, 'emails.SupportTicketNewCommentAdd'));
                } catch (\Exception $e) {
                    Log::error("Mail sending failed: " . $e->getMessage());
                }
            }
            return "success";
        } catch (Exception $ex) {
            return "error";
        }
    }

    function getAllCommentsById($tid)
    {
        $ticket = SupportTicket::with('building', 'user.avtardata')->where('SupportTicketNum', $tid)->first();
        $ticket->avtarname = $ticket->user->avtardata->avtar ?? null;
        if (isset($ticket)) {
            $get = SupportTicketComments::with('supportTicket.user.avtardata')
                ->where('SupportTicketID', $ticket->ID)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($ticket->Type == 1) {
                $issueType = $ticket->technology->Name;
            } else {
                $issueType = $ticket->maintenance->Name;
            }

            $ticket->IssueType = $issueType;
            $buildingIds = explode(',', $ticket->Building);
            $buildingNames = Building::whereIn('id', $buildingIds)->pluck('Building')->all();
            $ticket->BuildingName = implode(', ', $buildingNames);
            $ticket->makeHidden(['building', 'technology', 'maintenance', 'user', 'updated_at', 'created_at', 'deleted_at']);
        }

        $commentArray = [];
        $commentDates = [];
        foreach ($get as $data) {
            $user = $data->Firstname;
            $comment = $data->Comments;
            $time = $data->created_at->format('h:i A');
            $date = $data->created_at->format('M jS Y');
            $title = $data->supportTicket->Title;
            $name = $data->supportTicket->Name;
            $email = $data->supportTicket->Email;
            $role = $data->supportTicket->Title;
            $type = $data->supportTicket->Type;
            $staffmemberAvtar = $data->supportTicket->user->avtardata->avtar ?? null;

            $img = $data->Img;
            $commentArray[] = [
                'User' => $user,
                'Comment' => $comment,
                'Time' => $time,
                'Title' => $title,
                'Name' => $name,
                'Email' => $email,
                'Date' => $date,
                'Img' => $img,
                'Type' => $type,
                'IssueType' => $issueType,
                'staffmemberAvtar' => $staffmemberAvtar
            ];
        }
        $isTicketClosed = ($ticket->Status == 'Closed' ? 1 : 0);
        $isTicketOlderThan30Days = $ticket->created_at->format('m-d-y');
        $todaydate = now()->format('m-d-y');
        $startDate = Carbon::createFromFormat('m-d-Y', $isTicketOlderThan30Days)->startOfDay();
        $endDate = Carbon::createFromFormat('m-d-Y', $todaydate)->endOfDay();
        $differnence = $startDate->diffInDays($endDate);
        if ($differnence >= 30 || $isTicketClosed == 1) {
            $allowComments = 0;
        } else {
            $allowComments = 1;
        }
        return Response::json(
            array(
                'status' => "success",
                'SupportTicketDetails' => $ticket,
                'msg' => $commentArray,
                'allowComments' => $allowComments,
            )
        );
    }

    function addSupportTicketFromLink(Request $request)
    {

        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $randomID = Str::random(8, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $supportTicket = new SupportTicket;
        $supportTicket->Title = $request->input('Title');
        $supportTicket->Discription = $request->input('Description');
        $supportTicket->Name = $request->input('Name');
        $supportTicket->Email = $request->input('Email');
        $buildingValues = explode(',', $request->input('Building'));
        $buildingIds = [];
        $supportTicket->RoomNo = $request->input('RoomNo');
        $supportTicket->Status = 'Open';
        $supportTicket->SchoolId = $request->input('SchoolID');
        $supportTicket->Role = $request->input('Flag') ?? null;
        $supportTicket->SupportTicketNum = $randomString;
        $supportTicket->StudentNum = $request->input('StudentNumber');
        $supportTicket->Type = $request->input('TypeFlag'); //1 techno
        $supportTicket->TypeChildId = $request->input('TypeChildId'); //1 techno
        $supportTicket->TicketGuID = $randomID;

        $recaptcha = new ReCaptcha(env('RECAPTCHA_SECRET_KEY'));
        $response = $request->input('Captcha');
        $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
        // if ($resp->isSuccess()) {
        foreach ($buildingValues as $buildingValue) {
            $buildingValue = is_numeric($buildingValue) ? (int) $buildingValue : $buildingValue;

            if ($buildingValue === 0) {
                $addBuilding = new Building;
                $addBuilding->Building = $request->input('BuildingName');
                $addBuilding->SchoolID = $request->input('SchoolID');
                $addBuilding->save();
                $buildingIds[] = $addBuilding->id;
            } elseif ($buildingValue === 'null' || $buildingValue === NULL) {
                // $supportTicket->Building = NULL;
            } else {
                $buildingIds[] = $buildingValue;
            }
        }
        $supportTicket->Building = implode(',', $buildingIds);
        $supportTicket->save();
        //img save

        $imageFile = $request->file('Document');
        if ($imageFile !== null && $imageFile->isValid()) {
            $filePath = 'SupportTickets/' . $supportTicket->id . '/img.jpg';
            Storage::disk('public')->put($filePath, file_get_contents($imageFile->getRealPath()));
            SupportTicket::where('ID', $supportTicket->id)->update(['Img' => $filePath]);
        }

        //video save
        try {
            $videos = $request->file('videos');
            if (isset($videos) && $videos->isValid()) {
                $videoName = uniqid() . '.' . $videos->getClientOriginalExtension();

                // Save the video in the same folder as the image
                $videoPath = 'SupportTickets/' . $supportTicket->id . '/' . $videoName;

                Storage::disk('public')->putFileAs('SupportTickets/' . $supportTicket->id, $videos, $videoName);
                SupportTicket::where('ID', $supportTicket->id)->update(['videoPath' => $videoPath]);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }

        //  AssignedTo
        $school = School::where('ID', $request->input('SchoolID'))->first();
        $frontendUrl = 'https://rocket.k12techrepairs.com';
        $link = $frontendUrl . '/ticket/' . $school->schoolNumber . '/' . $supportTicket->SupportTicketNum;
        $checkTicketAssignment = SupportTicketAssignment::where('building_id', $supportTicket->Building)->where('category_id', $request->input('TypeFlag'))->where('school_id', $request->input('SchoolID'))->whereNotNull('staffmember_id')->first();
        if (isset($checkTicketAssignment)) {
            $user = User::where('id', $checkTicketAssignment->staffmember_id)->first();
            $data = [
                'SchoolName' => $school->name,
                'Name' => $user->first_name . ' ' . $user->last_name,
                'TicketNum' => $supportTicket->SupportTicketNum,
                'Title' => $supportTicket->Title,
                'CreatedBy' => $supportTicket->Name,
                'Discription' => $supportTicket->Discription,
                'Link' => $link
            ];
            SupportTicket::where('ID', $supportTicket->id)->update(['AssignedTo' => $checkTicketAssignment->staffmember_id]);

            try {
                Mail::to($user->email)->send(new SupportTicketAssignMailer($data));
                Mail::to($request->input('Email'))->send(new SupportTicketCreatedMailer($data, 'emails.SupportTicketCreated'));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        } else {
            $user = User::where('school_id', $request->input('SchoolID'))->where('access_type', 1)->first();
            $data = [
                'SchoolName' => $school->name,
                'Name' => $user->first_name . ' ' . $user->last_name,
                'CreatedBy' => $request->input('Name'),
                'Title' => $request->input('Title'),
                'Link' => $link
            ];

            try {
                Mail::to($user->email)->send(new SupportTicketCreatedMailer($data, 'emails.SupportTicketCreatedForSchool'));
                Mail::to($request->input('Email'))->send(new SupportTicketCreatedMailer($data, 'emails.SupportTicketCreated'));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        }

        return Response::json(
            array(
                'status' => "success",
                'SupportTicketDetails' => $supportTicket,
            )
        );
        // } else {
        //     return Response::json(
        //         array(
        //             'status' => "error",
        //             'SupportTicketDetails' => $supportTicket,
        //         )
        //     );
        // }
    }

    function assignSupportTicketTOStaffmember(Request $request)
    {
        $ticketID = $request->input('SupportTicketID');
        $staffmemberID = $request->input('StaffID');
        $schoolID = $request->input('SchoolID');
        $user = User::where('id', $staffmemberID)->first();
        $school = School::where('ID', $schoolID)->first();
        $supportticket = SupportTicket::where('ID', $ticketID)->first();
        $frontendUrl = 'https://rocket.k12techrepairs.com/';
        $link = $frontendUrl . '/ticket/' . $school->schoolNumber . '/' . $supportticket->SupportTicketNum;
        $data = [
            'SchoolName' => $school->name,
            'Name' => $user->first_name . ' ' . $user->last_name,
            'TicketNum' => $supportticket->SupportTicketNum,
            'Title' => $supportticket->Title,
            'CreatedBy' => $supportticket->Name,
            'Discription' => $supportticket->Discription,
            'Link' => $link
        ];
        SupportTicket::where('SchoolId', $schoolID)->where('ID', $ticketID)->update(['AssignedTo' => $staffmemberID]);


        try {
            Mail::to($user->email)->send(new SupportTicketAssignMailer($data));
        } catch (\Exception $e) {
            Log::error("Mail sending failed: " . $e->getMessage());
        }
        return 'success';
    }

    function getTechnologyAndMaintenanceData($flag)
    {
        $technologies = Technology::all();
        $maintenance = Maintenance::all();

        if ($flag == 1) {
            return response()->json([
                'status' => 'success',
                'Technologies' => $technologies,
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'Maintenace' => $maintenance,
            ]);
        }
    }

}