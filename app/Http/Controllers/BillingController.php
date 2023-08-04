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
use App\Exceptions\InvalidOrderException;

class BillingController extends Controller {

    function PaymentDetails($sid, $skey) {
        $array_billingLog = array();
        $Paymentdata = PaymentLog::where('School_ID', $sid)->get();
        foreach ($Paymentdata as $data) {
            $ticketID = $data['TicketID'];
            $ticketdata = Ticket::where('ID', $ticketID)->first();
            $studentInventorydata = StudentInventory::where('Inventory_ID', $ticketdata->inventory_id)->whereNotNull('Student_ID')->first();
            if (isset($studentInventorydata)) {
                $studentdata = Student::where('ID', $studentInventorydata->Student_ID)->first();
                $parentemail = $studentdata->Parent_Guardian_Email;
            } else {
                $parentemail = null;
            }

            $sentAmount = $data['SentPayment'];
            $receiveAmount = $data['ReceivePayment'];

            if ($sentAmount == null || $receiveAmount == null) {
                $status = 'Payment Pending';
            } elseif ($receiveAmount >= $sentAmount) {
                $status = 'Payment Receive';
            } else {
                $status = 'Payment Pending';
            }
            $date = $data['created_at']->format('Y-m-d');
            array_push($array_billingLog, ["Date" => $date, "Ticket" => $ticketID, "ParentEmail" => $parentemail, "SentAmount" => $sentAmount, "ReceiveAmount" => $receiveAmount, "Status" => $status]);
        }
        if ($skey == 'null') {
            return $array_billingLog;
        } else {
            $searchedArray = array_filter($array_billingLog, function ($obj) use ($skey) {
                return strpos(strtolower($obj['Date']), $skey) !== false || strpos(strtolower($obj['Ticket']), $skey) !== false || strpos(strtolower($obj['ParentEmail']), $skey) !== false || strpos(strtolower($obj['SentAmount']), $skey) !== false || strpos(strtolower($obj['ReceiveAmount']), $skey) !== false || strpos(strtolower($obj['Status']), $skey) !== false;
            });

            return response()->json(array_values($searchedArray));
        }
    }  
}
