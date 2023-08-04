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
use Carbon\Carbon;
use App\Models\DeviceIssue;
use App\Models\TechnicianLocation;
use App\Models\Location;
use App\Models\K12User;
use App\Models\TicketRepairLog;

class AdminDashboardController extends Controller {

    function TechnicianRepairedLog($id) {
        $TechnicianData = TicketStatusLog::whereNotNull('School_id')->where('who_worked_on', $id)->get();
//       return $TechnicianData;
        $TechnicianLocation = TechnicianLocation::where('Technician', $id)->first();
        $TechnicianLocationName = Location::where('ID', $TechnicianLocation->Location)->first();
        $count = 0;
        $technician_array = array();
        foreach ($TechnicianData as $data) {

            $ticket = Ticket::where('ID', $data->Ticket_id)->first();
            $user = User::where('ID', $data->who_worked_on)->first();
            $count++;
            $ticketStatusData = TicketStatus::where('ID', $ticket->ticket_status)->first();
            $ticket->status_name = $ticketStatusData->status ?? null;
            $ticket->inventory_name = $ticket->inventoryManagement->Device_model ?? null;
            if (isset($TechnicianLocationName)) {
                $ticket->location = $TechnicianLocationName->Location ?? null;
            } else {
                $ticket->location = 'null';
            }

            $ticket->makeHidden(['status', 'inventoryManagement']);
            array_push($technician_array, $ticket);
        }

        return Response::json(array(
                    'status' => "success",
                    'msg' => $technician_array,
        ));
    }

    function adminDashboardData($startDate, $endDate, $lid,$sid) {
        $startDate = Carbon::createFromFormat('m-d-Y', $startDate)->startOfDay();
        $endDate = Carbon::createFromFormat('m-d-Y', $endDate)->endOfDay();
        //////////technician               
        $technicians = K12User::where('access_type', 6)->get();     
        $technician_array = array();
        foreach ($technicians as $technician) {           
            $userData = User::where('email', $technician->email)->first();
            if(isset($userData)){
                 $TechnicianLocation = TechnicianLocation::where('Technician', $userData->id)->get();
            $techLoc_array_name = array();
            $techLoc_array_id = array();
            foreach ($TechnicianLocation as $tlData) {
                $TechnicianLocationName = Location::where('ID', $tlData->Location)->first();
                if(isset($TechnicianLocationName)){
                $techLoc_array_name[] = $TechnicianLocationName->Location;
                $techLoc_array_id[] = $TechnicianLocationName->ID;                
                }else{
                    $techLoc_array_name[] = null;
                    $techLoc_array_id[] = null;  
                }                
            }

            $repairedTickets = TicketStatusLog::where('who_worked_on', $userData->id)->get();          
            $CountsofTicket = TicketStatusLog::where('who_worked_on', $userData->id)->count();
            $point = 0;
            foreach ($repairedTickets as $repairedTicket) {

                $ticketData = Ticket::where('ID', $repairedTicket->Ticket_id)->first();
                if (isset($ticketData)) {                   
                    $inventoryData = InventoryManagement::where('ID', $ticketData->inventory_id)->first();
                    if (isset($inventoryData)) {
                        $deviceTypeData = DeviceType::where('ID', $inventoryData->Device_type)->first();
                        $point += $deviceTypeData->point ?? null;                    
                }
            }
            }
            array_push($technician_array, ['TechnicianName' => $userData->first_name . ' ' . $userData->last_name, 'TechID' => $userData->id, 'Points' => $point, 'TotalTickets' => $CountsofTicket, 'locationName' => implode(',', $techLoc_array_name), 'locationID' => implode(',', $techLoc_array_id)]);
            }           
        }
        /////////  school invoice 

        if ($lid == 'null' && $sid == 'null') {
            $schools = School::select('name', 'ID')->get();
        } elseif ($lid == 'null' && $sid != 'null') {
            $schools = School::where('ID', $sid)->select('name', 'ID')->get();
        } elseif ($lid != 'null' && $sid == 'null') {
            $schools = School::where('location', $lid)->select('name', 'ID')->get();
        } elseif ($lid != 'null' && $sid != 'null') {
            $schools = School::where('location', $lid)->where('ID', $sid)->select('name', 'ID')->get();
        } else {
            $schools = School::select('name', 'ID')->get();
        }
        $school_array = array();

        foreach ($schools as $schData) {
           $techcount = TicketStatusLog::where('School_id', $schData->ID)->whereNotNull('who_worked_on')->whereBetween('created_at', [$startDate, $endDate])->count();
            $invoice = InvoiceLog::where('School_Id', $schData->ID)->whereBetween('created_at', [$startDate, $endDate])->pluck('Batch_ID')->all();
            $invoiceCount =InvoiceLog::where('School_Id', $schData->ID)->whereBetween('created_at', [$startDate, $endDate])->count();
            $totalamount = 0;
            foreach ($invoice as $invoicedata) {
                $batchTicket = CloseTicketBatchLog::where('Batch_Id', $invoicedata)->distinct()->pluck('Ticket_Id')->all();
//                $batchTicketCount = count($batchTicket);
                $batchData = CloseTicketBatchLog::where('Batch_Id', $invoicedata)->get();
                foreach ($batchData as $batch) {
                    $totalamount += $batch->Batch_Sub_Total;
                }
            }

            if ($totalamount > 0) {
                array_push($school_array, ['count' => $techcount,'id' => $schData->ID, 'name' => $schData->name, 'Invoice' => $totalamount,'InvoiceCount'=>$invoiceCount]);
            }
        }


        /////   incomming 

       if ($lid == 'null' && $sid != 'null') {
    $ticktdata = Ticket::with(['school', 'inventoryManagement.studentInventory'])
        ->whereHas('school', function ($query) use ($sid) {
            $query->where('id', $sid);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
} elseif ($lid != 'null' && $sid == 'null') {
    $ticktdata = Ticket::with(['school', 'inventoryManagement.studentInventory'])
        ->whereHas('school', function ($query) use ($lid) {
            $query->where('location', $lid);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
} elseif ($lid != 'null' && $sid != 'null') {
    $ticktdata = Ticket::with(['school', 'inventoryManagement.studentInventory'])
        ->whereHas('school', function ($query) use ($lid, $sid) {
            $query->where('location', $lid)->where('id', $sid);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
} else {
    $ticktdata = Ticket::with('inventoryManagement.studentInventory')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
}

$incomming_array = [];
$outgoing_array = [];
$inventoryCount = 0;

foreach ($ticktdata as $ticket) {
    $school = $ticket->school;
    $ticket->makeHidden(['inventoryManagement', 'school', 'created_at', 'updated_at']);

    if ($ticket->ticket_status == 3) {
        if (!isset($incomming_array[$school->ID])) {
            $incomming_array[$school->ID] = [
                'schoolName' => $school->name,
                'ticketCount' => 0,
                'amount' => 0,
                'inventoryCount' => 0, // New addition
            ];
        }

        $incomming_array[$school->ID]['ticketCount']++;
        $incomming_array[$school->ID]['amount'] += $ticket->ticketAttachments->sum(function ($attachment) {
            return $attachment->Parts_Price * $attachment->Quantity;
        });

        $inventories = collect(); // Collect unique inventory IDs
     
        $incomming_array[$school->ID]['inventoryCount'] += $inventories->unique('ID')->count(); // Count unique inventory IDs
    } elseif ($ticket->ticket_status == 10 || $ticket->ticket_status == 9) {
        if (!isset($outgoing_array[$school->ID])) {
            $outgoing_array[$school->ID] = [
                'schoolName' => $school->name,
                'ticketCount' => 0,
                'amount' => 0,
                'inventoryCount' => 0, // New addition
            ];
        }

        $outgoing_array[$school->ID]['ticketCount']++;
        $outgoing_array[$school->ID]['amount'] += $ticket->ticketAttachments->sum(function ($attachment) {
            return $attachment->Parts_Price * $attachment->Quantity;
        });

        $inventories = collect(); // Collect unique inventory IDs   
        $outgoing_array[$school->ID]['inventoryCount'] += $inventories->unique('ID')->count(); // Count unique inventory IDs
    }
}


        $incomming_array = array_values($incomming_array);
        $outgoing_array = array_values($outgoing_array);

//for tech 
        if ($lid == 'null') {
            $allSchool = School::select('name', 'ID')->get();
            return Response::json(array(
                        'status' => "success",
                        'technician' => $technician_array,
                        'incomming' => $incomming_array,
                        'outgoing' => $outgoing_array,
                        'schoolinvoice' => $school_array,
                        'school' => $allSchool
            ));
        } else {
            $searchedArray = array_filter($technician_array, function ($obj) use ($lid) {
                return strpos(strtolower($obj['locationID']), $lid) !== false;
            });
            $allSchool = School::where('location', $lid)->select('name', 'ID')->get();
            return Response::json(array(
                        'status' => "success",
                        'technician' => array_values($searchedArray),
                        'incomming' => $incomming_array,
                        'outgoing' => $outgoing_array,
                        'schoolinvoice' => $school_array,
                        'school' => $allSchool
            ));
        }
    }

}
