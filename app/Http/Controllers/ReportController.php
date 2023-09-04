<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Personal;
use App\Models\User;
use App\Models\PartSKUs;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketStatusLog;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\TicketIssue;
use App\Models\TicketsAttachment;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Models\DeviceIssue;
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
use Carbon\Carbon;
use App\Models\InvoiceLog;
use Illuminate\Support\Facades\DB;
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;

class ReportController extends Controller {

    function AdminSideReports($startDate, $endDate, $lid, $sid) {
        $startDate = Carbon::createFromFormat('m-d-Y', $startDate)->startOfDay();
        $endDate = Carbon::createFromFormat('m-d-Y', $endDate)->endOfDay();

        $ticketIssues = DeviceIssue::all();
        $issues_array = [];

        foreach ($ticketIssues as $ticketIssue) {


            $ticketIssueData = TicketIssue::with('ticket.school')
                    ->where('issue_Id', $ticketIssue['ID'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->when($lid == 'null' && $sid != 'null', function ($query) use ($sid) {
                        return $query->whereHas('ticket.school', function ($subQuery) use ($sid) {
                            $subQuery->where('ID', $sid);
                        });
                    })
                    ->when($lid === 'null' && $sid === 'null', function ($query) {
                        return $query->with('ticket.school');
                    })
                    ->when($lid !== 'null' && $sid === 'null', function ($query) use ($lid) {
                        return $query->whereHas('ticket.school', function ($subQuery) use ($lid) {
                            $subQuery->where('location', $lid);
                        });
                    })
                    ->when($lid !== 'null' && $sid !== 'null', function ($query) use ($lid, $sid) {
                        return $query->whereHas('ticket.school', function ($subQuery) use ($sid) {
                            $subQuery->where('ID', $sid);
                        });
                    })
                    ->whereHas('ticket', function ($subQuery) {
                        $subQuery->where('ticket_status', 3);
                    })
                    ->get();

            $totalAmount = 0;
            $ticketCount = 0;
            foreach ($ticketIssueData as $issueData) {
                $ticket = $issueData->ticket;
                if ($ticket) {
                    $ticketAttachments = $ticket->ticketAttachments;
                    $amount = $ticketAttachments->sum(function ($attachment) {
                        return $attachment->Parts_Price * $attachment->Quantity;
                    });
                    $totalAmount += $amount;
                    $ticketCount++;
                }
            }
            array_push($issues_array, ['name' => $ticketIssue['issue'], 'count' => $ticketCount, 'amount' => $totalAmount]);
        }



        //Ticket By School
        $tickets = Ticket::with('school', 'ticketAttachments')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

        if ($lid == 'null' && $sid != 'null') {
            $tickets = Ticket::with(['school', 'ticketAttachments'])
                    ->whereHas('school', function ($query) use ($sid) {
                        $query->where('id', $sid);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();
        } elseif ($lid != 'null' && $sid == 'null') {
            $tickets = Ticket::with(['school', 'ticketAttachments'])
                    ->whereHas('school', function ($query) use ($lid) {
                        $query->where('location', $lid);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();
        } elseif ($lid != 'null' && $sid != 'null') {
            $tickets = Ticket::with(['school', 'ticketAttachments'])
                    ->whereHas('school', function ($query) use ($lid, $sid) {
                        $query->where('location', $lid)->where('id', $sid);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();
        }

        if (isset($tickets)) {
            $groupedTickets = $tickets->groupBy('school.name')->map(function ($tickets, $schoolName) {
                        $amount = $tickets->sum(function ($ticket) {
                            return $ticket->ticketAttachments->sum(function ($attachment) {
                                return $attachment->Parts_Price * $attachment->Quantity;
                            });
                        });

                        return [
                    'School' => $schoolName,
                    'Amount' => $amount,
                    'Tickets' => $tickets->count() // Count of all tickets for the particular school
                        ];
                    })->values()->all();
        }
        $monthlyData = [];

// Usage example
        if ($sid == 'null' && $lid == 'null') {
            $invoices = InvoiceLog::with(['school', 'batch.batchLog'])->get();
        } elseif ($sid != 'null' && $lid == 'null') {
            $invoices = InvoiceLog::with(['school', 'batch'])
                    ->whereHas('school', function ($query) use ($sid) {
                        $query->where('id', $sid);
                    })
                    ->get();
        } elseif ($sid == 'null' && $lid != 'null') {
            $invoices = InvoiceLog::with(['school', 'batch.batchLog'])
                    ->whereHas('school', function ($query) use ($lid) {
                        $query->where('location', $lid);
                    })
                    ->get();
        } elseif ($sid != 'null' && $lid != 'null') {
            $invoices = InvoiceLog::with(['school', 'batch.batchLog'])
                    ->whereHas('school', function ($query) use ($sid) {
                        $query->where('id', $sid);
                    })
                    ->get();
        }

        $currentYear = date('Y');
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthLabel = date('F Y', mktime(0, 0, 0, $month, 1, $currentYear));
            $months[] = $monthLabel;

            $monthlyData[$monthLabel] = [
                'totalInvoiceAmount' => 0,
                'pendingAmount' => 0,
            ];
        }

        foreach ($invoices as $invoice) {
            $invoiceMonth = date('F Y', strtotime($invoice->Created_at));

            foreach ($invoice->batch->batchLog as $batchlog) {
                if ($invoice->Payment_Status === 'In Progress' || $invoice->Payment_Status === 'Success') {
                    $monthlyData[$invoiceMonth]['totalInvoiceAmount'] += $batchlog->Batch_Sub_Total;
                }
                if ($invoice->Payment_Status === 'Pending') {
                    $monthlyData[$invoiceMonth]['pendingAmount'] += $batchlog->Batch_Sub_Total;
                }
            }
        }

        $series = [
            [
                'name' => 'Paid',
                'data' => [],
            ],
            [
                'name' => 'Due',
                'data' => [],
            ],
        ];

        foreach ($months as $month) {
            $series[0]['data'][] = $monthlyData[$month]['totalInvoiceAmount'];
            $series[1]['data'][] = $monthlyData[$month]['pendingAmount'];
        }

        if ($lid == 'null') {
            $allSchool = School::select('name', 'ID')->get();
            return Response::json(array(
                        'status' => "success",
                        'ticketbyissues' => $issues_array,
                        'ticketbyschool' => $groupedTickets,
                        'school' => $allSchool,
                        'revenue' => $series
            ));
        } else {
            $allSchool = School::where('location', $lid)->select('name', 'ID')->get();
            return Response::json(array(
                        'status' => "success",
                        'ticketbyissues' => $issues_array,
                        'ticketbyschool' => $groupedTickets,
                        'school' => $allSchool,
                        'revenue' => $series
            ));
        }
    }

    function SchoolSideReports($sid, $startDate, $endDate, $grade, $building) {
        $startDate = Carbon::createFromFormat('m-d-Y', $startDate)->startOfDay();
        $endDate = Carbon::createFromFormat('m-d-Y', $endDate)->endOfDay();
        $count_array = array();
        $ticktdata = Ticket::with('inventoryManagement.studentInventory', 'school')->where('school_id', $sid)->whereBetween('created_at', [$startDate, $endDate])->get();
        $daysElapsed = $endDate->diffInDays($startDate);
        $percentageofYear = ($daysElapsed / 365) * 100;
        $withoutpercentage = $daysElapsed / 365;
        $ticketsCreated = Ticket::where('school_id', $sid)->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)->count();
//        Breakage rate by reports 
        if ($grade == 'null' && $building == 'null') {
            $totalUsers = Student::where('School_ID', $sid)->count();
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->count();
        } elseif ($grade != 'null' && $building != 'null') {
            $totalUsers = Student::where('School_ID', $sid)->where('Grade', $grade)->count();
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->where('Building', $building)->count();
        } elseif ($grade != 'null' && $building == 'null') {
            $totalUsers = Student::where('School_ID', $sid)->where('Grade', $grade)->count();
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->count();
        } elseif ($grade == 'null' && $building != 'null') {
            $totalActiveDevice = InventoryManagement::where('school_id', $sid)->where('inventory_status', 1)->where('Building', $building)->count();
            $totalUsers = Student::where('School_ID', $sid)->count();
        }

        $breakageRateByUser = 0;
        $breakageRateByDevice = 0;

        if ($totalUsers == 0 || $withoutpercentage == 0) {
            $breakageRateByUser = 0;
        } else {
            $breakageRateByUser = number_format((($ticketsCreated / $totalUsers) / $withoutpercentage), 2);
        }

        if ($totalActiveDevice == 0 || $withoutpercentage == 0) {
            $breakageRateByDevice = 0;
        } else {
            $breakageRateByDevice = number_format((($ticketsCreated / $totalActiveDevice) / $withoutpercentage), 2);
        }

//        Tickets by status
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
//        Tickets by issue
        $allissues = TicketIssue::leftJoin('tickets', function ($join) use ($startDate, $endDate) {
                            $join->on('ticket_issues.ticket_Id', '=', 'tickets.ID')
                            ->whereBetween('tickets.created_at', [$startDate, $endDate]);
                        })->leftJoin('inventory_management', 'tickets.inventory_id', '=', 'inventory_management.ID')
                        ->leftJoin('student_inventories', 'inventory_management.ID', '=', 'student_inventories.Inventory_ID')
                        ->leftJoin('students', 'student_inventories.Student_ID', '=', 'students.ID')
                        ->leftJoin('deviceissues', 'deviceissues.ID', '=', 'ticket_issues.issue_Id')
                        ->select('deviceissues.issue as issue', 'deviceissues.ID as issueID', DB::raw('COALESCE(count(tickets.id), 0) as count'))
                        ->where(function ($query) use ($sid) {
                            $query->where('tickets.school_id', $sid)
                            ->orWhereNull('tickets.school_id');
                        })->groupBy('deviceissues.issue', 'deviceissues.ID'); // Group by issue and issueID

        if ($building !== 'null' && $grade !== 'null') {
            $allissues->where('inventory_management.Building', $building)
                    ->where('students.Grade', $grade);
        } elseif ($building === 'null' && $grade !== 'null') {        
            $allissues->where('students.Grade', $grade);
        } elseif ($building !== 'null' && $grade === 'null') {          
            $allissues->where('inventory_management.Building', $building);
        }

        $allissues->whereNotNull('student_inventories.Student_ID');
        $results = $allissues->get();
        $issueCounts = $results->groupBy('issueID')->map(function ($group) {
                    return [
                'issue' => $group->first()->issue,
                'issueID' => $group->first()->issueID,
                'count' => $group->sum('count'),
                    ];
                })->values()->all();
        $allIssue = DB::table('deviceissues')->select('issue', 'ID as issueID')->get();
        foreach ($allIssue as $issue) {
            $existingIssue = collect($issueCounts)->firstWhere('issueID', $issue->issueID);

            if (!$existingIssue) {
                $issueCounts[] = [
                    'issue' => $issue->issue,
                    'issueID' => $issue->issueID,
                    'count' => 0,
                ];
            }
        }

//////////////////

        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $series = [
            [
                'name' => 'Receiving Tickets',
                'data' => [],
            ],
            [
                'name' => 'Spending Amounts',
                'data' => [],
            ],
        ];

        $currentYear = Carbon::now()->year;

        for ($month = 1; $month <= 12; $month++) {

            $date = Carbon::createFromDate($currentYear, $month, 1);
            $ticketData = Ticket::with('ticketAttachments')
                    ->where('school_id', $sid)
                    ->whereYear('created_at', $currentYear)
                    ->whereMonth('created_at', $month)
                    ->get();

            $ticketCount = $ticketData->count();
            $totalAmount = $ticketData->sum(function ($ticket) {
                return $ticket->ticketAttachments->sum(function ($attachment) {
                    return $attachment->Parts_Price * $attachment->Quantity;
                });
            });

            $series[0]['data'][] = $ticketCount;
            $series[1]['data'][] = $totalAmount;
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'breakageratebydevice' => $breakageRateByDevice,
                    'breakageratebyuser' => $breakageRateByUser,
                    'TicketsByStatus' => $statusCounts,
                    'TicketsByIssue' => $issueCounts,
                    'ReceivingTickets' => $series
        ]));
    }

}
