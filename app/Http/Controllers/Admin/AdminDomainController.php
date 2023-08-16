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



class AdminDomainController extends Controller {
    
    function AddUpdateDomain(Request $request) {
        if ($request->input('Id') == 0) {
           
            $names = explode(',', $request->input('name'));
             
            foreach ($names as $name) {
                $checkDomain = Domain::where('Name', $name)->first();
                if ($checkDomain == null) {
                    $domain = new Domain;
                    $domain->Name = trim($name);
                    $domain->Status = 'active';
                    $domain->save();
                }
            }
            return response()->json(collect([
                        'response' => 'success',
            ]));
        } else {
            if ($request->input('flag') == 1) {
                Domain::where('ID', $request->input('Id'))->update(['Status' => 'active']);
                return response()->json(collect([
                            'response' => 'success',
                ]));
            } elseif ($request->input('flag') == 2) {
                Domain::where('ID', $request->input('Id'))->update(['Status' => 'deactive']);
                return response()->json(collect([
                            'response' => 'success',
                ]));
            } else {
                $checkDomain = Domain::where('Name',$request->input('name'))->first();
                if (isset($checkDomain)) {
                    return response()->json(collect([
                                'response' => 'error',
                                'msg' => 'email already exists'
                    ]));
                } else {
                    Domain::where('ID', $request->input('Id'))->update(['Name' => $request->input('name')]);
                    return response()->json(collect([
                                'response' => 'success',
                    ]));
                }
            }
        }
    }

function AllDomain($skey, $flag,$page,$limit)
    {
        if ($skey == 'null') {
            $get = Domain::orderBy('Name', $flag)->paginate($limit, ['*'], 'page', $page);
        } else {
            $get = Domain::where(function ($query) use ($skey) {
                $query->where('Name', 'LIKE', "%$skey%");
            })->orderBy('Name', $flag)->paginate($limit, ['*'], 'page', $page);
        }

        return $get;
    }
    
     function DomainDataByID($id){
        $get = Domain::where('ID',$id)->first();
        return $get;
    }
    
    
    
}