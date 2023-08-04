<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use FedEx\ShipService\Request as ShipRequest;
use FedEx\ShipService\ComplexType;
use FedEx\ShipService\SimpleType;
use HTTP_Request2;

class FedexController extends Controller {

    private $accessToken;

    function getAccessToken() {       
        $request = new HTTP_Request2();          
        $request->setUrl(env('FEDEX_AUTH_API'));  
     
        $request->setMethod(HTTP_Request2::METHOD_POST);          
        $request->setConfig(array(
            'ssl_verify_peer' => false,
            'ssl_verify_host' => false
        ));
        $request->setHeader(array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ));//      
        $request->setBody("grant_type=client_credentials&client_id=".env('FEDEX_CLIENT_ID')."&client_secret=".env('FEDEX_CLIENT_SECRET'));
        try {
            $response = $request->send();
            if ($response->getStatus() == 200) {
                $rawResponse = $response->getBody(); // Get the raw response
                $accessToken = json_decode($rawResponse)->access_token;
                $this->accessToken = $accessToken;
                return $accessToken;
            } else {
                return null;
            }
        } catch (HTTP_Request2_Exception $e) {
            return null;
    }
    }

    function createShipment(Request $request) {
        // Create the HTTP_Request2 object
        $shipmentRequest = new HTTP_Request2();
        $shipmentRequest->setUrl(env('FEDEX_SHIP_API'));
        $shipmentRequest->setMethod(HTTP_Request2::METHOD_POST);
        $shipmentRequest->setConfig(array(
            'follow_redirects' => TRUE
        ));
   
        $accessToken = $this->getAccessToken();
    
        $shipmentRequest->setHeader(array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'X-locale' => 'en_US'
        ));

        // JSON format
        $shipmentData = '{
            "labelResponseOptions": "URL_ONLY",
            "requestedShipment":' . json_encode($request->input('requestedShipment')) . ',
                 "accountNumber": {
                 "value": "' . $request->input('accountNumber.value') . '"
                }
          
        }';

        $shipmentRequest->setBody($shipmentData);

        try {
        $response = $shipmentRequest->send();      
        $responseData = json_decode($response->getBody(), true);        
        if ($response->getStatus() == 200) {
            $trackingNumber = $responseData['output']['transactionShipments'][0]['masterTrackingNumber'];
            $packageDocuments = $responseData['output']['transactionShipments'][0]['pieceResponses'][0]['packageDocuments'][0]['url'];
            return [
                'status' => 'success',
                'trackingNumber' => $trackingNumber,
                'url' => $packageDocuments
            ];
        } else {
            return [
                'msg' => 'Unexpected HTTP status: ' . $response->getStatus() . ' ' . $response->getReasonPhrase(),
                'status' => 'error'
            ];
        }
        } catch (HTTP_Request2_Exception $e) {
            return ['msg' => 'Error sending HTTP request: ' . $e->getMessage(),
                'status' => 'error'
            ];
  }
    }

    function validateShipment(Request $request) {
        $shipmentRequest = new HTTP_Request2();
        $shipmentRequest->setUrl(env('FEDEX_VALIDATE_API'));
        $shipmentRequest->setMethod(HTTP_Request2::METHOD_POST);
        $shipmentRequest->setConfig(array(
            'follow_redirects' => TRUE
        ));

        $accessToken = $this->getAccessToken();

        $shipmentRequest->setHeader(array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'X-locale' => 'en_US'
        ));

        // JSON format
        $shipmentData = '{
            "labelResponseOptions": "URL_ONLY",
            "requestedShipment":' . json_encode($request->input('requestedShipment')) . ',
                 "accountNumber": {
                 "value": "' . $request->input('accountNumber.value') . '"
                }
          
        }';

        $shipmentRequest->setBody($shipmentData);
        $response = $shipmentRequest->send();
        $responseData = json_decode($response->getBody(), true);
        if (isset($responseData['errors'])) {
            $msg = $responseData['errors'][0]['message'];
            $code = $responseData['errors'][0]['code'];

            return [
                'status' => 'error',
                'msg' => $msg,
                'code' => $code,
            ];
        } elseif (isset($responseData['output']) && !empty($responseData['output']['alerts'])) {
            $warnings = [];
            $code =[];
            foreach ($responseData['output']['alerts'] as $alert) {
                $warnings[] = $alert['message'];
                $code[]=$alert['code'];
            }

            return [
                'status' => 'error',
                'msg' => 'Warnings occurred during validation.',
                'warnings' => $warnings,
                'code'=>$code
            ];
        } else {
            return [
                'status' => 'success',
                'msg' => 'Details are valid.',
            ];
        }
    }

}
