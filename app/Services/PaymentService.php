<?php

namespace App\Services;

use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;

class PaymentService
{

    public function generateMonthlyBills(int $month, int $year): array
    {
        DB::beginTransaction();

        try {

            $feeCategories = FeeCategory::query()
                ->where('is_active', true)
                ->where('is_mandatory', true)
                ->get();

            $residents = Resident::query()
                ->where(function ($query) {
                    $query->where('status', 'tetap')
                        ->orWhere('status', 'kontrak');
                })
                ->whereHas('activeHouse')
                ->get();

            $residents = Resident::query()
                ->where(function ($query) {
                    $query->where('status', 'tetap')
                        ->orWhere('status', 'kontrak');
                })
                ->whereHas('activeHouse')
                ->get();

            $createdPayments = [];

            foreach ($residents as $resident) {

                foreach ($feeCategories as $feeCategory) {

                    $payment = Payment::firstOrCreate(

                        [
                            'resident_id'     => $resident->id,
                            'fee_category_id' => $feeCategory->id,
                            'for_month'       => $month,
                            'for_year'        => $year,
                        ],

                        [
                            'amount_paid'  => $feeCategory->amount,
                            'status'       => 'belum',
                            'payment_date' => null,
                        ]

                    );

                    if ($payment->wasRecentlyCreated) {
                        $createdPayments[] = $payment;
                    }
                }
            }

            DB::commit();

            return [
                'month' => $month,
                'year' => $year,
                'generated' => count($createdPayments),
                'payments' => $createdPayments,
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function pay(int $paymentId): Payment
    {
        $payment = Payment::findOrFail($paymentId);

        if ($payment->status === 'lunas') {
            return $payment;
        }

        $payment->update([
            'status' => 'lunas',
            'payment_date' => now(),
        ]);

        return $payment->fresh();
    }

        public function recordManualPayment(array $data): array
    {
        $feeCategory = \App\Models\FeeCategory::findOrFail($data['fee_category_id']);
        $payments = [];

        for ($i = 0; $i < $data['number_of_months']; $i++) {
            $totalMonths = $data['for_month'] - 1 + $i;
            $month = ($totalMonths % 12) + 1;
            $year  = $data['for_year'] + intdiv($totalMonths, 12);

            $payment = Payment::updateOrCreate(
                [
                    'resident_id'     => $data['resident_id'],
                    'fee_category_id' => $data['fee_category_id'],
                    'for_month'       => $month,
                    'for_year'        => $year,
                ],
                [
                    'amount_paid'  => $feeCategory->amount,
                    'payment_date' => $data['payment_date'],
                    'status'       => 'lunas',
                ]
            );

            $payments[] = $payment;
        }

        return $payments;
    }
}