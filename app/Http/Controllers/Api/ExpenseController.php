<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::latest('expense_date')->get();
        return response()->json(['status' => 'success', 'data' => $expenses]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|in:operasional,perbaikan,darurat,lainnya',
            'description' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'expense_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense = Expense::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran berhasil ditambahkan.',
            'data' => $expense
        ], 201);
    }

    public function show(string $id)
    {
        $expense = Expense::find($id);
        if (!$expense) return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        return response()->json(['status' => 'success', 'data' => $expense]);
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::find($id);

        if (!$expense) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|in:operasional,perbaikan,darurat,lainnya',
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|integer|min:1',
            'expense_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengeluaran berhasil diperbarui.',
            'data' => $expense
        ]);
    }

    public function destroy(string $id)
    {
        $expense = Expense::find($id);
        if (!$expense) return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);

        $expense->delete();
        return response()->json(['status' => 'success', 'message' => 'Data dihapus!']);
    }
}