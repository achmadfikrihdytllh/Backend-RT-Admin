<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Menampilkan seluruh tagihan
     */
    public function index(Request $request)
    {
        $query = Payment::with(['resident', 'feeCategory']);

        if ($request->filled('month')) {
            $query->where('for_month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('for_year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query
            ->orderBy('for_year', 'desc')
            ->orderBy('for_month', 'desc')
            ->orderBy('resident_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Detail tagihan
     */
    public function show(string $id)
    {
        $payment = Payment::with(['resident', 'feeCategory'])->find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    /**
     * Generate tagihan bulanan
     */
    public function generateMonthlyBills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->paymentService->generateMonthlyBills(
            $request->month,
            $request->year
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Tagihan berhasil digenerate.',
            'data' => $result
        ], 201);
    }

    /**
     * Melunasi tagihan
     */
    public function pay(string $id)
    {
        try {

            $payment = $this->paymentService->pay($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran berhasil dicatat.',
                'data' => $payment
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);

        }
    }

    /**
     * Update manual (opsional)
     * Hanya boleh mengubah status & tanggal pembayaran
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:lunas,belum',
            'payment_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Tagihan berhasil diperbarui.',
            'data' => $payment
        ]);
    }

    /**
     * Hapus tagihan
     */
    public function destroy(string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan.'
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tagihan berhasil dihapus.'
        ]);
    }

    public function outstanding(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $payments = Payment::with(['resident', 'feeCategory'])
            ->where('status', 'belum')
            ->where('for_month', $month)
            ->where('for_year', $year)
            ->orderBy('resident_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'count' => $payments->count(),
            'total_amount' => $payments->sum('amount_paid'),
            'data' => $payments,
        ]);
    }


        public function store(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'resident_id'      => 'required|exists:residents,id',
                'fee_category_id'  => 'required|exists:fee_categories,id',
                'for_month'        => 'required|integer|between:1,12',
                'for_year'         => 'required|integer|min:2000',
                'number_of_months' => 'required|integer|min:1|max:12',
                'payment_date'     => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            $result = $this->paymentService->recordManualPayment($data);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pembayaran berhasil dicatat!',
                'data'    => $result
            ], 201);
        }
}