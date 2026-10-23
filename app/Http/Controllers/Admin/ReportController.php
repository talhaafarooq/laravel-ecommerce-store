<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesReportRequest;
use App\Services\SalesReportService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $reports) {}

    public function index(SalesReportRequest $request): Response
    {
        return Inertia::render('admin/reports', [
            'report' => $this->reports->build($request->range()),
        ]);
    }

    public function export(SalesReportRequest $request): StreamedResponse
    {
        $range    = $request->range();
        $filename = sprintf('sales-%s-to-%s.csv', $range->from->toDateString(), $range->to->toDateString());

        return response()->streamDownload(function () use ($range): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            foreach ($this->reports->csvRows($range) as $row) {
                fputcsv($handle, $row, escape: '');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
