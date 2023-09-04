<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h3 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="header">
        <img src="{{ public_path('K12Logo.png') }}" alt="K12 Logo">
        <h1>Parental Coverage</h1>
</div>
<div class="section">
    <h3>Customer Details</h3>
    <p><strong>Customer ID:</strong> {{ $customer->id }}</p>
    <p><strong>Student Number:</strong> {{ $enrollment->student->Student_num }}</p>
    <p><strong>Student Name:</strong> {{ $enrollment->student->Device_user_first_name }} {{ $enrollment->student->Device_user_last_name }}</p>
    <p><strong>Email:</strong> {{ $customer->email }}</p>
    <p><strong>Invoice Number:</strong> {{ $customer->invoice_prefix }}</p>
    <p><strong>School Name:</strong> {{ $enrollment->plan->SchoolName }}</p> 
</div>

<!-- Plan Details -->
<div class="section">
    <h3>Plan Details</h3>
    <p><strong>Plan Name:</strong> {{ $enrollment->plan->PlanName }}</p>
    <p><strong>Plan Number:</strong> {{ $enrollment->plan->PlanNum }}</p>
    <p><strong>Contact Email:</strong> {{ $enrollment->plan->ContactEmail }}</p>
    <p><strong>Contact Name:</strong> {{ $enrollment->plan->ContactName }}</p>    
</div>

<!-- Payment Details -->
<div class="section">
    <h3>Payment Details</h3>
    <p><strong>Card:</strong> {{ $paymentMethod->card->brand }}</p>
    <p><strong>Card Last Four Digits:</strong> {{ $paymentMethod->card->last4 }}</p>
    <p><strong>Expiry Date:</strong> {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}</p>
    <p><strong>Paid Amount:</strong> ${{ $enrollment->PaidAmount}}</p>
</div>

</body>
</html>
