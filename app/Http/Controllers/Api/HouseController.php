<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Services\HouseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HouseController extends Controller
{
    protected HouseService $houseService;

    public function __construct(HouseService $houseService)
    {
        $this->houseService = $houseService;
    }

    public function index()
    {
        $houses = House::with(['histories' => function($query) {
            $query->whereNull('end_date')->with('resident');
        }])->get();

        return response()->json(['status' => 'success', 'data' => $houses]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house_code' => 'required|string|unique:houses,house_code'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        $house = House::create([
            'house_code' => $request->house_code,
            'status' => 'tidak dihuni'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Rumah ditambahkan!', 'data' => $house], 201);
    }

    public function show(string $id)
    {
        $house = House::with(['histories.resident.payments.feeCategory'])->find($id);
        if (!$house) return response()->json(['status' => 'error', 'message' => 'Rumah tidak ditemukan'], 404);

        return response()->json(['status' => 'success', 'data' => $house]);
    }

    public function update(Request $request, string $id)
    {
        $house = House::find($id);
        if (!$house) return response()->json(['status' => 'error', 'message' => 'Rumah tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'house_code' => 'required|string|unique:houses,house_code,'.$id
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        $house->update(['house_code' => $request->house_code]);
        
        return response()->json(['status' => 'success', 'message' => 'Data rumah diupdate!', 'data' => $house]);
    }

    public function destroy(string $id)
    {
        $house = House::find($id);
        if (!$house) return response()->json(['status' => 'error', 'message' => 'Rumah tidak ditemukan'], 404);

        $house->delete();
        return response()->json(['status' => 'success', 'message' => 'Data rumah dihapus!']);
    }

    public function assignResident(Request $request, string $id)
    {
        $house = House::find($id);
        if (!$house) return response()->json(['status' => 'error', 'message' => 'Rumah tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'resident_id' => 'required|exists:residents,id',
            'start_date' => 'required|date'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);

        try {
            $this->houseService->assignResident($house, $request->all());
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Penghuni berhasil dimasukkan dan history tercatat!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function unassignResident(string $id)
{
    $house = House::find($id);
    if (!$house) return response()->json(['status' => 'error', 'message' => 'Rumah tidak ditemukan'], 404);

    try {
        $this->houseService->unassignResident($house);
        return response()->json([
            'status' => 'success',
            'message' => 'Penghuni berhasil dikeluarkan dan history diperbarui!'
        ]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
}
}