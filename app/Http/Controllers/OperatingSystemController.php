<?php

namespace App\Http\Controllers;
use App\Models\OperatingSystem;
use App\Models\Device;
use App\Models\InventoryManagement;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request as Input;
use Illuminate\Contracts\Container\BindingResolutionException;

class OperatingSystemController extends Controller
{
    public function addOs(Request $request) {
        $os = new OperatingSystem;
        $os->os = $request->input('name');

        $checkos = OperatingSystem::where('ID', $request->input('ID'))->first();
        if (isset($checkos)) {
            $osIDfromDB = $checkos->ID;
            $osId = $request->input('ID');
            $osName = $request->input('name');
            if ($osIDfromDB == $osId) {
                $updatedOsDetail = OperatingSystem::where('ID', $osId)->update(['os' => $osName]);
            }
            return "success";
        } else {
            $os->save();
            return "success";
        }
    }

    public function allOs() {
        $os = OperatingSystem::all();
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $os,
        ]));
    }

    public function fetchOs($id) {
        $os = OperatingSystem::where('ID', $id)->first();
        return response()->json(
                        collect([
                    'response' => 'success',
                    'msg' => $os,
        ]));
    }

    public function DeleteOs(Request $request) {
        $os = OperatingSystem::where('ID', $request->input('ID'))->delete();
        return 'success';
    }

}