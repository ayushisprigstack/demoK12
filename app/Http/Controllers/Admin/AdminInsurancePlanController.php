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
use App\Models\CoverdServiceLog;
use App\Models\CoverdDeviceModelLog;

class AdminInsurancePlanController extends Controller {

    function AddInsurancePlan(Request $request) {
        $schoolID = $request->input('SchoolId');
        $schoolName = $request->input('SchoolName');
        $contactName = $request->input('ContactName');
        $contactEmail = $request->input('ContactEmail');
        $planName = $request->input('PlanName');
        $estimatedStudent = $request->input('EstimatedStudent');
        $prcofDevices = $request->input('PercentOfDevices');
        $deviceModels = $request->input('DeviceModels');
        $otherProductsArray = $request->input('OtherProducts');

        $insurancePlan = new InsurancePlan;

        $insurancePlan->SchoolID = $schoolID;
        $insurancePlan->PlanName = $planName;
        $insurancePlan->SchoolName = $schoolName;
        $insurancePlan->ContactName = $contactName;
        $insurancePlan->ContactEmail = $contactEmail;
        $insurancePlan->EstimatedEnrollment = $estimatedStudent;
        $insurancePlan->DevicesNotLeaveSchool = $prcofDevices;
        $insurancePlan->save();

        $deviceModelsnames = explode(',', $deviceModels);
        foreach ($deviceModelsnames as $name) {

            $coverdmodel = new CoverdDeviceModelLog;
            $coverdmodel->Device = $name;
            $coverdmodel->PlanID = $insurancePlan->id;
            $coverdmodel->save();
        }
        foreach ($otherProductsArray as $otherproduct) {
            $coverdproduct = new CoverdServiceLog();
            $coverdproduct->PlanID = $insurancePlan->id;
            $coverdproduct->ServiceID = $otherproduct['id'];
            $coverdproduct->save();
        }
        return 'success';
    }

    function getAllOtherProducts() {
        $get = ProductsForInsurancePlan::all();
        return $get;
    }

    function getAllPlans($sid) {
        $plan = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school')->where('SchoolID', $sid)->orderByDesc('ID')->get();
        return $plan;
    }

    function getPlanById($pid) {
        $plan = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school')->where('ID', $pid)->first();
        return $plan;
    }

    function getAllPlanForAdmin($pid) {
        $plan = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school')
                ->where('ID', $pid)
                ->orderByDesc('ID')
                ->first();

        $allServices = ProductsForInsurancePlan::all();

        $planServices = $allServices->map(function ($service) use ($plan) {
            $used = $plan->coverdServices->pluck('ServiceID')->contains($service->ID);
            return [
        'ID' => $service->ID,
        'ServiceName' => $service->Name,
        'used' => $used ? 'yes' : 'no',
            ];
        });

        $plan->services = $planServices;
        $plan->makeHidden(['created_at', 'updated_at', 'deleted_at', 'coverdServices']);

        return response()->json($plan);
    }
    
    function setPlanServicesPrice(Request $request){
         $schoolID = $request->input('SchoolId');
        $planID = $request->input('PlanId');
        $servicesArray = $request->input('Services');
        foreach ($servicesArray as $service) {
            $checkData = CoverdServiceLog::where('PlanID', $planID)->where('ServiceID', $service['id'])->first();
            if (isset($checkData)) {
                CoverdServiceLog::where('PlanID', $planID)->where('ServiceID', $service['id'])->update(['Price' => $service['price']]);
            } else {
                $coverdproduct = new CoverdServiceLog();
                $coverdproduct->PlanID = $planID;
                $coverdproduct->ServiceID = $service['id'];
                $coverdproduct->Price = $service['price'];
                $coverdproduct->save();
            }
        }
        
        return  'success';
    }
    
   

}
