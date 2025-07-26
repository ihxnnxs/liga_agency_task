<?php
// In app/Services/GoogleSheetService.php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Revolution\Google\Sheets\Facades\Sheets;

class GoogleSheetService
{
    /**
     * Получает все данные с указанного листа.
     *
     * @throws Exception - если произошла ошибка при обращении к API
     */
    public function getSheetData(string $sheetId, string $sheetName): Collection
    {
        return Sheets::spreadsheet($sheetId)->sheet($sheetName)->get();
    }

    /**
     * Полностью очищает указанный лист.
     *
     * @throws Exception
     */
    public function clearSheet(string $sheetId, string $sheetName): void
    {
        Sheets::spreadsheet($sheetId)->sheet($sheetName)->clear();
    }

    /**
     * Записывает массив данных на указанный лист.
     *
     * @throws Exception
     */
    public function updateSheet(string $sheetId, string $sheetName, array $data): void
    {
        Sheets::spreadsheet($sheetId)->sheet($sheetName)->update($data);
    }
}
