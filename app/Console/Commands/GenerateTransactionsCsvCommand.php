<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:generate-csv {--count=100000 : Количество строк для генерации} {--file=test_transactions.csv : Имя файла} ')]
#[Description('Генерирует CSV файл с моковыми транзакциями для тестирования импорта')]
class GenerateTransactionsCsvCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $fileName = $this->option('file');

        $filePath = storage_path('app/' . $fileName);

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $file = fopen($filePath, 'w');

        if (!$file) {
            $this->error("Не удалось открыть файл для записи: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Начинаем генерацию {$count} строк в файл {$fileName}...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = time();
        $oneYearAgo = strtotime('-1 year', $now);

        try {
            fputcsv($file, ['user_id', 'amount', 'transaction_date']);

            for ($i = 0; $i < $count; $i++) {
                $userId = random_int(1, 1000);
                $amount = random_int(100, 100000);

                $timestamp = random_int($oneYearAgo, $now);
                $date = date('Y-m-d H:i:s', $timestamp);

                fputcsv($file, [$userId, $amount, $date]);

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Файл успешно сгенерирован: {$filePath}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("\nПроизошла ошибка при генерации файла: " . $e->getMessage());
            return self::FAILURE;
        } finally {
            if (is_resource($file)) {
                fclose($file);
            }
        }
    }
}
