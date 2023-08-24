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
use Illuminate\Http\Request as IlluminateRequest;
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
use App\Mail\adminSetPricingForInsurancePlanMailer;
use App\Mail\ContactUsMailer;
use App\Mail\InsurancePlanNegotiationMailer;
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
use App\Models\InsurancePlanEnrollment;
use App\Models\InsurancePlanEnrollmentLog;
use App\Models\CoverdServiceLog;
use App\Models\CoverdDeviceModelLog;
use App\Models\SchoolParentalCoverageCcSetting;
use App\Models\AdminCorporateStaffCcSetting;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Customer;
use ReCaptcha\ReCaptcha;
use Stripe\Exception\CardException;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
use App\Models\ContactUs;
use App\Http\Controllers\StripController;

class AdminInsurancePlanController extends Controller {

    function AddUpdateInsurancePlan(Request $request) {
        $schoolID = $request->input('SchoolId');
        $schoolName = $request->input('SchoolName');
        $contactName = $request->input('ContactName');
        $contactEmail = $request->input('ContactEmail');
        $planName = $request->input('PlanName');
        $estimatedStudent = $request->input('EstimatedStudent');
        $prcofDevices = $request->input('PercentOfDevices');
        $deviceModels = $request->input('DeviceModels');
        $otherProductsArray = $request->input('OtherProducts');
        $planID = $request->input('PlanID');
        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        CoverdServiceLog::where('PlanID', $planID)->forceDelete();
        CoverdDeviceModelLog::where('PlanID', $planID)->forceDelete();
        if ($planID == 0) {
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
        } else {
            InsurancePlan::where('ID', $planID)->update(['PlanName' => $planName, 'ContactName' => $contactName, 'ContactEmail' => $contactEmail, 'EstimatedEnrollment' => $estimatedStudent, 'DevicesNotLeaveSchool' => $prcofDevices]);
            $insurancePlan = InsurancePlan::where('ID', $planID)->first();
            $deviceModelsnames = explode(',', $deviceModels);
            foreach ($deviceModelsnames as $name) {
                $checkModels = CoverdDeviceModelLog::where('PlanID', $insurancePlan->ID)->where('Device', $name)->first();
                if (empty($checkModels)) {
                    $coverdmodel = new CoverdDeviceModelLog;
                    $coverdmodel->Device = $name;
                    $coverdmodel->PlanID = $insurancePlan->ID;
                    $coverdmodel->save();
                }

                foreach ($otherProductsArray as $otherproduct) {
                    $checkServices = CoverdServiceLog::where('PlanID', $insurancePlan->ID)->where('ServiceID', $otherproduct['id'])->first();

                    if (empty($checkServices)) {
                        $coverdproduct = new CoverdServiceLog();
                        $coverdproduct->PlanID = $insurancePlan->ID;
                        $coverdproduct->ServiceID = $otherproduct['id'];
                        $coverdproduct->save();
                    }
                }
            }
        }
        return 'success';
    }

    function getAllOtherProducts() {
        $get = ProductsForInsurancePlan::all();
        return $get;
    }

    function getAllPlans($sid, $skey, $pflag) {
        $planQuery = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school');

        if ($sid != 'null') {
            $planQuery->where('SchoolID', $sid);
        }

        $statusMap = [
            1 => 'New_Added',
            2 => 'Admin_Approve',
            3 => 'Live',
            4 => 'Past',
            5 => 'Rejected'
        ];

        if ($pflag != 'null' && isset($statusMap[$pflag])) {
            $planQuery->where('Status', $statusMap[$pflag]);
        } else {
            $planQuery->whereIn('Status', $statusMap);
        }

        if ($skey != 'null') {
            $planQuery->where(function ($query) use ($skey) {
                $query->where('PlanName', 'like', '%' . $skey . '%')
                        ->orWhere('SchoolName', 'like', '%' . $skey . '%')
                        ->orWhere('ContactName', 'like', '%' . $skey . '%')
                        ->orWhere('ContactEmail', 'like', '%' . $skey . '%')
                        ->orWhere('Price', 'like', '%' . $skey . '%')
                        ->orWhereHas('coverdServices.services', function ($innerQuery) use ($skey) {
                            $innerQuery->where('Name', 'like', '%' . $skey . '%');
                        });
            });
        }
        $plan = $planQuery->orderByDesc('ID')->get();
        $today = Carbon::today();
        foreach ($plan as $data) {
            if (Carbon::parse($data->EndDate)->lt($today)) {
                $data->Url = null;
            }
            if ($data->Status == 'New_Added') {
                $data->color_code = '#FDFAE4';
            } elseif ($data->Status == 'Admin_Approve') {
                $data->color_code = '#EFF4FF';
            } elseif ($data->Status == 'Live') {
                $data->color_code = '#E7FEF6';
            } elseif ($data->Status == 'Past') {
                $data->color_code = '#FFD3DD';
            } elseif ($data->Status == 'Rejected') {
                $data->color_code = '#F9BDB5';
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

    function createAndStoreInsurancePlanPdf($planid) {
        $data = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school')->where('ID', $planid)->first();
        $filename = 'InsurancePlan/' . $planid . '_' . time() . '.pdf';
        $pdf = PDF::loadView('insurancePlanPdf', ['data' => $data]);
        Storage::disk('public')->put($filename, $pdf->output());
        InsurancePlan::where('ID', $planid)->update(['Pdf' => $filename]);
    }

    function setPlanServicesPrice(Request $request) {
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
            InsurancePlan::where('ID', $planID)->update(['Price' => $totalPrice, 'Status' => 'Admin_Approve']);
        }

        $ccRecipients = NotificationEventsLog::where('EventID', 9)->pluck('UserID')->all();
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
                Mail::to($staffmember->email)->send(new adminSetPricingForInsurancePlanMailer($data));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        }
        $tdata = $this->createAndStoreInsurancePlanPdf($planID);

        return 'success';
    }

    function confirmPlan(Request $request) {
        $schoolId = $request->input('SchoolId');
        $planId = $request->input('PlanId');
        $flag = $request->input('Flag');
        $schoolName = $request->input('SchoolName');
        $formattedSchoolName = strtolower(str_replace(' ', '-', $schoolName));
        $formattedPlanName = strtolower(str_replace(' ', '-', $request->input('PlanName')));
        $url = 'plan/' . $formattedSchoolName . '/' . $formattedPlanName . '/' . $request->input('PlanNum');
        $startDate = $request->input('StartDate');
        $carbonStartDate = Carbon::createFromFormat('Y-m-d', $startDate);
        $endYear = $carbonStartDate->year + 1;
        $carbonEndDate = Carbon::create($endYear, 6, 30);
        $endDate = $carbonEndDate->format('Y-m-d');
        if ($flag == 1) {
            InsurancePlan::where('ID', $planId)->update(['Status' => 'Live', 'Url' => $url, 'StartDate' => $startDate, 'EndDate' => $endDate]);
        } else {
            $negotiatedPrice = $request->input('NegotiatedPrice');
            InsurancePlan::where('ID', $planId)->update(['Status' => 'Rejected', 'NegotiatedPrice' => $negotiatedPrice, 'StartDate' => $startDate, 'EndDate' => $endDate]);
        }

        $ccRecipients = NotificationEventsLog::where('EventID', 8)->pluck('UserID')->all();
        $plan = InsurancePlan::where('ID', $planId)->first();
        $school = School::where('ID', $schoolId)->first();

        $recipients = $ccRecipients;
        $recipients[] = $plan->ContactEmail;

        $data = [
            'school_name' => $school->name,
            'plannum' => $plan->PlanNum,
            'planname' => $plan->PlanName,
            'plancreateddate' => $plan->created_at->format('m-d-y'),
        ];

        foreach ($recipients as $recipient) {
            if ($recipient === $plan->ContactEmail) {
                $name = $plan->ContactName;  // Assuming there's a ContactName column in the plan table
                $email = $plan->ContactEmail;
            } else {
                $staffmember = User::where('id', $recipient)->first();
                $name = $staffmember->first_name . ' ' . $staffmember->last_name;
                $email = $staffmember->email;
            }

            try {
                if ($flag == 1) {
                    Mail::to($staffmember->email)->send(new SchoolSideParentalCoverageMailer($data));
                } else {
                    Mail::to($staffmember->email)->send(new InsurancePlanNegotiationMailer($data));
                }
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        }

        return 'success';
    }

    function getPlanByPlanNum($planmum) {
        $plan = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school.logo')->where('PlanNum', $planmum)->first();
        $today = Carbon::today();
        if (Carbon::parse($plan->EndDate)->lt($today)) {
            $plan->Url = null;
        }
        return $plan;
    }

    function purchasePlan(Request $request) {
        $studentNum = $request->input('StudentNo');
        $email = $request->input('Email');
        $paidAmount = $request->input('Total');
        $schoolID = $request->input('SchoolID');
        $grade = $request->input('Grade');
        $planID = $request->input('PlanId');
        $otherServiceArray = $request->input('OtherProducts');
        $stripController = new StripController();
        $paymentDataResponse = $stripController->teststrip($request);
        $paymentData = $paymentDataResponse->original;        
        if ($paymentData['msg'] == 'error') {
            return response()->json(['msg' => 'stripe payment have some issue', 'status' => 'error']);
        } else {
            $enrollment = new InsurancePlanEnrollment;
            $enrollment->SchoolID = $schoolID;
            $enrollment->PlanID = $schoolID;
            $enrollment->StudentID = $studentNum;
            $enrollment->PaidAmount = $paidAmount;
            $enrollment->StripCustomerID = $paymentData['customer_id'];
            $enrollment->save();

            foreach ($otherServiceArray as $otherService) {
                $enrollmentLog = new InsurancePlanEnrollmentLog;
                $enrollmentLog->StudentID = $studentNum;
                $enrollmentLog->ServiceID = $otherService['id'];
                $enrollmentLog->PlanID = $planID;
                $enrollmentLog->save();
                
                Student::where('Student_num',$studentNum)->where('School_ID',$schoolID)->update(['stripeCustomerID'=>$paymentData['customer_id']]);
            }
            return response()->json(['msg' => 'payment successful', 'status' => 'success']);
        }
    }

    function allPlansForAdmin($sid, $skey, $pflag) {
        $planQuery = InsurancePlan::with('coverdDeviceModels', 'coverdServices.services', 'school');

        if ($sid != 'null') {
            $planQuery->where('SchoolID', $sid);
        }

        $statusMap = [
            1 => 'New_Added',
            2 => 'Rejected',
            3 => 'Admin_Approve'
        ];

        if ($pflag != 'null' && isset($statusMap[$pflag])) {
            $planQuery->where('Status', $statusMap[$pflag]);
        } else {
            $planQuery->whereIn('Status', $statusMap);
        }

        if ($skey != 'null') {
            $planQuery->where(function ($query) use ($skey) {
                $query->where('PlanName', 'like', '%' . $skey . '%')
                        ->orWhere('SchoolName', 'like', '%' . $skey . '%')
                        ->orWhere('ContactName', 'like', '%' . $skey . '%')
                        ->orWhere('ContactEmail', 'like', '%' . $skey . '%')
                        ->orWhere('Price', 'like', '%' . $skey . '%')
                        ->orWhereHas('coverdServices.services', function ($innerQuery) use ($skey) {
                            $innerQuery->where('Name', 'like', '%' . $skey . '%');
                        });
            });
        }
        $plan = $planQuery->orderByDesc('ID')->get();
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

    function contactUs(Request $request) {
        $studentNum = $request->input('StudentNumber');
        $question = $request->input('Question');
        $feedBack = $request->input('Feedback');
        $planNum = $request->input('Plannumber');
        $schoolId = $request->input('Schoolid');
        $captcha = $request->input('Captcha');
        $recaptcha = new ReCaptcha(env('RECAPTCHA_SECRET_KEY'));
        $response = $request->input('Captcha');
        $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            $contactUs = new ContactUs();
            $contactUs->SchoolID = $schoolId;
            $contactUs->StudentNum = $studentNum;
            $contactUs->Question = $question;
            $contactUs->FeedBack = $feedBack;
            $contactUs->PlanNum = $planNum;
            $contactUs->save();

            $user = User::where('school_id', $schoolId)->where('access_type', 1)->orderBy('created_at', 'asc')->first();
            $planQuery = InsurancePlan::with('school')->where('PlanNum', $planNum)->first();

            $data = [
                'school_name' => $planQuery->school->name,
                'plannum' => $planQuery->PlanNum,
                'planname' => $planQuery->PlanName,
                'plancreateddate' => $planQuery->created_at->format('m-d-y'),
                'name' => $user->first_name . ' ' . $user->last_name
            ];

            try {
                Mail::to($user->email)->send(new ContactUsMailer($data));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }

            return response()->json([
                        'status' => 'success'
            ]);
        } else {
            return response()->json([
                        'status' => 'error',
                        'msg' => 'something went wrong'
            ]);
        }
    }

}
