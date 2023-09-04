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
use FedEx\ShipService\ComplexType;
use FedEx\ShipService\SimpleType;
use FedEx\ShipService\ShipServiceRequest;
use App\Models\PostalCode;

class ShippingController extends Controller {

   function TicketsForShipping($sid, $gridflag, $key, $skey, $sflag) {
//       $sid = school id   ,$gridflag = open,all $key = search key ,$flag = open mathi k close mathi ,$sflag = as k desc ,$skey = sortby key, tflag = schoolside k admin side 
        $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues.deviceIssue', 'ticketAttachments')
                ->where('school_id', $sid)
                ->get();

        $data->each(function ($ticket) {
            $inventoryManagement = $ticket->inventoryManagement;
            $studentInventory = $inventoryManagement->studentInventory;

            $ticket->Device_model = $inventoryManagement->Device_model ?? null;
            $ticket->serialNum = $inventoryManagement->Serial_number ?? null;
            $ticket->Student_id = $studentInventory->Student_ID ?? null;
            $student = $studentInventory?->student;
            $ticket->Studentname = $student ? ($student->Device_user_first_name . ' ' . $student->Device_user_last_name) ?? null : null;
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
            $ticket->date = $ticket->created_at->format('Y-m-d') ?? null;
        });
        if ($skey == 1) {
            $data = $sflag == 'as' ? $data->sortBy('ticket_num') : $data->sortByDesc('ticket_num');
        } elseif ($skey == 2) {
            $data = $sflag == 'as' ? $data->sortBy('serialNum') : $data->sortByDesc('serialNum');
        } elseif ($skey == 3) {
            $data = $sflag == 'as' ? $data->sortBy('Studentname') : $data->sortByDesc('Studentname');
        } elseif ($skey == 4) {
            $data = $sflag == 'as' ? $data->sortBy('subtotal') : $data->sortByDesc('subtotal');
        } elseif ($skey == 5) {
            $data = $sflag == 'as' ? $data->sortBy('Status') : $data->sortByDesc('Status');
        }else
        {
            $data = $sflag == 'as' ? $data->sortBy('ticket_num') : $data->sortByDesc('ticket_num');
        }
        $data->makeHidden(['inventoryManagement', 'user', 'ticketIssues', 'statusname', 'created_at', 'updated_at', 'ticket_status']);

        $allOpenTicket_array = array();
        $allSentOutTicket_array = array();
        $allCloseTicket_array = array();
        foreach ($data as $ticket) {
            if ($ticket->ticket_status == 1 || $ticket->ticket_status == 4 || $ticket->ticket_status == 5 || $ticket->ticket_status == 6) {
                array_push($allOpenTicket_array, $ticket);
            } elseif ($ticket->ticket_status == 3) {
                array_push($allSentOutTicket_array, $ticket);
            }
        }
        if ($key == 'null') {
            if ($gridflag == 1) {
                return response()->json(collect([
                            'response' => 'success',
                            'Tickets' => $allOpenTicket_array,
                ]));
            } else {
                return response()->json(collect([
                            'response' => 'success',
                            'Tickets' => $allSentOutTicket_array,
                ]));
            }
        } else {
            $searched = $gridflag == 1 ? $allOpenTicket_array : $allSentOutTicket_array;

            $searchedArray = array_filter($allOpenTicket_array, function ($ticket) use ($key) {
                return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                strpos(strtolower($ticket->Date), strtolower($key)) !== false;
            });

            return response()->json(collect([
                        'response' => 'success',
                        'Tickets' => array_values(array_filter($searchedArray)),
            ]));
        }
    }  

    function PostalCodes(){
        $get = PostalCode::all();
        return $get;
    }   
}
