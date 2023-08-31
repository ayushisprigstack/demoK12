<?php

namespace App\Http\Controllers;

use App\Models\PartSKUs;
use Illuminate\Http\Request;

class MasterInventoryController extends Controller {

    function GetAllMasterInventory($skey, $flag, $page, $limit) {
        if ($skey == 'null') {
            $masterparts = PartSKUs::where('School_ID', null)->orderBy('Title', $flag)->paginate($limit, ['*'], 'page', $page);
        } else {
            $masterparts = PartSKUs::where('School_ID', null)->where(function ($query) use ($skey) {
                        $query->where('Title', 'LIKE', "%$skey%");
                    })->orderBy('Title', $flag)->paginate($limit, ['*'], 'page', $page);
        }
        return response()->json(
                        collect([
                    'response' => 'success',
                    'MasterSku' => $masterparts,
                        ])
        );
    }

    function GetMasterInventoryById($id) {
        $part = PartSKUs::where('ID', $id)->first();
        return $part;
    }

    function updateMasterInventory(Request $request) {
        $part = PartSKUs::where('ID', $request->input('ID'))->first();
        if (isset($part)) {
            if ($request->input('Flag') == 0) {
                PartSKUs::where('ID', $request->input('ID'))
                        ->update([
                            'Title' => $request->input('Title'),
                            'Variant_Price' => $request->input('Price'),
                ]);
                return response()->json(collect(['response' => 'success',]));
            } else {

                $getStatus = PartSKUs::where('ID', $request->input('ID'))->first();
//                return $getStatus;
                if ($getStatus->Status == 'active') {
                    PartSKUs::where('ID', $request->input('ID'))->update(['Status' => 'deactive',]);
                } else {
                    PartSKUs::where('ID', $request->input('ID'))->update(['Status' => 'active',]);
                }
                return response()->json(collect(['response' => 'success',]));
            }
          
        }
        return response()->json(collect(['response' => 'error',]));
    }

    function allupdate() {
        $get = PartSKUs::all();
        foreach ($get as $data) {
            PartSKUs::where('ID', $data->ID)->where('Status', null)
                    ->update(['status' => 'active']);
        }
    }

    function importMasterInventory(Request $request) {
        set_time_limit(0);
        try {
                            
            $result = $request->file('file');
            $file = fopen($result, 'r');
            $header = fgetcsv($file);

            $expectedHeaders = [];           
            $expectedHeaders = ['partname', 'partprice', 'quantity', 'handle'];          
            $escapedheader = [];
            foreach ($header as $key => $value) {
                $lheader = strtolower($value);
                $escapedItem = preg_replace('/[^a-z]/', '', $lheader);
                array_push($escapedheader, $escapedItem);
            }          
            if (array_diff($expectedHeaders, $escapedheader))
             {
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
                $data = array_map('trim', $data);
                 $partName = $data['partname'];
                 $partPrice =  $data['partprice'];
                 $partquantity = $data['quantity'];
                 $parthandle = $data['handle'];
                 $checkPart = PartSKUs::where('Title',$partName)->whereNull('School_ID')->whereNull('Master_ID')->first();
                 if(isset( $checkPart)){
                    $finalQuantity = $partquantity + $checkPart->Quantity;
                    PartSKUs::where('Title',$partName)->whereNull('School_ID')->whereNull('Master_ID')->update(['Quantity'=> $finalQuantity,'Variant_Price'=> $partPrice]);
                 }else{
                    $part = new PartSKUs;
                    $part->Title =  $partName;
                    $part->Variant_Price =  $partPrice;
                    $part->Quantity =  $partquantity;
                    $part->Handle =  $parthandle;
                    $part->save();
                 }
                
            }
            return 'success';
        } catch (\Throwable $error) {
            return 'Something went wrong';
        }
    }

}
