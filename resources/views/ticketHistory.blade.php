<!DOCTYPE html>
<html>

<head>
    <title>Invoice Template</title>
       <style>
         @media print {
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        /* table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
            vertical-align: top;
        }

        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        } */

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
    }
    </style>
</head>

<body>

    @foreach($data as $ticket)
        <div class="invoice-header">
            <h1>Device: {{ $ticket['Device'] }}</h1>
            <p>Ticket Number: {{ $ticket['TicketNum'] }}</p>
            <p>Created Date: {{ $ticket['CreatedDate'] }}</p>
            <p>Closed Date: {{ $ticket['ClosedDate'] }}</p>
        </div>

        <h2>Issues</h2>
        <ul>
            @foreach($ticket['Issue'] as $issue)
                <li>{{ $issue[0] }}</li>
            @endforeach
        </ul>

        <h2>Parts</h2>
        @if(count($ticket['Parts']) > 0)
        <table>
            <thead>
                <tr>
                    <th>Part Name</th>
                    <th>Part Price</th>
                    <th>Quantity</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticket['Parts'] as $part)
                <tr>
                    <td>{{ $part['PartName'] }}</td>
                    <td>{{ $part['PartPrice'] }}</td>
                    <td>{{ $part['Quantity'] }}</td>
                    <td>{{ $part['Notes'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No parts associated with this ticket.</p>
        @endif

        <h2>Status Logs</h2>
        <ul>
            @foreach($ticket['StatusLog'] as $log)
                <li>
                    On {{ $log['date'] }}, {{ $log['update_by_user'] }} changed the ticket status from {{ $log['previous_status'] }} to {{ $log['updated_status'] }}.
                </li>
            @endforeach
        </ul>

        <hr> <!-- Separator for multiple tickets -->
    @endforeach

</body>

</html>
