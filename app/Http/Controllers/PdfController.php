<?php

namespace App\Http\Controllers;

use App\Models\DataRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function export()
{
    $dataRequests = DataRequest::all();

    $summary = [
        'received' => DataRequest::where('status','received')->count(),
        'pending' => DataRequest::where('status','pending')->count(),
        'on_review' => DataRequest::where('status','on_review')->count(),
        'partially_received' => DataRequest::where('status','partially_received')->count(),
        'not_applicable' => DataRequest::where('status','not_applicable')->count(),
    ];

    $pdf = Pdf::loadView(
        'pdf.data-request-report',
        compact('dataRequests','summary')
    );

    $pdf->setPaper('a4','landscape');

    return $pdf->download('Data-Request-Report.pdf');
}
}