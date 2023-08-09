<!DOCTYPE html>
<html>

<head>
    <title>Insurance Template</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
            padding: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header img {
            max-height: 80px;
            display: block;
            margin: 0 auto;
        }

        h1 {
            font-size: 24px;
            margin-top: 20px;
            color: #444;
        }

        .invoice-details {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .invoice-details p {
            margin-bottom: 10px;
        }

        .invoice-details strong {
            margin-right: 10px;
        }

        .models, .services {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .grand-total {
            text-align: right;
            font-size: 16px;
            margin-top: 20px;
        }

        .grand-total strong {
            color: #000;
        }
    </style>
</head>

<body>
    <div class="header">
        <img src="{{ public_path('K12Logo.png') }}" alt="K12 Logo">
        <h1>Parental Coverage</h1>
    </div>

    <div class="invoice-details">
        <p><strong>Plan Name:</strong> {{$data['PlanName'] ?? 'N/A'}}</p>
        <p><strong>School Name:</strong>{{$data['SchoolName']}}</p>
        <p><strong>Contact Name:</strong>{{$data['ContactName']}}</p>
        <p><strong>Contact Email:</strong>{{$data['ContactEmail']}}</p>
        <p><strong>Date:</strong>{{$data['created_at']}}</p>
    </div>

    <div class="models">
        <h2>Covered Device Models:</h2>
        @if(isset($data->coverdDeviceModels))
            @foreach($data->coverdDeviceModels as $model)
            <p>{{ $model->Device }}</p>
            @endforeach 
        @else
            <p>No covered device models found.</p>
        @endif
    </div>

    <div class="services">
        <h2>Covered Services:</h2>
        @if($data->coverdServices && $data->coverdServices->count())
            @foreach($data->coverdServices as $coveredService)
                @if($coveredService->services && $coveredService->services->count())
                    @foreach($coveredService->services as $service)
                        <p><strong>{{ $service->Name }}</strong> - ${{ $coveredService->Price }}</p>
                    @endforeach        
                @endif
            @endforeach
        @else
            <p>No covered services found.</p>
        @endif
    </div>

    <div class="grand-total">
        <p><strong>Grand Total:</strong> ${{$data['Price']}}</p>
    </div>

</body>
</html>
