<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketStatusLog;
use App\Models\Student;
use App\Models\TicketIssue;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\QueryBuilder\QueryBuilder;
use Exception;
use App\Models\DeviceIssue;
use Illuminate\Support\Facades\DB;
use App\Models\ErrorLog;
use App\Helpers\Helper;
use App\Exceptions\InvalidOrderException;
use App\Models\StudentInventory;
use Carbon\Carbon;
use DateTime;
use App\Models\DeviceAllocationLog;
use ReCaptcha\ReCaptcha;

class CaptchaController extends Controller
{
    public function verifyRecaptcha(Request $request)
    {
        $response = $request->input('response');
        $recaptcha = new ReCaptcha(env('RECAPTCHA_SECRET_KEY'));
        $resp = $recaptcha->verify($response, $_SERVER['REMOTE_ADDR']);

        if ($resp->isSuccess()) {
            // reCAPTCHA verification successful
            return response()->json([
                'success' => true,
                'message' => 'reCAPTCHA verification successful.',
            ]);
        } else {
            // reCAPTCHA verification failed
            $errors = $resp->getErrorCodes();
            return response()->json([
                'success' => false,
                'message' => 'reCAPTCHA verification failed.',
                'errors' => $errors,
            ], 400);
        }
    }
}