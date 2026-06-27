<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\FeeCategoryController;

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Backend RT Admin berjalan dengan lancar!'
    ]);
});

Route::apiResource('residents', ResidentController::class);
Route::get('/residents/{id}/ktp', [ResidentController::class, 'showKtp']);

Route::apiResource('houses', HouseController::class);

Route::post('houses/{id}/assign', [HouseController::class, 'assignResident']);
Route::post('houses/{id}/unassign', [HouseController::class, 'unassignResident']);

Route::post('payments/generate-monthly', [PaymentController::class, 'generateMonthlyBills']);
Route::get('payments/outstanding', [PaymentController::class, 'outstanding']);

Route::get('/payments', [PaymentController::class, 'index']);
Route::post('/payments', [PaymentController::class, 'store']); 
Route::get('/payments/{id}', [PaymentController::class, 'show']);
Route::patch('/payments/{id}/pay', [PaymentController::class, 'pay']);
Route::patch('/payments/{id}', [PaymentController::class, 'update']);
Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);


Route::apiResource('expenses', ExpenseController::class);

Route::get('reports/summary', [ReportController::class, 'summary']);
Route::get('reports/detail', [ReportController::class, 'detail']);
Route::get('reports/outstanding-summary', [ReportController::class, 'outstandingSummary']);
Route::get('reports/dashboard', [ReportController::class, 'dashboard']);
Route::get('reports/dashboard/export', [ReportController::class, 'dashboardExport']);
Route::get('reports/detail/export', [ReportController::class, 'detailExport']);
Route::get('reports/outstanding-summary/export', [ReportController::class, 'outstandingSummaryExport']);



Route::apiResource('fee-categories', FeeCategoryController::class);