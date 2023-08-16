<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Response;
use App\Exceptions\InvalidOrderException;
use App\Models\ErrorLog;
use App\Helper;
use App\Models\School;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Models\User;
use App\Models\AdminSetting;
use App\Models\CloseTicketBatch;
use App\Models\CloseTicketBatchLog;
use App\Models\DeviceAllocationLog;
use App\Models\InventoryCcSetting;
use App\Models\InventoryManagement;
use App\Models\InvoiceLog;
use App\Models\PartSKUs;
use App\Models\PaymentLog;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\LonerDeviceLog;
use App\Models\TicketCcSetting;
use App\Models\TicketRepairLog;
use App\Models\Ticket;
use App\Models\TicketIssue;
use App\Models\TicketStatusLog;
use App\Models\TicketsAttachment;
use App\Models\Logo;
use Illuminate\Support\Facades\DB;
use App\Models\TechnicianLocation;
use App\Models\SupportTicket;
use App\Models\InvoiceCcSetting;
use App\Models\IncomingOutgoingBatchNotification;
use App\Models\SignUpCcSetting;
use App\Models\SchoolAddress;
use App\Models\ManageSoftware;
use App\Models\SupportTicketComments;
use App\Models\SupportTicketAssignment;
use App\Models\SchoolBatch;
use App\Models\SchoolBatchLog;
use App\Models\K12User;
use App\Models\Location;
class SchoolController extends Controller {

    public function GetallSchools($skey, $uid) {
        $user = User::where('id', $uid)->first();
        if (isset($user)) {
            if ($user->access_type == 6) {
                $getLocation = TechnicianLocation::where('Technician', $user->id)->pluck('Location')->all();
                $get = School::whereIn('location', $getLocation)->select('ID', 'name', 'status');
            } else {
                $get = School::select('ID', 'name', 'status');
            }
            if ($skey !== 'null') {
                $get->where(function ($query) use ($skey) {
                    $query->where('ID', 'LIKE', "%$skey%");
                    $query->orWhere('name', 'LIKE', "%$skey%");
                    $query->orWhere('status', 'LIKE', "%$skey%");
                });
            }
        }
        $data = $get->get();
        return Response::json(array(
                    'status' => "success",
                    'msg' => $data
        ));
    }

    public function schoolDatabyId($sid) {
        $get = School::where('ID', $sid)->select('ID', 'name', 'status')->first();
        return $get;
    }

    public function schoolDatabyNumber($snum) {
        $get = School::where('schoolNumber', $snum)->first();
        return $get;
    }

    public function deleteSchool($sid) {
        DB::transaction(function () use ($sid) {
            // Delete records from AdminSetting table
            AdminSetting::where('School_ID', $sid)->forceDelete();

            // Delete records from CloseTicketBatchLog and CloseTicketBatch tables
            $batchIds = CloseTicketBatch::where('School_ID', $sid)->pluck('ID');
            CloseTicketBatchLog::whereIn('Batch_Id', $batchIds)->forceDelete();
            CloseTicketBatch::where('School_ID', $sid)->forceDelete();

            // Delete records from DeviceAllocationLog, InventoryCcSetting, and InventoryManagement tables
            DeviceAllocationLog::where('School_ID', $sid)->forceDelete();
            InventoryCcSetting::where('School_ID', $sid)->forceDelete();
            InventoryManagement::where('school_id', $sid)->forceDelete();
            Logo::where('School_ID', $sid)->forceDelete();

            // Delete records from LonerDeviceLog and Student tables
            $studentIds = Student::where('School_ID', $sid)->pluck('ID');
            LonerDeviceLog::whereIn('Student_ID', $studentIds)->forceDelete();
            Student::where('School_ID', $sid)->forceDelete();

            // Delete records from StudentInventory, TicketCcSetting, and TicketRepairLog tables
            StudentInventory::where('School_ID', $sid)->forceDelete();
            TicketCcSetting::where('School_ID', $sid)->forceDelete();
            TicketRepairLog::where('School_Id', $sid)->forceDelete();

            // Delete records from TicketIssue, TicketStatusLog, and Ticket tables
            $ticketIds = Ticket::where('school_id', $sid)->pluck('ID');
            TicketIssue::whereIn('ticket_Id', $ticketIds)->forceDelete();
            TicketStatusLog::whereIn('Ticket_id', $ticketIds)->forceDelete();
            Ticket::where('school_id', $sid)->forceDelete();
            TicketsAttachment::where('School_ID', $sid)->forceDelete();

            // Delete records from InvoiceLog, PartSKUs, and PaymentLog tables
            InvoiceLog::where('School_Id', $sid)->forceDelete();
            PartSKUs::where('School_ID', $sid)->forceDelete();
            PaymentLog::where('School_ID', $sid)->forceDelete();

            //delete records from all setting tables
            IncomingOutgoingBatchNotification::where('School_ID', $sid)->forceDelete();
            SignUpCcSetting::where('School_ID', $sid)->forceDelete();
            InvoiceCcSetting::where('School_ID', $sid)->forceDelete();
            InventoryCcSetting::where('School_ID', $sid)->forceDelete();
            TicketCcSetting::where('School_ID', $sid)->forceDelete();
            SchoolAddress::where('SchoolID', $sid)->forceDelete();
            ManageSoftware::where('school_id', $sid)->forceDelete();

            // delete records from all support ticket details
            SupportTicket::where('SchoolId', $sid)->forceDelete();
            SupportTicketComments::where('SchoolID', $sid)->forceDelete();
            SupportTicketAssignment::where('school_id', $sid)->forceDelete();
           
            //
            $batch = SchoolBatch::where('SchoolId',$sid)->pluck('ID');
            SchoolBatchLog::whereIn('BatchID',$batch)->forceDelete();
            
            // Delete records from User table
            User::where('school_id', $sid)->forceDelete();
            School::where('ID', $sid)->forceDelete();
        });

        return 'Success';
    }
    
function getOverAllData()
    {
        $getSchools = School::count();
        $totalActiveSchools = School::where('status', 'Active')->count();
        $totalTechnicians = K12User::where('access_type', 6)->where('status', 'Approve')->count();
        $totalincomingdevices = Ticket::where('ticket_status', 3)->count();
        $totaloutgoingdevices = Ticket::where('ticket_status', 9)->orWhere('ticket_status', 10)->count();
        $totaldomains = Domain::count();
        $totalactivedomains = Domain::where('Status', 'active')->count();
        $totallocations = Location::count();
        $totalactivelocations = Location::where('Status', 'Active')->count();
        $totalactivemasterinventory = PartSKUs::where('Master_ID', null)->Where('School_ID', null)->where('Status', 'active')->count();
        $totalUsers = User::count();
        $highestTicket = DB::table('tickets')
            ->select('schools.name as name', 'tickets.school_id', DB::raw('count(*) as ticket_count'))
            ->join('schools', 'tickets.school_id', '=', 'schools.id')
            ->groupBy('tickets.school_id', 'schools.name')
            ->orderBy('ticket_count', 'desc')
            ->limit(1)
            ->first();

        $highestDevice = DB::table('inventory_management')
            ->join('schools', 'inventory_management.school_id', '=', 'schools.id')
            ->select('schools.name', 'inventory_management.school_id', DB::raw('count(*) as device_count'))
            ->groupBy('schools.name', 'inventory_management.school_id')
            ->orderBy('device_count', 'desc')
            ->limit(1)
            ->first();

        $totaltechnologysupporttickets = SupportTicket::where('Type', 1)->count();
        $totalmaintencesupporttickets = SupportTicket::where('Type', 2)->count();
        $totalsoftwares = ManageSoftware::count();

        return response()->json([
            'totalschools' => $getSchools,
            'totalactiveschools' => $totalActiveSchools,
            'totalactivetechnicians' => $totalTechnicians,
            'totalincomingdevices' => $totalincomingdevices,
            'totaloutgoingdevices' => $totaloutgoingdevices,
            'totaldomains' => $totaldomains,
            'totalactivedomains' => $totalactivedomains,
            'totallocations' => $totallocations,
            'totalactivelocations' => $totalactivelocations,
            'totalactivemasterinventory' => $totalactivemasterinventory,
            'highestTicket' => $highestTicket,
            'highestDevice' => $highestDevice,
            'totalStaffmembers' => $totalUsers,
            'technologysupportticket' => $totaltechnologysupporttickets,
            'totalmaintencesupporttickets' => $totalmaintencesupporttickets,
            'totalsoftwares' => $totalsoftwares,
        ]);
    }

}
     
   
