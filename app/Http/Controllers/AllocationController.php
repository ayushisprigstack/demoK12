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

class AllocationController extends Controller {

   public function allActiveDevice($sid, $skey) {
        $allocatedDevice = StudentInventory::where('School_ID', $sid)->whereNotNull('Inventory_ID')->pluck('Inventory_ID')->all();
        $query = InventoryManagement::whereNotIn('inventory_status', [2])
                ->whereNotIn('ID', $allocatedDevice)
                ->where('school_id', $sid)
                ->select('ID', 'Device_model', 'Asset_tag', 'Serial_number');

        if ($skey != 'null') {
            $query->where(function ($query) use ($skey) {
                $query->where('ID', 'LIKE', "%$skey%")
                        ->orWhere('Device_model', 'LIKE', "%$skey%")
                        ->orWhere('Asset_tag', 'LIKE', "%$skey%")
                        ->orWhere('Serial_number', 'LIKE', "%$skey%");
            });
        }

        $devices = $query->get();

        return response()->json([
                    'response' => 'success',
                    'msg' => $devices,
        ]);
    }

    public function allActiveUtilizer($sid, $skey) {
        $get = Student::where('School_ID', $sid)->where(function ($query) use ($skey) {
                    $query->where('Device_user_first_name', 'LIKE', "%$skey%");
                    $query->orWhere('Grade', 'LIKE', "%$skey%");                   
                    $query->orWhere('Parent_guardian_name', 'LIKE', "%$skey%");
                    $query->orWhere('Parent_Guardian_Email', 'LIKE', "%$skey%");
                })->get();

        if ($skey == 'null') {
            $get = Student::where('School_ID', $sid)->get();
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $get
        ]));
    }
    
     public function allAllocatedDevice($sid,$skey) { 
         $inventory = null;
         if ($skey == 'null') {
            $inventory = InventoryManagement::with('studentInventory.student')->where('school_id', $sid)->has('studentInventory')->select('Serial_number', 'ID')->get();
//            $inventory->makeHidden(['studentInventory', 'created_at', 'deleted_at', 'updated_at']);
            return $inventory;
        } else {
            $inventory = InventoryManagement::with('studentInventory.student')
                    ->where('school_id', $sid)
                    ->has('studentInventory')
                    ->where(function ($query) use ($skey) {
                        $query->where('Serial_number', 'like', "%$skey%")
                        ->orWhere('ID', 'like', "%$skey%");
                    })
                    ->select('Serial_number', 'ID')
                    ->get();
//                 $inventory->makeHidden(['studentInventory', 'created_at', 'deleted_at', 'updated_at']);     
            return $inventory;
        }
    }

    public function deviceAllocation(Request $request) {
        $schoolID = $request->input('Student_Id');
        $allocationArray = $request->input('Device_allocate');
        foreach ($allocationArray as $data) {
            $inventoryId = $data['deviceId'];
            $userId = $data['userId'];
            $studentInventory = StudentInventory::where('Inventory_ID', $inventoryId)
                    ->orWhere('Student_ID', $userId)
                    ->first();
            if (isset($studentInventory)) {
                $RemoveStudentInventory = StudentInventory::where('Student_ID', $userId)
                        ->orWhere('Inventory_ID', $inventoryId)
                        ->forceDelete();                
            }

            $StudentInventory = new StudentInventory;
            $StudentInventory->Inventory_ID = $inventoryId;
            $StudentInventory->Student_ID = $userId;
            $StudentInventory->School_ID = $schoolID;
            $StudentInventory->save();
            
        }

        return response()->json([
                    'response' => 'success'
        ]);
    }
    
        function DeviceAllocationToUSer(Request $request){
        $schoolID = $request->input('Schoolid');
        $idArray = $request->input('DeviceArray');
        $flag =  $request->input('Flag');
        foreach($idArray as $data){
            if($flag == 1){          
            $studentInventory = new StudentInventory();
            $studentInventory->Inventory_ID = $data['deviceid'];
            $studentInventory->School_ID = $schoolID;
            $studentInventory->Student_ID =$data['userid'];
            $studentInventory->save();

            $deviceAllocationLog = new DeviceAllocationLog;
            $deviceAllocationLog->Inventory_ID = $data['deviceid'];
            $deviceAllocationLog->Student_ID = $data['userid'];
            $deviceAllocationLog->School_ID = $schoolID;
            $deviceAllocationLog->Allocated_Date = date("Y-m-d");
            $checkLonerdevice = InventoryManagement::where('ID',$data['deviceid'])->first();
            if ($checkLonerdevice->Loaner_device == 1) {
                $deviceAllocationLog->Loner_Allocation_Date = date("Y-m-d");
                $deviceAllocationLog->save();
            }
            $deviceAllocationLog->save();
            }else{  
            $studentinventorydata = StudentInventory::where('School_ID', $schoolID)->where(function ($query) use ($data) {$query->where('Inventory_ID', $data['deviceid'])->orWhere('Loner_ID', $data['deviceid']);})->first();
            DeviceAllocationLog::where('School_ID',$schoolID)->whereNull('Vacant_Date')->where('Student_ID',$studentinventorydata->Student_ID)->where('Inventory_ID',$data['deviceid'])->update(['Vacant_Date'=>date("Y-m-d")]);             
            StudentInventory::where('School_ID', $schoolID)
                        ->where(function ($query) use ($data) {
                            $query->where('Inventory_ID', $data['deviceid'])
                            ->orWhere('Loner_ID', $data['deviceid']);
                        })
                        ->forceDelete();                      
                       
                        
            }
        }
       return 'success'; 
        
    }

    public function deallocateUsers($sid,$grade){
        $allAllocation = StudentInventory::where('School_ID',$sid)->get();
        foreach($allAllocation as $allocation){
         $studentData = Student::where('ID',$allocation->Student_ID)->where('Grade',$grade)->first();
         if(isset($studentData)){
          StudentInventory::where('School_ID',$sid)->where('Student_ID',$studentData->ID)->forceDelete();
          
         }

        }
        return 'success';
    }
    
    function allGradeandBuilding($sid) {
        $building = InventoryManagement::with('building')
                ->whereNotNull('Building')
                ->where('school_id', $sid)
                ->distinct()
                ->get(['Building']);

        $grade = Student::whereNotNull('Grade')
                ->where('school_id', $sid)
                ->select('Grade')
                ->distinct()
                ->get();

        $response = [
            "response" => "success",
            "building" => [],
            "grade" => []
        ];

        foreach ($building as $item) {
            $buildingName = $item->building ? $item->building->Building : null;

            $response["building"][] = [
                "ID" => $item->Building,
                "building" => $buildingName
            ];
        }

        foreach ($grade as $item) {
            $response["grade"][] = [
                "Grade" => $item->Grade
            ];
        }
        return response()->json(collect($response));
    }

}
