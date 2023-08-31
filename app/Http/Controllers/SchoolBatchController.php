<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Response;
use App\Exceptions\InvalidOrderException;
use App\Models\ErrorLog;
use App\Helper;
use App\Models\School;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Models\User;
use App\Models\AdminSetting;
use App\Models\CloseTicketBatch;
use App\Models\CloseTicketBatchLog;
use App\Models\DeviceAllocationLog;
use App\Models\InventoryCcSetting;
use App\Models\InventoryManagement;
use App\Models\InvoiceLog;
use App\Models\PartSKUs;
use App\Models\PaymentLog;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\LonerDeviceLog;
use App\Models\TicketCcSetting;
use App\Models\TicketRepairLog;
use App\Models\Ticket;
use App\Models\TicketIssue;
use App\Models\TicketStatusLog;
use App\Models\TicketsAttachment;
use App\Models\Logo;
use Illuminate\Support\Facades\DB;
use App\Models\TechnicianLocation;
use App\Models\SchoolBatch;
use App\Models\SchoolBatchLog;
use App\Mail\incomingBatchMailer;
use App\Mail\outgoingBatchMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\IncomingOutgoingBatchNotification;
use App\Models\SchoolAddress;
use App\Http\Controllers\FedexController;
use Illuminate\Support\Facades\Storage;
use App\Models\Location;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
class SchoolBatchController extends Controller {

    function saveSchoolBatches(Request $request) {
        $schoolBatch = new SchoolBatch;
        $schoolBatch->BatchName = $request->input('BatchName');
        $schoolBatch->Notes = $request->input('Notes');
        $schoolBatch->SchoolId = $request->input('SchoolId');
        $schoolBatch->BatchType = $request->input('BatchFlag');
        $schoolDetails = School::where('ID', $request->input('SchoolId'))->first();
        if ($request->input('BatchFlag') == 2) {
            //save address 
            $jsonData = $request->all();
            $address = $jsonData['requestedShipment']['shipper']['address'];
            $streetLines = $address['streetLines'][0];
            $city = $address['city'];
            $state = $address['stateOrProvinceCode'];
            $postalCode = $address['postalCode'];
            $countryCode = $address['countryCode'];
            $phoneNum = $jsonData['requestedShipment']['shipper']['contact']['phoneNumber'];

            $checkSchoolAddress = SchoolAddress::firstOrCreate(
                            ['SchoolID' => $request->input('SchoolId')],
                            [
                                'PhoneNum' => $phoneNum,
                                'StreetLine' => $streetLines,
                                'City' => $city,
                                'StateOrProvinceCode' => $state,
                                'PostalCode' => $postalCode,
                                'CountryCode' => $countryCode,
                                'Location' => $schoolDetails->location
                            ]
            );
            //fedex call   
            SchoolBatch::where('ID', $schoolBatch->id)->update(['ShippingMethod' => $request->input('ShippingMethod')]);
            $fedexController = new FedexController();
            $shipmentData = $fedexController->createShipment($request);
            if ($shipmentData['status'] == 'error') {
                return response()->json([
                            'status' => 'error',
                            'Msg' => $shipmentData,
                ]);
            } else {
                $schoolBatch->save();
                $count = 0;
                try {
                    foreach ($request->input('TicketArray') as $tickets) {
                        $schoolBatchLog = new SchoolBatchLog;
                        $schoolBatchLog->BatchID = $schoolBatch->id;
                        $schoolBatchLog->TicketID = $tickets['id'];
                        $schoolBatchLog->save();
                        $count++;
                        Ticket::where('ID', $tickets['id'])->update(['ticket_status' => 3]);
                    }
                  $ccRecipients = NotificationEventsLog::where('EventID',4)->pluck('UserID')->all();
                    if (isset($ccRecipients)) {
                        $schoolname = School::where('ID', $request->input('SchoolId'))->select('name')->first();
                        foreach ($ccRecipients as $recipent) {
                         
                                $staffmember = User::where('id', $recipent)->first();
                                $data = [
                                    'name' => $staffmember->first_name . '' . $staffmember->last_name,
                                    'batchname' => $schoolBatch->BatchName,
                                    'school_name' => $schoolname->name,
                                    'batchnotes' => $schoolBatch->Notes,
                                    'totaltickets' => $count,
                                ];
                               
                                try {
                                 Mail::to($staffmember->email)->send(new outgoingBatchMailer($data));
                            } catch (\Exception $e) {
                                Log::error("Mail sending failed: " . $e->getMessage());
                            }
                       
                        }
                         $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                      $imageData = file_get_contents($shipmentData['url']);                       
                        $filename = 'FedExQrCodes/' . $schoolBatch->id .$randomString.'.jpg'; // assuming JPEG format, adjust extension if different
// Save to public directory
                        Storage::disk('public')->put($filename, $imageData);
                        if (!Storage::disk('public')->exists('FedExQrCodes')) {
                            Storage::disk('public')->makeDirectory('FedExQrCodes');
                        }
                        SchoolBatch::where('ID', $schoolBatch->id)->update(['FedExQr' => $filename, 'TrackingNum' => $shipmentData['trackingNumber']]);
                        return response()->json([
                                    'status' => 'success',
                                    'Msg' => $shipmentData,
                        ]);
                    }
                } catch (\Throwable $error) {
                    return 'Something went wrong';
            }
            }
        } else {
            $schoolBatch->save();
            $count = 0;
            foreach ($request->input('TicketArray') as $tickets) {
                $schoolBatchLog = new SchoolBatchLog;
                $schoolBatchLog->BatchID = $schoolBatch->id;
                $schoolBatchLog->TicketID = $tickets['id'];
                $schoolBatchLog->save();
                $count++;
                Ticket::where('ID', $tickets['id'])->update(['ticket_status' => 3]);
            }

          $ccRecipients = NotificationEventsLog::where('EventID',4)->pluck('UserID')->all();
            if (isset($ccRecipients)) {
                $schoolname = School::where('ID', $request->input('SchoolId'))->select('name')->first();
                foreach ($ccRecipients as $recipent) {                 
                        $staffmember = User::where('id',$recipent)->first();
                        $data = [
                            'name' => $staffmember->first_name . '' . $staffmember->last_name,
                            'batchname' => $schoolBatch->BatchName,
                            'school_name' => $schoolname->name,
                            'batchnotes' => $schoolBatch->Notes,
                            'totaltickets' => $count,
                        ];
                        
                        
                        try {
                               Mail::to($staffmember->email)->send(new outgoingBatchMailer($data));
                            } catch (\Exception $e) {
                                Log::error("Mail sending failed: " . $e->getMessage());
                            }
                        
        
                }
            }
            return response()->json([
                        'status' => 'success',
                        'Msg' => 'batch created successfully'
            ]);
        }
    }


    function getAllSchoolBatch($sid, $skey, $sortkey, $sflag) {
        $sortField = '';
        if ($sortkey == 1) {
            $sortField = 'BatchName';
        } else {
            $sortField = 'ID';
        }
        if ($skey == 'null') {
            $get = SchoolBatch::where('SchoolId', $sid)->whereIn('Status', [1, 2])->orderBy($sortField, $sflag)->get();
            return response()->json([
                        'response' => 'success',
                        'msg' => $get
            ]);
        } else {
            $get = SchoolBatch::where('SchoolId', $sid)->whereIn('Status', [1, 2])->where('BatchName', 'LIKE', "%$skey%")->orderBy($sortField, $sflag)->get();
            return response()->json([
                        'response' => 'success',
                        'msg' => $get
            ]);
        }
    }


    function getSchoolBatchData($bid) {
        $get = SchoolBatchLog::with('ticket')->where('BatchID', $bid)->get()->groupBy('BatchID')->map(function ($items) {
            $tickets = $items->map(function ($item) {

                $inventory = InventoryManagement::where('ID', $item->ticket->inventory_id)->first();
                $attachments = $item->ticket->ticketAttachments->map(function ($attachment) {
                    // Get the part name based on the Parts_ID
                    $partName = PartSKUs::where('ID', $attachment->Parts_ID)->value('Title');

                    return [
                'ID' => $attachment->ID,
                'School_ID' => $attachment->School_ID,
                'Ticket_ID' => $attachment->Ticket_ID,
                'Parts_ID' => $attachment->Parts_ID,
                'part_name' => $partName, // Include the part name
                'Parts_Notes' => $attachment->Parts_Notes,
                'Quantity' => $attachment->Quantity,
                'Parts_Price' => $attachment->Parts_Price,
                'Original_Price' => $attachment->Original_Price,
                'Admin_Price' => $attachment->Admin_Price,
                'Parts_Flag' => $attachment->Parts_Flag,
                'created_at' => $attachment->created_at,
                    ];
                });

                // Calculate the total Parts_Price * Quantity
                $totalPrice = $attachments->sum(function ($attachment) {
                    return $attachment['Parts_Price'] * $attachment['Quantity'];
                });

                return [
            'ID' => $item->ticket->ID,
            'inventory_id' => $item->ticket->inventory_id,
            'SerialNum' => $inventory->Serial_number,
            'AssetTag' => $inventory->Asset_tag,
            'ticket_num' => $item->ticket->ticket_num,
            'school_id' => $item->ticket->school_id,
            'user_id' => $item->ticket->user_id,
            'ticket_status' => $item->ticket->ticket_status,
            'notes' => $item->ticket->notes,
            'ticket_attachment' => $attachments,
            'total_price' => $totalPrice, // Include the total price
            'created_at' => $item->ticket->created_at,
            'updated_at' => $item->ticket->updated_at,
                ];
            });

            // Calculate the subtotal of all total_price values
            $subtotal = $tickets->sum('total_price');

            return [
        'BatchID' => $items->first()->BatchID,
        'ticket' => $tickets->values(),
        'subtotal' => $subtotal, // Include the subtotal
            ];
        });

        return $get->values()->first(); // Assuming there is only one BatchID in the result
    }

    function getSchoolAddress($sid) {
        $getschool = School::where('ID', $sid)->first();
        if (isset($getschool)) {
            $getSchoolLocation = $getschool->location;
            if (isset($getSchoolLocation)) {
                $getK12LocationAddress = Location::where('ID', $getSchoolLocation)->first();
                $checkSchoolAddress = SchoolAddress::where('SchoolID', $sid)->first();
                if (isset($checkSchoolAddress)) {
                    return response()->json([
                                'status' => 'success',
                                'schoolAddress' => $checkSchoolAddress,
                                'adminAddress' => $getK12LocationAddress,
                                'schoolDetail' => $getschool
                    ]);
                } else {
                    return response()->json([
                                'status' => 'success',
                                'schoolAddress' => 'null',
                                'adminAddress' => $getK12LocationAddress,
                                'schoolDetail' => $getschool
                    ]);
                }
            } else {
                $defaultLocation = Location::where('ID', 1)->first();
                return response()->json([
                            'status' => 'success',
                            'schoolAddress' => 'null',
                            'adminAddress' => $defaultLocation,
                            'schoolDetail' => $getschool
                ]);
            }
        }
    }

    function calculateTheBatchWeight(Request $request) {
        $ticketIdArray = $request->input('TicketArray');
        $deviceType = $request->input('DeviceType');
        $count = count($ticketIdArray);
        if ($deviceType == 3) {
            $calculation = $count * 1;
        } else {
            $calculation = $count * 3;
        }

        return $calculation;
    }

}
