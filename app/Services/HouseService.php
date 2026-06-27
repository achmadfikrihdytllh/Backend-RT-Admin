<?php

namespace App\Services;

use App\Models\House;
use App\Models\HouseHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class HouseService
{

    public function assignResident(House $house, array $data)
    {
        $activeHistory = HouseHistory::where('house_id', $house->id)
            ->whereNull('end_date')
            ->first();

        $residentActiveHistory = HouseHistory::where('resident_id', $data['resident_id'])
            ->whereNull('end_date')
            ->first();

        if ($activeHistory && $activeHistory->resident_id == $data['resident_id']) {
            throw new Exception('Penghuni tersebut sudah menempati rumah ini saat ini.');
        }

        DB::transaction(function () use ($house, $data, $activeHistory, $residentActiveHistory) {
            $previousHouse = null;

            if ($residentActiveHistory && $residentActiveHistory->house_id !== $house->id) {
                $previousHouse = $residentActiveHistory->house;
                $residentActiveHistory->update([
                    'end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString(),
                ]);
            }

            if ($activeHistory) {
                $activeHistory->update([
                    'end_date' => Carbon::parse($data['start_date'])->subDay()->toDateString(),
                ]);
                $previousHouse = $activeHistory->house;
            }

            HouseHistory::create([
                'house_id' => $house->id,
                'resident_id' => $data['resident_id'],
                'start_date' => $data['start_date'],
                'end_date' => null,
            ]);

            $house->update(['status' => 'dihuni']);

            if ($previousHouse && $previousHouse->id !== $house->id) {
                $previousHouse->update(['status' => 'tidak dihuni']);
            }
        });

        
    }
    public function unassignResident(House $house): void
{
    $activeHistory = HouseHistory::where('house_id', $house->id)
        ->whereNull('end_date')
        ->first();

    if (!$activeHistory) {
        throw new Exception('Rumah ini tidak memiliki penghuni aktif.');
    }

    DB::transaction(function () use ($house, $activeHistory) {
        $activeHistory->update([
            'end_date' => Carbon::now()->toDateString(),
        ]);

        $house->update(['status' => 'tidak dihuni']);
    });
}
}
