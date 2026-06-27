<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\HouseHistory;
use Carbon\Carbon;

class ReportService
{
    public function getYearlySummary(int $year)
    {
        $summary = [];
        $totalSaldo = 0;

        for ($month = 1; $month <= 12; $month++) {
            $income = Payment::whereYear('payment_date', $year)
                             ->whereMonth('payment_date', $month)
                             ->where('status', 'lunas')
                             ->sum('amount_paid');

            $expense = Expense::whereYear('expense_date', $year)
                              ->whereMonth('expense_date', $month)
                              ->sum('amount');

            $totalSaldo += ($income - $expense);

            $summary[] = [
                'month' => Carbon::create()->month($month)->translatedFormat('F'),
                'income' => $income,
                'expense' => $expense,
                'saldo_sisa' => $totalSaldo
            ];
        }

        return $summary;
    }

    public function getMonthlyDetail(int $month, int $year)
    {
        $incomes = Payment::with(['resident', 'feeCategory'])
                          ->whereYear('payment_date', $year)
                          ->whereMonth('payment_date', $month)
                          ->where('status', 'lunas')
                          ->get();

        $expenses = Expense::whereYear('expense_date', $year)
                           ->whereMonth('expense_date', $month)
                           ->get();

        return [
            'incomes' => $incomes,
            'expenses' => $expenses,
            'total_income' => $incomes->sum('amount_paid'),
            'total_expense' => $expenses->sum('amount')
        ];
    }

    public function getOutstandingSummary(int $month, int $year): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $payments = Payment::with(['resident', 'feeCategory'])
            ->where('status', 'belum')
            ->where('for_month', $month)
            ->where('for_year', $year)
            ->get();

        $byResident = [];
        $byHouse = [];

        foreach ($payments as $payment) {
            $resident = $payment->resident;

            if (!$resident) {
                continue;
            }

            $activeHistory = HouseHistory::with('house')
                ->where('resident_id', $resident->id)
                ->where('start_date', '<=', $periodEnd->toDateString())
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $periodStart->toDateString());
                })
                ->orderByDesc('start_date')
                ->first();

            $residentEntry = $byResident[$resident->id] ?? [
                'resident' => [
                    'id' => $resident->id,
                    'full_name' => $resident->full_name,
                    'status' => $resident->status,
                    'phone_number' => $resident->phone_number,
                ],
                'house' => null,
                'total_amount' => 0,
                'payments' => [],
            ];

            if ($activeHistory && $activeHistory->house) {
                $residentEntry['house'] = [
                    'id' => $activeHistory->house->id,
                    'house_code' => $activeHistory->house->house_code,
                    'status' => $activeHistory->house->status,
                ];
            }

            $residentEntry['total_amount'] += (int) $payment->amount_paid;
            $residentEntry['payments'][] = [
                'id' => $payment->id,
                'fee_category' => $payment->feeCategory?->name,
                'amount_paid' => (int) $payment->amount_paid,
                'for_month' => $payment->for_month,
                'for_year' => $payment->for_year,
                'status' => $payment->status,
            ];

            $byResident[$resident->id] = $residentEntry;

            if ($activeHistory && $activeHistory->house) {
                $houseId = $activeHistory->house->id;

                $houseEntry = $byHouse[$houseId] ?? [
                    'house' => [
                        'id' => $activeHistory->house->id,
                        'house_code' => $activeHistory->house->house_code,
                        'status' => $activeHistory->house->status,
                    ],
                    'resident_count' => 0,
                    'total_amount' => 0,
                    'payments' => [],
                ];

                $houseEntry['resident_count'] = count($houseEntry['payments']) > 0
                    ? $houseEntry['resident_count']
                    : 0;
                $houseEntry['resident_count'] = max($houseEntry['resident_count'], 1);
                $houseEntry['total_amount'] += (int) $payment->amount_paid;
                $houseEntry['payments'][] = [
                    'id' => $payment->id,
                    'resident' => [
                        'id' => $resident->id,
                        'full_name' => $resident->full_name,
                    ],
                    'fee_category' => $payment->feeCategory?->name,
                    'amount_paid' => (int) $payment->amount_paid,
                ];

                $byHouse[$houseId] = $houseEntry;
            }
        }

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'label' => $periodStart->translatedFormat('F Y'),
            ],
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount_paid'),
            'by_resident' => array_values($byResident),
            'by_house' => array_values($byHouse),
        ];
    }

    public function getDashboardSummary(int $month, int $year): array
    {
        $incomeQuery = Payment::whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->where('status', 'lunas');

        $expenseQuery = Expense::whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month);

        $outstandingQuery = Payment::where('for_month', $month)
            ->where('for_year', $year)
            ->where('status', 'belum');

        $income = (int) $incomeQuery->sum('amount_paid');
        $expense = (int) $expenseQuery->sum('amount');
        $outstandingCount = (int) $outstandingQuery->count();
        $outstandingAmount = (int) $outstandingQuery->sum('amount_paid');

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
            ],
            'income' => $income,
            'expense' => $expense,
            'saldo' => $income - $expense,
            'outstanding_count' => $outstandingCount,
            'outstanding_amount' => $outstandingAmount,
        ];
    }
}