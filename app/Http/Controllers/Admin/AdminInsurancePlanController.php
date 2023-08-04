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
use App\Models\InsurancePlan;
use App\Models\ProductsForInsurancePlan;

class AdminInsurancePlanController extends Controller
{
    
    function AddInsurancePlan(Request $request)
    {        
                $planName = $request->input('planName');
                $deviceModels = $request->input('deviceModels');
                $numberofDevices = $request->input('numberofDevices');
                $takeHome = $request->input('takeHome');
                
                $insurancePlan = new InsurancePlan;
                $insurancePlan->Name  = $planName;
                $insurancePlan->DeviceModels  = $deviceModels;
                $insurancePlan->NumberofDevices  = $numberofDevices;
                $insurancePlan->TakeHome  = $takeHome;
                $insurancePlan->save();
                
                return 'success';
    }
    
    function getAllOtherProducts(){      
        $get = ProductsForInsurancePlan::all();
        return $get;
    }
    
}