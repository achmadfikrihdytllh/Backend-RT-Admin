<?php

namespace App\Console\Commands;

use App\Services\PaymentService;
use Illuminate\Console\Command;

class GenerateMonthlyBills extends Command
{
    protected $signature = 'rt-admin:generate-monthly-bills {--month= : Target month (1-12)} {--year= : Target year}';

    protected $description = 'Generate pending monthly bills for eligible residents';

    public function __construct(private PaymentService $paymentService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);

        $result = $this->paymentService->generateMonthlyBills($month, $year);

        $this->info(sprintf(
            'Generated %d pending bill(s) for %d/%d.',
            $result['created_count'],
            $month,
            $year,
        ));

        return self::SUCCESS;
    }
}