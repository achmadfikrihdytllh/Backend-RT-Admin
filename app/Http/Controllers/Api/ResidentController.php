<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    public function index()
    {
        $residents = Resident::latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $residents
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'ktp_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|in:tetap,kontrak',
            'phone_number' => 'required|string|max:15',
            'is_married' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = $request->file('ktp_photo')->store('ktp_photos', 'public');

        $resident = Resident::create([
            'full_name' => $request->full_name,
            'ktp_photo_path' => $imagePath,
            'status' => $request->status,
            'phone_number' => $request->phone_number,
            'is_married' => $request->is_married,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Penghuni berhasil ditambahkan!',
            'data' => $resident
        ], 201);
    }

    public function show(string $id)
    {
        $resident = Resident::find($id);
        if (!$resident) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['status' => 'success', 'data' => $resident]);
    }

    public function update(Request $request, string $id)
    {
        $resident = Resident::find($id);
        if (!$resident) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'ktp_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|in:tetap,kontrak',
            'phone_number' => 'sometimes|string|max:15',
            'is_married' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('ktp_photo');

        if ($request->hasFile('ktp_photo')) {
            if ($resident->ktp_photo_path) {
                Storage::disk('public')->delete($resident->ktp_photo_path);
            }
            $data['ktp_photo_path'] = $request->file('ktp_photo')->store('ktp_photos', 'public');
        }

        $resident->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Data penghuni berhasil diupdate!',
            'data' => $resident
        ]);
    }

    public function destroy(string $id)
    {
        $resident = Resident::find($id);
        if (!$resident) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($resident->ktp_photo_path) {
            Storage::disk('public')->delete($resident->ktp_photo_path);
        }

        $resident->delete();

        return response()->json(['status' => 'success', 'message' => 'Penghuni berhasil dihapus!']);
    }

    public function showKtp(string $id)
    {
        $resident = Resident::find($id);

        dd([
            'resident' => $resident,
            'path' => storage_path('app/private/' . ($resident?->ktp_photo_path ?? '')),
            'exists' => file_exists(storage_path('app/private/' . ($resident?->ktp_photo_path ?? ''))),
        ]);
    }
}