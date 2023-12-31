<!DOCTYPE html>
<html>
    <head>
        <title>Invoice Template</title>
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
           <h1>Invoice</h1> 
        </div>
        <div class="invoice-details" style="text-align:left;text-align:left;margin-bottom: 5px">
            <p><strong style="color: #000">Invoice Number:</strong> {{$data['invoicenum']}}</p>                               
        </div>
        <div>
            <p style="text-align:left;margin-bottom: 5px;margin-top: 1px;color: #000 "><strong style="color: #000">School Name:</strong>{{$data['school']}}</p>
            <p style="text-align:left;margin-bottom: 5px;margin-top: 1px;color: #000"><strong style="color: #000">Batch Name:</strong>{{$data['batchname']}}</p>
            <p style="margin-top: 1px ;color: #000"><strong style="color: #000">Date:</strong>{{$data['date']}}</p>
        </div>

        <table style="margin-top: 30px">
            <thead>
                <tr>
                    <th style="color: #000">Ticket Number</th>
                    <th style="color: #000">Serial Number</th>
                    <th style="color: #000">Asset Tag</th>
                    <th style="color: #000">Attached Part</th>
                    <th style="color: #000">Quantity</th>
                    <th style="color: #000">Price</th>
                    <th style="color: #000">Amount</th>
                </tr>
            </thead>

            @foreach($data['batchdata'] as $batch) 
            @if(isset($batch['Part']) && !empty($batch['Part']))
            @foreach($batch['Part'] as $part)
            <tr>
                <td style="color: #000">{{$batch['TicketNum']}}</td>
                <td style="color: #000">{{$batch['SerialNum']}}</td>
                <td style="color: #000">{{$batch['AssetTag']}}</td>
                <td style="color: #000">{{$part['PartName']}}</td> 
                <td style="color: #000">{{$part['PartsQuantity']}}</td> 
                <td style="color: #000">${{$part['PartPrice']}}</td>
                <td style="color: #000">${{$part['PartsAmount']}}</td> 
            </tr>
            @endforeach 
            @else
            <tr>
                <td style="color: #000">{{$batch['TicketNum']}}</td>
                <td style="color: #000">{{$batch['SerialNum']}}</td>
                <td style="color: #000">{{$batch['AssetTag']}}</td>
                <td style="color: #000">{{'No Parts Found'}}</td> 
                <td style="color: #000">{{'0'}}</td> 
                <td style="color: #000">{{'0'}}</td>  
                <td style="color: #000">{{'0'}}</td>                  
            </tr>
            @endif 
            <tr>
                <td style="color: #000">{{$batch['TicketNum']}}</td>
                <td style="color: #000">{{$batch['SerialNum']}}</td>
                <td style="color: #000">{{$batch['AssetTag']}}</td>                           
                <td style ="font-weight: bold;color:#000">Paid Amount</td> 
                <td></td>
                <td></td>
                <td style="font-weight: bold;color: #000">${{$batch['Ticketsubtotal']}}</td> 
            </tr>
            @endforeach


        </table>

        <div class="" style="color: #000;text-align:right;">
            <p><strong style="font-weight: bold;color: #000">Grand Total:</strong>${{$data['batchamount']}}</p>             
        </div>
