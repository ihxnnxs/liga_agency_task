<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    /**
     * Отображает главную страницу с элементами и настройками
     */
    public function index()
    {
        $items = Item::query()->latest()->paginate(15);
        $sheetId = Cache::get('google_sheet_id');

        return view('items.index', compact('items', 'sheetId'));
    }

    /**
     * Генерирует 1000 записей в базе данных.
     */
    public function generate()
    {
        Item::factory()->count(1000)->create();

        return redirect()->route('items.index')->with('success', '1000 записей успешно сгенерировано!');
    }

    /**
     * Очищает все записи в базе данных.
     */
    public function clear()
    {
        Item::query()->truncate();

        return redirect()->route('items.index')->with('success', 'Все записи успешно удалены!');
    }

    /**
     * Сохраняет URL Google Таблицы в кэш.
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'sheet_url' => 'required|string|url'
        ]);

        $url = $request->input('sheet_url');
        preg_match('/spreadsheets\/d\/([a-zA-Z0-9\-_]+)/', $url, $matches);

        if (isset($matches[1])) {
            $sheetId = $matches[1];
            Cache::forever('google_sheet_id', $sheetId);
            return redirect()->route('items.index')->with('success', 'URL таблицы успешно сохранен!');
        }

        return redirect()->route('items.index')->with('error', 'Не удалось извлечь ID из URL. Проверьте ссылку.');
    }
}
