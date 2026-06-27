<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function summary(Request $request)
    {
        $year = (int) $request->query('year', Carbon::now()->year);
        
        $data = $this->reportService->getYearlySummary($year);

        return response()->json([
            'status' => 'success',
            'year' => $year,
            'data' => $data
        ]);
    }

    public function detail(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $data = $this->reportService->getMonthlyDetail($month, $year);

        return response()->json([
            'status' => 'success',
            'period' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            'data' => $data
        ]);
    }

    public function outstandingSummary(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $data = $this->reportService->getOutstandingSummary($month, $year);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function dashboard(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        $data = $this->reportService->getDashboardSummary($month, $year);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function dashboardExport(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        $data = $this->reportService->getDashboardSummary($month, $year);

        $rows = [
            ['metric', 'value'],
            ['period', $data['period']['label']],
            ['income', $data['income']],
            ['expense', $data['expense']],
            ['saldo', $data['saldo']],
            ['outstanding_count', $data['outstanding_count']],
            ['outstanding_amount', $data['outstanding_amount']],
        ];

        return $this->csvDownload(
            sprintf('dashboard-%02d-%d.csv', $month, $year),
            ['metric', 'value'],
            $rows
        );
    }

    public function detailExport(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        $data = $this->reportService->getMonthlyDetail($month, $year);

        $rows = [];

        foreach ($data['incomes'] as $income) {
            $rows[] = [
                'income',
                $income->payment_date,
                'Pembayaran lunas',
                $income->resident?->full_name,
                $income->feeCategory?->name,
                $income->amount_paid,
            ];
        }

        foreach ($data['expenses'] as $expense) {
            $rows[] = [
                'expense',
                $expense->expense_date,
                $expense->description,
                '',
                '',
                $expense->amount,
            ];
        }

        return $this->csvDownload(
            sprintf('report-detail-%02d-%d.csv', $month, $year),
            ['type', 'date', 'description', 'resident', 'fee_category', 'amount'],
            $rows
        );
    }

    public function outstandingSummaryExport(Request $request)
    {
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        $data = $this->reportService->getOutstandingSummary($month, $year);

        $rows = [];

        foreach ($data['by_resident'] as $item) {
            $rows[] = [
                'resident',
                $item['resident']['id'],
                $item['resident']['full_name'],
                $item['house']['house_code'] ?? '',
                $item['total_amount'],
                count($item['payments']),
            ];
        }

        foreach ($data['by_house'] as $item) {
            $rows[] = [
                'house',
                $item['house']['id'],
                $item['house']['house_code'],
                '',
                $item['total_amount'],
                count($item['payments']),
            ];
        }

        return $this->csvDownload(
            sprintf('outstanding-summary-%02d-%d.csv', $month, $year),
            ['section', 'id', 'name', 'house_code', 'total_amount', 'item_count'],
            $rows
        );
    }

    private function csvDownload(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}