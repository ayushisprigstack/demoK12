<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Personal;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketStatusLog;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\TicketIssue;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Response;
use App\Models\ErrorLog;
use App\Helper;
use App\Exceptions\InvalidOrderException;
use App\Models\PartSKUs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SupportTicket;
use App\Models\InvoiceLog;
use App\Models\CloseTicketBatch;
use App\Models\DeviceIssue;

class DashboardController extends Controller {
  
    function DashboardData($sid, $startDate, $endDate, $grade, $building) {

        $startDate = Carbon::createFromFormat('m-d-Y', $startDate)->startOfDay();
        $endDate = Carbon::createFromFormat('m-d-Y', $endDate)->endOfDay();
        $ticktdata = Ticket::with('inventoryManagement.studentInventory', 'school')->where('school_id', $sid)->whereBetween('created_at', [$startDate, $endDate])->get();
        $daysElapsed = $endDate->diffInDays($startDate);
        $percentageofYear = ($daysElapsed / 365) * 100;
        $withoutpercentage = $daysElapsed / 365;
        $ticketsCreated = Ticket::where('school_id', $sid)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->count();
//        Breakage rate by reports 
        if ($grade == 'null' && $building == 'null') {
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->count(); //
        } elseif ($grade != 'null' && $building != 'null') {
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->where('Building', $building)->count(); //
        } elseif ($grade != 'null' && $building == 'null') {
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->count();
        } elseif ($grade == 'null' && $building != 'null') {
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->where('Building', $building)->count();
        }
        $breakageRateByDevice = 0;
        if ($totalActiveDevice == 0 || $withoutpercentage == 0) {
            $breakageRateByDevice = 0;
        } else {
            $breakageRateByDevice = number_format((($ticketsCreated / $totalActiveDevice) / $withoutpercentage), 2);
        }
        //status 
        $allstatuses = TicketStatus::leftJoin('tickets', function ($join) use ($startDate, $endDate) {
                            $join->on('ticket_status.ID', '=', 'tickets.ticket_status')
                            ->whereBetween('tickets.created_at', [$startDate, $endDate]);
                        })->leftJoin('inventory_management', 'tickets.inventory_id', '=', 'inventory_management.ID')
                        ->leftJoin('student_inventories', 'inventory_management.ID', '=', 'student_inventories.Inventory_ID')
                        ->leftJoin('students', 'student_inventories.Student_ID', '=', 'students.ID')
                        ->select('ticket_status.status as status_name', DB::raw('COALESCE(count(tickets.id), 0) as count'))
                        ->where(function ($query) use ($sid) {
                            $query->where('tickets.school_id', $sid)
                            ->orWhereNull('tickets.school_id');
                        })->groupBy('ticket_status.status');

if ($building !== 'null' && $grade !== 'null') {
$allstatuses->where('inventory_management.Building', $building)->where('students.Grade', $grade);
$allstatuses->whereNotNull('student_inventories.Student_ID');
} elseif ($building === 'null' && $grade !== 'null') {
$allstatuses->where('students.Grade', $grade);
$allstatuses->whereNotNull('student_inventories.Student_ID');
} elseif ($building !== 'null' && $grade === 'null') {
$allstatuses->where('inventory_management.Building', $building);
$allstatuses->whereNotNull('student_inventories.Student_ID');
}

        $results = $allstatuses->get();
        $statuses = $allstatuses->groupBy('ticket_status.ID', 'ticket_status.status')->get();

        $allStatuses = TicketStatus::whereIn('ID', [1, 3, 4, 5, 6, 9, 10])->get(); // with grade 
        $statusCounts = [];

        foreach ($allStatuses as $status) {
            $existingStatus = $statuses->where('status_name', $status->status)->first();
            if ($existingStatus) {
                $statusCounts[] = $existingStatus;
            } else {
                $statusCounts[] = [
                    'status_name' => $status->status,
                    'count' => 0,
                ];
            }
        }
//
        $supportTicket = SupportTicket::with('user')->where('SchoolId', $sid)->where('Status', 'Open')->whereBetween('created_at', [$startDate, $endDate])->orderBy('ID', 'desc')->get();

        $allBatch = CloseTicketBatch::with('invoice', 'batchLog.ticket.ticketAttachments')
                ->where('School_ID', $sid)
                ->whereHas('invoice', function ($query) {
                    $query->where('Payment_Status', 'pending');
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('ID', 'Amount', 'Name', 'Date')
                ->orderBy('ID', 'desc')
                ->get();

        $allBatch->each(function ($batch) {
            $batch->InvoiceNum = $batch->invoice[0]['ID'];
            $batch->InvoiceStatus = $batch->invoice[0]['Payment_Status'];
            $batch->TransactionId = $batch->invoice[0]['ChequeNo'];

            foreach ($batch->batchLog as $batchLog) {
                $subtotal = 0;
                foreach ($batchLog->ticket->ticketAttachments as $ticketAttachment) {
                    if ($ticketAttachment['Parts_Flag'] == 1) {
                        $amount = $ticketAttachment['Parts_Price'] * $ticketAttachment['Quantity'];
                        $subtotal += $amount;
                    }
                    $batch->SubTotal = $subtotal;
                }
            }
        });

        //tickets     
        $ticktdata = Ticket::with('inventoryManagement.studentInventory')
                ->where('school_id', $sid)
                ->whereBetween('created_at', [$startDate, $endDate]);

        if ($building !== 'null' && $grade === 'null') {
            $ticktdata->whereHas('inventoryManagement', function ($query) use ($building) {
                $query->where('Building', $building);
            });
        } elseif ($building == 'null' && $grade !== 'null') {
            $ticktdata->whereHas('inventoryManagement.studentInventory.student', function ($query) use ($grade) {
                $query->where('Grade', $grade);
            });
        } elseif ($building !== 'null' && $grade !== 'null') {
            $ticktdata->whereHas('inventoryManagement', function ($query) use ($building, $grade) {
                        $query->where('Building', $building);
                    })
                    ->whereHas('inventoryManagement.studentInventory.student', function ($query) use ($grade) {
                        $query->where('Grade', $grade);
                    });
        }
        $ticktdata = $ticktdata->orderBy('ID', 'desc')->get();
        $openTicket_array = array();
        $repairedTicket_array = array();
        $partNames = [];
        foreach ($ticktdata as $ticket) {
            if ($ticket->ticket_status == 9 || $ticket->ticket_status == 10) {

                array_push($repairedTicket_array, $ticket);
            } elseif ($ticket->ticket_status == 1 || $ticket->ticket_status == 3 || $ticket->ticket_status == 4 || $ticket->ticket_status == 5 || $ticket->ticket_status == 6) {

                array_push($openTicket_array, $ticket);
            }
            $ticket->SerialNum = $ticket->inventoryManagement->Serial_number;
            $ticket->makeHidden(['inventoryManagement', 'updated_at']);
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'opentickets' => $openTicket_array,
                    'repairedtickets' => $repairedTicket_array,
                    'supportticket' => $supportTicket,
                    'invoices' => $allBatch,
                    'breakageratebydevice' => $breakageRateByDevice,
                    'TicketsByStatus' => $statusCounts
        ]));
    }

}
