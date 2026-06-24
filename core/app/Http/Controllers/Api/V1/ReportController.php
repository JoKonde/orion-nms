<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    /**
     * GET /api/v1/reports/types
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'types' => $this->reportService->listTypes(),
        ]);
    }

    /**
     * GET /api/v1/reports/preview
     */
    public function preview(GenerateReportRequest $request): JsonResponse
    {
        $report = $this->buildFromRequest($request);

        return response()->json(['report' => $report]);
    }

    /**
     * GET /api/v1/reports/export — CSV ou HTML imprimable.
     */
    public function export(GenerateReportRequest $request): Response
    {
        $format = $request->input('format', 'csv');
        $report = $this->buildFromRequest($request);

        if ($format === 'html') {
            $html = view('reports.export', ['report' => $report])->render();

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="'.$this->reportService->filename($report, 'html').'"',
            ]);
        }

        $export = $this->reportService->exportCsv($report);

        return response($export['content'], 200, [
            'Content-Type' => $export['mime'],
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFromRequest(GenerateReportRequest $request): array
    {
        $type = ReportType::from($request->input('type'));
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        return $this->reportService->build($type, $from, $to);
    }
}
