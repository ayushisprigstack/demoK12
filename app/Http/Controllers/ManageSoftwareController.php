<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ManageSoftware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManageSoftwareController extends Controller
{
    function GetAllSoftware($sid, $searchkey, $skey, $sflag, $page, $limit)
    {
        $softwareQuery = ManageSoftware::where('school_id', $sid);
        if ($searchkey != 'null') {
            $softwareQuery->where(function ($query) use ($searchkey) {
                $query->where('Name', 'LIKE', '%' . $searchkey . '%')
                    ->orWhere('Date_Purchased', 'LIKE', '%' . $searchkey . '%')
                    ->orWhere('Cost', 'LIKE', '%' . $searchkey . '%')
                    ->orWhere('Buildings', 'LIKE', '%' . $searchkey . '%')
                    ->orWhere('License_length', 'LIKE', '%' . $searchkey . '%');
            });
        }

        if ($skey == 1) {
            $softwareQuery = $sflag == 'as' ? $softwareQuery->orderBy('Name') : $softwareQuery->orderByDesc('Name');
        } elseif ($skey == 2) {
            $softwareQuery = $sflag == 'as' ? $softwareQuery->orderBy('Date_Purchased') : $softwareQuery->orderByDesc('Date_Purchased');
        } elseif ($skey == 3) {
            $softwareQuery = $sflag == 'as' ? $softwareQuery->orderBy('Cost') : $softwareQuery->orderByDesc('Cost');
        } elseif ($skey == 4) {
            $softwareQuery = $sflag == 'as' ? $softwareQuery->orderBy('Buildings') : $softwareQuery->orderByDesc('Buildings');
        } else {
            $softwareQuery->orderByDesc('ID');
        }

        $software = $softwareQuery->paginate($limit, ['*'], 'page', $page);
        return response()->json(
            collect([
                'response' => 'success',
                'msg' => $software,
            ])
        );
    }

    function addupdatesoftware(Request $request)
    {
        if ($request->input('addupdateflag') == 1) {
            $software = new ManageSoftware();
            $schoolID = $request->input('schoolId');
            if ($request->input('schoolId') != null) {
                $software->school_id = $request->input('schoolId');
                $software->Name = $request->input('Name');
                $software->Date_Purchased = $request->input('purchaseDate');
                $software->License_length = $request->input('licenselength');
                $software->License_Renewal = $request->input('licenserenewal');
                $software->Cost = $request->input('cost');
                $software->Buildings = $request->input('Buildings');
                $software->License_Qty = $request->input('licenseQty');
                $software->Notes = $request->input('Notes');
                $software->Grade = $request->input('Grade');
                $software->Subject = $request->input('Subject');
                $software->save();
                $Document = $request->file('Document');
                if ($request->file('Document')) {
                    $file = fopen($Document, 'r');
                    $filename = 'Software/' . $software->id . '_' . time() . '.pdf';
                    Storage::disk('public')->put($filename, $file);
                    ManageSoftware::where('school_id', $schoolID)->where('ID', $software->id)->update(['Document' => $filename]);
                }
                return response()->json(collect(['response' => 'success',]));
            }
        } elseif ($request->input('addupdateflag') == 2) {
            $MatchwithId = ManageSoftware::where('ID', $request->input('ID'))->first();
            if (isset($MatchwithId)) {
                $updatedSoftware = ManageSoftware::where('ID', $request->input('ID'))
                    ->update([
                        'school_id' => $request->input('schoolId'),
                        'Name' => $request->input('Name'),
                        'Date_Purchased' => $request->input('purchaseDate'),
                        'License_length' => $request->input('licenselength'),
                        'License_Renewal' => $request->input('licenserenewal'),
                        'Cost' => $request->input('cost'),
                        'Buildings' => $request->input('Buildings'),
                        'License_Qty' => $request->input('licenseQty'),
                        'Notes' => $request->input('Notes'),
                        'Grade' => $request->input('Grade'),
                        'Subject' => $request->input('Subject'),
                    ]);
                $Document = $request->file('Document');
                if ($request->file('Document')) {
                    $file = fopen($Document, 'r');
                    $filename = 'Software/' . $software->id . '_' . time() . '.pdf';
                    Storage::disk('public')->put($filename, $file);
                    ManageSoftware::where('ID', $MatchwithId->ID)->update(['Document' => $filename]);
                }
            }
            return response()->json(collect(['response' => 'success',]));
        } else {
            return response()->json(collect(['response' => 'error',]));
        }
        return 'success';
    }

    function GetSoftwareById($id)
    {
        $softwaredata = ManageSoftware::where('ID', $id)->first();
        return $softwaredata;
    }

    function GetSoftwareDocument($id)
    {
        $software = ManageSoftware::where('ID', $id)->first();
        if (isset($software->Document)) {
            return $software->Document;
        } else {
            return 'error';
        }
    }
}
