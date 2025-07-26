<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class FetchSheetComments extends Command
{
    private const SHEET_NAME = 'Лист1';

    protected $signature = 'app:fetch-comments {--count= : Limit the number of rows to display}';
    protected $description = 'Fetches comments from the Google Sheet and displays them in the console with a progress bar.';

    // Laravel автоматически внедрит наш сервис в конструктор
    public function __construct(private GoogleSheetService $sheetService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting to fetch data from Google Sheets...');

        $sheetId = Cache::get('google_sheet_id');
        if (!$sheetId) {
            $this->error('Google Sheet ID is not configured.');
            return SymfonyCommand::FAILURE;
        }

        try {
            $sheetData = $this->sheetService->getSheetData($sheetId, self::SHEET_NAME);
        } catch (\Exception $e) {
            $this->error('An error occurred while fetching data: ' . $e->getMessage());
            return SymfonyCommand::FAILURE;
        }

        if ($sheetData->isEmpty() || $sheetData->count() <= 1) {
            $this->warn('The sheet is empty or contains only headers. Nothing to display.');
            return SymfonyCommand::SUCCESS;
        }

        $this->processAndDisplayResults($sheetData);

        $this->info('Command finished successfully.');
        return SymfonyCommand::SUCCESS;
    }

    private function processAndDisplayResults(Collection $sheetData): void
    {
        $headersArray = $sheetData->shift();
        $headers = collect($headersArray)->map(fn($h) => strtolower(trim($h ?? '')));

        $idIndex = $headers->search('id');
        $commentIndex = $headers->search('comment');

        if ($idIndex === false || $commentIndex === false) {
            $this->error("Sheet must contain 'id' and 'comment' columns. Found: " . $headers->implode(', '));
            return;
        }

        $outputData = $sheetData->map(function ($row) use ($idIndex, $commentIndex) {
            return ['id' => $row[$idIndex] ?? null, 'comment' => $row[$commentIndex] ?? ''];
        })->filter(fn($row) => !empty($row['id']));

        $count = $this->option('count');
        if ($count && is_numeric($count)) {
            $outputData = $outputData->take((int)$count);
        }

        if ($outputData->isEmpty()) {
            $this->info('No data rows with IDs found to display.');
            return;
        }

        $this->comment('Processing ' . $outputData->count() . ' rows...');
        $progressBar = $this->output->createProgressBar($outputData->count());

        $progressBar->start();
        $this->newLine();

        foreach ($outputData as $row) {
            $this->line("Model ID: <fg=yellow>{$row['id']}</> / Comment: <fg=cyan>{$row['comment']}</>");
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }
}
