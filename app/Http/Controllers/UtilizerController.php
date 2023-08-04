<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentInventory;
use App\Models\InventoryManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\DeviceIssue;
use App\Models\Ticket;
use App\Models\TicketIssue;
use App\Models\DeviceAllocationLog;

class UtilizerController extends Controller {

    public function allUtilizer($sid, $skey) {
        if ($skey == 'null') {
            $get = DB::table('students as s')
                    ->leftJoin('student_inventories as si', 'si.Student_ID', '=', 's.ID')
                    ->leftJoin('inventory_management as im', function ($join) {
                        $join->on('im.ID', '=', 'si.Inventory_ID')
                        ->orWhere('im.ID', '=', 'si.Loner_ID');
                    })
                    ->select('s.ID as utilizerid', 's.School_ID as schId', 's.Device_user_first_name', 's.Device_user_last_name', 's.Parent_guardian_name', 's.Parent_Guardian_Email', 's.Student_num', 's.Grade',
                            DB::raw("GROUP_CONCAT(CONCAT(im.Device_model, IF(im.inventory_status = 3, '(Loaner)', '(Active)'), '(', im.Serial_number, ')') SEPARATOR ', ') as model"),
                            DB::raw("GROUP_CONCAT(CONCAT(im.Device_model, '(', im.Serial_number, ')')) AS serialnum"))
                    ->where('s.School_ID', $sid)
                    ->groupBy('s.ID')
                    ->get();
        } else {
            $get = DB::table('students as s')
                    ->leftJoin('student_inventories as si', 'si.Student_ID', '=', 's.ID')
                    ->leftJoin('inventory_management as im', function ($join) {
                        $join->on('im.ID', '=', 'si.Inventory_ID')
                        ->orWhere('im.ID', '=', 'si.Loner_ID');
                    })
                    ->select('s.ID as utilizerid', 's.School_ID as schId', 's.Device_user_first_name', 's.Device_user_last_name', 's.Parent_guardian_name', 's.Parent_Guardian_Email', 's.Student_num', 's.Grade',
                            DB::raw("GROUP_CONCAT(CONCAT(im.Device_model, IF(im.inventory_status = 3, '(Loaner)', '(Active)'), ' ', ' ') SEPARATOR '') as model"))
                    ->where('s.School_ID', $sid)
                    ->where(function ($query) use ($skey) {
                        $query->where('s.Device_user_first_name', 'like', '%' . $skey . '%')
                        ->orWhere('s.Device_user_last_name', 'like', '%' . $skey . '%')
                        ->orWhere('s.Grade', 'like', '%' . $skey . '%')
//                        ->orWhere('s.Building', 'like', '%' . $skey . '%')
                        ->orWhere('s.Parent_guardian_name', 'like', '%' . $skey . '%')
                        ->orWhere('s.Parent_Guardian_Email', 'like', '%' . $skey . '%')
                        ->orWhere('s.Student_num', 'like', '%' . $skey . '%');
                    })
                    ->groupBy('s.ID')
                    ->get();
        }

        foreach ($get as $result) {
            // Split the model string into an array of models
            $models = explode(',', $result->model);
            $model_array = array();

            // Iterate over the models and create an array of objects for each one
            foreach ($models as $model) {
                $model_parts = explode('(', $model);
                $device_model = trim($model_parts[0]);
                $flag = "";
                $serial_num = "";
                $inventory_id = "";
                foreach ($model_parts as $index => $part) {
                    if ($index == 0)
                        continue;
                    $part = trim(str_replace(')', '', $part));
                    if ($index % 2 == 1) {
                        $flag = $part;
                    } else {
                        $serial_num = $part;
                        $inventory_id = DB::table('inventory_management')
                                ->where('Serial_number', '=', $serial_num)
                                ->value('ID');
                    }
                }
                $model_object = array("deviceModel" => $device_model, "Flag" => $flag, "SerialNum" => $serial_num, "InventoryID" => $inventory_id);
                array_push($model_array, $model_object);
            }

            // Replace the original model property with the new array of objects
            $result->model = $model_array;
        }

        return Response::json(array(
                    'status' => "success",
                    'msg' => $get
        ));
    }

    public function utilizerdatabyID($id) {

        $get = Student::where('ID', $id)->first();

        return Response::json(array(
                    'status' => "success",
                    'msg' => $get,
        ));
    }

    public function deleteUtilizer($id) {

        $studentinventoriesdata = StudentInventory::where('Student_ID', $id)->first();
        if (isset($studentinventoriesdata)) {
            $getdata = StudentInventory::where('Student_ID', $id)->get();
            foreach ($getdata as $data) {

                $studentinventories = StudentInventory::where('Inventory_ID', $data['Inventory_ID'])->forceDelete();
                DeviceAllocationLog::where('Student_ID', $id)->where('Inventory_ID', $data['Inventory_ID'])->update(['Vacant_Date' => date("Y-m-d")]);
                $ticket = Ticket::where('inventory_id', $data['Inventory_ID'])->update(['ticket_status' => 2]);
                $inventory = InventoryManagement::where('ID', $data['Inventory_ID'])->update(['inventory_status' => 1]);
                $get = Student::where('ID', $id)->forceDelete();
            }
            return Response::json(array(
                        'status' => "success",
            ));
        } else {
            $get = Student::where('ID', $id)->forceDelete();
            return Response::json(array(
                        'status' => "success",
            ));
        }
    }

    function importUtilizer(Request $request) {
        try {
            $userId = $request->input('ID');
            $schId = $request->input('schId');
            $result = $request->file('file');
            $file = fopen($result, 'r');
            $header = fgetcsv($file);
            $escapedheader = [];
            foreach ($header as $key => $value) {
                $lheader = strtolower($value);
                $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                array_push($escapedheader, $escapedItem);
            }
            while ($columns = fgetcsv($file)) {

                if ($columns[0] == "") {
                    continue;
                }

                foreach ($columns as $key => &$value) {
                    $value;
                }

                $data = array_combine($escapedheader, $columns);

                $FirstName = $data['userfirstname'];
                $LastName = $data['userlastname'];
                $Grade = $data['grade'] != "" ? $data['grade'] : NULL;
                $ParentName = $data['parentguardianname'];
                $ParentEmail = $data['parentguardianemail'];
                $ParentNum = $data['parentphonenumber'];
                $ParentCoverage = $data['parentalcoverage'];
                $StudentNum = $data['studentnumber'];

//                $savedInventory = Student::where('Device_user_first_name',$FirstName)->where('Device_user_last_name',$LastName)->where('Parent_Guardian_Email',$ParentEmail)->where('school_id',$schId)->first(); 
                $savedInventory = Student::where('Student_num', $StudentNum)->where('School_ID', $schId)->first();
                if (isset($savedInventory)) {
                    if ($ParentCoverage == 'Yes' || $ParentCoverage == 'YES' || $ParentCoverage == 'yes') {
                        $FinalParentCoverage = 1;
                    } else {
                        $FinalParentCoverage = 0;
                    }
                    $updatedDetail = Student::where('School_ID', $schId)->where('Student_num', $savedInventory->Student_num)
                            ->update(['Device_user_first_name' => $FirstName,
                        'Device_user_last_name' => $LastName,
                        'Grade' => $Grade,
                        'Parent_guardian_name' => $ParentName,
                        'Parent_phone_number' => $ParentNum,
                        'Parent_Guardian_Email' => $ParentEmail,
                        'Parental_coverage' => $FinalParentCoverage,
                        'Student_num' => $StudentNum,
                    ]);
                } else {
                    $Utilizer = new Student;
                    $Utilizer->School_ID = $schId;
                    $Utilizer->Device_user_first_name = $FirstName;
                    $Utilizer->Device_user_last_name = $LastName;
                    $Utilizer->Grade = $Grade;
                    $Utilizer->Parent_guardian_name = $ParentName;
                    $Utilizer->Parent_Guardian_Email = $ParentEmail;
                    if ($ParentCoverage == 'Yes' || $ParentCoverage == 'YES' || $ParentCoverage == 'yes') {
                        $Utilizer->Parental_coverage = 1;
                    } else {
                        $Utilizer->Parental_coverage = 0;
                    }
                    $Utilizer->Parent_phone_number = $ParentNum;
                    $savedInventory = Student::where('School_ID', $schId)->where('Student_num', $StudentNum)->first();
                    if (isset($savedInventory)) {
                        Student::where('Student_num', $StudentNum)->update(['Device_user_first_name' => $FirstName, 'Device_user_last_name' => $LastName, 'Grade' => $Grade, 'Building' => $Building, 'Parent_guardian_name' => $ParentName, 'Parent_Guardian_Email' => $ParentEmail, 'Parent_phone_number' => $ParentNum, 'Parental_coverage' => $ParentCoverage, 'Student_num' => $StudentNum]);
                    } else {
                        $Utilizer->Student_num = $StudentNum;
                        $Utilizer->save();
                    }
                }
            }
            return "success";
        } catch (\Throwable $th) {
            return "Invalid CSV";
        }
    }

    function AddUpdateUtilizer(Request $request) {

        if ($request->input('ID') == 0) {
            $Student = new Student;
            $Student->Device_user_first_name = $request->input('firstname');
            $Student->Device_user_last_name = $request->input('lastname');
            $Student->Parent_guardian_name = $request->input('Parentname');
            $Student->Parent_guardian_Email = $request->input('Parentemail');
            $Student->Parent_phone_number = $request->input('Parentnum');
            $Student->Parental_coverage = $request->input('Parentcoverage');
            $Student->School_ID = $request->input('Scoolid');
            $Student->Grade = $request->input('Grade');
            $savedInventory = Student::where('School_ID', $request->input('Scoolid'))
                    ->where('Student_num', $request->input('Studentnum'))
                    ->first();

            if (isset($savedInventory)) {
                return Response::json(['status' => 'error', 'msg' => 'Student Number already exists']);
            } else {
                $Student->Student_num = $request->input('Studentnum');
                $Student->save();
                return Response::json(['status' => 'success',]);
            }
        } else {
            $checkStudentNum = Student::where('School_ID', $request->input('Scoolid'))->where('Student_num', $request->input('Studentnum'))->first();

            if (isset($checkStudentNum)) {
                $checkStudentNumWithId = Student::where('School_ID', $request->input('Scoolid'))->where('ID', $request->input('ID'))->where('Student_num', $request->input('Studentnum'))->first();
                if (isset($checkStudentNumWithId)) {
                    $updateUser = Student::where('ID', $request->input('ID'))->update(['Device_user_first_name' => $request->input('firstname'), 'Device_user_last_name' => $request->input('lastname'), 'Grade' => $request->input('Grade'), 'Parent_guardian_name' => $request->input('Parentname'), 'Parent_Guardian_Email' => $request->input('Parentemail'), 'Parent_phone_number' => $request->input('Parentnum'), 'Parental_coverage' => $request->input('Parentcoverage'), 'Student_num' => $request->input('Studentnum')]);
                    return Response::json(['status' => 'success',]);
                } else {
                    return Response::json(['status' => 'error', 'msg' => 'Student Number is already exists']);
                }
            } else {
                $updateUser = Student::where('ID', $request->input('ID'))->update(['Device_user_first_name' => $request->input('firstname'), 'Device_user_last_name' => $request->input('lastname'), 'Grade' => $request->input('Grade'), 'Parent_guardian_name' => $request->input('Parentname'), 'Parent_Guardian_Email' => $request->input('Parentemail'), 'Parent_phone_number' => $request->input('Parentnum'), 'Parental_coverage' => $request->input('Parentcoverage'), 'Student_num' => $request->input('Studentnum')]);
                return Response::json(['status' => 'success',]);
            }
        }
    }

    function UtilizerDetailsById($id) {
        $get = Student::where('ID', $id)->first();
        $studentInventory = StudentInventory::where('Student_ID', $id)->get();
        $devicearray = array();
        foreach ($studentInventory as $data) {
            $Inventoryid = $data['Inventory_ID'];
            $inventoryData = InventoryManagement::where('ID', $Inventoryid)->first();
            array_push($devicearray, $inventoryData);
        }
        $utilizerLog = DeviceAllocationLog::where('School_ID', $get->School_ID)->where('Student_ID', $id)->get();
        $data_array = array();
        foreach ($utilizerLog as $utilizerLogData) {
            $device = InventoryManagement::where('ID', $utilizerLogData['Inventory_ID'])->first();
            array_push($data_array, ['SerialNum' => $device->Serial_number, 'AllocatedDate' => $utilizerLogData['Allocated_Date'], 'LonerDeviceAllocationDate' => $utilizerLogData['Loner_Allocation_Date']]);
        }

        return Response::json(array(
                    'status' => "success",
                    'msg' => $get,
                    'allocatedDevice' => $devicearray,
                    'studentHistory' => $data_array,
        ));
    }

    function UtilizerData($sid, $key, $skey, $flag, $page) {
        $subQuery = DB::table('students as s')
                ->leftJoin('student_inventories as si', 'si.Student_ID', '=', 's.ID')
                ->leftJoin('inventory_management as im1', 'im1.ID', '=', 'si.Inventory_ID')
                ->leftJoin('inventory_management as im2', 'im2.ID', '=', 'si.Loner_ID')
                ->select(
                        's.id as utilizerid',
                        's.School_ID as schId',
                        's.Device_user_first_name',
                        's.Device_user_last_name',
                        's.Parent_guardian_name',
                        's.Parent_Guardian_Email',
                        's.Parent_phone_number',
                        's.Student_num',
                        's.Grade',
                        'im1.Serial_number as inventory_serial_number',
                        'im2.Serial_number as loner_serial_number',
                        'im1.ID as inventory_ID',
                        'im2.ID as loner_ID'
                )
                ->where('s.School_ID', $sid);

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery)
                ->select(
                        'sub.utilizerid',
                        DB::raw('ANY_VALUE(sub.schId) as schId'),
                        DB::raw('ANY_VALUE(sub.Device_user_first_name) as Device_user_first_name'),
                        DB::raw('ANY_VALUE(sub.Device_user_last_name) as Device_user_last_name'),
                        DB::raw('ANY_VALUE(sub.Parent_guardian_name) as Parent_guardian_name'),
                        DB::raw('ANY_VALUE(sub.Parent_Guardian_Email) as Parent_Guardian_Email'),
                        DB::raw('ANY_VALUE(sub.Parent_phone_number) as Parent_phone_number'),
                        DB::raw('ANY_VALUE(sub.Student_num) as Student_num'),
                        DB::raw('ANY_VALUE(sub.Grade) as Grade'),
                        DB::raw('CASE WHEN TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_serial_number, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_serial_number, "")))) = "" THEN null ELSE TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_serial_number, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_serial_number, "")))) END as Serial_number'),
                        DB::raw('CASE WHEN TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_ID, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_ID, "")))) = "" THEN null ELSE TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_ID, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_ID, "")))) END as Inventory_IDs')
                )
                ->groupBy('sub.utilizerid');

        $utilizerData = $query->get();

        if ($key != 'null') {
            $searchResults = $query->where(function ($q) use ($key) {
                $searchFields = [
                    'sub.Device_user_first_name',
                    'sub.Device_user_last_name',
                    'sub.Student_num',
                    'sub.inventory_serial_number',
                    'sub.loner_serial_number',
                ];
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%' . strtolower($key) . '%');
                }
            });
        }

        $totalCount = $query->get()->count();
        $utilizerData = $query->paginate(15, ['*'], 'page', $page);
        $collection = $utilizerData->getCollection();
        $searchResults = $collection;

        if ($skey == 1) {
            $searchResults = $searchResults->sortBy('Device_user_first_name', SORT_NATURAL | SORT_FLAG_CASE, $flag === 'as');
        } elseif ($skey == 2) {
            $searchResults = $searchResults->sortBy('Student_num', SORT_NATURAL | SORT_FLAG_CASE, $flag === 'as');
        } elseif ($skey == 3) {
            $searchResults = $searchResults->sortBy('Grade', SORT_NATURAL | SORT_FLAG_CASE, $flag === 'as');
        } else {
            $searchResults = $searchResults->sortByDesc('utilizerid');
        }
        $data_array = array();
        foreach ($utilizerData as $utilizerData) {
            $utilizerLog = DeviceAllocationLog::where('School_ID', $sid)->where('Student_ID', $utilizerData->utilizerid)->get();
            $allocation_array = array();
            foreach ($utilizerLog as $utilizerLogData) {
                $device = InventoryManagement::where('ID', $utilizerLogData->Inventory_ID)->first();
                array_push($allocation_array, ['SerialNum' => $device->Serial_number, 'AllocatedDate' => $utilizerLogData->Allocated_Date, 'LonerDeviceAllocationDate' => $utilizerLogData->Loner_Allocation_Date]);
            }
            array_push($data_array, ['StudentName' => $utilizerData->Device_user_first_name . ' ' . $utilizerData->Device_user_last_name, 'Student_num' => $utilizerData->Student_num, 'Grade' => $utilizerData->Grade, 'ParentName' => $utilizerData->Parent_guardian_name, 'ParentEmail' => $utilizerData->Parent_Guardian_Email, 'ParentNumber' => $utilizerData->Parent_phone_number, 'AllocationLog' => $allocation_array]);
        }
        return response()->json(['status' => 'success', 'msg' => $searchResults->values(), 'utilizerlog' => $data_array, 'count' => $totalCount]);
    }

    function UtilizerExport($sid) {
        $subQuery = DB::table('students as s')
                ->leftJoin('student_inventories as si', 'si.Student_ID', '=', 's.ID')
                ->leftJoin('inventory_management as im1', 'im1.ID', '=', 'si.Inventory_ID')
                ->leftJoin('inventory_management as im2', 'im2.ID', '=', 'si.Loner_ID')
                ->select(
                        's.id as utilizerid',
                        's.School_ID as schId',
                        's.Device_user_first_name',
                        's.Device_user_last_name',
                        's.Parent_guardian_name',
                        's.Parent_Guardian_Email',
                        's.Parent_phone_number',
                        's.Student_num',
                        's.Grade',
                        'im1.Serial_number as inventory_serial_number',
                        'im2.Serial_number as loner_serial_number',
                        'im1.ID as inventory_ID',
                        'im2.ID as loner_ID'
                )
                ->where('s.School_ID', $sid);

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery)
                ->select(
                        'sub.utilizerid',
                        DB::raw('ANY_VALUE(sub.schId) as schId'),
                        DB::raw('ANY_VALUE(sub.Device_user_first_name) as Device_user_first_name'),
                        DB::raw('ANY_VALUE(sub.Device_user_last_name) as Device_user_last_name'),
                        DB::raw('ANY_VALUE(sub.Parent_guardian_name) as Parent_guardian_name'),
                        DB::raw('ANY_VALUE(sub.Parent_Guardian_Email) as Parent_Guardian_Email'),
                        DB::raw('ANY_VALUE(sub.Parent_phone_number) as Parent_phone_number'),
                        DB::raw('ANY_VALUE(sub.Student_num) as Student_num'),
                        DB::raw('ANY_VALUE(sub.Grade) as Grade'),
                        DB::raw('CASE WHEN TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_serial_number, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_serial_number, "")))) = "" THEN null ELSE TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_serial_number, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_serial_number, "")))) END as Serial_number'),
                        DB::raw('CASE WHEN TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_ID, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_ID, "")))) = "" THEN null ELSE TRIM(BOTH "," FROM CONCAT(GROUP_CONCAT(IFNULL(sub.inventory_ID, "")), ",", GROUP_CONCAT(IFNULL(sub.loner_ID, "")))) END as Inventory_IDs')
                )
                ->groupBy('sub.utilizerid');

        $utilizerData = $query->get();

        $searchResults = $utilizerData->sortByDesc('utilizerid');

//
        $data_array = array();
        foreach ($utilizerData as $utilizerData) {
            $utilizerLog = DeviceAllocationLog::where('School_ID', $sid)->where('Student_ID', $utilizerData->utilizerid)->get();
            $allocation_array = array();
            foreach ($utilizerLog as $utilizerLogData) {
                $device = InventoryManagement::where('ID', $utilizerLogData->Inventory_ID)->first();
                array_push($allocation_array, ['SerialNum' => $device->Serial_number, 'AllocatedDate' => $utilizerLogData->Allocated_Date, 'LonerDeviceAllocationDate' => $utilizerLogData->Loner_Allocation_Date]);
            }
            array_push($data_array, ['StudentName' => $utilizerData->Device_user_first_name . ' ' . $utilizerData->Device_user_last_name, 'Student_num' => $utilizerData->Student_num, 'Grade' => $utilizerData->Grade, 'ParentName' => $utilizerData->Parent_guardian_name, 'ParentEmail' => $utilizerData->Parent_Guardian_Email, 'ParentNumber' => $utilizerData->Parent_phone_number, 'AllocationLog' => $allocation_array]);
        }
        return response()->json(['status' => 'success', 'msg' => $searchResults->values(), 'utilizerlog' => $data_array]);
    }

}
