<?php

namespace App\Http\Controllers;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use App\Models\ErrorLog;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketIssue;
use App\Models\TicketStatusLog;
use App\Models\InventoryManagement;
use App\Models\PartSKUs;
use App\Models\TicketsAttachment;
use App\Models\User;
use App\Models\School;
use App\Models\DeviceIssue;
use App\Mail\ReorderPartsMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\InventoryCcSetting;
use App\Models\AdminSetting;
use App\Models\PaymentLog;


class RepairPartController extends Controller
{
  // ge ticket data by id ->http://127.0.0.1:8000/api/fetchDeviceDetailforTicket/53&2;
    
   function getAllParts($sid, $skey) {
    if ($skey == 'null') {
        $schiddata = PartSKUs::where('School_ID', $sid)->pluck('ID')->all();

        $get = PartSKUs::whereIn('School_ID', $schiddata)->orWhereNull('School_ID')->get();

        foreach ($get as $partSKU) {
            if ($partSKU->School_ID === null) {
                $partSKU->flag = ' '; // Assuming $schoolColorStatus is already defined
            } else {
                $partSKU->flag = 'schoolColorStatus'; // Empty flag if School_ID is not null
            }
        }
    } else {
        $words = preg_split('/\s+/', $skey);
        $get = PartSKUs::select('*')->where(function ($query) use ($sid) {
            $query->where('School_ID', '=', $sid)->orWhere('School_ID', '=', null);
        });

        foreach ($words as $word) {
            $get->where(function ($query) use ($word) {
                $query->where('Title', 'like', "%$word%");
            });
        }

        $get = $get->get();

        foreach ($get as $partSKU) {
            if ($partSKU->School_ID === null) {
                $partSKU->flag = ' '; // Assuming $schoolColorStatus is already defined
            } else {
                $partSKU->flag = 'schoolColorStatus'; // Empty flag if School_ID is not null
            }
        }
    }

    return $get;
}

    function getPartsById($id) {
        $get = PartSKUs::select('*')->where('ID', $id)->first();

        if ($get->School_ID === null) {
            $get->flag = ' '; // Assuming $schoolColorStatus is already defined
        } else {
            $get->flag = 'schoolColorStatus'; // Empty flag if School_ID is not null
        }
        return $get;
    }
    
}
