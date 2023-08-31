<?php

namespace App\Http\Controllers;

use App\Models\InsurancePlanEnrollment;
use Stripe\Stripe;
use Illuminate\Http\Request;
use App\Models\Student;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use App\Models\InsurancePlanEnrollmentLog;
use PDF;
use Illuminate\Support\Facades\Storage;
class StripController extends Controller
{
    function teststrip(Request $request)
    {
        $studentNum = $request->input('StudentNo');
        $stripToken = $request->input('token');
        $email = $request->input('Email');
        $deposit = $request->input('Total');
        $schoolId = $request->input('SchoolID');
 try{
     
 
        Stripe::setApiKey('sk_test_51NZoRmSJJr0Q4SKxWcia7EJVpQazViDSNRHqEInMNsqLw0qW05tL7TiuBGHv3VWcWV7rttgJbI5q0ooSTNQ1rCPG00O2KLnm7d');
        $student = Student::where('Student_num', $studentNum)->where('School_ID', $schoolId)->first();
        if ($student) {
            $combinename = $request->input('sFirstName') . ' ' . $request->input('sLastName');
            $studentStripeID = $student->stripeCustomerID;
            if ($studentStripeID != '') {
                $stripeCustomer = Customer::retrieve($studentStripeID);
            } else {
                $stripeCustomer = Customer::create([
                    'source' => $stripToken,
                    'name' => $combinename,
                    'email' => $email,
                ]);
                
                Student::where('Student_num', $studentNum)->update(['stripeCustomerID' => $stripeCustomer->id]);
            }
            $customer = Customer::retrieve($stripeCustomer->id);
            $paymentMethods = PaymentMethod::all([
                'customer' => $customer->id,
                'type' => 'card',
            ]);
            if (count($paymentMethods->data) === 0) {
                return response()->json(['success' => false, 'message' => 'No payment method found for the customer.']);
            }

            $paymentMethod = $paymentMethods->data[0];
            $paymentIntent = PaymentIntent::create([
                'amount' => $deposit * 100,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'customer' => $stripeCustomer->id,
                'payment_method' => $paymentMethod->id,
            ]);         
            return response()->json([
                'msg' => 'success',
                'message' => 'Payment successful!',
                'customer_id' => $customer->id
            ]);
        } else {
            return response()->json(['msg' => 'error', 'message' => 'Student not found.']);
        }
        } catch (\Exception $e) {
         return response()->json(['msg' => 'error', 'message' => $e->getMessage()]);               
                    }
    }

    function getstripedata($custid,$schoolid,$planid)
    {
        Stripe::setApiKey('sk_test_51NZoRmSJJr0Q4SKxWcia7EJVpQazViDSNRHqEInMNsqLw0qW05tL7TiuBGHv3VWcWV7rttgJbI5q0ooSTNQ1rCPG00O2KLnm7d');
        $customer = Customer::retrieve($custid);
        $student = Student::where('stripeCustomerID', $custid)->where('School_ID',$schoolid)->first();
        $enrollment = InsurancePlanEnrollment::with('plan','student','school')->where('StripCustomerID',$custid)->first();
        $enrollmentLog = InsurancePlanEnrollmentLog::where('PlanID',$planid)->get();
        $studentnumber = $student->Student_num;
        $paymentMethods = PaymentMethod::all([
            'customer' => $customer->id,
            'type' => 'card',
        ]);
        if (!empty($paymentMethods->data)) {
            $paymentMethod = $paymentMethods->data[0];
            $cardBrand = $paymentMethod->card->brand;
            $lastFour = $paymentMethod->card->last4;
        } else {
            $cardBrand = null;
            $lastFour = null;
        }
        $lastPaymentDate = $student->updated_at;    
        $filename = 'InsurancePlanEnrollment/' .$custid.'/'. time() . '.pdf';
    
        $pdf = PDF::loadView('InsurancePlanEnrollmentPdf', ['customer' => $customer, 'enrollment' => $enrollment,'cardName'=>$cardBrand,'cardNum'=>$lastFour,'paymentMethod'=> $paymentMethod,'enrollmentLog'=>$enrollmentLog]);
        Storage::disk('public')->put($filename, $pdf->output());
        // return $filename;
        InsurancePlanEnrollment::where('StripCustomerID',$custid)->where('PlanID',$planid)->update(['Pdf'=>$filename]);
        return response()->json([
            'success' => true,
            'total' =>  $enrollment->PaidAmount,
            'name' => $customer->name,
            'email' => $customer->email,
            'customer_id' => $customer->id,
            'payment_method_last_four' => $lastFour,
            'invoice_number' => $customer->invoice_prefix,
            'payment_success_date' => $lastPaymentDate,
            'card_name' => $cardBrand,
            'student_number' => $studentnumber,
            'url'=>$filename,
            'plan'=>$enrollment->plan->PlanName,
        ]);
    }

}
