<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use App\Models\InvoiceLog;
use App\Models\School;
use App\Models\Personal;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketsAttachment;
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
use App\Models\PartSKUs;
use App\Exceptions\InvalidOrderException;
use Illuminate\Support\Facades\Storage;
use App\Mail\SchoolToAdminMailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
class SchoolInvoiceController extends Controller
{
    function showInvoice($sid,$skey){
       $invoiceLogs = InvoiceLog::where('School_Id', $sid)->whereNotNull('Batch_ID')->get();
       $batch_data = array();
       foreach($invoiceLogs as $data){          
           $batchdata = CloseTicketBatch::where('ID',$data->Batch_ID)->first();
           array_push($batch_data,['BatchId'=>$data->Batch_ID,'BatchName'=>$batchdata->Name,'Amount'=>$batchdata->Amount,'InvoiceNum'=>$data->ID,'Status'=>$data->Payment_Status,'TransactionId'=>$data->ChequeNo]);
       }
       if($skey == 'null'){
          return $batch_data; 
       }else{
            $searchedArray = array_filter($batch_data, function ($obj) use ($skey) {        
           return  strpos(strtolower($obj['BatchName']), $skey) !== false
                   || strpos(strtolower($obj['InvoiceNum']), $skey) !== false  
                || strpos(strtolower($obj['Status']), $skey) !== false  ;         
          });
             return  response()->json(array_values($searchedArray));  
       }
       
    }
    
    function paymentDetailsSchoolSide(Request $request){
        
             $batchId = $request->input('batchId');
             $invoiceId = $request->input('invoiceId');
             $chequeNo = $request->input('chequeNo');
             $schoolId = $request->input('schoolId'); 
            
             $invoiceLogs = InvoiceLog::where('School_Id', $schoolId)->where('Batch_ID',$batchId)->where('ID',$invoiceId)->update(['Payment_Status'=>'In Progress','ChequeNo'=>$chequeNo,'Invoice_Receive'=>1]);  
            //mail 
             $batch = CloseTicketBatch::where('ID',$batchId)->first();
             $schoolData = School::where('ID',$schoolId)->first();
             $batchName = $batch->Name;
             $data = [
                'batchId'=> $batchId,
                 'invoiceId'=>$invoiceId,
                 'chequeNo'=>$chequeNo,
                 'schoolName' => $schoolData->name,
                 'batchName'=>$batchName
             ];            
              try {
            Mail::to('Info@k12techrepairs.com')->send(new SchoolToAdminMailer($data));
        } catch (\Exception $e) {
            Log::error("Mail sending failed: " . $e->getMessage());
        }
        return 'success';
    }
    
    function downloadReceipt($invoiceId){        
        $get = InvoiceLog::where('ID',$invoiceId)->first();
        return $get->Receipt;
    }
}
