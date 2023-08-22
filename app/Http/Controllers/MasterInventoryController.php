<?php

namespace App\Http\Controllers;

use App\Models\PartSKUs;
use Illuminate\Http\Request;
class MasterInventoryController extends Controller
{
  function GetAllMasterInventory($skey, $flag, $page, $limit)
    {
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
    function GetMasterInventoryById($id)
    {
        $part = PartSKUs::where('ID', $id)->first();
        return $part;
    }
    function updateMasterInventory(Request $request)
    {
        $part = PartSKUs::where('ID', $request->input('ID'))->first();
        if (isset($part)) {
            if($request->input('Flag') == 0){
                 PartSKUs::where('ID', $request->input('ID'))
                ->update([
                    'Title' => $request->input('Title'),
                    'Variant_Price' => $request->input('Price'),
                ]);
                return response()->json(collect(['response' => 'success',])); 
            }else{
                
                $getStatus = PartSKUs::where('ID', $request->input('ID'))->first();
//                return $getStatus;
                if($getStatus->Status == 'active'){
                    PartSKUs::where('ID', $request->input('ID'))->update(['Status' => 'deactive',]); 
                }else{
                    PartSKUs::where('ID', $request->input('ID'))->update(['Status' => 'active',]); 
                }
                return response()->json(collect(['response' => 'success',])); 
            }
          
        }
        return response()->json(collect(['response' => 'error',])); 
    }
    
        function allupdate(){
        $get = PartSKUs::all();
        foreach($get as $data){
            PartSKUs::where('ID',$data->ID)->where('Status', null)
    ->update(['status' => 'active']); 
        }
       
    }
}
