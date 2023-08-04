<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\TicketsAttachment;
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMailer;
use App\Mail\AdminToSchoolMailer;
use App\Mail\AdminToSchoolPaymentFailMailer;
use App\Models\InvoiceLog;
use App\Models\PartSKUs;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use App\Models\DeviceIssue;
use App\Models\Location;
use App\Models\SchoolAddress;


class AdminLocationController extends Controller {

    function AddUpdateLocation(Request $request) {
        if ($request->input('Id') == 0) {
            $names = explode(',', $request->input('name'));
            foreach ($names as $name) {
                $location = new Location;
                $location->Location = trim($name); // Trim any leading/trailing spaces
                $location->Status = 'Active';
                $location->save();
            }
            return 'success';
        } else {
            if ($request->input('flag') == 1) {
                Location::where('ID', $request->input('Id'))->update(['Status' => 'Active']);
            } elseif ($request->input('flag') == 2) {
                Location::where('ID', $request->input('Id'))->update(['Status' => 'Deactive']);
            } else {
                Location::where('ID', $request->input('Id'))->update(['Location' => $request->input('name')]);
            }
            return 'success';
        }
    }

    function LocationAddress($skey,$sortkey,$sortflag) {
    $defaultAddress = Location::where('ID',1)->first();
    
    $sortField = '';       
    if ($sortkey == 1) {
        $sortField = 'Location';
    } elseif ($sortkey == 2) {
        $sortField = 'StreetLine1';
    } elseif ($sortkey == 3) {
        $sortField = 'City';
    } elseif ($sortkey == 4) {
        $sortField = 'PostalCode';
    } elseif ($sortkey == 5) {
        $sortField = 'StateOrProvinceCode';    
    }else{
        $sortField = 'ID';
        $sortflag = 'desc';
    }
        
    
    if ($skey == 'null') {
        $get = Location::orderBy($sortField,$sortflag)->get();
    } else {
        $get = Location::where(function ($query) use ($skey) {
            $query->where('StreetLine1', 'LIKE', "%$skey%")                               
                    ->orWhere('City', 'LIKE', "%$skey%")
                    ->orWhere('PostalCode', 'LIKE', "%$skey%")
                    ->orWhere('Location','LIKE', "%$skey%")
                    ->orWhere('Location','LIKE', "%$skey%")
                    ->orWhere('StateOrProvinceCode', 'LIKE', "%$skey%");
        })->orderBy($sortField,$sortflag)->get();
    }

    return response()->json(
        collect([
            'response' => 'success',
            'orignalAddress'=>$get,
            'defaultAddress'=>$defaultAddress
        ]));  
}


    function LocationDataByID($id) {
        $get = Location::where('ID', $id)->first();
        return $get;
    }

    function AddUpdateLocationAddress(Request $request)
    {         
        $streetLine1 = $request->input('StreetLine1');       
        $city = $request->input('City');
        $stateOrProvinceCode = $request->input('StateOrProvinceCode');
        $postal = $request->input('PostalCode');
        $id = $request->input('Id');
        $locationName = $request->input('LocationName');
        if ($id == 0) {
            $address =  new Location;
            $address->Location = $locationName;
            $address->StreetLine1 = $streetLine1;           
            $address->City = $city;
            $address->StateOrProvinceCode = $stateOrProvinceCode;
            $address->PostalCode = $postal;
            $address->CountryCode = 'US';
            $address->Status = 'Active';
            $address->save();
        } else {
            Location::where('ID', $id)->update(['Location' => $locationName, 'StreetLine1' => $streetLine1, 'City' => $city, 'StateOrProvinceCode' => $stateOrProvinceCode, 'PostalCode' => $postal]);
        }
        return 'success';
    }
  
}
