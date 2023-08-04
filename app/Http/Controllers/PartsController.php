<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Domain;
use App\Models\DeviceType;
use App\Models\InventoryManagement;
use App\Models\PartSKUs;
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

class PartsController extends Controller {

//    parts no search no and listing no call getAllParts

 function getPartsList($sid,$skey,$flag) {
        $masterSku_array = array();
        $schoolSku_array = array();
        $get = PartSKUs::select('*')->where(function ($query) use ($sid) {
                    $query->where('School_ID', '=', $sid)
                            ->orWhere('School_ID', '=', null);
                })->get();
        foreach ($get as $data) {
            if ($data->School_ID == $sid) {
                if ($data->Quantity <= $data->Reminder_Quantity && $data->Master_ID != null) {
                    $Stockflag = '1';
                } else {
                    $Stockflag = '2';
                }
                $merge = $data . ' ' . $Stockflag;
                array_push($schoolSku_array,['data' => $data, 'flag' => $Stockflag]);
            }else {
                if($data->Status == 'active'){
                    array_push($masterSku_array,['data' => $data, 'flag' =>2]);
                }               
            }
        }
        if($skey == 'null'){
            if($flag == 1){
             return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $schoolSku_array,                
             ]));   
            }else{
               return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $masterSku_array,                
             ]));     
            }
             
        }else{
            if ($flag == 1) {
                $searchedArray = array_filter($schoolSku_array, function ($obj) use ($skey) {
                    $keywords = explode(" ", $skey); // Split search term into keywords
                    $title = $obj['data']['Title'];
                    $brand_name = $obj['data']['Brand_Name'];
                    $variant_price = $obj['data']['Variant_Price'];
                    $quantity = $obj['data']['Quantity'];

                    foreach ($keywords as $keyword) {                       
                        if (stripos($title, $keyword) === false &&
                                stripos($brand_name, $keyword) === false &&
                                stripos($variant_price, $keyword) === false &&
                                stripos($quantity, $keyword) === false) {
                            return false; // If any keyword is not found, exclude this data
                        }
                    }

                    return true; 
                });
                 return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' =>array_values($searchedArray),                
             ])); 
            } else {
                $searchedArray = array_filter($masterSku_array, function ($obj) use ($skey) {
                    $keywords = explode(" ", $skey); // Split search term into keywords
                    $title = $obj['data']['Title'];
                    $brand_name = $obj['data']['Brand_Name'];
                    $variant_price = $obj['data']['Variant_Price'];
                    $quantity = $obj['data']['Quantity'];

                    foreach ($keywords as $keyword) {
                        // Check if each keyword appears in the data
                        if (stripos($title, $keyword) === false &&
                                stripos($brand_name, $keyword) === false &&
                                stripos($variant_price, $keyword) === false &&
                                stripos($quantity, $keyword) === false) {
                            return false; // If any keyword is not found, exclude this data
                        }
                    }

                    return true;
                });
                 return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' =>array_values($searchedArray),                
             ])); 
            }
        }
    }                               
   

    public function addUpdateDeleteParts(Request $request) {
        if ($request->input('Flag') == 1) {
            $part = new PartSKUs();
            $part->Title = $request->input('Title');
            $part->Variant_Price = $request->input('Price');
            $part->School_ID = $request->input('SchoolId');
            $part->Brand_Name = $request->input('BrandName');
            $part->Quantity = $request->input('Quantity');
            $part->Reminder_Quantity = $request->input('ReminderQuantity');
            $handle = strtolower(str_replace(' ', '-', $request->input('Title')));
            $handle .= '-' . $request->input('SchoolId');
            $part->Handle = $handle;
            $part->save();
        } elseif ($request->input('Flag') == 2) {
            PartSKUs::where('ID', $request->input('ID'))->update(['Variant_Price' => $request->input('Price'), 'Title' => $request->input('Title'), 'Quantity' => $request->input('Quantity'), 'Reminder_Quantity' => $request->input('ReminderQuantity'), 'Brand_Name' => $request->input('BrandName')]);
        } elseif ($request->input('Flag') == 3) {
            PartSKUs::where('ID', $request->input('ID'))->delete();
        } elseif ($request->input('Flag') == 4) {
            $givenPart = PartSKUs::where('ID', $request->input('ID'))->first();
            $checkMasterId = PartSKUs::where('School_ID', $request->input('SchoolId'))->where('Master_ID', $request->input('ID'))->first();
            if (isset($checkMasterId)) {
                $finalQuantity = $request->input('Quantity') + $checkMasterId->Quantity;
                PartSKUs::where('ID', $checkMasterId->ID)->update(['Quantity' => $finalQuantity]);
            } else {
                $Parts = new PartSKUs;
                $Parts->Title = $givenPart->Title;
                $Parts->Variant_Price = $givenPart->Variant_Price;
                $Parts->School_ID = $request->input('SchoolId');
                $Parts->Brand_Name = $givenPart->Brand_Name;
                $Parts->Quantity = $request->input('Quantity');
                $Parts->Master_ID = $request->input('ID');
                $Parts->Handle = $request->input('Handel');
                $Parts->save();
            }
        } else {
            return 'Error';
        }
        return Response::json(array('status' => "success"));
    }

    public function removeAttachedPart($tid, $pid) {
        TicketsAttachment::where('Ticket_ID', $tid)->where('Parts_ID', $pid)->forceDelete();
        return 'success';
    }

}
