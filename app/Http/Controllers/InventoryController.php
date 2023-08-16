<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\InventoryManagement;
use App\Models\TicketStatusLog;
use App\Models\TicketRepairLog;
use App\Models\TicketImage;
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
use App\Models\TicketsAttachment;
use App\Models\PartSKUs;
use App\Models\DeviceAllocationLog;
use Illuminate\Support\Facades\Response;
use App\Models\DeviceType;
use App\Models\Building;

class InventoryController extends Controller {

    public function uploadInventory(Request $request) {
         set_time_limit(0);
        try {
            $userId = $request->input('ID');
            $schId = $request->input('schId');
            $flag = $request->input('flag');
            $result = $request->file('file');
            $file = fopen($result, 'r');
            $header = fgetcsv($file);

            $expectedHeaders = [];
            if ($flag == 1) {
                $expectedHeaders = ['devicemanufacturer', 'devicemodel', 'deviceos', 'manufacturerwarrantyuntil', 'manufactureradpuntil', 'thirdpartyextendedwarrantyuntil', 'thirdpartyadpuntil', 'expectedretirement', 'deviceuserfirstname', 'deviceuserlastname', 'gradedepartment', 'devicempn', 'serialnumber', 'assettag', 'purchasedate', 'building', 'parentguardianname', 'parentguardianemail', 'parentphonenumber', 'parentalcoverage', 'repaircap'];
            } else {
                $expectedHeaders = ['devicemanufacturer', 'devicemodel', 'deviceos', 'manufacturerwarrantyuntil', 'manufactureradpuntil', 'thirdpartyextendedwarrantyuntil', 'thirdpartyadpuntil', 'expectedretirement', 'isloanerdevice', 'devicempn', 'serialnumber', 'assettag', 'purchasedate', 'repaircap','building'];
            }

            $escapedheader = [];           
            foreach ($header as $key => $value) {
                $lheader = strtolower($value);
                $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                array_push($escapedheader, $escapedItem);
            }   
            if (array_diff($expectedHeaders, $escapedheader)) {
                return 'Invalid CSV';
            }
             
            while ($columns = fgetcsv($file)) {

                if ($columns[0] == "") {
                    continue;
                }

                foreach ($columns as $key => &$value) {
                    $value;
                }

                $data = array_combine($escapedheader, $columns);               
                //withstudent data          
                if ($flag == 1) {
                    $Device_manufacturer = $data['devicemanufacturer'];
                    $Device_model = $data['devicemodel'];
                    $Device_os = $data['deviceos'];
                    $Manufacturer_warranty_until = $data['manufacturerwarrantyuntil']; // ?? null
                    $Manufacturer_ADP_until = $data['manufactureradpuntil'];
                    $Third_party_extended_warranty_until = $data['thirdpartyextendedwarrantyuntil'];
                    $Third_party_ADP_until = $data['thirdpartyadpuntil'];
                    $Expected_retirement = $data['expectedretirement'];
                    $Device_user_first_name = $data['deviceuserfirstname'];
                    $Device_user_last_name = $data['deviceuserlastname'];
                    $Grade = $data['gradedepartment'] ?? null;
                    $Device_MPN = $data['devicempn'];
                    $Serial_number = $data['serialnumber'];
                    $Asset_tag = $data['assettag'];
                    $Purchase_date = $data['purchasedate'];                                       
                    $Parent_guardian_name = $data['parentguardianname'] ?? null;
                    $Parent_guardian_Email = $data['parentguardianemail'] ?? null;
                    $Parent_phone_number = $data['parentphonenumber'] ?? null;
                    $Parental_coverage = $data['parentalcoverage'] ?? null;
                    $Repair_cap = $data['repaircap'];
                    $Student_num = $data['studentnumber'];
                    $checkBuilding = Building::where('SchoolID', $schId)
                            ->where('Building', 'LIKE', "%" . $data['building'] . "%")
                            ->first();

                    if (isset($checkBuilding)) {
                        $Building = $checkBuilding->ID;
                      
                    } else {
                        $Building = NULL;
                                        
                    }

$savedInventory = InventoryManagement::where('Serial_number', $data['serialnumber'])->where('school_id', $schId)->first();

                    if (isset($savedInventory)) {
    $SerialNum = $savedInventory->Serial_number;
    $InventoryID = $savedInventory->ID;
    $CsvSerialNum = $data['serialnumber'];
                    $updatedDetail = InventoryManagement::where('Serial_number', $CsvSerialNum)
                            ->update([
                        'Purchase_date' => $Purchase_date ? date("Y-m-d", strtotime(str_replace('-', '/', $Purchase_date))) : $savedInventory->Purchase_date,
                        'Device_manufacturer' => $Device_manufacturer ?: $savedInventory->Device_manufacturer,
                        'Device_model' => $Device_model ?: $savedInventory->Device_model,
                        'Device_os' => $Device_os ?: $savedInventory->Device_os,
                        'Manufacturer_warranty_until' => $Manufacturer_warranty_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_warranty_until))) : $savedInventory->Manufacturer_warranty_until,
                        'Manufacturer_ADP_until' => $Manufacturer_ADP_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_ADP_until))) : $savedInventory->Manufacturer_ADP_until,
                        'Third_party_extended_warranty_until' => $Third_party_extended_warranty_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_extended_warranty_until))) : $savedInventory->Third_party_extended_warranty_until,
                        'Third_party_ADP_until' => $Third_party_ADP_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_ADP_until))) : $savedInventory->Third_party_ADP_until,
                        'Expected_retirement' => $Expected_retirement ? date("Y-m-d", strtotime(str_replace('-', '/', $Expected_retirement))) : $savedInventory->Expected_retirement,
                        'Device_MPN' => $Device_MPN ?: $savedInventory->Device_MPN,
                        'Asset_tag' => $Asset_tag ?: $savedInventory->Asset_tag,
                        'Repair_cap' => $Repair_cap ?: $savedInventory->Repair_cap,
                        'Building' => $Building ?: $savedInventory->Building,
                    ]);

                    $studentinventory = StudentInventory::where('Inventory_Id', $InventoryID)->first();
                        if (isset($studentinventory)) {

                            $savedStudent = Student::where('Student_num', $Student_num)->where('School_ID', $schId)->first();
                            if (isset($savedStudent)) {
                                $updatedstudentDetail = Student::where('ID', $savedStudent->ID)
                                        ->update(['Device_user_first_name' => $Device_user_first_name ? $Device_user_first_name : $savedStudent->Device_user_first_name,
                                    'Device_user_last_name' => $Device_user_last_name ? $Device_user_last_name : $savedStudent->Device_user_last_name,
                                    'Grade' => $Grade ? $Grade : $savedStudent->Grade,//                              
                                    'Parent_guardian_name' => $Parent_guardian_name ? $Parent_guardian_name : $savedStudent->Parent_guardian_name,
                                    'Parent_guardian_Email' => $Parent_guardian_Email ? $Parent_guardian_Email : $savedStudent->Parent_guardian_Email,
                                    'Parent_phone_number' => $Parent_phone_number ? $Parent_phone_number : $savedStudent->Parent_phone_number,
                                    'Parental_coverage' => $Parental_coverage ? $Parental_coverage : $savedStudent->Parental_coverage ?? null,
                                ]);

                                StudentInventory::where('Inventory_ID', $InventoryID)->where('School_ID', $schId)->update(['Student_ID' => $savedStudent->ID]);
                            } else {

                                $Student = new Student;
                                $Student->Device_user_first_name = $Device_user_first_name;
                                $Student->Device_user_last_name = $Device_user_last_name;
                                $Student->Grade = $Grade;
                                $Student->Parent_guardian_name = $Parent_guardian_name;
                                $Student->Parent_guardian_Email = $Parent_guardian_Email;
                                $Student->Parent_phone_number = $Parent_phone_number;
                                $Student->Parental_coverage = $Parental_coverage;
                                $Student->School_ID = $schId;
                                $Student->Student_num = $Student_num;
                                $Student->save();

                                StudentInventory::where('Inventory_ID', $InventoryID)->where('School_ID', $schId)->update(['Student_ID' => $Student->id]);
                            }
                        }else{
                                $Student = new Student;
                                $Student->Device_user_first_name = $Device_user_first_name;
                                $Student->Device_user_last_name = $Device_user_last_name;
                                $Student->Grade = $Grade;//                            
                                $Student->Parent_guardian_name = $Parent_guardian_name;
                                $Student->Parent_guardian_Email = $Parent_guardian_Email;
                                $Student->Parent_phone_number = $Parent_phone_number;
                                $Student->Parental_coverage = $Parental_coverage;
                                $Student->School_ID = $schId;
                                $Student->Student_num = $Student_num;
                                $Student->save();
                               
                            $StudentInventory = new StudentInventory;
                            $StudentInventory->Inventory_ID = $InventoryID;
                            $StudentInventory->Student_ID = $Student->id;
                            $StudentInventory->School_ID = $schId;
                            $StudentInventory->save();                                
                        } 
                    } else {

                        $inventory = new InventoryManagement;
                        $inventory->Purchase_date = $data['purchasedate'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Purchase_date))) : null;
                        $inventory->Device_manufacturer = $Device_manufacturer;//                
                        $inventory->Device_model = $Device_model;
                        $inventory->Device_os = $Device_os;
                        $inventory->Manufacturer_warranty_until = $data['manufacturerwarrantyuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_warranty_until))) : null;
                        $inventory->Manufacturer_ADP_until = $data['manufactureradpuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_ADP_until))) : null;
                        $inventory->Serial_number = $Serial_number;
                        $inventory->Third_party_extended_warranty_until = $data['thirdpartyextendedwarrantyuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_extended_warranty_until))) : null;
                        $inventory->Third_party_ADP_until = $data['thirdpartyadpuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_ADP_until))) : null;
                        $inventory->Expected_retirement = $data['expectedretirement'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Expected_retirement))) : null;
                        $inventory->Loaner_device = 0;
                        $inventory->Device_MPN = $Device_MPN;
                        $inventory->Asset_tag = $Asset_tag;
                        $inventory->Repair_cap = $Repair_cap;
                        $inventory->user_id = $userId;
                        $inventory->school_id = $schId;
                        $inventory->Building = $Building;                 
                        $inventory->save();

                        $Student = new Student;
                        $Student->Device_user_first_name = $Device_user_first_name;
                        $Student->Device_user_last_name = $Device_user_last_name;
                        $Student->Grade = $Grade;
                        $Student->Parent_guardian_name = $Parent_guardian_name;
                        $Student->Parent_guardian_Email = $Parent_guardian_Email;
                        $Student->Parent_phone_number = $Parent_phone_number;
                        $Student->Parental_coverage = $Parental_coverage;
                        $Student->School_ID = $schId;

                        $savedStudent = Student::where('Student_num', $Student_num)->where('School_ID', $schId)->first();
                        if (isset($savedStudent)) {

                            $updatedstudentDetail = Student::where('Student_num', $savedStudent->Student_num ?? null)
                                    ->update(['Device_user_first_name' => $Device_user_first_name ? $Device_user_first_name : $savedStudent->Device_user_first_name,
                                'Device_user_last_name' => $Device_user_last_name ? $Device_user_last_name : $savedStudent->Device_user_last_name,
                                'Grade' => $Grade ? $Grade : $savedStudent->Grade,
                                'Parent_guardian_name' => $Parent_guardian_name ? $Parent_guardian_name : $savedStudent->Parent_guardian_name,
                                'Parent_guardian_Email' => $Parent_guardian_Email ? $Parent_guardian_Email : $savedStudent->Parent_guardian_Email,
                                'Parent_phone_number' => $Parent_phone_number ? $Parent_phone_number : $savedStudent->Parent_phone_number,
                                'Parental_coverage' => $Parental_coverage ? $Parental_coverage : $savedStudent->Parental_coverage ?? null,
                            ]);

                            $StudentInventory = new StudentInventory;
                            $StudentInventory->Inventory_ID = $inventory->id;
                            $StudentInventory->Student_ID = $savedStudent->ID;
                            $StudentInventory->School_ID = $schId;
                            $StudentInventory->save();
                        } else {  

                            $Student->Student_num = $Student_num;
                            $Student->save();

                            $StudentInventory = new StudentInventory;
                            $StudentInventory->Inventory_ID = $inventory->id;
                            $StudentInventory->Student_ID = $Student->id;
                            $StudentInventory->School_ID = $schId;
                            $StudentInventory->save();
                        }

                        $deviceAllocationLog = new DeviceAllocationLog;
                        $deviceAllocationLog->Inventory_ID = $inventory->id;
                        $deviceAllocationLog->Student_ID = $Student->id;
                        $deviceAllocationLog->School_ID = $schId;
                        $deviceAllocationLog->Allocated_Date = date("Y-m-d");
                        $checkLonerdevice = InventoryManagement::where('ID', $inventory->id)->first();
                        if ($checkLonerdevice->Loaner_device == 1) {
                            $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                            $deviceAllocationLog->save();
                        }
                        $deviceAllocationLog->save();
                    }
                } else {

                    $Device_manufacturer = $data['devicemanufacturer'];
                    $Device_model = $data['devicemodel'];
                    $Device_os = $data['deviceos'];
                    $Manufacturer_warranty_until = $data['manufacturerwarrantyuntil'];
                    $Manufacturer_ADP_until = $data['manufactureradpuntil'];
                    $Third_party_extended_warranty_until = $data['thirdpartyextendedwarrantyuntil'];
                    $Third_party_ADP_until = $data['thirdpartyadpuntil'];
                    $Expected_retirement = $data['expectedretirement'];
                    $Loaner_device = $data['isloanerdevice'];
                    $Device_MPN = $data['devicempn'];
                    $Serial_number = $data['serialnumber'];
                    $Asset_tag = $data['assettag'];
                    $Purchase_date = $data['purchasedate'];
                    $Repair_cap = $data['repaircap'];
                    $checkBuilding = Building::where('SchoolID', $schId)
                            ->where('Building', 'LIKE', "%" . $data['building'] . "%")
                            ->first();

                    if (isset($checkBuilding)) {
                        $Building = $checkBuilding->ID;
                    } else {
                         $Building = NULL;
                    }//                   
                     
                    $savedInventory = InventoryManagement::where('Serial_number', $data['serialnumber'])->where('school_id', $schId)->first();

                    if (isset($savedInventory)) {
                        $SerialNum = $savedInventory->Serial_number;
                        $InventoryID = $savedInventory->ID;
                        $CsvSerialNum = $data['serialnumber'];
                        //                        
                         if ($Loaner_device == 'Yes' || $Loaner_device == 'yes'|| $Loaner_device == 'YES') {                         
                            $Updatedinventory_status = 3;
                            $UpdatedLoaner_device = 1;
                        } else {
                            $UpdatedLoaner_device = 0;
                            $Updatedinventory_status = 1;
                        }
                        
                        $updatedDetail = InventoryManagement::where('Serial_number', $CsvSerialNum)
                            ->update([
                        'Purchase_date' => $Purchase_date ? date("Y-m-d", strtotime(str_replace('-', '/', $Purchase_date))) : $savedInventory->Purchase_date,
                        'Device_manufacturer' => $Device_manufacturer ?: $savedInventory->Device_manufacturer,
                        'Device_model' => $Device_model ?: $savedInventory->Device_model,
                        'Device_os' => $Device_os ?: $savedInventory->Device_os,
                        'Manufacturer_warranty_until' => $Manufacturer_warranty_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_warranty_until))) : $savedInventory->Manufacturer_warranty_until,
                        'Manufacturer_ADP_until' => $Manufacturer_ADP_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_ADP_until))) : $savedInventory->Manufacturer_ADP_until,
                        'Third_party_extended_warranty_until' => $Third_party_extended_warranty_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_extended_warranty_until))) : $savedInventory->Third_party_extended_warranty_until,
                        'Third_party_ADP_until' => $Third_party_ADP_until ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_ADP_until))) : $savedInventory->Third_party_ADP_until,
                        'Expected_retirement' => $Expected_retirement ? date("Y-m-d", strtotime(str_replace('-', '/', $Expected_retirement))) : $savedInventory->Expected_retirement,
                        'Device_MPN' => $Device_MPN ?: $savedInventory->Device_MPN,
                        'Asset_tag' => $Asset_tag ?: $savedInventory->Asset_tag,
                        'Repair_cap' => $Repair_cap ?: $savedInventory->Repair_cap,
                        'Building' => $Building ?: $savedInventory->Building,
                        'inventory_status'=>$Updatedinventory_status,
                        'Loaner_device'=>$UpdatedLoaner_device      
                    ]);
                    } else {

                        $inventory = new InventoryManagement;
                        $inventory->Purchase_date = $data['purchasedate'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Purchase_date))) : null;
                        $inventory->Device_manufacturer = $Device_manufacturer;
                        $inventory->Device_model = $Device_model;
                        $inventory->Device_os = $Device_os;
                        $inventory->Manufacturer_warranty_until = $data['manufacturerwarrantyuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_warranty_until))) : null;
                        $inventory->Manufacturer_ADP_until = $data['manufactureradpuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Manufacturer_ADP_until))) : null;
                        $inventory->Serial_number = $Serial_number;
                        $inventory->Third_party_extended_warranty_until = $data['thirdpartyextendedwarrantyuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_extended_warranty_until))) : null;
                        $inventory->Third_party_ADP_until = $data['thirdpartyadpuntil'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Third_party_ADP_until))) : null;
                        $inventory->Expected_retirement = $data['expectedretirement'] ? date("Y-m-d", strtotime(str_replace('-', '/', $Expected_retirement))) : null;

                        if ($Loaner_device == 'Yes' || $Loaner_device == 'yes'|| $Loaner_device == 'YES') {                         
                            $inventory->inventory_status = 3;
                            $inventory->Loaner_device = 1;
                        } else {
                            $inventory->Loaner_device = 0;
                            $inventory->inventory_status = 1;
                        }
                        $inventory->Device_MPN = $Device_MPN;
                        $inventory->Asset_tag = $Asset_tag;
                        $inventory->Repair_cap = $Repair_cap;
                        $inventory->user_id = $userId;
                        $inventory->school_id = $schId;
                        $inventory->Building = $Building;//                      
                        $inventory->save();
                    }
                }
            }

            return 'success';
        } catch (\Throwable $error) {

            return 'Something went wrong';
        }
    }

    public function exportInventory($flag, $sid) {
        if ($flag == 1) {
            $inventory = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->where('inventory_management.inventory_status', 1)
                    ->select('inventory_management.*','students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num','students.Grade')
                    ->get();
        } elseif ($flag == 2) {
            $inventory = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->where('inventory_management.inventory_status', 2)
                    ->select('inventory_management.*','students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num','students.Grade')
                    ->get();
        } elseif ($flag == 3) {
            $inventory = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->where('inventory_management.Loaner_device', 1)
                    ->select('inventory_management.*', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num','students.Grade')
                    ->get();
        } elseif ($flag == 4) {
            $inventory = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->where('inventory_management.school_id', $sid)
                    ->select('inventory_management.*', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', 'students.Student_num','students.Grade')
                    ->get();
        } elseif ($flag == 5) {
            $inventory = Ticket::leftJoin('inventory_management', 'tickets.inventory_id', '=', 'inventory_management.ID')
                    ->where('inventory_management.school_id', $sid)
                    ->select('inventory_management.Serial_number', 'inventory_management.Device_model', 'tickets.ticket_status', 'tickets.notes')
                    ->get();
        } else {
            return response()->json(
                            collect([
                        'response' => 'error',
                        'msg' => 'select one of the option',
            ]));
        }

        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $inventory,
        ]));
    }

    function manageInventoryAction(Request $request) {
        $idArray = $request->input('IDArray');
        $actionId = $request->input('actionid');

        foreach ($idArray as $id) {
            if ($actionId == 1) {//active to decommission
                $ticketcheck = Ticket::where('inventory_id', $id)->whereNotIn('ticket_status',[2])->first();
                if (isset($ticketcheck)) {
                    $changeTicketStatus = Ticket::where('inventory_id', $id)->update(['ticket_status' => 2]);
                    $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' => 2]);
                    $studentinventories = StudentInventory::where('Inventory_ID', $id)->first();
                } else {
                    $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' => 2]);
                    $studentinventories = StudentInventory::where('Inventory_ID', $id)->first();
                }
                if (isset($studentinventories->Student_ID)) {
//                          $student = Student::where('ID',$studentinventories->Student_ID)->delete();
                    $deallocateStudent = StudentInventory::where('Inventory_ID', $id)->forceDelete(); // or delete     
                    DeviceAllocationLog::where('Student_ID', $studentinventories->Student_ID)->where('Inventory_ID', $id)->update(['Vacant_Date' => date("Y-m-d")]);
                }
            } elseif ($actionId == 2) {//decommission to active
                $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' => 1]);
            } elseif ($actionId == 3) {//loner to decommission 
                $ticketcheck = Ticket::where('inventory_id', $id)->whereNotIn('ticket_status',[2])->first();
                if (isset($ticketcheck)) {
                    $changeTicketStatus = Ticket::where('inventory_id', $id)->update(['ticket_status' => 2]);
                }
                $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' => 2,'Loaner_device'=>0]);
                $studentinventories = StudentInventory::where('Inventory_ID', $id)->first();
                if (isset($studentinventories->Student_ID)) {
                    $deallocateStudent = StudentInventory::where('Loner_ID', $id)->forceDelete(); // or delete     
                    DeviceAllocationLog::where('Student_ID', $studentinventories->Student_ID)->where('Inventory_ID', $id)->update(['Vacant_Date' => date("Y-m-d")]);
                }
            } elseif ($actionId == 4) { // decommisision to loner
                $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' =>3,'Loaner_device'=>1]);
            } elseif ($actionId == 5) { //active to loner
                $ticketcheck = Ticket::where('inventory_id', $id)->whereNotIn('ticket_status',[2])->first();
                if (isset($ticketcheck)) {
                    $changeTicketStatus = Ticket::where('inventory_id', $id)->update(['ticket_status' => 2]);
                }               
                $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' => 3, 'Loaner_device' => 1]);
                
                $studentinventories = StudentInventory::where('Inventory_ID', $id)->first();
                if (isset($studentinventories->Student_ID)) {                      
                    $deallocateStudent = StudentInventory::where('Inventory_ID', $id)->forceDelete(); // or delete     
                    DeviceAllocationLog::where('Student_ID', $studentinventories->Student_ID)->where('Inventory_ID', $id)->update(['Vacant_Date' => date("Y-m-d")]);
                }
            }elseif($actionId == 6){ //loner to active
                $ticketcheck = Ticket::where('inventory_id', $id)->whereNotIn('ticket_status',[2])->first();
                if (isset($ticketcheck)) {
                    $changeTicketStatus = Ticket::where('inventory_id', $id)->update(['ticket_status' => 2]);
                }               
                $updatedInventory = InventoryManagement::where('ID', $id)->update(['inventory_status' =>1, 'Loaner_device' => 0]);                
                $studentinventories = StudentInventory::where('Inventory_ID', $id)->first();
                if (isset($studentinventories->Student_ID)) {                      
                    $deallocateStudent = StudentInventory::where('Inventory_ID', $id)->forceDelete(); // or delete     
                    DeviceAllocationLog::where('Student_ID', $studentinventories->Student_ID)->where('Inventory_ID', $id)->update(['Vacant_Date' => date("Y-m-d")]);
                }
            }
            else {
                return 'select active or decommission';
            }
        }
        return "success";
    }

     function allInventories($sid, $flag, $key, $skey, $sflag,$page,$limit) {
        $inventory = InventoryManagement::leftJoin('student_inventories', 'student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                ->leftJoin('buildings','buildings.ID','=','inventory_management.Building')
                ->where('inventory_management.school_id', $sid)
                ->where('inventory_management.inventory_status', $flag)
                ->leftJoin('tickets', function ($join) {
                    $join->on('tickets.inventory_id', '=', 'inventory_management.ID')
                    ->whereIn('tickets.ticket_status', [1, 3, 4, 5, 6,7,8,9,10]);
                })
                ->select('inventory_management.*', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_Guardian_Email', 'students.Parent_phone_number', 'students.Parental_coverage', DB::raw('IF(tickets.ID IS NOT NULL,"redText"," ") as TicketFlag'),'tickets.ID as ticketId','students.Grade','buildings.Building as BuildingName');


        if ($flag == 1 || $flag == 2) {
            if ($key !== 'null') {
                $inventory->where(function ($query) use ($key) {
                    $query->Where('inventory_management.Building', 'LIKE', "%$key%")
                            ->orWhere('students.Grade', 'LIKE', "%$key%")
                            ->orWhere('students.Device_user_first_name', 'LIKE', "%$key%")
                            ->orWhere('students.Device_user_last_name', 'LIKE', "%$key%")
                            ->orWhere('inventory_management.Serial_number', 'LIKE', "%$key%")
                            ->orWhere('inventory_management.Purchase_date', 'LIKE', "%$key%")
                            ->orWhere('inventory_management.Asset_tag', 'LIKE', "%$key%");
                });
            }
        } elseif ($flag == 3) {
            $inventory = InventoryManagement::select('inventory_management.*','students.ID as studentid', 'students.Device_user_first_name', 'students.Device_user_last_name', 'students.Parent_guardian_name', 'students.Parent_phone_number', 'students.Parent_Guardian_Email', 'students.Parental_coverage', DB::raw('IF(tickets.ID IS NOT NULL,"redText"," ") AS TicketFlag'),'tickets.ID as ticketId','students.Grade','buildings.Building as BuildingName')
                    ->leftJoin('student_inventories', function ($join) {
                        $join->On('student_inventories.Inventory_ID', '=', 'inventory_management.ID')
                        ->orOn('student_inventories.Loner_ID', '=', 'inventory_management.ID');
                    })->leftJoin('buildings','buildings.ID','=','inventory_management.Building')
                    ->leftJoin('students', 'students.ID', '=', 'student_inventories.Student_ID')
                    ->leftJoin('tickets', function ($join) {
                        $join->on('tickets.Inventory_ID', '=', 'inventory_management.ID')
                        ->whereIn('tickets.ticket_status',[1, 3, 4, 5, 6,7,8,9,10]);
                    })
//                     ->leftJoin('devicetypes', 'devicetypes.ID', '=', 'inventory_management.Device_type')
                    ->where('inventory_management.school_id', $sid)
                    ->whereIn('inventory_management.inventory_status', [3]);
            if ($key !== 'null') {
                $inventory->where(function ($query) use ($key) {
                    $query->where('students.Device_user_first_name', 'like', '%' . $key . '%')
                            ->orWhere('students.Device_user_last_name', 'like', '%' . $key . '%')
                            ->orWhere('students.Grade', 'like', '%' . $key . '%')
                            ->orWhere('inventory_management.Building', 'like', '%' . $key . '%')
                            ->orWhere('inventory_management.Serial_number', 'like', '%' . $key . '%')
                            ->orWhere('inventory_management.Asset_tag', 'like', '%' . $key . '%');
                });
            }
        } else {
            return response()->json(
                            collect([
                        'response' => 'error'
            ]));
        }

        if ($skey == 1) {
            $sflag == 'as' ? $inventory->orderBy("inventory_management.Serial_number", "asc") : $inventory->orderBy("inventory_management.Serial_number", "desc");
        } elseif ($skey == 2) {
            $sflag == 'as' ? $inventory->orderBy("inventory_management.Asset_tag", "asc") : $inventory->orderBy("inventory_management.Asset_tag", "desc");
        } elseif ($skey == 3) {
            $sflag == 'as' ? $inventory->orderBy("students.Device_user_first_name", "asc") : $inventory->orderBy("students.Device_user_first_name", "desc");
        } elseif ($skey == 4) {
            $sflag == 'as' ? $inventory->orderBy("students.Grade", "asc") : $inventory->orderBy("students.Grade", "desc");
        } elseif ($skey == 5) {
            $sflag == 'as' ? $inventory->orderBy("inventory_management.Building", "asc") : $inventory->orderBy("inventory_management.Building", "desc");
        } elseif ($skey == 6) {
            $sflag == 'as' ? $inventory->orderBy("inventory_management.Purchase_date", "asc") : $inventory->orderBy("inventory_management.Purchase_date", "desc");
        }else {
        $inventory->orderByDesc('inventory_management.ID');
    }
     $data = $inventory->get();
     $data = $inventory->paginate($limit, ['*'], 'page',$page);
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $data,
        ]));
    }
    public function createStudent(Request $request,$inventory)
{     
    $checkStudentNum = Student::where('School_ID', $request->input('schoolid'))->where('Student_num', $request->input('Studentnum'))->first();
    if(isset($checkStudentNum)){
        return response()->json(collect([
            'response' => 'error',
            'msg' => 'Student Number already exists',                          
        ]));
    }

    $Student = new Student;
    $Student->Device_user_first_name = $request->input('Deviceuserfirstname');
    $Student->Device_user_last_name = $request->input('Deviceuserlastname');      
    $Student->Parent_guardian_name = $request->input('Parentname');
    $Student->Parent_guardian_Email = $request->input('ParentEmail');
    $Student->Parent_phone_number = $request->input('Parentphonenumber');
    $Student->Parental_coverage = $request->input('Parentalcoverage');
    $Student->School_ID = $request->input('schoolid');
    $Student->Student_num = $request->input('Studentnum');
    $Student->Grade = $request->input('Grade');
    $Student->save();

    $StudentInventory = new StudentInventory;
    $StudentInventory->Inventory_ID = $inventory;
    $StudentInventory->Student_ID = $Student->id;
    $StudentInventory->School_ID = $request->input('schoolid');
    $StudentInventory->save();
                        
    $deviceAllocationLog = new DeviceAllocationLog;
    $deviceAllocationLog->Inventory_ID = $inventory;
    $deviceAllocationLog->Student_ID = $Student->id;
    $deviceAllocationLog->School_ID = $request->input('schoolid');
    $deviceAllocationLog->Allocated_Date = date("Y-m-d");
    $checkLonerdevice = InventoryManagement::where('ID',$inventory)->first();                      
    if ($checkLonerdevice->Loaner_device == 1) {
        $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
    }
    $deviceAllocationLog->save();      
}

   
    function addUpdateInventory(Request $request) {
               
        if ($request->input('AddUpdateFlag') == 1) {//add inventory
            $inventory = new InventoryManagement;
            $inventory->Purchase_date = $request->input('PurchaseDate');
            $inventory->Device_manufacturer = $request->input('Devicemanufacturer');
            $inventory->Device_model = $request->input('Devicemodel');
            $inventory->Device_os = $request->input('Deviceos');
            $inventory->Manufacturer_warranty_until = $request->input('Manufacturerwarrantyuntil');
            $inventory->Manufacturer_ADP_until = $request->input('ManufacturerADPuntil');
            $inventory->Serial_number = $request->input('Serialnumber');
            $inventory->Third_party_extended_warranty_until = $request->input('Thirdpartyextendedwarrantyuntil');
            $inventory->Third_party_ADP_until = $request->input('ThirdpartyADPuntil');
            $inventory->Expected_retirement = $request->input('Expectedretirement');
            $inventory->Loaner_device = $request->input('Loanerdevice');
            if ($request->input('Building') == 0) {
                $addBuilding = new Building;
                $addBuilding->Building = $request->input('BuildingName');
                $addBuilding->SchoolID = $request->input('schoolid');
                $addBuilding->save();
                $inventory->Building = $addBuilding->id;
            } elseif ($request->input('Building') == 'null') {
                $building = NULL;
            } else {
                $inventory->Building = $request->input('Building');
            }

            if ($request->input('Loanerdevice') == 1) {
                $inventory->inventory_status = 3;
            } else {
                $inventory->inventory_status = 1;
            }
            $inventory->Device_MPN = $request->input('DeviceMPN');
            $inventory->Asset_tag = $request->input('Assettag');
            $inventory->Repair_cap = $request->input('Repaircap');
            $inventory->user_id = $request->input('userid');
            $inventory->school_id = $request->input('schoolid');
            
            $checkSerialNum = InventoryManagement::where('school_id', $request->input('schoolid'))->where('Serial_number', $request->input('Serialnumber'))->first();
            if (isset($checkSerialNum)) { //serial num check
                return response()->json(collect(['response' => 'error', 'msg' => 'Serial Number already exists',]));
            } else {
                if ($request->input('LoanerFlag') == 'yes') {//make device loner
                    $inventory->save();
                    return response()->json(collect(['response' => 'success',]));
                } elseif ($request->input('LoanerFlag') == 'no' && $request->input('UserFlag') == 'no') {//device without user 
                    $inventory->save();
                    return response()->json(collect(['response' => 'success',]));
                } elseif ($request->input('LoanerFlag') == 'no' && $request->input('UserFlag') == 'yes') {// device with user 
                    $inventory->save();
                    if ($request->input('Student_Id') == '') {//new student
                        $this->createStudent($request, $inventory->id);
                    } else { //student inventory
                        $StudentInventory = new StudentInventory;
                        $StudentInventory->Inventory_ID = $inventory->id;
                        $StudentInventory->Student_ID = $request->input('Student_Id');
                        $StudentInventory->School_ID = $request->input('schoolid');
                        $StudentInventory->save();
                    }
                    return response()->json(collect(['response' => 'success',]));
                } else {//only update student inventories
                    return response()->json(collect(['response' => 'error',]));
                }
            }
        }
        elseif ($request->input('AddUpdateFlag') == 2) {
             if ($request->input('Building') == 0) {              
                $addBuilding = new Building;
                $addBuilding->Building = $request->input('BuildingName');
                $addBuilding->SchoolID = $request->input('schoolid');
                $addBuilding->save();
                $building = $addBuilding->id;
            }elseif($request->input('Building') == 'null'){
                $building = NULL;
            } 
            else {
                $building = $request->input('Building');
            }
           $serialNum = InventoryManagement::where('Serial_number', $request->input('Serialnumber'))->where('school_id',$request->input('schoolid'))->first();
           if (isset($serialNum)) {
            $MatchwithId = InventoryManagement::where('ID', $request->input('ID'))->where('Serial_number', $request->input('Serialnumber'))->first();
            if(isset($MatchwithId)){
                  $loanerDevice = $request->input('Loanerdevice');
                  $inventoryStatus = ($loanerDevice == 1) ? 3 : 1;
                  $updatedInventory = InventoryManagement::where('ID', $request->input('ID'))
                        ->update(['Purchase_date' => $request->input('PurchaseDate'),
                    'Device_manufacturer' => $request->input('Devicemanufacturer'),
                    'Device_model' => $request->input('Devicemodel'),
                    'Device_os' => $request->input('Deviceos'),
                    'Manufacturer_warranty_until' => $request->input('Manufacturerwarrantyuntil'),
                    'Manufacturer_ADP_until' => $request->input('ManufacturerADPuntil'),
                    'Serial_number' => $request->input('Serialnumber'),
                    'Third_party_extended_warranty_until' => $request->input('Thirdpartyextendedwarrantyuntil'),
                    'Third_party_ADP_until' => $request->input('ThirdpartyADPuntil'),
                    'Expected_retirement' => $request->input('Expectedretirement'),
                    'Loaner_device' => $request->input('Loanerdevice'),
                    'Device_MPN' => $request->input('DeviceMPN'),
                    'Asset_tag' => $request->input('Assettag'),
                    'Repair_cap' => $request->input('Repaircap'),
                    'user_id' => $request->input('userid'),
                    'school_id' => $request->input('schoolid'),
                    'Building' =>$building,                   
                    'inventory_status' => $inventoryStatus        
                ]);                        
            }else{
                return response()->json(
                                collect([
                            'response' => 'error',
                            'msg' => 'Serial Number already exists',                           
                ]));
            }
        }
            else{
                  $loanerDevice = $request->input('Loanerdevice');
                  $inventoryStatus = ($loanerDevice == 1) ? 3 : 1;
                  $updatedInventory = InventoryManagement::where('ID', $request->input('ID'))
                        ->update(['Purchase_date' => $request->input('PurchaseDate'),
                    'Device_manufacturer' => $request->input('Devicemanufacturer'),
                    'Device_model' => $request->input('Devicemodel'),
                    'Device_os' => $request->input('Deviceos'),
                    'Manufacturer_warranty_until' => $request->input('Manufacturerwarrantyuntil'),
                    'Manufacturer_ADP_until' => $request->input('ManufacturerADPuntil'),
                    'Serial_number' => $request->input('Serialnumber'),
                    'Third_party_extended_warranty_until' => $request->input('Thirdpartyextendedwarrantyuntil'),
                    'Third_party_ADP_until' => $request->input('ThirdpartyADPuntil'),
                    'Expected_retirement' => $request->input('Expectedretirement'),
                    'Loaner_device' => $request->input('Loanerdevice'),
                    'Device_MPN' => $request->input('DeviceMPN'),
                    'Asset_tag' => $request->input('Assettag'),
                    'Repair_cap' => $request->input('Repaircap'),
                    'user_id' => $request->input('userid'),
                    'school_id' => $request->input('schoolid'),
                    'Building' => $building,
                    'inventory_status' => $inventoryStatus
                ]);   
        }
               if ($request->input('LoanerFlag') == 'no' && $request->input('UserFlag') == 'no') {            
                $checkAllocateDevice = StudentInventory::where('Inventory_Id', $request->input('ID'))->first();
                if(isset($checkAllocateDevice)){
                    DeviceAllocationLog::where('School_ID',$request->input('schoolid'))->where('Student_ID',$checkAllocateDevice->Student_ID)->where('Inventory_ID',$request->input('ID'))->update(['Vacant_Date'=>date("Y-m-d")]);  
                  StudentInventory::where('Inventory_Id', $request->input('ID'))->forceDelete();                             
                }
            } elseif ($request->input('LoanerFlag') == 'no' && $request->input('UserFlag') == 'yes') {   
                if ($request->input('ExistingNewFlag') == 'existing') {
                    $checkInventory = StudentInventory::where('Inventory_Id', $request->input('ID'))->first();
                    if (isset($checkInventory)) {
                        StudentInventory::where('Inventory_Id', $request->input('ID'))->update(['Student_ID' => $request->input('Student_Id')]);
                    } else {
                        $StudentInventory = new StudentInventory;
                        $StudentInventory->Inventory_ID = $request->input('ID');
                        $StudentInventory->Student_ID = $request->input('Student_Id');
                        $StudentInventory->School_ID = $request->input('schoolid');
                        $StudentInventory->save();
                    }
                } elseif ($request->input('ExistingNewFlag') == 'new') {
                  
                   $checkInventory = StudentInventory::where('Inventory_Id', $request->input('ID'))->first();
                    if (isset($checkInventory)) {   
                        DeviceAllocationLog::where('School_ID',$request->input('schoolid'))->where('Student_ID',$checkInventory->Student_ID)->where('Inventory_ID',$request->input('ID'))->update(['Vacant_Date'=>date("Y-m-d")]);  
                        StudentInventory::where('Inventory_Id', $request->input('ID'))->forceDelete();                         
                    } 
                      $this->createStudent($request, $request->input('ID'));                                         
                } else {
                    return response()->json(collect(['response' => 'error',]));
                }
            }elseif($request->input('LoanerFlag') == 'yes'){
              return response()->json(
                            collect([
                        'response' => 'success',
            ]));  
            }
            else{
               return response()->json(collect(['response' => 'error',])); 
            }
            return response()->json(
                            collect([
                        'response' => 'success',
            ]));                  
        } else {
            return 'error';
        }
    }
   
    function allDevice(){
     $get = DeviceType::all();
     return $get;
    }
    
    function getInventoryDataById($id) {
        $inventory = InventoryManagement::with('studentInventory.student', 'ticket', 'ticket.ticketHistory','building')
                ->where('ID', $id)
                ->first();

        $inventory->ticket->each(function ($ticket) {
            $statusData = TicketStatus::where('ID', $ticket->ticket_status)->first(); // Accessing the status directly from the ticket      
            $ticket->statusName = $statusData->status;
            $ticket->ticketCreatedBy = $ticket->user->first_name . ' ' . $ticket->user->last_name;
            $ticket->createdDate = $ticket->created_at;
            $ticket->issues = $ticket->ticketIssues->map(function ($ticketIssue) {
                        $issueId = $ticketIssue->issue_Id;
                        $deviceIssue = DeviceIssue::find($issueId);
                        return $deviceIssue ? $deviceIssue->issue : null;
                    })->toArray();
                    
            $ticket->makeHidden(['ticketIssues', 'statusname', 'created_at', 'updated_at']);
            $ticket->ticketHistory->map(function ($ticketHistory) {
                $userData = User::where('ID', $ticketHistory->updated_by_user_id)->first();
                $ticketStatus = TicketStatus::where('ID', $ticketHistory->Status_from)->first();
                $ticketHistory->to = $ticketStatus->status;
                $ticketStatus = TicketStatus::where('ID', $ticketHistory->Status_to)->first();
                $ticketHistory->from = $ticketStatus->status;
                $ticketHistory->updateby = $userData->first_name . ' ' . $userData->last_name;
                $ticketHistory->date = $ticketHistory->created_at;
                $ticketHistory->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            })->toArray();
            
        });
//        $deviceData = DeviceType::find($inventory->Device_type);
        $inventory->device_name = $deviceData->type ?? null;
        $inventory->student = $inventory->studentInventory->student ?? null;
        $inventory->makeHidden(['created_at', 'updated_at', 'studentInventory', 'ticket_issues', 'statusname']);
        return $inventory;
    }

    
}
