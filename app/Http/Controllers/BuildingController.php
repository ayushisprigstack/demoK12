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
use App\Models\Building;

class BuildingController extends Controller {
    
  
    function addUpdateBuildings(Request $request) {
        $buildingName = $request->input('Building');
        $schoolId = $request->input('SchoolID');
        $addUpdateFlag = $request->input('AddUpdateFlag');
        $buildingId = $request->input('BuildingID');
        if ($addUpdateFlag == 1) {
            $building = new Building;
            $building->Building = $buildingName;
            $building->SchoolID = $schoolId;
            $building->save();
        } else {
            Building::where('ID', $buildingId)->update(['Building' => $buildingName]);
        }
        return 'success';
    }
    
    function getBuildingDataById($id){
        $get = Building::where('ID',$id)->first();
          return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $get,
        ]));
    }
    
    function deleteBuilding($id){
        Building::where('ID',$id)->forceDelete();
         return 'success';
    }
    
function allBuildings($sid, $skey, $sortkey, $sflag, $page, $limit)
{
    $query = ($skey == 'null')
        ? Building::where('SchoolID', $sid)
        : Building::where('SchoolID', $sid)->where(function ($query) use ($skey) {
            $query->where('Building', 'LIKE', "%$skey%");
        });

    if ($page !== 'null') {
        $results = $query->paginate($limit, ['*'], 'page', $page);
        $data = $results->items();
    } else {
        $data = $query->get();
    }

    if ($sortkey == 1) {
        $sortColumn = 'Building';
    } elseif ($sortkey == 2) {
        $sortColumn = 'created_at';
    } else {
        $sortColumn = 'ID';
    }

    $sortDirection = ($sflag == 'desc') ? SORT_DESC : SORT_ASC;

    // Sort only the paginated results or the retrieved data
    usort($data, function ($a, $b) use ($sortColumn, $sortDirection) {
        if ($a[$sortColumn] == $b[$sortColumn]) return 0;
        if ($sortDirection == SORT_DESC) {
            return $a[$sortColumn] > $b[$sortColumn] ? -1 : 1;
        } else {
            return $a[$sortColumn] < $b[$sortColumn] ? -1 : 1;
        }
    });

    if ($page !== 'null') {
        $results->setCollection(collect($data));
    } else {
        $results = $data;
    }

    return response()->json([
        'response' => 'success',
        'msg' => $results
    ]);
}



}