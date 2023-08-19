<?php
namespace App\Http\Controllers;
use App\Models\OperatingSystem;
use App\Models\DeviceIssue;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketIssue;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Models\Student;
use App\Models\PaymentLog;
use App\Models\StudentInventory;
use App\Models\InventoryManagement;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\LonerDeviceLog;
use App\Exceptions\InvalidOrderException;
use App\Models\TicketRepairLog;
use App\Models\TicketsAttachment;
use App\Models\PartSKUs;
use App\Models\TicketImage;
use Illuminate\Support\Facades\Storage;
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use App\Models\School;
use App\Models\InventoryCcSetting;
use App\Models\AdminSetting;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReorderPartsMailer;
use App\Models\DamageType;
use App\Models\SchoolBatchLog;
use App\Models\SchoolBatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
class ManageTicketController extends Controller {

    function allTickets($sid, $uid) {

        $data = Ticket::where('school_id', $sid)->get();
        $array_openTicket = array();
        $array_closeTicket = array();
        foreach ($data as $ticketdata) {
            $ticketInventoryID = $ticketdata['inventory_id'];
            $statusID = $ticketdata['ticket_status'];
            $StatusallData = TicketStatus::where('ID', $statusID)->first();
            $status = $StatusallData->status;
            $Inventory = InventoryManagement::where('id', $ticketInventoryID)->first();
            $serialNum = $Inventory['Serial_number'];
            $userdId = $Inventory['user_id'];
            $user = User::where('id', $userdId)->first();
            $userName = $user->first_name . ' ' . $user->last_name;
            $ticketID = $ticketdata['ID'];
            $ticketCreateDate = $ticketdata['created_at']->format('Y-m-d');
            $ticketCreatedByUserName = $ticketdata['user_id'];
            $userdata = User::where('id', $ticketCreatedByUserName)->first();
            $ticketCreatedBy = $userdata->first_name . ' ' . $userdata->last_name;
            $Issuealldata = TicketIssue::where('ticket_Id', $ticketdata['ID'])->get();
            foreach ($Issuealldata as $Issuedata) {
                $issueId = $Issuedata->issue_Id;
                $issue_inventory_id = $Issuedata->inventory_id;
                $inventory_student = InventoryManagement::where('id', $issue_inventory_id)->first();

                $student_inventory = StudentInventory::where('Inventory_ID', $inventory_student->ID)->first();
                if (isset($student_inventory)) {
                    $student_data = Student::where('ID', $student_inventory->Student_ID)->first();
                    $firstName = $student_data->Device_user_first_name ?? 'null';
                    $lastName = $student_data->Device_user_last_name ?? 'null';
                } else {
                    $firstName = 'null';
                    $lastName = 'null';
                }
                $Device_model = $inventory_student->Device_model;
            }
            if ($statusID == 2 || $statusID == 7 || $statusID == 8) {
                array_push($array_closeTicket, ["InventoryID" => $ticketInventoryID, "Device_model" => $Device_model, "studentname" => $firstName . ' ' . $lastName, "TicketCreatedBy" => $ticketCreatedBy, "username" => $userName, "serialNum" => $serialNum, "ticketid" => "$ticketID", "ticketnum" => $ticketdata['ticket_num'], "ticket_status" => $status, "Date" => $ticketCreateDate]);
            } else {
                array_push($array_openTicket, ["InventoryID" => $ticketInventoryID, "Device_model" => $Device_model, "studentname" => $firstName . ' ' . $lastName, "TicketCreatedBy" => $ticketCreatedBy, "username" => $userName, "serialNum" => $serialNum, "ticketid" => "$ticketID", "ticketnum" => $ticketdata['ticket_num'], "ticket_status" => $status, "Date" => $ticketCreateDate]);
            }
        }
        $openTicket = collect($array_openTicket)->unique('ticketid')->values();
        $closeTicket = collect($array_closeTicket)->unique('ticketid')->values();

        return response()->json(
                        collect([
                    'response' => 'success',
                    'Openticket' => $openTicket,
                    'Closeticket' => $array_closeTicket,
        ]));
    }
    function searchLonerforTicket($sid, $key) {
        $assignLonerDevice = DB::table('student_inventories')->whereNotNull('Loner_ID')->where('School_ID', $sid)->pluck('Loner_ID')->all();
        $allocatedLonerDevice = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                ->where('inventory_management.school_id', $sid)
                ->select('inventory_management.ID', 'students.ID as studentid', 'students.Grade', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage')
                ->where('inventory_management.inventory_status', 3)
                ->whereNotNull('students.ID')
                ->pluck('inventory_management.ID')
                ->all();
        if ($key != 'null') {
            $search = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->select('inventory_management.*', 'students.ID as studentid', 'students.Grade', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage')
                    ->where('inventory_management.inventory_status', 3)
                    ->whereNotIn('inventory_management.ID', $assignLonerDevice)
                    ->whereNotIn('inventory_management.ID', $allocatedLonerDevice)
                    ->where(function ($query) use ($key) {
                        $query->where('inventory_management.Device_model', 'LIKE', "%$key%");
                        $query->orWhere('inventory_management.Serial_number', 'LIKE', "%$key%");
                    })
                    ->get();
        } else {
            $search = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->select('inventory_management.*', 'students.ID as studentid', 'students.Grade', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage')
                    ->where('inventory_management.inventory_status', 3)
                    ->whereNotIn('inventory_management.ID', $assignLonerDevice)
                    ->whereNotIn('inventory_management.ID', $allocatedLonerDevice)
                    ->get();
        }


        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $search
        ]));
    }
    function changeticketStatus(Request $request) {
//        try {
        $ticketStatusID = $request->input('Status');
        $ticketupdateduserId = $request->input('UserId');
        $idArray = $request->input('IssueIDArray');
        $flag = $request->input('Flag');
        $closestatus = $request->input('closestatus');

        foreach ($idArray as $ids) {
            $ticketlog = new TicketStatusLog();
            $ticketlog->Ticket_id = $ids['TicketID'];
            $ticketdata = Ticket::where('ID', $ids['TicketID'])->first();
            $ticketlog->Status_from = $ticketdata->ticket_status ?? '';
            $ticketlog->Status_to = $ticketStatusID;
            $ticketlog->updated_by_user_id = $ticketupdateduserId;
            $ticketlog->save();
            $studentID = $ticketdata->school_id ?? '';
            $inventoryID = $ticketdata->inventory_id;

            $studentinentorydata = StudentInventory::where('Inventory_Id', $inventoryID)->first();

            if ($studentinentorydata == "") {
                $updatedTicketStatus = Ticket::where('ID', $ids['TicketID'])->update(['ticket_status' => $ticketStatusID]);
            } else {
                $lonerID = $studentinentorydata->Loner_ID;
                if ($flag == 1) {
                    if ($closestatus == 1) {
                        //if student_inventories ma inventory id sathe loner b to j update 
                        $checkInventoryAndLoner = StudentInventory::where('Inventory_Id', $inventoryID)->whereNotNull('Loner_ID')->first();
                        if (isset($checkInventoryAndLoner)) {
                            $updateStudentInventory = StudentInventory::where('Inventory_Id', $inventoryID)->update(['Loner_ID' => null, 'Inventory_Id' => $lonerID]);
                            $updateInventory = InventoryManagement::where('id', $lonerID)->update(['Loaner_device' => 0, 'inventory_status' => 1]);
                            $updatedTicketStatus = Ticket::where('ID', $ids['TicketID'])->update(['ticket_status' => $ticketStatusID]);
                        } else {
                            $updatedTicketStatus = Ticket::where('ID', $ids['TicketID'])->update(['ticket_status' => $ticketStatusID]);
                        }
                    } else {
                        $updateStudentInventory = StudentInventory::where('Inventory_Id', $inventoryID)->update(['Loner_ID' => null]);
                        $updatedTicketStatus = Ticket::where('ID', $ids['TicketID'])->update(['ticket_status' => $ticketStatusID]);
                    }
                    $date = now()->format('Y-m-d');
                    LonerDeviceLog::where('Loner_ID', $lonerID)->update(['End_date' => $date]);
                } else {

                    $updatedTicketStatus = Ticket::where('ID', $ids['TicketID'])->update(['ticket_status' => $ticketStatusID]);
                }
            }

            if ($ticketStatusID == 2) {
                Ticket::where('id', $ids['TicketID'])->update(['ticket_status' => 2]);
            }
        }
        return "success";
//        }catch (\Throwable $th) {
//            return "something went wrong.";
//        }
    }

    function getTicketStatusforManageTicket($flag) {
        if ($flag == 1) {
            $status = TicketStatus::whereNotIn('ID', [9, 10])->get();
        } elseif ($flag == 2) {
            $status = TicketStatus::whereIn('ID', [2, 7, 8])->get();
        } elseif ($flag == 3) {
            $status = TicketStatus::whereIn('ID', [9, 10])->get();
        } else {
            $status = TicketStatus::all();
        }

        return $status;
    }

    function searchInventoryCT($sid, $key) {
        $decommissiondevice = InventoryManagement::where('inventory_status', 2)->where('school_id', $sid)->pluck('ID')->all();
        $createdtickets = Ticket::where('school_id', $sid)->pluck('inventory_id')->all();
        $excludedIDs = array_merge($decommissiondevice, $createdtickets);
        if ($key != 'null') {
            $get = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                            ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                            ->leftJoin('devicetypes', 'devicetypes.ID', '=', 'inventory_management.Device_type')
                            ->where('inventory_management.school_id', $sid)
                            ->select('inventory_management.*', 'devicetypes.type as DeviceTypeName', 'devicetypes.point as DevicePoint', 'students.Grade', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num')
                            ->where(function ($query) use ($key) {
                                $query->where('inventory_management.Device_model', 'LIKE', "%$key%");
                                $query->orWhere('students.Device_user_last_name', 'LIKE', "%$key%");
                                $query->orWhere('students.Device_user_first_name', 'LIKE', "%$key%");
                                $query->orWhere('inventory_management.Serial_number', 'LIKE', "%$key%");
                                $query->orWhere('inventory_management.Asset_tag', 'LIKE', "%$key%");
                                $query->orWhere('students.Student_num', 'LIKE', "%$key%");
                            })->whereNotIn('inventory_management.ID', $excludedIDs)->get();
        } else {
            $get = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->leftJoin('devicetypes', 'devicetypes.ID', '=', 'inventory_management.Device_type')
                    ->where('inventory_management.school_id', $sid)
                    ->select('inventory_management.*', 'devicetypes.type as DeviceTypeName', 'devicetypes.point as DevicePoint', 'students.Grade', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num')
                    ->whereNotIn('inventory_management.ID', $excludedIDs)
                    ->get();
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $get
        ]));
    }

    function sortbyforLoner($sid, $key, $flag) {

        if ($key == 1) {
            if ($flag == 'as') {
                $utilizer = "inventory_management.Serial_number asc";
            } else {
                $utilizer = "inventory_management.Serial_number desc";
            }
        } elseif ($key == 2) {
            if ($flag == 'as') {
                $utilizer = "inventory_management.Device_model asc";
            } else {
                $utilizer = "inventory_management.Device_model DESC";
            }
        } elseif ($key == 3) {
            if ($flag == 'as') {
                $utilizer = "students.Device_user_first_name asc";
            } else {
                $utilizer = "students.Device_user_first_name DESC";
            }
        } elseif ($key == 4) {
            if ($flag == 'as') {
                $utilizer = "students.Grade asc";
            } else {
                $utilizer = "students.Grade DESC";
            }
        } elseif ($key == 5) {
            if ($flag == 'as') {
                $utilizer = "students.Building asc";
            } else {
                $utilizer = "students.Building DESC";
            }
        } elseif ($key == 6) {
            if ($flag == 'as') {
                $utilizer = "inventory_management.Purchase_date  asc";
            } else {
                $utilizer = "inventory_management.Purchase_date DESC";
            }
        } else {
            return response()->json(
                            collect([
                        'response' => 'error',
            ]));
        }

        $data = "select inventory_management.*, students.ID as studentid ,students.Device_user_first_name , students.Device_user_last_name ,students.Grade ,students.Building ,students.Parent_guardian_name ,students.Parent_phone_number ,students.Parent_Guardian_Email ,students.Parental_coverage from
                   inventory_management left join student_inventories on(student_inventories.Inventory_ID = inventory_management.ID  OR student_inventories.Loner_ID = inventory_management.ID)
                   left join students on students.ID = student_inventories.Student_ID
                   where inventory_management.school_id =" . $sid . "
                   and inventory_management.inventory_status in (3) order by " . $utilizer . " ;";
        $get = (DB::select(DB::raw($data)));

        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $get,
        ]));
    }

    function lonerdeviceHistory($id) {
        $lonerdevicelogdata = LonerDeviceLog::where('Loner_ID', $id)->first();
        if (isset($lonerdevicelogdata)) {
            $startDate = $lonerdevicelogdata->Start_date;
            $endDate = $lonerdevicelogdata->End_date;
            $array_lonerdevice = array();
            $lonerdata = InventoryManagement::where('id', $id)->first();
            $lonermodel = $lonerdata->Device_model;
            $studentinventories = StudentInventory::where('Loner_ID', $id)->first();
            $lonerstudentdata = Student::where('ID', $studentinventories->Student_ID)->first();
            $lonername = $lonerstudentdata->Device_user_first_name . ' ' . $lonerstudentdata->Device_user_last_name;
            $studentwhouselonerdevice = $lonerdevicelogdata->Student_ID;
            $studentwhouselonerdevicedata = Student::where('ID', $studentwhouselonerdevice)->first();
            array_push($array_lonerdevice, ["lonerdevicemodel" => $lonermodel, "startDate" => $startDate, "endDate" => $endDate, "whoUseLonerDevice" => $lonername]);
            return response()->json(
                            collect([
                        'response' => 'success',
                        'msg' => $array_lonerdevice
            ]));
        } else {
            return response()->json(
                            collect([
                        'response' => 'Error',
            ]));
        }
    }

    public function closedTicketsPdf($sid) {
        $allCloseTicket = Ticket::where('school_id', $sid)->whereIn('ticket_status', [2, 7, 8])->get();
        $ticket_data = array();
        foreach ($allCloseTicket as $ticket) {
            $inventorydata = InventoryManagement::where('ID', $ticket['inventory_id'])->first();
            $ticketAllStatusLog = TicketStatusLog::where('Ticket_id', $ticket['ID'])->get();

            $ticketstatuslog = array();
            $ticketClosedDate = null; 
            foreach ($ticketAllStatusLog as $logdata) {
                $StatusallData = TicketStatus::where('ID', $logdata['Status_from'])->first();
                $StatusData = TicketStatus::where('ID', $logdata['Status_to'])->first();
                if ($logdata['Status_to'] == 2 || $logdata['Status_to'] == 7 || $logdata['Status_to'] == 8) {
                    $ticketClosedDate = $logdata['updated_at']->format('m-d-Y');
                }
                $date = $logdata['created_at']->format('m-d-Y');
                $updated_by = $logdata['updated_by_user_id'];
                $user = User::where('id', $updated_by)->first();
                $updated_by_user = $user->first_name . ' ' . $user->last_name;
                array_push($ticketstatuslog, ["ID" => $logdata['ID'], "update_by_user" => $updated_by_user, "date" => $date, "updated_status" => $StatusData->status, "previous_status" => $StatusallData->status]);
            }
            $ticketIssueData = TicketIssue::where('ticket_Id', $ticket['ID'])->get();
            $array_issue = array();
            foreach ($ticketIssueData as $data) {
                $issuedata = DeviceIssue::where('ID', $data['issue_Id'])->first();
                $issue = $issuedata->issue;
                array_push($array_issue, [$issue]);
            }
            $ticketAttachment = TicketsAttachment::where('Ticket_ID', $ticket['ID'])->get();
            $parts = Array();
            foreach ($ticketAttachment as $ticketattachmentData) {
                $partsData = PartSKUs::where('ID', $ticketattachmentData['Parts_ID'])->first();
                array_push($parts, ['PartName' => $partsData->Title, 'PartPrice' => $ticketattachmentData['Parts_Price'], 'Quantity' => $ticketattachmentData['Quantity'], 'Notes' => $ticketattachmentData['Parts_Notes']]);
            }
//            $days = $ticket['created_at']->diffInDays($logdata['updated_at']);
            array_push($ticket_data, ['Device' => $inventorydata->Device_model,'TicketNum'=>$ticket['ticket_num'] ,'CreatedDate' => $ticket['created_at']->format('m-d-Y'), 'ClosedDate' => $ticketClosedDate, 'StatusLog' => $ticketstatuslog, 'Parts' => $parts, 'Issue' => $array_issue]);
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $ticket_data,
        ]));
    }

    public function editTicketData(Request $request) {
        // ticket , ticket issue id , student inventory 
        Ticket::where('ID', $request->input('TicketId'))->where('school_id', $request->input('SchoolId'))->update(['notes' => $request->input('Notes')]);
        foreach ($request->input('Issue') as $dataIssue) {

            if ($dataIssue['Flag'] == 1) {
                $checkTicketIssue = TicketIssue::where('ticket_Id', $request->input('TicketId'))
                        ->where('issue_Id', $dataIssue['IssueID'])
                        ->exists();

                if (!$checkTicketIssue) {
                    TicketIssue::firstOrCreate([
                        'ticket_Id' => $request->input('TicketId'),
                        'issue_Id' => $dataIssue['IssueID'],
                        'user_id' => $request->input('UserId'),
                        'inventory_id' => $request->input('InventoryId')
                    ]);
                }
            } elseif ($dataIssue['Flag'] == 3) {
                TicketIssue::where('ticket_Id', $request->input('TicketId'))->where('issue_Id', $dataIssue['IssueID'])->forceDelete();
            }
        }
        $count = 0;
        foreach ($request->input('ImageArray') as $img) {
            if ($img['ID'] == 0) {
                $count += 1;
                $file = $img['Img'];
                $name = $count . 'img_' . time();
                $filePath = 'Tickets/' . $request->input('TicketId') . '/' . $name;
                Storage::disk('public')->put($filePath, file_get_contents($file));

                $TicketImg = new TicketImage();
                $TicketImg->Ticket_ID = $request->input('TicketId');
                $TicketImg->Img = $filePath;
                $TicketImg->save();
            } else {
                $ticketIssue = TicketImage::where('ticket_Id', $request->input('TicketId'))->where('Img', $img['Img'])->first();
            }
        }

        foreach ($request->input('DeleteImgArray') as $DdeletedImg) {
            $ticketIssue = TicketImage::where('ID', $DdeletedImg['ID'])->forceDelete();
        }

        if ($request->input('IsLoner') == 1) {

            StudentInventory::where('School_ID', $request->input('SchoolId'))->where('Inventory_ID', $request->input('InventoryId'))->update(['Loner_ID' => $request->input('AssignLonerID')]);
            $StudentInventoryId = StudentInventory::where('School_ID', $request->input('SchoolId'))->where('Inventory_ID', $request->input('InventoryId'))->first();

            $lonerdevicelog = new LonerDeviceLog();
            $lonerdevicelog->Student_ID = $StudentInventoryId->Student_ID;
            $lonerdevicelog->Loner_ID = $request->input('AssignLonerID');
            $lonerdevicelog->Start_date = now()->format('Y-m-d');
            $lonerdevicelog->save();
        }
        return response()->json(
                        collect([
                    'response' => 'success',]));
    }

    public function TicketData($ticketID, $inventoryID) {
        $ticket_array = array();
        $ticket = Ticket::where('ID', $ticketID)->first();
        $inventory = InventoryManagement::where('ID', $inventoryID)->first();
        $created_user = $ticket->user_id;
        $user_data = User::where('id', $created_user)->first();
        $createdByUser = $user_data->first_name . ' ' . $user_data->last_name;
        $ticketNotes = $ticket->notes;
        $ticketIssue = TicketIssue::where('ticket_Id', $ticketID)->pluck('issue_Id')->all();
        $ticketAllIssue = DeviceIssue::all();
        $issue_array = array();
        foreach ($ticketAllIssue as $issue) {
            $flag = 0;
            if (in_array($issue->ID, $ticketIssue)) {
                $flag = 1;
            }
            array_push($issue_array, ['ID' => $issue->ID, 'Flag' => $flag, 'issue' => $issue->issue]);
        }
        $LonerCheck = StudentInventory::where('Inventory_ID', $inventoryID)->first();
        
        $lonerDevice = '';
        if (isset($LonerCheck->Loner_ID)) {           
            $inventoryforLoner = InventoryManagement::where('ID', $LonerCheck->Loner_ID)->first();
            $lonerDevice = $inventoryforLoner->Serial_number;
        }

        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $ticket,
                    'inventoryData' => $inventory,
                    'createdBy' => $createdByUser,
                    'issues' => $issue_array,
                    'loner' => $lonerDevice,
        ]));
    }

    public function RepairTagPopUpData(Request $request) {
        $schoolId = $request->input('SchoolID');
        $ticketId = $request->input('TicketID');
        $repairedItem = $request->input('RepairedItem');
        $RepairFinished = $request->input('RepairFinished');
        $partsNotes = $request->input('PartNotes');
        $attchPartData = $request->input('Part');
        $damageType = $request->input('DamageType');
        $flag = $request->input('Flag');
        $deviceType =  $request->input('DeviceType');
        
        if($RepairFinished == 1){
            Ticket::where('school_id',$schoolId)->where('ID',$ticketId)->update(['ticket_status'=>9]);
          //for incomming batch         
            $schoolBatchLog = SchoolBatchLog::where('TicketID',$ticketId)->first();
            if ($schoolBatchLog != null) {
                SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 2]);

                $schoolBatchLogData = SchoolBatchLog::where('BatchID', $schoolBatchLog->BatchID)->get();
                $allCompleted = true; // Flag to track if all ticket statuses are completed

                foreach ($schoolBatchLogData as $data) {
                   $ticket = Ticket::where('ID',$data->TicketID)->first();
                        if ($ticket->ticket_status != 9 && $ticket->ticket_status != 10){
                        $allCompleted = false;
                        break;
        }
                }
                if ($allCompleted) {
                    SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 3]);
                }
            }        
        }
        //Parts notes change      
        foreach ($partsNotes as $note) {
            TicketsAttachment::where('Ticket_ID', $ticketId)->where('Parts_ID', $note['Part_id'])->update(['Parts_Notes' => $note['Notes'], 'Parts_Price' => $note['Part_Price']]);
        }
        //end
   
        // aa ticketid batch ma hoy tya amount change 
        //attach part 
        foreach ($attchPartData as $data) {
            $checkAlreadyAttached = TicketsAttachment::where('Ticket_ID', $ticketId)->where('Parts_ID', $data['PartID'])->first();
            // if aa ticket jode a part(id)attached thai gayo hoy
            if (isset($checkAlreadyAttached)) {
                $finalQuantity = $checkAlreadyAttached->Quantity + 1;
                TicketsAttachment::where('Ticket_ID', $ticketId)->where('Parts_ID', $data['PartID'])->update(['Parts_Price' => $data['Price'], 'Parts_Notes' => $data['Notes'], 'Quantity' => $finalQuantity]);
            } else {
                $partsdata = PartSKUs::where('ID', $data['PartID'])->first();
                $ticketAttachment = new TicketsAttachment();
                $ticketAttachment->School_ID = $schoolId;
                $ticketAttachment->Ticket_ID = $ticketId;
                $ticketAttachment->Parts_ID = $data['PartID'];
                $ticketAttachment->Parts_Price = $data['Price'];
                $ticketAttachment->Parts_Notes = $data['Notes'];
                $ticketAttachment->Quantity = 1;
                $ticketAttachment->Original_Price = $partsdata->Variant_Price;
                $ticketAttachment->Parts_Flag = $data['PartFlag']; //1 for master         
                $ticketAttachment->save();
            }
            $ticketattchment = TicketsAttachment::where('Ticket_ID', $ticketId)->where('Parts_Flag', 1)->get();
            if (isset($ticketattchment)) {
                $subtotal = 0;
                foreach ($ticketattchment as $partdata) {
                    $partId = $partdata['ID'];
                    $partAmount = $partdata['Parts_Price'] * $partdata['Quantity'];
                    $subtotal += $partAmount;
                }
                CloseTicketBatchLog::where('Ticket_Id', $ticketId)->update(['Batch_Sub_Total' => $subtotal]);
            }
            //j partid male ch ema sch id male ch to eni quantity - 
            $partssku = PartSKUs::where('ID', $data['PartID'])->first();
            if ($partssku->School_ID != '') {
                $partsdata = PartSKUs::where('ID', $data['PartID'])->first();
                $quantity = $partsdata->Quantity - 1;
                PartSKUs::where('ID', $data['PartID'])->update(['Quantity' => $quantity]);
                //mail send 
                $schoolname = School::where('ID', $schoolId)->select('name')->first();
                $ccRecipients = NotificationEventsLog::where('EventID',2)->pluck('UserID')->all();              
                $data = [
                    'partname' => $partssku->Title,
                    'remaining_quantity' => $partssku->Quantity - 1,
                    'school_name' => $schoolname->name
                ];
                 foreach ($ccRecipients as $recipent) {
                    $staffmember = User::where('id', $recipent)->first();                  
                    try {
                        Mail::to($staffmember->email)->send(new ReorderPartsMailer($data));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
                }
            }
        }
        //end   
        //repair tech data     
        $checkData = TicketRepairLog::where('Ticket_Id', $ticketId)->first();
        if (isset($checkData)) {
            if($flag == 1){
                TicketRepairLog::where('Ticket_Id', $ticketId)->update(['RepairedItem' => $repairedItem,'DeviceType'=>$deviceType]);
            }else{
                TicketRepairLog::where('Ticket_Id', $ticketId)->update(['RepairedItem' => $repairedItem, 'DamageType' => $damageType]);
            }
            
        } else {
            $TicketRepairLog = new TicketRepairLog();
            $TicketRepairLog->School_Id = $schoolId;
            $TicketRepairLog->Ticket_Id = $ticketId;
            $TicketRepairLog->RepairedItem = $repairedItem;
            if ($flag == 1) {
                $TicketRepairLog->DeviceType = $deviceType;
            } else {
                $TicketRepairLog->DamageType = $damageType;
            }
            $TicketRepairLog->save();
        }
        //end     
        return "success";
    }

     function Tickets($sid,$gridflag,$key,$flag,$skey,$sflag,$tflag) {
//       $sid = school id   ,$gridflag = open,close,all,pending $key = search key ,$flag = open mathi k close mathi ,$sflag = as k desc ,$skey = sortby key, tflag = schoolside k admin side 
        $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues.deviceIssue','ticketAttachments')
                ->where('school_id', $sid)
                ->get();
   
        $data->each(function ($ticket) {
            $inventoryManagement = $ticket->inventoryManagement;
            $studentInventory = $inventoryManagement->studentInventory;

            $ticket->Device_model = $inventoryManagement->Device_model ?? null;
            $ticket->serialNum = $inventoryManagement->Serial_number ?? null;
            $ticket->Student_id = $studentInventory->Student_ID ?? null;
            $student = $studentInventory?->student;
            $ticket->Studentname =  $student ? ($student->Device_user_first_name . ' ' . $student->Device_user_last_name) ?? null : null;
            $ticket->Student_num = $studentInventory?->student?->Student_num ?? null;
            $ticket->Grade = $studentInventory?->student?->Grade ?? null;
            $ticket->Building = $studentInventory?->student?->Building ?? null;
            $ticket->Parent_guardian_name = $studentInventory?->student?->Parent_guardian_name ?? null;
            $ticket->Parent_phone_number = $studentInventory?->student?->Parent_phone_number ?? null;
            $ticket->Parent_Guardian_Email = $studentInventory?->student?->Parent_Guardian_Email ?? null;
            $ticket->Parental_coverage = $studentInventory?->student?->Parental_coverage ?? null;
            $ticket->TicketCreatedBy = $ticket->user->first_name . ' ' . $ticket->user->last_name ?? null;
            
            $ticket->issues = $ticket->ticketIssues->map(function ($ticketIssue) {
                        $issueId = $ticketIssue->issue_Id;
                        $deviceIssue = DeviceIssue::find($issueId);
                        return $deviceIssue ? $deviceIssue->issue : null;
                    })->toArray();
  
                    
                 $subtotal = 0;
            foreach ($ticket->ticketAttachments as $attachment) {
                $subtotal += $attachment->Parts_Price;
            }
            $ticket->subtotal = $subtotal;

            
            if ($studentInventory && $studentInventory->Loner_ID != null) {
                $ticket->LonerFlag = 'yes';
            } else {
                $ticket->LonerFlag = 'no';
            }
            $ticket->Status = $ticket->statusname->status ?? null;
            $ticket->StatusID = $ticket->statusname->ID ?? null;
            $ticket->date = $ticket->created_at->format('Y-m-d') ?? null;
        });
        if ($skey == 1) {
            $data = $sflag == 'as' ? $data->sortBy('ticket_num') : $data->sortByDesc('ticket_num');
        } elseif ($skey == 2) {
            $data = $sflag == 'as' ? $data->sortBy('serialNum') : $data->sortByDesc('serialNum');
        } elseif ($skey == 3) {
            $data = $sflag == 'as' ? $data->sortBy('Studentname') : $data->sortByDesc('Studentname');
        } elseif ($skey == 4) {
            $data = $sflag == 'as' ? $data->sortBy('date') : $data->sortByDesc('date');
        } elseif ($skey == 5) {
            $data = $sflag == 'as' ? $data->sortBy('Status') : $data->sortByDesc('Status');
        }else{
           $data = $data->sortByDesc('ID');
        }
        $data->makeHidden(['inventoryManagement', 'user', 'ticketIssues', 'statusname', 'created_at', 'updated_at', 'ticket_status']);
                 
                 $openTicket_array = array();
        $closeTicket_array = array();
        $pendingTicket_array = array();
        foreach ($data as $ticket) {
            if ($ticket->ticket_status == 2 || $ticket->ticket_status == 7 || $ticket->ticket_status == 8) {
                array_push($closeTicket_array, $ticket);
            } elseif ($ticket->ticket_status == 9 || $ticket->ticket_status == 10) {
                array_push($pendingTicket_array, $ticket);
            } else {
                if ($tflag == 1) {
                    if ($ticket->ticket_status == 3) {
                        array_push($openTicket_array, $ticket);
                    }
                } else {
                    array_push($openTicket_array, $ticket);
                }
            }
        }


    if ($gridflag == 1) {
        if ($key == 'null') {
            return response()->json(collect([
                'response' => 'success',
                'Openticket' => $openTicket_array,
                'Closeticket' => $closeTicket_array,
            ]));
        } else {
                $searched = $flag == 1 ? $openTicket_array : $closeTicket_array;

                $searchedArray = array_filter($searched, function ($ticket) use ($key) {
                    return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Date), strtolower($key)) !== false;
                });

            if ($flag == 1) {
                return response()->json(collect([
                    'response' => 'success',
                                'Openticket' => array_values(array_filter($searchedArray)),
                    'Closeticket' => $closeTicket_array,
                ]));
            } else {
                return response()->json(collect([
                    'response' => 'success',
                    'Openticket' => $openTicket_array,
                                'Closeticket' => array_values(array_filter($searchedArray)),
                ]));
            }
        }
    } elseif ($gridflag == 2 || $gridflag == 3) {
        if ($key == 'null') {
            $searchedArray = $gridflag == 2 ? $openTicket_array : $closeTicket_array;
            return response()->json([
                'response' => 'success',
                'tickets' => $searchedArray,
            ]);
        } else {
                $searched = $gridflag == 2 ? $openTicket_array : $closeTicket_array;
                $searchedArray = array_filter($searched, function ($ticket) use ($key) {
                    return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Date), strtolower($key)) !== false;
                });
            return response()->json([
                'response' => 'success',
                'tickets' => array_values($searchedArray),
            ]);
        }
    } elseif ($gridflag == 4) {
        if ($key == 'null') {
                $searchedArray = $pendingTicket_array;
            return response()->json([
                'response' => 'success',
                            'tickets' => $searchedArray,
            ]);
        } else {
                $searched = $pendingTicket_array;
                $searchedArray = array_filter($searched, function ($ticket) use ($key) {
                    return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Date), strtolower($key)) !== false;
                });
            return response()->json([
                'response' => 'success',
                'tickets' => array_values($searchedArray),
            ]);
        }
    } else {
        return 'error';
    }
    }
    function getTicketDataById($tid) {
        $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues', 'ticketHistory', 'ticketAttachments', 'ticketRepairLog', 'ticketImg','batchLog')
                ->where('ID', $tid)
                ->first();

        $data->ticketCreatedBy = $data->user->first_name . ' ' . $data->user->last_name;
        $data->Student = $data->inventoryManagement->studentInventory->student ?? null;
        $issueIds = $data->ticketIssues->pluck('issue_Id')->unique();
        $data->Status = $data->statusname->status ?? null;
    
         if (count($data->batchLog) === 0) {
        $data->ticketUsedInInvoiceFlag = '1';
    } else {
        $data->ticketUsedInInvoiceFlag = '0';
    } 
        $deviceIssues = DeviceIssue::whereIn('ID', $issueIds)->get()->keyBy('ID');

        $data->ticket_issues = $data->ticketIssues->map(function ($ticketIssue) use ($deviceIssues) {
            $issueId = $ticketIssue->issue_Id;
            $deviceIssue = $deviceIssues->get($issueId);
            $ticketIssue->issue_name = $deviceIssue ? $deviceIssue->issue : null;
            $ticketIssue->makeHidden('deleted_at', 'updated_at', 'created_at', 'inventory_id', 'ticket_Id', 'user_id', 'ID');
            return $ticketIssue;
        });

        $data->ticket_history = $data->ticketHistory->map(function ($ticketHistory) {
                    $userData = User::where('ID', $ticketHistory->updated_by_user_id)->first();
                    $ticketStatus = TicketStatus::where('ID', $ticketHistory->Status_from)->first();
                    $ticketHistory->to = $ticketStatus->status;
                    $ticketStatus = TicketStatus::where('ID', $ticketHistory->Status_to)->first();
                    $ticketHistory->from = $ticketStatus->status;
                    $ticketHistory->updateby = $userData->first_name . ' ' . $userData->last_name;
                    $ticketHistory->date = $ticketHistory->created_at;
                    $ticketHistory->makeHidden(['created_at', 'updated_at', 'deleted_at', 'School_id', 'Ticket_id', 'ID', 'updated_by_user_id']);
                })->toArray();

        $data->ticket_attachments = $data->ticketAttachments->map(function ($ticketAttachment) {
            $ticketAttachment->part_name = $ticketAttachment->part->Title ?? null;
            $ticketAttachment->makeHidden(['part', 'created_at', 'updated_at', 'deleted_at', 'Ticket_ID', 'ID', 'Admin_Price']);
            return $ticketAttachment;
        });
        $data->ticket_img = $data->ticketImg->map(function ($ticketImg) {
            $ticketImg->makeHidden(['created_at', 'updated_at', 'deleted_at', 'Ticket_ID', 'ID']);
            return $ticketImg;
        });
        
        $data->makeHidden(['user', 'statusname', 'updated_at', 'ticket_status', 'studentInventory']);

        return response()->json([
                    'response' => 'success',
                    'ticket' => $data,
        ]);
    }
    
    function DamageType(){
        $get = DamageType::all();
        return $get;
    }

}
