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
use App\Mail\SchoolSideParentalCoverageMailer;
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
use App\Models\SchoolParentalCoverageCcSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
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
        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $insurancePlan = new InsurancePlan;

        $insurancePlan->SchoolID = $schoolID;
        $insurancePlan->PlanName = $planName;
        $insurancePlan->SchoolName = $schoolName;
        $insurancePlan->ContactName = $contactName;
        $insurancePlan->ContactEmail = $contactEmail;
        $insurancePlan->EstimatedEnrollment = $estimatedStudent;
        $insurancePlan->DevicesNotLeaveSchool = $prcofDevices;
        $insurancePlan->PlanNum = $randomString; 
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
        foreach ($plan as $data) {
            if ($data->Status == 'New_Added') {
                $data->color_code = '#FDFAE4';
            } elseif ($data->Status == 'Admin_Approve') {
                $data->color_code = '#EFF4FF';
            } elseif ($data->Status == 'Live') {
                $data->color_code = '#E7FEF6';
            } else {
                $data->color_code = '#FFD3DD';
            }
        }

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
            if ($used) {
                $servicePrice = CoverdServiceLog::where('PlanID', $plan->ID)
                                ->where('ServiceID', $service->ID)
                                ->first()
                        ->Price;
            } else {
                $servicePrice = $service->Price;
            }
            return [
        'ID' => $service->ID,
        'ServiceName' => $service->Name,
        'Price' => $servicePrice,
        'used' => $used ? 'yes' : 'no',
            ];
        });

        $plan->services = $planServices;
        $plan->makeHidden(['updated_at', 'deleted_at', 'coverdServices']);

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
            $totalPrice = CoverdServiceLog::where('PlanID', $planID)->sum('Price');
            InsurancePlan::where('ID',$planID)->update(['Price'=>$totalPrice,'Status'=>'Admin_Approve']);
        }          
        return  'success';
    }
    
    function confirmPlan(Request $request) {
        $schoolName = $request->input('SchoolName');
        $formattedSchoolName = strtolower(str_replace(' ', '-', $schoolName));
        $formatePlanName = strtolower(str_replace(' ', '-', $request->input('PlanName')));
        $url = 'plan/' . $formattedSchoolName . '/' . $formatePlanName . '/' . $request->input('PlanNum');
        $data = InsurancePlan::where('ID', $request->input('PlanId'))->update(['Status' => 'Live', 'Url' => $url]);
        $ccRecipients = SchoolParentalCoverageCcSetting::where('SchoolID', $request->input('SchoolId'))->pluck('UserID')->all();

        foreach ($ccRecipients as $recipent) {
            $staffmember = User::where('id', $recipent)->first();
            $plan = InsurancePlan::where('ID', $request->input('PlanId'))->first();
            $school = School::where('ID', $request->input('SchoolId'))->first();
            $data = [
                'name' => $staffmember->first_name . '' . $staffmember->last_name,
                'school_name' => $school->name,
                'plannum' => $plan->PlanNum,
                'planname' => $plan->PlanName,
                'plancreateddate' => $plan->created_at->format('m-d-y'),
            ];
            try {
                Mail::to($staffmember->email)->send(new SchoolSideParentalCoverageMailer($data));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        }

        return 'success';
    }
    
    function getPlanByPlanNum($planmum){
         $plan = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school.logo')->where('PlanNum',$planmum)->first();
        return $plan; 
    }

}
