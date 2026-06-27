<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use Illuminate\Http\Request;

class FeeCategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => FeeCategory::all()
        ]);
    }

    public function store(Request $request)
    {
        $category = FeeCategory::create($request->validate([
            'name' => 'required|string',
            'amount' => 'required|integer|min:0',
        ]));

        return response()->json(['status' => 'success', 'data' => $category], 201);
    }
}