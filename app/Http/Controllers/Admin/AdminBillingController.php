<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\CloseTicketBatchLog;
use App\Models\CloseTicketBatch;
use App\Models\InvoiceLog;
use App\Models\Location;
use App\Models\TechnicianLocation;
use App\Models\SchoolAddress;

class AdminBillingController extends Controller {
  
    function fetchAllSchools($skey, $uid,$sortbykey, $sortby) {
        $user = User::where('id', $uid)->first();
        if (isset($user)) {
            if ($user->access_type == 6) {
                $getLocation = TechnicianLocation::where('Technician', $user->id)->pluck('Location')->all();
                $schools = School::whereIn('location', $getLocation)->orderBy('ID', 'desc')->get();
            } else {
                $schools = School::orderBy('ID', 'desc')->get();
            }
            $schoolDataArray = [];
            foreach ($schools as $school) {
                $location = Location::find($school['location']);
                $schoolData = [
                    'id' => $school['ID'],
                    'schoolName' => $school['name'],
                    'openTickets' => 0,
                    'closeTickets' => 0,
                    'totalTickets' => 0,
                    'contactEmail' => null,
                    'contactName' => null,
                    'contactID'=>null,
                    'accessToken' => null,
                    'isChecked' => false,
                    'location' => $school['location'],
                    'locationName' => $location->Location ?? null,
                    'shippingType'=>$school['shppingType'],
                ];

                $user = User::where('School_ID', $school['ID'])->first();

                if ($user) {
                    $schoolData['contactEmail'] = $user->email;
                    $schoolData['contactName'] = $user->first_name . ' ' . $user->last_name;
                    $schoolData['accessToken'] = $user->remember_token ?? null;
                    $schoolData['contactID'] = $user->id;
                }

                $schoolDataArray[] = $schoolData;
            }
                        
            foreach ($schoolDataArray as &$schoolData) {
                $schoolID = $schoolData['id'];
                $schoolData['openTickets'] = Ticket::where('school_id', $schoolID)->whereIn('ticket_status', [1, 3, 4, 5, 6])->count();
                $schoolData['closeTickets'] = Ticket::where('school_id', $schoolID)->whereIn('ticket_status', [2, 7, 8])->count();
                $schoolData['totalTickets'] = Ticket::where('school_id', $schoolID)->count();
            }
            
            if ($sortbykey == 1) {
                usort($schoolDataArray, function ($a, $b) use ($sortby) {
                    return $sortby == 'as' ? strcmp($a['schoolName'], $b['schoolName']) : strcmp($b['schoolName'], $a['schoolName']);
                });
            } elseif ($sortbykey == 2) {
                usort($schoolDataArray, function ($a, $b) use ($sortby) {
                    return $sortby == 'as' ? strcmp($a['locationName'], $b['locationName']) : strcmp($b['locationName'], $a['locationName']);
                });
            } elseif ($sortbykey == 3) {
                usort($schoolDataArray, function ($a, $b) use ($sortby) {
                return $sortby == 'as' ? $a['openTickets'] <=> $b['openTickets'] : $b['openTickets'] <=> $a['openTickets'];
            });
            }elseif ($sortbykey == 4) {
                usort($schoolDataArray, function ($a, $b) use ($sortby) {
                return $sortby == 'as' ? $a['totalTickets'] <=> $b['totalTickets'] : $b['totalTickets'] <=> $a['totalTickets'];
            });
            }
            
            if ($skey == 'null') {
                return response()->json(
                                collect([
                            'response' => 'success',
                            'Schools' => $schoolDataArray,
                ]));
            } else {
                $skey = strtolower($skey);  // convert the search key to lowercase

                $searchedArray = array_filter($schoolDataArray, function ($obj) use ($skey) {
                    return strpos(strtolower($obj['schoolName']), $skey) !== false ||
                    strpos(strtolower($obj['contactEmail']), $skey) !== false ||
                    strpos(strtolower($obj['openTickets']), $skey) !== false ||
                    strpos(strtolower($obj['totalTickets']), $skey) !== false ||
                    strpos(strtolower($obj['contactName']), $skey) !== false ||
                    strpos(strtolower($obj['locationName']), $skey) !== false;
                });
                return response()->json(
                                collect([
                            'response' => 'success',
                            'Schools' => array_values($searchedArray),
                ]));
            }
        }
    }

    function updateSchoolData(Request $request) {
        School::where('ID', $request->input('schoolId'))->update(['location' => $request->input('location'), 'shppingType' => $request->input('shppingType')]);
        SchoolAddress::where('SchoolID',$request->input('schoolId'))->update(['Location'=>$request->input('location')]);
        return 'success';
    }

    function AllBatchData($sid, $key, $skey, $sflag, $page, $limit)
    {
        $query = CloseTicketBatch::with('invoice', 'batchLog.ticket.ticketAttachments')->where('School_ID', $sid)->select('ID', 'Amount', 'Name', 'Date', 'Notes', 'FedExQr', 'TrackingNum');
        $allBatch = $query->get();
        $allBatch->each(function ($batch) {
            $batch->InvoiceNum = $batch->invoice[0]['ID'];
            $batch->InvoiceStatus = $batch->invoice[0]['Payment_Status'];
            $batch->TransactionId = $batch->invoice[0]['ChequeNo'];

            $total = 0;
            foreach ($batch->batchLog as $batchLog) {
                $subtotal = 0;
                foreach ($batchLog->ticket->ticketAttachments as $ticketAttachment) {
                    if ($ticketAttachment['Parts_Flag'] == 1) {
                        $amount = $ticketAttachment['Parts_Price'] * $ticketAttachment['Quantity'];
                        $subtotal += $amount;
                    }
                    $batch->SubTotal = $subtotal;

                }
                $batch->total += (int) $batchLog['Batch_Sub_Total'];
            }

        });
        if ($key != 'null') {
            $searchedArray = $allBatch->filter(function ($batch) use ($key) {
                return strpos(strtolower($batch['InvoiceNum']), strtolower($key)) !== false ||
                    strpos(strtolower($batch['InvoiceStatus']), strtolower($key)) !== false ||
                    strpos(strtolower($batch['TransactionId']), strtolower($key)) !== false ||
                    strpos(strtolower($batch['Name']), strtolower($key)) !== false ||
                    strpos(strtolower($batch['Date']), strtolower($key)) !== false;
            });
            $allBatch = $searchedArray->values();
        }

        if ($skey == 1) {
            $allBatch = $sflag == 'as' ? $allBatch->sortBy('Name') : $allBatch->sortByDesc('Name');
        } elseif ($skey == 2) {
            $allBatch = $sflag == 'as' ? $allBatch->sortBy('InvoiceNum') : $allBatch->sortByDesc('InvoiceNum');
        } elseif ($skey == 3) {
            $allBatch = $sflag == 'as' ? $allBatch->sortBy('SubTotal') : $allBatch->sortByDesc('SubTotal');
        } else {
            $allBatch = $allBatch->sortByDesc('ID');
        }
        $totalCount = $allBatch->count();
        $utilizerData = collect($allBatch)->forPage($page, $limit);
        $collection = $utilizerData->values();
        foreach ($collection as $result) {
            $result->makeHidden(['Amount', 'invoice', 'ticketAttachments']);
        }
        return response()->json([
            'status' => 'success',
            'msg' => $collection,
            'count' => $totalCount
        ]);
    }
      
         
    
}
