<?php

namespace App\Http\Controllers;

use Stripe\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use App\Models\Student;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Exception\StripeException;

class StripController extends Controller {

    public function createToken(Request $request) {
        Stripe::setApiKey('sk_test_51MgSiQHh3sGh6F3zR8jFp5nXKlsnc9HM6JedX9Gb2PDjnu8EtoOFNORXluSA94PusNdSrsWPRGYeYOn6gii2epJx001un1cc2y');

        try {
            $token = Token::create([
                        'card' => [
                            'number' => $request->input('card_number'),
                            'exp_month' => $request->input('exp_month'),
                            'exp_year' => $request->input('exp_year'),
                            'cvc' => $request->input('cvc'),
                        ],
            ]);

            return response()->json(['token' => $token->id]);
        } catch (\Exception $e) {
            // Handle the error appropriately
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    function teststrip(Request $request) {
        $studentNum = $request->input('StudentNo');
        $stripToken = $request->input('token');
        $email = $request->input('Email');
        $student = Student::where('Student_num', $studentNum)->where('School_ID',183)->first();
        $deposit = $request->input('Total');

        Stripe::setApiKey('sk_test_51NhtnpSCz88rM4RaKqDM74cq1xtmW2m9xpHNPTsRbhGTakeqiZ4Naig38KNFLlyeK6CwbIY6oNvjoFpJPgaD7w9700jZM2RPSu');

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found']);
        }

        try {

            if ($student->stripeCustomerID) {
                $stripeCustomer = Customer::retrieve($student->stripeCustomerID);
            } else {
                $stripeCustomer = Customer::create([
                            'source' => $stripToken,
                            'name' => $student->name,
                            'email' => $email
                ]);

                $student->update(['stripeCustomerID' => $stripeCustomer->id]);
            }

            $paymentIntent = PaymentIntent::create([
                        "amount" => $deposit * 100,
                        "currency" => "inr",
                        "customer" => $stripeCustomer->id,
                        "description" => "Payment for Student - " . $student->name,
            ]);

            if ($paymentIntent->status == 'requires_action' && $paymentIntent->next_action->type == 'use_stripe_sdk') {
                return response()->json(['requires_action' => true, 'payment_intent_client_secret' => $paymentIntent->client_secret]);
            }

            if ($paymentIntent->status == 'succeeded') {
                $enrollmentDetails = new InsurancePlanEnrollment();
                $enrollmentDetails->SchoolID = 168;
                $enrollmentDetails->PlanID = 8;
                $enrollmentDetails->save();

                return response()->json(['success' => true, 'message' => 'Payment successful!']);
            }
        } catch (StripeException $e) {
            return response()->json(['success' => false, 'message' => 'Stripe Error: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => 'Unknown error occurred.']);
    }

}
