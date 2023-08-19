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
use App\Models\TicketRepairLog;
use App\Models\SchoolBatch;
use App\Models\SchoolBatchLog;
use App\Http\Controllers\FedexController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
class AdminAllSchoolController extends Controller {

    public function AllTicketsForAdminPanel($sid, $gridflag, $key, $skey, $sflag, $bid) {
        if ($bid == 'null') {
            $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues.deviceIssue', 'ticketAttachments')
                    ->where('school_id', $sid)
                    ->get();
        } else {
            $batchdata = SchoolBatchLog::where('BatchID', $bid)->pluck('TicketID');
            $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues.deviceIssue', 'ticketAttachments')
                    ->whereIn('ID', $batchdata)
                    ->get();
        }


        $data->each(function ($ticket) {
            $inventoryManagement = $ticket->inventoryManagement;
            $studentInventory = $inventoryManagement->studentInventory;

            $ticket->Device_model = $inventoryManagement->Device_model ?? null;
            $ticket->serialNum = $inventoryManagement->Serial_number ?? null;
            $ticket->Student_id = $studentInventory->Student_ID ?? null;
            $student = $studentInventory?->student;
            $ticket->Studentname = $student ? ($student->Device_user_first_name . ' ' . $student->Device_user_last_name) ?? null : null;
            $ticket->Student_num = $studentInventory?->student?->Student_num ?? null;
            $ticket->Grade = $studentInventory?->student?->Grade ?? null;
            $ticket->Building = $studentInventory?->student?->Building ?? null;
            $ticket->Parent_guardian_name = $studentInventory?->student?->Parent_guardian_name ?? null;
            $ticket->Parent_phone_number = $studentInventory?->student?->Parent_phone_number ?? null;
            $ticket->Parent_Guardian_Email = $studentInventory?->student?->Parent_Guardian_Email ?? null;
            $ticket->Parental_coverage = $studentInventory?->student?->Parental_coverage ?? null;
            $ticket->TicketCreatedBy = $ticket->user->first_name . ' ' . $ticket->user->last_name ?? null;

            $ticket->issues = $ticket->ticketIssues->map(function ($ticketIssue) {
                        $issueId = $ticketIssue->issue_Id;
                        $deviceIssue = DeviceIssue::find($issueId);
                        return $deviceIssue ? $deviceIssue->issue : null;
                    })->toArray();

            $subtotal = 0;
            foreach ($ticket->ticketAttachments as $attachment) {
//                $subtotal += $attachment->Parts_Price;
                 if($attachment->Parts_Flag == 1){
                   $amount = $attachment->Parts_Price*$attachment->Quantity;
                   $subtotal += $amount;  
                }               
            }
            $ticket->subtotal = $subtotal;
            
            if ($studentInventory && $studentInventory->Loner_ID != null) {
                $ticket->LonerFlag = 'yes';
            } else {
                $ticket->LonerFlag = 'no';
            }
            $ticket->Status = $ticket->statusname->status ?? null;
            $ticket->StatusID = $ticket->statusname->ID ?? null;
            $ticket->date = $ticket->created_at->format('Y-m-d') ?? null;
        });
        if ($skey == 1) {
            $data = $sflag == 'as' ? $data->sortBy('ticket_num') : $data->sortByDesc('ticket_num');
        } elseif ($skey == 2) {
            $data = $sflag == 'as' ? $data->sortBy('serialNum') : $data->sortByDesc('serialNum');
        } elseif ($skey == 3) {
            $data = $sflag == 'as' ? $data->sortBy('Studentname') : $data->sortByDesc('Studentname');
        } elseif ($skey == 4) {
            $data = $sflag == 'as' ? $data->sortBy('date') : $data->sortByDesc('date');
        } elseif ($skey == 5) {
            $data = $sflag == 'as' ? $data->sortBy('Status') : $data->sortByDesc('Status');
        }
        $data->makeHidden(['inventoryManagement', 'user', 'ticketIssues', 'statusname', 'created_at', 'updated_at', 'ticket_status']);

        $openTicket_array = array();
        $submittedTickets_array = array();
        foreach ($data as $ticket) {
            if ($ticket->ticket_status == 3) {
                array_push($openTicket_array, $ticket);
            } elseif ($ticket->ticket_status == 9 || $ticket->ticket_status == 10) {
                array_push($submittedTickets_array, $ticket);
            }
        }
        if ($gridflag == 1) {
            if ($key == 'null') {
                return response()->json(collect([
                            'response' => 'success',
                            'tickets' => $openTicket_array,
                ]));
            } else {
                $searchedArray = array_filter($openTicket_array, function ($ticket) use ($key) {
                    return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Date), strtolower($key)) !== false;
                });
                return response()->json(collect([
                            'response' => 'success',
                            'tickets' => array_values($searchedArray),
                ]));
            }
        } else {
            if ($key == 'null') {
                return response()->json([
                            'response' => 'success',
                            'tickets' => $submittedTickets_array,
                ]);
            } else {

                $searchedArray = array_filter($submittedTickets_array, function ($ticket) use ($key) {
                    return strpos(strtolower($ticket->serialNum), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->ticket_num), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Studentname), strtolower($key)) !== false ||
                    strpos(strtolower($ticket->Date), strtolower($key)) !== false;
                });
                return response()->json([
                            'response' => 'success',
                            'tickets' => array_values($searchedArray),
                ]);
            }
        }
    }

    public function CreatePdfAndStore($batchid) {
        $batch_data_array = array();
        $batch = CloseTicketBatchLog::where('Batch_Id', $batchid)->get();
        $batchData = CloseTicketBatch::where('ID', $batchid)->select('Name', 'School_ID', 'Amount', 'Date')->first();
        $invoice = InvoiceLog::where('Batch_ID', $batchid)->first();
        $schooldata = School::where('ID', $invoice->School_Id)->first();
        $batchName = $batchData->Name;
        $batchSchoolID = $batchData->School_ID;
        $batchAmount = $batchData->Amount;
        $batchtotal = 0;
        foreach ($batch as $batchdata) {
            $ticket = Ticket::where('ID', $batchdata->Ticket_Id)->first();
            $inventoryData = InventoryManagement::where('id', $ticket->inventory_id)->first();
            $ticketattchment = TicketsAttachment::where('Ticket_ID', $batchdata->Ticket_Id)->where('Parts_Flag', 1)->get();
            $subtotal = 0;
            $parts_array = array();
            foreach ($ticketattchment as $partdata) {
                $partId = $partdata['Parts_ID'];
                $partNotes = $partdata['Parts_Notes'];
                $partQuantity = $partdata['Quantity'];
                $partPrice = $partdata['Parts_Price'];
                $partAmount = $partdata['Parts_Price'] * $partdata['Quantity'];
                $subtotal += $partAmount;
                $part = PartSKUs::where('ID', $partId)->select('Title')->first();

                array_push($parts_array, ['PartID' => $partId, 'PartName' => $part->Title, 'PartNote' => $partNotes, 'PartsQuantity' => $partQuantity, 'PartPrice' => $partPrice, 'PartsAmount' => $partAmount]);
            }
            $batchtotal += $subtotal;
            array_push($batch_data_array, ['BatchStatus' => $invoice->Payment_Status, 'TicketID' => $batchdata->Ticket_Id, 'TicketNum' => $ticket->ticket_num, 'Device' => $inventoryData->Device_model, 'SerialNum' => $inventoryData->Serial_number, 'AssetTag' => $inventoryData->Asset_tag, 'InventoryID' => $inventoryData->ID, 'Notes' => $ticket->notes, 'Ticketsubtotal' => $subtotal, 'Part' => $parts_array]);
        }
        $data = ['response' => 'success',
            'batchdata' => $batch_data_array,
            'batchname' => $batchName,
            'batchamount' => $batchtotal,
            'invoicenum' => $invoice->ID,
            'school' => $schooldata->name,
            'date' => $batchData->Date,
            'invoicestatus' => $invoice->Payment_Status];

        $filename = 'Invoice/' . $invoice->ID . '_' . time() . '.pdf';
        $pdf = PDF::loadView('invoicePdf', compact('data'));
        Storage::disk('public')->put($filename, $pdf->output());
        InvoiceLog::where('ID', $invoice->ID)->update(['Invoice_Pdf' => $filename]);
    }

    public function CreateBatchForAdminPage(Request $request) {
        $schoolID = $request->input('SchoolId');
        $TicketID = $request->input('TicketArray');
        $BatchName = $request->input('BatchName');
        $BatchNote = $request->input('Notes');
        $BatchDate = $request->input('CreatedBatchDate');
        $ExtraDoc = $request->input('File');
        if ($ExtraDoc) {
            $batch = new CloseTicketBatch;
            $batch->School_ID = $schoolID;
            $batch->Name = $BatchName;
            $batch->Notes = $BatchNote;
            $batch->Date = $BatchDate;
            $batch->save();
            $subtotal = 0;
//
            if ($ExtraDoc) {
                $fileContent = base64_decode($ExtraDoc);
                $filename = 'Batch/' . $batch->id . '_' . time() . '.pdf';
                Storage::disk('public')->put($filename, $fileContent);
//                $filePath = 'Batch/' . $filename;
            }
//        
            foreach ($TicketID as $data) {
                $batchLog = new CloseTicketBatchLog;
                $batchLog->Batch_Id = $batch->id;
                $batchLog->Ticket_Id = $data['ID'];
                $batchLog->Batch_Sub_Total = $data['SubTotal'];
                $batchLog->save();
                $subtotal += $data['SubTotal'];
                $schoolBatchLog = SchoolBatchLog::where('TicketID', $data['ID'])->first();
                if ($schoolBatchLog != null) {
                    SchoolBatch::where('ID', $schoolBatchLog->ID)->update(['Status' => 2]);
                    $schoolBatchLogData = SchoolBatchLog::where('BatchID', $schoolBatchLog->BatchID)->get();
                    $allCompleted = true; // Flag to track if all ticket statuses are completed

                    foreach ($schoolBatchLogData as $data) {
                        $ticket = Ticket::where('ID', $data->TicketID)->first();
                        if ($ticket->ticket_status != 9 && $ticket->ticket_status != 10) {
                            $allCompleted = false;
                            break;
                        }
                    }
                    if ($allCompleted) {
                        SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 3]);
                    }
                }
            }
            CloseTicketBatch::where('School_ID', $schoolID)->where('ID', $batch->id)->update(['Amount' => $subtotal, 'Extra_Doc' => $filename]);

            $invoiceLog = new InvoiceLog();
            $invoiceLog->Batch_ID = $batch->id;
            $invoiceLog->School_Id = $schoolID;
            $invoiceLog->Invoice_sent = 1;
            $invoiceLog->Payment_Status = 'Pending';
            $invoiceLog->save();
        } else {
            $batch = new CloseTicketBatch;
            $batch->School_ID = $schoolID;
            $batch->Name = $BatchName;
            $batch->Notes = $BatchNote;
            $batch->Date = $BatchDate;
            $batch->save();
            $subtotal = 0;

            foreach ($TicketID as $data) {
                $batchLog = new CloseTicketBatchLog;
                $batchLog->Batch_Id = $batch->id;
                $batchLog->Ticket_Id = $data['ID'];
                $batchLog->Batch_Sub_Total = $data['SubTotal'];
                $batchLog->save();
                $subtotal += $data['SubTotal'];
                $schoolBatchLog = SchoolBatchLog::where('TicketID', $data['ID'])->first();
                if ($schoolBatchLog != null) {
                    SchoolBatch::where('ID', $schoolBatchLog->ID)->update(['Status' => 2]);
                    $schoolBatchLogData = SchoolBatchLog::where('BatchID', $schoolBatchLog->BatchID)->get();
                    $allCompleted = true; // Flag to track if all ticket statuses are completed

                    foreach ($schoolBatchLogData as $data) {
                        $ticket = Ticket::where('ID', $data->TicketID)->first();
                        if ($ticket->ticket_status != 9 && $ticket->ticket_status != 10) {
                            $allCompleted = false;
                            break;
                        }
                    }
                    if ($allCompleted) {
                        SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 3]);
                    }
                }
            }
            CloseTicketBatch::where('School_ID', $schoolID)->where('ID', $batch->id)->update(['Amount' => $subtotal]);

            $invoiceLog = new InvoiceLog();
            $invoiceLog->Batch_ID = $batch->id;
            $invoiceLog->School_Id = $schoolID;
            $invoiceLog->Invoice_sent = 1;
            $invoiceLog->Payment_Status = 'Pending';
            $invoiceLog->save();
        }
        $BatchID = $batch->id;
        return response()->json([
                    'status' => 'success',
                    'Msg' => 'Batch Created Successfully',
                    'BatchID' => $BatchID,
        ]);
    }

    function createInvoiceBatchwithFedex(Request $request) {
        if ($request->input('BatchFlag') == 1) {
            $result = $this->CreateBatchForAdminPage($request);
            return $result;
        } else {

            $fedexController = new FedexController();
            $shipmentData = $fedexController->createShipment($request);
            if ($shipmentData['status'] == 'error') {
                return response()->json([
                            'status' => 'error',
                            'Msg' => $shipmentData,
                ]);
            } else {
                $result = $this->CreateBatchForAdminPage($request);
                $responseData = json_decode($result->getContent(), true); // Convert JSON string to an array
                $batchId = $responseData['BatchID']; // Access the BatchID property           
                $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $imageData = file_get_contents($shipmentData['url']);
                $filename = time() . '_' . rand(1000, 9999) . '.jpg';
                $filePath = 'FedExQrCodes/' . $batchId .$randomString.'.jpg'; // assuming JPEG format, adjust extension if different   
                Storage::disk('public')->put($filePath, $imageData);
                CloseTicketBatch::where('ID', $batchId)->update(['FedExQr' => $filePath, 'TrackingNum' => $shipmentData['trackingNumber']]);
                return response()->json([
                            'status' => 'success',
                            'Msg' => $shipmentData,
                ]);
            }
        }
    }

    public function DataForBatch($batchid) {
        $batch_data_array = array();
        $batch = CloseTicketBatchLog::where('Batch_Id', $batchid)->get();
        $batchData = CloseTicketBatch::where('ID', $batchid)->select('Name', 'School_ID', 'Amount', 'Date')->first();

        $invoice = InvoiceLog::where('Batch_ID', $batchid)->first();
        $schooldata = School::where('ID', $invoice->School_Id)->first();
        $batchName = $batchData->Name;
        $batchSchoolID = $batchData->School_ID;
        $batchAmount = $batchData->Amount;
        $batchtotal = 0;
        foreach ($batch as $batchdata) {
            $ticket = Ticket::with('user')->where('ID', $batchdata->Ticket_Id)->first();
            $inventoryData = InventoryManagement::where('id', $ticket->inventory_id)->first();
            $ticketattchment = TicketsAttachment::where('Ticket_ID', $batchdata->Ticket_Id)->where('Parts_Flag', 1)->get();
            $RepairLogDetails = TicketRepairLog::where('Ticket_Id', $batchdata->Ticket_Id)->first();
            $subtotal = 0;
            $parts_array = array();
            foreach ($ticketattchment as $partdata) {
                $partId = $partdata['Parts_ID'];
                $partNotes = $partdata['Parts_Notes'];
                $partQuantity = $partdata['Quantity'];
                $partPrice = $partdata['Parts_Price'];
                $partAmount = $partdata['Parts_Price'] * $partdata['Quantity'];
                $subtotal += $partAmount;
                $part = PartSKUs::where('ID', $partId)->select('Title')->first();

                array_push($parts_array, ['PartID' => $partId, 'PartName' => $part->Title, 'PartNote' => $partNotes, 'PartsQuantity' => $partQuantity, 'PartPrice' => $partPrice, 'PartsAmount' => $partAmount]);
            }
            $batchtotal += $subtotal;
            array_push($batch_data_array, ['BatchStatus' => $invoice->Payment_Status, 'TicketID' => $batchdata->Ticket_Id, 'TicketNum' => $ticket->ticket_num, 'Device' => $inventoryData->Device_model, 'SerialNum' => $inventoryData->Serial_number, 'AssetTag' => $inventoryData->Asset_tag,'CreatedBy'=>$ticket->user->first_name.' '.$ticket->user->last_name ?? null,'CreatedDate'=>$ticket->created_at ,'InventoryID' => $inventoryData->ID, 'Notes' => $ticket->notes, 'Ticketsubtotal' => $subtotal, 'Part' => $parts_array, 'RepaiedNotes' => $RepairLogDetails->RepairedItem ?? null]);
        }


        return response()->json(
                        collect([
                    'response' => 'success',
                    'batchdata' => $batch_data_array,
                    'batchname' => $batchName,
                    'batchamount' => $batchtotal,
                    'invoicenum' => $invoice->ID,
                    'school' => $schooldata->name,
                    'date' => $batchData->Date,
                    'invoicestatus' => $invoice->Payment_Status,
        ]));
    }

    public function ExtraAttachedDocForBatch($batchid) {
        $batch = CloseTicketBatch::where('ID', $batchid)->first();
        $url = $batch->Extra_Doc;
        if (isset($batch->Extra_Doc)) {
            return $url;
        } else {
            return 'error';
        }
    }

    public function InvoicePaymentCheck(Request $request) {
        $receipt = $request->input('Receipt');
        $schoolId = $request->input('SchoolId');
        $invoiceId = $request->input('InvoiceId');
        $invoiceStatus = $request->input('InvoiceStatus');
        $batchId = $request->input('BatchId');
        $flag = $request->input('Flag');
        $notes = $request->input('Notes');
        //mail data
        $userdata = User::where('school_id', $schoolId)->where('access_type', 1)->first();
        $schoolData = School::where('ID', $schoolId)->first();
        $batchData = CloseTicketBatchLog::where('Batch_Id', $batchId)->first();
        $data = [
            'batchId' => $batchId,
            'batchName' => $batchData->Name,
            'schoolName' => $schoolData->name,
            'receipt' => $receipt,
            'invoiceId' => $invoiceId,
            'notes' => $notes,
        ];

        if ($flag == 1) {//payment success
            $invoiceLogs = InvoiceLog::where('School_Id', $schoolId)->where('Batch_ID', $batchId)->where('ID', $invoiceId)->update(['Payment_Status' => 'Success', 'Receipt' => $receipt, 'Admin_notes' => $notes]);
            $batchDetails = CloseTicketBatchLog::where('Batch_Id', $batchId)->get();
            $ticketarray = array();
            foreach ($batchDetails as $batchData) {
                Ticket::where('ID', $batchData->Ticket_Id)->update(['ticket_status' => 2]);
            }
            try {
                Mail::to('Info@k12techrepairs.com')->send(new AdminToSchoolMailer($data));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
        } else {
            $invoiceLogs = InvoiceLog::where('School_Id', $schoolId)->where('Batch_ID', $batchId)->where('ID', $invoiceId)->update(['Receipt' => $receipt, 'Admin_notes' => $notes]);
           
            try {
                       Mail::to('Info@k12techrepairs.com')->send(new AdminToSchoolPaymentFailMailer($data));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
        }

        return 'success';
    }

    public function BatchAmount(Request $request) {
        $partsData = $request->input('Part');

        foreach ($partsData as $part) {
            TicketsAttachment::where('Ticket_ID', $request->input('TicketId'))->where('Parts_ID', $part['PartID'])->update(['Parts_Price' => $part['Price']]);
        }

        CloseTicketBatchLog::where('Ticket_Id', $request->input('TicketId'))->update(['Batch_Sub_Total' => $request->input('Total')]);

        return 'success';
    }

    public function TicketAdminPriceData($tid) {

        $partsData = TicketsAttachment::where('Ticket_ID', $tid)->where('Parts_Flag', 1)->get();
        $parts_array = array();
        foreach ($partsData as $part) {

            $partDetails = PartSKUs::where('ID', $part['Parts_ID'])->first();
            array_push($parts_array, ['PartName' => $partDetails->Title, 'PartID' => $part['Parts_ID'], 'Quantity' => $part['Quantity'], 'Amount' => $part['Parts_Price']]);
        }

        $ticketData = Ticket::where('ID', $tid)->first();
        $inventoryData = InventoryManagement::where('ID', $ticketData->inventory_id)->first();      

        return response()->json(
                        collect([
                    'part' => $parts_array,                   
                    'Serial' => $inventoryData->Serial_number
        ]));
    }

    public function SendInvoice($batchid) {
        try {
            $this->CreatePdfAndStore($batchid);
            $invoiceLog = InvoiceLog::where('Batch_ID', $batchid)->first();
            $userData = User::where('school_id', $invoiceLog->School_Id)->where('access_type', 1)->first();
            $pdfPath =  $invoiceLog->Invoice_Pdf;
            
           try {
                Mail::to($userData->email)->send(new InvoiceMailer($pdfPath));
            } catch (\Exception $e) {
                Log::error("Mail sending failed: " . $e->getMessage());
            }
            return 'success';
        } catch (Exception $ex) {
            return 'error';
        }
    }

    public function InvoicePaymentBatchAmountCheck(Request $request) {

        $batchData = CloseTicketBatch::where('ID', $request->input('batchId'))->where('School_ID', $request->input('SchoolId'))->update(['Amount' => $request->input('subtotal')]);
        $ticketArray = $request->input('ticketData');
        foreach ($ticketArray as $tickeData) {
            $subTotal = 0;
            foreach ($tickeData['parts'] as $part) {
                $subTotal += $part['Price'];
                TicketsAttachment::where('Ticket_ID', $tickeData['ticketId'])->where('Parts_ID', $part['PartID'])->update(['Parts_Price' => $part['Price']]);
                CloseTicketBatchLog::where('Batch_Id', $request->input('batchId'))->where('Ticket_Id', $tickeData['ticketId'])->update(['Batch_Sub_Total' => $subTotal]);
            }
        }

        return 'success';
    }

    public function AdminChangeTicketStatus(Request $request) {
        $ticketStatusID = $request->input('Status');
        $ticketupdateduserId = $request->input('UserId');
        $idArray = $request->input('TicketIDArray');
        $note = $request->input('Note');
        $whoWorkOn = $request->input('WhoWorkedOn');
        foreach ($idArray as $ids) {
            $ticketlog = new TicketStatusLog();
            $ticketlog->Ticket_id = $ids['ID'];
            $ticketdata = Ticket::where('ID', $ids['ID'])->first();
            $ticketlog->Status_from = $ticketdata->ticket_status ?? '';
            $ticketlog->Status_to = $ticketStatusID;
            $ticketlog->updated_by_user_id = $ticketupdateduserId;
            $ticketlog->Note = $note;
            $ticketlog->who_worked_on = $whoWorkOn;
            $ticketlog->School_id = $ticketdata->school_id;
            $ticketlog->save();
            Ticket::where('ID', $ids['ID'])->update(['ticket_status' => $ticketStatusID]);
            $schoolBatchLog = SchoolBatchLog::where('TicketID', $ids['ID'])->first();
            if ($schoolBatchLog != null) {
                SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 2]);
                $schoolBatchLogData = SchoolBatchLog::where('BatchID', $schoolBatchLog->BatchID)->get();
                foreach ($schoolBatchLogData as $data) {
                    
                }
            }

            Ticket::where('ID', $ids['ID'])->update(['ticket_status' => $ticketStatusID]);

            $schoolBatchLog = SchoolBatchLog::where('TicketID', $ids['ID'])->first();
            if ($schoolBatchLog != null) {
                SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 2]);

                $schoolBatchLogData = SchoolBatchLog::where('BatchID', $schoolBatchLog->BatchID)->get();
                $allCompleted = true; // Flag to track if all ticket statuses are completed

                foreach ($schoolBatchLogData as $data) {
                   $ticket = Ticket::where('ID',$data->TicketID)->first();
                        if ($ticket->ticket_status != 9 && $ticket->ticket_status != 10){
                        $allCompleted = false;
                        break;
                    }
                }
                if ($allCompleted) {
                    SchoolBatch::where('ID', $schoolBatchLog->BatchID)->update(['Status' => 3]);
                }
            }
        }
        return 'success';
    }

}
