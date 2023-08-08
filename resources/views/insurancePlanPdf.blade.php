<!DOCTYPE html>
<html>
    <head>
        <title>Insurance Template</title>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            table th, table td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: left;
                vertical-align: top;
            }
            table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .invoice-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .invoice-header img {
                max-height: 80px;
            }
            .invoice-header h1 {
                font-size: 24px;
                margin: 0;
            }
            .invoice-details {
                display: flex;
                /*justify-content: space-between;*/
                align-items: center;
                margin-bottom: 20px;
            }
            .invoice-details p {
                margin: 0;
            }
            .invoice-details strong {
                font-weight: bold;
            }
            .invoice-summary {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                margin-bottom: 20px;
            }
            .invoice-summary p {
                margin: 0;
                margin-left: 10px;
            }
            .invoice-summary strong {
                font-weight: bold;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
        </style>
    </head> 
    <body> 
        <div style="text-align:center;">               
            <img align="" alt="" src="{{ public_path('K12Logo.png') }}" width="196" style="max-width:170px; padding-bottom: 0; display: inline !important; vertical-align: bottom;">                     
        </div>       
        <div>
            <h1>Parental Coverage</h1> 
        </div>

        <div class="invoice-details" style="text-align:left;text-align:left;margin-bottom: 5px">
            <p><strong style="color: #000">Plan Name:</strong> {{$data['PlanName'] ?? 'N/A'}}</p>                               

        </div>
        <div>
            <p style="text-align:left;margin-bottom: 5px;margin-top: 1px;color: #000 "><strong style="color: #000">School Name:</strong>{{$data['SchoolName']}}</p>
            <p style="text-align:left;margin-bottom: 5px;margin-top: 1px;color: #000"><strong style="color: #000">Contact Name:</strong>{{$data['ContactName']}}</p>
            <p style="text-align:left;margin-bottom: 5px;margin-top: 1px;color: #000"><strong style="color: #000">Contact Email:</strong>{{$data['ContactEmail']}}</p>
            <p style="margin-top: 1px ;color: #000"><strong style="color: #000">Date:</strong>{{$data['created_at']}}</p>
        </div>
<div>
        <p><strong style="color: #000">Models:</strong></p>
        @if(isset($data->coverd_device_models))
            @foreach($data->coverd_device_models as $model) 
            <p>{{ $model->Device }}</p>
            @endforeach 
        @else
            <p>No covered device models found.</p>
        @endif
    </div>
    
    <div>
        <p><strong style="color: #000">Services:</strong></p>
       <p>{{ json_encode($data->coverd_services) }}</p>
       <p>{{ json_encode($data->coverd_device_models) }}</p>
     <?php echo json_encode($data, JSON_PRETTY_PRINT);?>
        @if(isset($data->coverd_services))
        
         
            @foreach($data->coverd_services as $coveredService) 
            <p>{{ $coveredService->services[0]->Name }}</p>
            <p>{{ $coveredService->Price }}</p>
            @endforeach
        @else
            <p>No covered services found.</p>
        @endif
    </div>

        <div class="" style="color: #000;text-align:right;">
            <p><strong style="font-weight: bold;color: #000">Grand Total:</strong>${{$data['batchamount']}}</p>             
        </div>
