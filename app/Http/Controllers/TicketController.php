<?php

namespace App\Http\Controllers;

use App\Models\OperatingSystem;
use App\Models\DeviceIssue;
use App\Models\Ticket;
use App\Models\TicketIssue;
use App\Models\TicketsAttachment;
use App\Models\User;
use App\Models\StudentInventory;
use App\Models\Student;
use App\Models\School;
use App\Models\InventoryManagement;
use App\Models\LonerDeviceLog;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use App\Mail\CreateTicketMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\TicketCcSetting;
use App\Models\AdminSetting;
use Illuminate\Support\Str;
use App\Models\DeviceAllocationLog;
use App\Models\TicketImage;
use Illuminate\Support\Facades\Storage;
use App\Models\PartSKUs;
use App\Models\TicketStatus;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationEvents;
use App\Models\NotificationEventsLog;
class TicketController extends Controller {

    public function allIssue() {
        $issues = DeviceIssue::all();
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $issues,
        ]));
    }

 

    function exportTickets($sid) {
        $data = Ticket::with('inventoryManagement.studentInventory', 'ticketIssues')
                ->where('school_id', $sid)
                ->get();
        $data->each(function ($ticket) {
            $ticket->Status = $ticket->statusname->status ?? null;
            $inventoryManagement = $ticket->inventoryManagement;
            $studentInventory = $inventoryManagement->studentInventory;
            $ticket->SerialNum = $inventoryManagement->Serial_number ?? null;
            $ticket->Student = $studentInventory?->student?->Device_user_first_name . ' ' . $studentInventory?->student?->Device_user_last_name ?? null;
            $ticket->TicketCreatedBy = $ticket->user->first_name . ' ' . $ticket->user->last_name ?? null;
            
       });
      
     $data->makeHidden(['inventoryManagement','ticketIssues','statusname','created_at','updated_at','user']);
     return $data;
    }
    
    public function generateIssue(Request $request) {
         $msg = $request->input('msg');
        $devicearray = $request->input('DeviceIssueArray');
        $imgarray = $request->input('ImageArray');
        $studentinventory = StudentInventory::where('Inventory_Id', $msg['inventoryId'])->first();
        if (isset($studentinventory)) {
            $studentdata = Student::where('ID', $studentinventory->Student_ID)->first();
            $studentId = $studentdata->ID ?? '';
        } else {
            $studentId = null;
        }

        $ticketdata = Ticket::where('inventory_id', $msg['inventoryId'])->where('ticket_status', 1)->pluck('ticket_status');
        $count = count($ticketdata);
        if ($count > 1) {
            return "Ticket already generated";
        } else {
            $data = Ticket::where('inventory_id', $msg['inventoryId'])->first();
            if (isset($data)) {

                if ($data->ticket_status == 2) {
                    $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                    $ticket = new Ticket();
                    $ticket->school_id = $msg['schoolId'];
                    $ticket->user_id = $msg['userId']; //who use the k12
                    $ticket->inventory_id = $msg['inventoryId'];
                    $ticket->notes = $msg['Notes'];
                    $ticket->ticket_status = 1;
                    $ticket->ticket_num = $randomString;
                    $ticket->save();

                    foreach ($devicearray as $devicearraydata) {
                        $Issue = new TicketIssue();
                        $Issue->ticket_Id = $ticket->id;
                        $Issue->issue_Id = $devicearraydata['ID'];
                        $Issue->user_id = $msg['userId'];
                        $Issue->inventory_id = $msg['inventoryId'];
                        $Issue->save();
                    }
                    $count = 0;
                    foreach ($imgarray as $img) {
                        $count += 1;
                        $base64_image = $img['Img'];
                        $image_parts = explode(";base64,", $base64_image);                     
                        if (count($image_parts) == 2) {
                            $image_type_aux = explode("image/", $image_parts[0]);
                            $image_type = end($image_type_aux);

                            $extension = ($image_type == "jpeg" ? "jpg" : $image_type);

                            $base64_image = $image_parts[1];
                        } else {                         
                            $extension = "png";
                        }

                        $imageData = base64_decode($base64_image);
                        $name = $count.$randomString. 'img.' . $extension;
                        $filePath = 'Tickets/' . $ticket->id . '/' . $name;                    
                        if (!Storage::disk('public')->exists('Tickets/' . $ticket->id)) {
                            Storage::disk('public')->makeDirectory('Tickets/' . $ticket->id);
                        }

                        Storage::disk('public')->put($filePath, $imageData);

                        $TicketImg = new TicketImage();
                        $TicketImg->Ticket_ID = $ticket->id;
                        $TicketImg->Img = $filePath;
                        $TicketImg->save();
                    }

//mail send 
                    $schoolname = School::where('ID', $msg['schoolId'])->select('name')->first();
                    $inventory = InventoryManagement::where('ID', $msg['inventoryId'])->select('Device_model')->first();
                    $ccRecipients = TicketCcSetting::where('School_ID', $msg['schoolId'])->pluck('UserID')->all();

                    foreach ($ccRecipients as $recipent) {
                        $staffmember = User::where('id', $recipent)->first();
                        $data = [
                            'name' => $staffmember->first_name . '' . $staffmember->last_name,
                            'device' => $inventory->Serial_number,
                            'school_name' => $schoolname->name,
                            'ticketnum' => $ticket->ticket_num,
                            'ticketnote' => $ticket->notes,
                            'ticketcreateddate' => $ticket->created_at->format('m-d-y'),
                        ];
                   try {
                            Mail::to($staffmember->email)->send(new CreateTicketMailer($data));
                        } catch (\Exception $e) {
                            Log::error("Mail sending failed: " . $e->getMessage());
                        }
                    }
                } else {
                    return "Ticket already generated";
                }
            } else {
                $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                $ticket = new Ticket();
                $ticket->school_id = $msg['schoolId'];
                $ticket->user_id = $msg['userId'];
                $ticket->inventory_id = $msg['inventoryId'];
                $ticket->notes = $msg['Notes'];
                $ticket->ticket_status = 1;
                $ticket->ticket_num = $randomString;
                $ticket->save();

                foreach ($devicearray as $devicearraydata) {
                    $Issue = new TicketIssue();
                    $Issue->ticket_Id = $ticket->id;
                    $Issue->issue_Id = $devicearraydata['ID'];
                    $Issue->user_id = $msg['userId'];
                    $Issue->inventory_id = $msg['inventoryId'];
                    $Issue->save();
                }

                $count = 0;
                foreach ($imgarray as $img) {
                   $count += 1;
                        $base64_image = $img['Img'];
                        $image_parts = explode(";base64,", $base64_image);                     
                        if (count($image_parts) == 2) {
                            $image_type_aux = explode("image/", $image_parts[0]);
                            $image_type = end($image_type_aux);

                            $extension = ($image_type == "jpeg" ? "jpg" : $image_type);

                            $base64_image = $image_parts[1];
                        } else {                         
                            $extension = "png";
                        }

                        $imageData = base64_decode($base64_image);
                        $name = $count.$randomString. 'img.' . $extension;
                        $filePath = 'Tickets/' . $ticket->id . '/' . $name;                    
                        if (!Storage::disk('public')->exists('Tickets/' . $ticket->id)) {
                            Storage::disk('public')->makeDirectory('Tickets/' . $ticket->id);
                        }

                        Storage::disk('public')->put($filePath, $imageData);

                        $TicketImg = new TicketImage();
                        $TicketImg->Ticket_ID = $ticket->id;
                        $TicketImg->Img = $filePath;
                        $TicketImg->save();
                }

                // mail send   
                $schoolname = School::where('ID', $msg['schoolId'])->select('name')->first();
                $inventory = InventoryManagement::where('ID', $msg['inventoryId'])->select('Device_model')->first();
                $ccRecipients = TicketCcSetting::where('School_ID', $msg['schoolId'])->pluck('UserID')->all();

                foreach ($ccRecipients as $recipent) {
                    $staffmember = User::where('id', $recipent)->first();
                    $data = [
                        'name' => $staffmember->first_name . '' . $staffmember->last_name,
                        'device' => $inventory->Serial_number,
                        'school_name' => $schoolname->name,
                        'ticketnum' => $ticket->ticket_num,
                        'ticketnote' => $ticket->notes,
                        'ticketcreateddate' => $ticket->created_at->format('m-d-y'),
                    ];
                    try {
                        Mail::to($staffmember->email)->send(new CreateTicketMailer($data));
                    } catch (\Exception $e) {
                        Log::error("Mail sending failed: " . $e->getMessage());
                    }
                }
            }

            if ($msg['lonerDeviceStatus'] == 1) {

                $studentinventorydata = StudentInventory::where('Inventory_Id', $msg['inventoryId'])->first();
                if (isset($studentinventorydata)) {
                    StudentInventory::where('Inventory_Id', $msg['inventoryId'])->update(['Loner_ID' => $msg['lonerId']]);
                    if (isset($msg['lonerId'])) {
                        $deviceAllocationLog = new DeviceAllocationLog;
                        $deviceAllocationLog->Inventory_ID = $msg['lonerId'];
                        $deviceAllocationLog->Student_ID = $studentId;
                        $deviceAllocationLog->School_ID = $msg['schoolId'];
                        $deviceAllocationLog->Allocated_Date = date("Y-m-d");
                        $checkLonerdevice = InventoryManagement::where('ID', $msg['lonerId'])->first();
                        if ($checkLonerdevice->Loaner_device == 1) {
                            $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                            $deviceAllocationLog->save();
                        }
                        $deviceAllocationLog->save();
                    }
                } else {
                    $studentInventory = new StudentInventory();
                    $studentInventory->Student_ID = $studentId;
                    $studentInventory->Inventory_Id = $msg['inventoryId'];
                    $studentInventory->Loner_ID = $msg['lonerId'];
                    $studentInventory->save();

                    $deviceAllocationLog = new DeviceAllocationLog;
                    $deviceAllocationLog->Inventory_ID = $msg['inventoryId'];
                    $deviceAllocationLog->Student_ID = $studentId;
                    $deviceAllocationLog->School_ID = $msg['schoolId'];
                    $deviceAllocationLog->Allocated_Date = date("Y-m-d");
                    $checkLonerdevice = InventoryManagement::where('ID', $msg['inventoryId'])->first();
                    if ($checkLonerdevice->Loaner_device == 1) {
                        $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                        $deviceAllocationLog->save();
                    }
                    $deviceAllocationLog->save();
                }

                $lonerdevicelog = new LonerDeviceLog();
                $lonerdevicelog->Student_ID = $studentId;
                $lonerdevicelog->Loner_ID = $msg['lonerId'];
                $lonerdevicelog->Start_date = now()->format('Y-m-d');
                $lonerdevicelog->save();
                return "success";
            } else {
                return "success";
            }
        }
    }

    public function TicketImages($tid) {
        $ticket = TicketImage::where('Ticket_ID', $tid)->select('Img', 'ID')->get();
        return $ticket;
    }

    public function AddUpdateTicket(Request $request) {
        $msg = $request->input('msg');
        $devicearray = $request->input('DeviceIssueArray');
        $imgarray = $request->input('ImageArray');
        $studentinventory = StudentInventory::where('Inventory_Id', $msg['inventoryId'])->first();
        $studentdata = $studentinventory ? $studentinventory->student : null;
        $studentId = $studentdata ? $studentdata->ID : null;
        $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $ticketExists = Ticket::where('inventory_id', $msg['inventoryId'])->whereIn('ticket_status', [1, 3, 4, 5, 6, 9, 10])->exists();      
        //add ticket
        if($request->input('TicketID') == 0){
                       if ($ticketExists) {
            return "Ticket already generated";
        } else {
            $randomString = Str::random(6, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
            $ticket = new Ticket();
            $ticket->school_id = $msg['schoolId'];
            $ticket->user_id = $msg['userId'];
            $ticket->inventory_id = $msg['inventoryId'];
            $ticket->notes = $msg['Notes'];
            $ticket->ticket_status = 1;
            $ticket->ticket_num = $randomString;
            $ticket->save();

            foreach ($devicearray as $devicearraydata) {
                $Issue = new TicketIssue();
                $Issue->ticket_Id = $ticket->id;
                $Issue->issue_Id = $devicearraydata['ID'];
                $Issue->user_id = $msg['userId'];
                $Issue->inventory_id = $msg['inventoryId'];
                $Issue->save();
            }

            $count = 0;
            foreach ($imgarray as $img) {
                $count += 1;
                $file = $img['Img'];
                $name = $count . 'img';
                $filePath = 'Tickets/' . $ticket->id . '/' . $name;
                Storage::disk('public')->put($filePath, file_get_contents($file));

                $TicketImg = new TicketImage();
                $TicketImg->Ticket_ID = $ticket->id;
                $TicketImg->Img = $filePath;
                $TicketImg->save();
            }

                if ($msg['lonerDeviceStatus'] == 1) {
                    $studentinventorydata = StudentInventory::where('Inventory_Id', $msg['inventoryId'])->first();
                    if (isset($studentinventorydata)) {
                        StudentInventory::where('Inventory_Id', $msg['inventoryId'])->update(['Loner_ID' => $msg['lonerId']]);
                        if (isset($msg['lonerId'])) {
                            $deviceAllocationLog = new DeviceAllocationLog;
                            $deviceAllocationLog->Inventory_ID = $msg['lonerId'];
                            $deviceAllocationLog->Student_ID = $studentId;
                            $deviceAllocationLog->School_ID = $msg['schoolId'];
                            $deviceAllocationLog->Allocated_Date = date("Y-m-d");
                            $checkLonerdevice = InventoryManagement::where('ID', $msg['lonerId'])->first();
                            if ($checkLonerdevice->Loaner_device == 1) {
                                $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                                $deviceAllocationLog->save();
                            }
                            $deviceAllocationLog->save();
                        }
                    } else {
                        $studentInventory = new StudentInventory();
                        $studentInventory->Student_ID = $studentId;
                        $studentInventory->Inventory_Id = $msg['inventoryId'];
                        $studentInventory->Loner_ID = $msg['lonerId'];
                        $studentInventory->save();

                        $deviceAllocationLog = new DeviceAllocationLog;
                        $deviceAllocationLog->Inventory_ID = $msg['lonerId'];
                        $deviceAllocationLog->Student_ID = $studentId;
                        $deviceAllocationLog->School_ID = $msg['schoolId'];
                        $deviceAllocationLog->Allocated_Date = date("Y-m-d");
                        $checkLonerdevice = InventoryManagement::where('ID', $msg['lonerId'])->first();
                        if ($checkLonerdevice->Loaner_device == 1) {
                            $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                            $deviceAllocationLog->save();
                        }
                        $deviceAllocationLog->save();
                    }

                    $lonerdevicelog = new LonerDeviceLog();
                    $lonerdevicelog->Student_ID = $studentId;
                    $lonerdevicelog->Loner_ID = $msg['lonerId'];
                    $lonerdevicelog->Start_date = now()->format('Y-m-d');
                    $lonerdevicelog->save();
                    return "success";
                } else {
                    return "success";
                }
            }
        } 
    }

}
