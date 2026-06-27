<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FeeCategory;
use App\Models\House;
use App\Models\Resident;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeResidentPhoto(): UploadedFile
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl8rZQAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('ktp.png', $pngBytes);
    }

    public function test_ping_endpoint_returns_success(): void
    {
        $this->getJson('/api/ping')
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_residents_endpoints_work(): void
    {
        Storage::fake('public');

        $createResponse = $this->postJson('/api/residents', [
            'full_name' => 'Budi Santoso',
            'ktp_photo' => $this->fakeResidentPhoto(),
            'status' => 'tetap',
            'phone_number' => '08123456789',
            'is_married' => true,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('status', 'success');

        $residentId = $createResponse->json('data.id');

        $this->getJson('/api/residents')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/residents/' . $residentId)
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Budi Santoso');

        $this->putJson('/api/residents/' . $residentId, [
            'full_name' => 'Budi Santoso Update',
            'phone_number' => '08999999999',
        ])->assertOk()
            ->assertJsonPath('data.full_name', 'Budi Santoso Update');

        $this->deleteJson('/api/residents/' . $residentId)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('residents', ['id' => $residentId]);
    }

    public function test_houses_and_assign_endpoint_work(): void
    {
        $resident = Resident::create([
            'full_name' => 'Siti Aminah',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'kontrak',
            'phone_number' => '08111111111',
            'is_married' => false,
        ]);

        $createResponse = $this->postJson('/api/houses', [
            'house_code' => 'A-01',
        ]);

        $createResponse->assertCreated();
        $houseId = $createResponse->json('data.id');

        $this->getJson('/api/houses')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/houses/' . $houseId)
            ->assertOk()
            ->assertJsonPath('data.house_code', 'A-01');

        $this->putJson('/api/houses/' . $houseId, [
            'house_code' => 'A-02',
        ])->assertOk()
            ->assertJsonPath('data.house_code', 'A-02');

        $this->postJson('/api/houses/' . $houseId . '/assign', [
            'resident_id' => $resident->id,
            'start_date' => '2026-06-01',
        ])->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('house_histories', [
            'house_id' => $houseId,
            'resident_id' => $resident->id,
            'start_date' => '2026-06-01',
            'end_date' => null,
        ]);

        $this->deleteJson('/api/houses/' . $houseId)
            ->assertOk();

        $this->assertDatabaseMissing('houses', ['id' => $houseId]);
    }

    public function test_payments_endpoints_work(): void
    {
        $resident = Resident::create([
            'full_name' => 'Ahmad Farid',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '08222222222',
            'is_married' => true,
        ]);

        $feeCategory = FeeCategory::create([
            'name' => 'Iuran Bulanan',
            'amount' => 150000,
        ]);

        $createResponse = $this->postJson('/api/payments', [
            'resident_id' => $resident->id,
            'fee_category_id' => $feeCategory->id,
            'for_month' => 6,
            'for_year' => 2026,
            'number_of_months' => 1,
            'payment_date' => '2026-06-15',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('status', 'success');

        $paymentId = $createResponse->json('data.0.id');

        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/payments/' . $paymentId)
            ->assertOk()
            ->assertJsonPath('data.amount_paid', 150000);

        $this->putJson('/api/payments/' . $paymentId, [
            'status' => 'belum',
        ])->assertOk()
            ->assertJsonPath('data.status', 'belum');

        $this->putJson('/api/payments/' . $paymentId, [
            'status' => 'invalid-status',
        ])->assertStatus(422);

        $this->deleteJson('/api/payments/' . $paymentId)
            ->assertOk();

        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    }

    public function test_expenses_endpoints_work(): void
    {
        $createResponse = $this->postJson('/api/expenses', [
            'description' => 'Perbaikan lampu',
            'amount' => 50000,
            'expense_date' => '2026-06-10',
        ]);

        $createResponse->assertCreated();
        $expenseId = $createResponse->json('data.id');

        $this->getJson('/api/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/expenses/' . $expenseId)
            ->assertOk()
            ->assertJsonPath('data.description', 'Perbaikan lampu');

        $this->putJson('/api/expenses/' . $expenseId, [
            'description' => 'Perbaikan listrik',
        ])->assertOk()
            ->assertJsonPath('data.description', 'Perbaikan listrik');

        $this->deleteJson('/api/expenses/' . $expenseId)
            ->assertOk();

        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }

    public function test_report_endpoints_work(): void
    {
        $resident = Resident::create([
            'full_name' => 'Citra Dewi',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '08333333333',
            'is_married' => false,
        ]);

        $feeCategory = FeeCategory::create([
            'name' => 'Kas Bulanan',
            'amount' => 100000,
        ]);

        Payment::create([
            'resident_id' => $resident->id,
            'fee_category_id' => $feeCategory->id,
            'for_month' => 6,
            'for_year' => 2026,
            'amount_paid' => 100000,
            'status' => 'lunas',
            'payment_date' => '2026-06-05',
        ]);

        Expense::create([
            'description' => 'Beli cat',
            'amount' => 25000,
            'expense_date' => '2026-06-06',
        ]);

        $this->getJson('/api/reports/summary?year=2026')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(12, 'data');

        $this->getJson('/api/reports/detail?month=6&year=2026')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_income', 100000)
            ->assertJsonPath('data.total_expense', 25000);
    }

    public function test_generate_monthly_bills_creates_pending_dues_for_active_residents_only(): void
    {
        $residentFixed = Resident::create([
            'full_name' => 'Warga Tetap',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '08110000001',
            'is_married' => true,
        ]);

        $residentContractActive = Resident::create([
            'full_name' => 'Warga Kontrak Aktif',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'kontrak',
            'phone_number' => '08110000002',
            'is_married' => false,
        ]);

        $residentContractInactive = Resident::create([
            'full_name' => 'Warga Kontrak Kosong',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'kontrak',
            'phone_number' => '08110000003',
            'is_married' => false,
        ]);

        $house = House::create([
            'house_code' => 'B-01',
            'status' => 'tidak dihuni',
        ]);

        $this->postJson('/api/houses/' . $house->id . '/assign', [
            'resident_id' => $residentContractActive->id,
            'start_date' => '2026-06-01',
        ])->assertOk();

        FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        FeeCategory::create([
            'name' => 'Kebersihan',
            'amount' => 15000,
        ]);

        $this->postJson('/api/payments/generate-monthly', [
            'month' => 6,
            'year' => 2026,
        ])->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.created_count', 4);

        $this->assertDatabaseHas('payments', [
            'resident_id' => $residentFixed->id,
            'status' => 'belum',
            'for_month' => 6,
            'for_year' => 2026,
        ]);

        $this->assertDatabaseHas('payments', [
            'resident_id' => $residentContractActive->id,
            'status' => 'belum',
            'for_month' => 6,
            'for_year' => 2026,
        ]);

        $this->assertDatabaseMissing('payments', [
            'resident_id' => $residentContractInactive->id,
            'for_month' => 6,
            'for_year' => 2026,
        ]);
    }

    public function test_monthly_bill_command_generates_bills(): void
    {
        $resident = Resident::create([
            'full_name' => 'Jalan RT 1',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '081133334444',
            'is_married' => true,
        ]);

        FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        $this->artisan('rt-admin:generate-monthly-bills', [
            '--month' => 7,
            '--year' => 2026,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('payments', [
            'resident_id' => $resident->id,
            'for_month' => 7,
            'for_year' => 2026,
            'status' => 'belum',
        ]);
    }

    public function test_outstanding_bills_endpoint_lists_unpaid_payments_for_period(): void
    {
        $resident = Resident::create([
            'full_name' => 'Tagihan RT',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '081199998888',
            'is_married' => true,
        ]);

        FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        $this->postJson('/api/payments/generate-monthly', [
            'month' => 8,
            'year' => 2026,
        ])->assertCreated();

        $this->getJson('/api/payments/outstanding?month=8&year=2026')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('period.month', 8)
            ->assertJsonPath('period.year', 2026)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('total_amount', 100000)
            ->assertJsonPath('data.0.resident.id', $resident->id)
            ->assertJsonPath('data.0.status', 'belum');
    }

    public function test_outstanding_summary_groups_unpaid_bills_by_resident_and_house(): void
    {
        $resident = Resident::create([
            'full_name' => 'Summary RT',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'kontrak',
            'phone_number' => '081177776666',
            'is_married' => false,
        ]);

        $house = House::create([
            'house_code' => 'D-01',
            'status' => 'tidak dihuni',
        ]);

        $this->postJson('/api/houses/' . $house->id . '/assign', [
            'resident_id' => $resident->id,
            'start_date' => '2026-08-01',
        ])->assertOk();

        FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        FeeCategory::create([
            'name' => 'Kebersihan',
            'amount' => 15000,
        ]);

        $this->postJson('/api/payments/generate-monthly', [
            'month' => 8,
            'year' => 2026,
        ])->assertCreated();

        $this->getJson('/api/reports/outstanding-summary?month=8&year=2026')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.period.month', 8)
            ->assertJsonPath('data.period.year', 2026)
            ->assertJsonPath('data.total_payments', 2)
            ->assertJsonPath('data.total_amount', 115000)
            ->assertJsonPath('data.by_resident.0.resident.id', $resident->id)
            ->assertJsonPath('data.by_resident.0.house.house_code', 'D-01')
            ->assertJsonPath('data.by_resident.0.total_amount', 115000)
            ->assertJsonPath('data.by_house.0.house.house_code', 'D-01')
            ->assertJsonPath('data.by_house.0.total_amount', 115000);
    }

    public function test_dashboard_endpoint_summarizes_income_expense_and_outstanding(): void
    {
        $resident = Resident::create([
            'full_name' => 'Dashboard RT',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '081144443333',
            'is_married' => true,
        ]);

        $feeCategory = FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        Payment::create([
            'resident_id' => $resident->id,
            'fee_category_id' => $feeCategory->id,
            'for_month' => 9,
            'for_year' => 2026,
            'amount_paid' => 100000,
            'status' => 'lunas',
            'payment_date' => '2026-09-05',
        ]);

        Payment::create([
            'resident_id' => $resident->id,
            'fee_category_id' => $feeCategory->id,
            'for_month' => 9,
            'for_year' => 2026,
            'amount_paid' => 100000,
            'status' => 'belum',
            'payment_date' => null,
        ]);

        Expense::create([
            'description' => 'Token pos',
            'amount' => 25000,
            'expense_date' => '2026-09-06',
        ]);

        $this->getJson('/api/reports/dashboard?month=9&year=2026')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.period.month', 9)
            ->assertJsonPath('data.period.year', 2026)
            ->assertJsonPath('data.income', 100000)
            ->assertJsonPath('data.expense', 25000)
            ->assertJsonPath('data.saldo', 75000)
            ->assertJsonPath('data.outstanding_count', 1)
            ->assertJsonPath('data.outstanding_amount', 100000);
    }

    public function test_dashboard_and_report_exports_download_csv_files(): void
    {
        $resident = Resident::create([
            'full_name' => 'Export RT',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '081122334455',
            'is_married' => true,
        ]);

        $feeCategory = FeeCategory::create([
            'name' => 'Satpam',
            'amount' => 100000,
        ]);

        Payment::create([
            'resident_id' => $resident->id,
            'fee_category_id' => $feeCategory->id,
            'for_month' => 10,
            'for_year' => 2026,
            'amount_paid' => 100000,
            'status' => 'lunas',
            'payment_date' => '2026-10-05',
        ]);

        Expense::create([
            'description' => 'Lampu pos',
            'amount' => 20000,
            'expense_date' => '2026-10-06',
        ]);

        $this->get('/api/reports/dashboard/export?month=10&year=2026')
            ->assertOk()
            ->assertDownload('dashboard-10-2026.csv');

        $this->get('/api/reports/detail/export?month=10&year=2026')
            ->assertOk()
            ->assertDownload('report-detail-10-2026.csv');

        $outstandingResident = Resident::create([
            'full_name' => 'Export Tunggakan',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'tetap',
            'phone_number' => '081122334456',
            'is_married' => false,
        ]);

        FeeCategory::create([
            'name' => 'Kebersihan',
            'amount' => 15000,
        ]);

        $this->postJson('/api/payments/generate-monthly', [
            'month' => 11,
            'year' => 2026,
        ])->assertCreated();

        $this->get('/api/reports/outstanding-summary/export?month=11&year=2026')
            ->assertOk()
            ->assertDownload('outstanding-summary-11-2026.csv');

        $this->assertDatabaseHas('payments', [
            'resident_id' => $outstandingResident->id,
            'for_month' => 11,
            'for_year' => 2026,
            'status' => 'belum',
        ]);
    }

    public function test_assigning_same_resident_to_new_house_closes_previous_history(): void
    {
        $resident = Resident::create([
            'full_name' => 'Pindahan RT',
            'ktp_photo_path' => 'ktp_photos/sample.jpg',
            'status' => 'kontrak',
            'phone_number' => '081122223333',
            'is_married' => false,
        ]);

        $firstHouse = House::create([
            'house_code' => 'C-01',
            'status' => 'tidak dihuni',
        ]);

        $secondHouse = House::create([
            'house_code' => 'C-02',
            'status' => 'tidak dihuni',
        ]);

        $this->postJson('/api/houses/' . $firstHouse->id . '/assign', [
            'resident_id' => $resident->id,
            'start_date' => '2026-06-01',
        ])->assertOk();

        $this->postJson('/api/houses/' . $secondHouse->id . '/assign', [
            'resident_id' => $resident->id,
            'start_date' => '2026-06-10',
        ])->assertOk();

        $this->assertDatabaseHas('house_histories', [
            'house_id' => $firstHouse->id,
            'resident_id' => $resident->id,
            'end_date' => '2026-06-09',
        ]);

        $this->assertDatabaseHas('houses', [
            'id' => $firstHouse->id,
            'status' => 'tidak dihuni',
        ]);

        $this->assertDatabaseHas('house_histories', [
            'house_id' => $secondHouse->id,
            'resident_id' => $resident->id,
            'end_date' => null,
        ]);

        $this->assertDatabaseHas('houses', [
            'id' => $secondHouse->id,
            'status' => 'dihuni',
        ]);
    }
}
