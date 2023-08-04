<?php

namespace App\Http\Controllers;
use App\Models\Personal;
use App\Models\DeviceType;
use App\Models\InventoryManagement;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;

class DeviceTypeController extends Controller
{
    public function addDevice(Request $request) {
        $device = new DeviceType;
        $device->type = $request->input('type');

        $checkdevice = DeviceType::where('ID', $request->input('ID'))->first();
        if (isset($checkdevice)) {
            $deviceIDfromDB = $checkdevice->ID;
            $deviceId = $request->input('ID');
            $deviceType = $request->input('type');
            if ($deviceIDfromDB == $deviceId) {
                $updatedLoginDetail = DeviceType::where('ID', $deviceId)->update(['type' => $deviceType]);
            }
            return "success";
        } else {
            $device->save();
            return "success";
        }
    }

    public function allDevice() {
        $devices = DeviceType::all();
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $devices,
        ]));
    }

    public function fetchDevice($id) {
        $device = DeviceType::where('ID', $id)->first();
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $device,
        ]));
    }

    public function DeleteDevice(Request $request) {
        $device = DeviceType::where('ID', $request->input('ID'))->delete();
        return 'success';
    }

}