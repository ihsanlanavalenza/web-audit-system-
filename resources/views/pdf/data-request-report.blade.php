<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Data Request Report</title>

    <style>
        body{
            font-family: DejaVu Sans;
            font-size:10px;
        }

        h2{
            text-align:center;
            margin-bottom:5px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th,td{
            border:1px solid #000;
            padding:5px;
        }

        th{
            background:#eaeaea;
        }

        .text-center{
            text-align:center;
        }
    </style>
</head>
<body>

<h2>CLIENT ASSISTANCE SCHEDULE</h2>

<p>
<b>Client :</b> PT PMUI Tbk
</p>

<p>
<b>Generated :</b>
{{ date('d/m/Y H:i') }}
</p>

<h3>Summary Status</h3>

<table style="margin-bottom:20px;">
<tr>
    <th>Received</th>
    <th>Pending</th>
    <th>Review</th>
    <th>Partial</th>
    <th>N/A</th>
</tr>

<tr>
    <td>{{ $summary['received'] }}</td>
    <td>{{ $summary['pending'] }}</td>
    <td>{{ $summary['on_review'] }}</td>
    <td>{{ $summary['partially_received'] }}</td>
    <td>{{ $summary['not_applicable'] }}</td>
</tr>
</table>

<p>
Tanggal Cetak :
{{ date('d/m/Y H:i') }}
</p>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th>Section</th>
        <th>Account</th>
        <th>Description</th>
        <th>Request Date</th>
        <th>Expected</th>
        <th>Status</th>
    </tr>
    </thead>

    <tbody>

    @foreach($dataRequests as $item)

    <tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $item->section_no }}</td>
        <td>{{ $item->account_process }}</td>
        <td>{{ $item->description }}</td>
        <td>{{ \Carbon\Carbon::parse($item->request_date)->format('d/m/Y') }}</td>
        <td>{{ \Carbon\Carbon::parse($item->expected_received)->format('d/m/Y') }}</td>
        <td>{{@if($item->status=='received')Received
            @elseif($item->status=='pending')Pending
            @elseif($item->status=='on_review')On Review
            @elseif($item->status=='partially_received')Partially Received
            @elseif($item->status=='not_applicable')Not Applicable
            @endif}}</td>
    </tr>

    @endforeach

    </tbody>
</table>

</body>
</html>