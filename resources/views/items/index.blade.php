<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Синхронизация с Google Sheets</title>
    {{-- Предполагается, что вы подключаете скомпилированный CSS через Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">

<div class="container mx-auto p-4 md:p-8 max-w-7xl">

    <h1 class="text-3xl font-bold mb-6 text-gray-700">Управление данными и синхронизация</h1>

    {{-- Блок для вывода сообщений --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <p class="font-bold">Успех</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm" role="alert">
            <p class="font-bold">Ошибка</p>
            <p>{{ session('error') }}</p>
        </div>
    @endif

    {{-- Блок с настройками --}}
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Настройки Google Таблицы</h2>
        <form action="{{ route('items.saveSettings') }}" method="POST" class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            @csrf
            <div class="flex-grow w-full">
                <label for="sheet_url" class="sr-only">URL Google Таблицы:</label>
                <input type="url" id="sheet_url" name="sheet_url"
                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       value="https://docs.google.com/spreadsheets/d/{{ $sheetId ?? '' }}"
                       placeholder="https://docs.google.com/spreadsheets/d/xxxxxxxxxxxx/edit"
                       required>
            </div>
            <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-300">
                Сохранить
            </button>
        </form>
        @if ($sheetId)
            <div class="mt-4 bg-gray-50 p-3 rounded-md">
                <p class="text-sm text-gray-600">Текущий ID таблицы: <strong class="font-mono text-gray-800">{{ $sheetId }}</strong></p>
            </div>
        @endif
    </div>

    {{-- Блок с действиями --}}
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4">Действия с базой данных</h2>
        <div class="flex flex-col sm:flex-row gap-4">
            <form action="{{ route('items.generate') }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-md transition duration-300">
                    Сгенерировать 1000 записей
                </button>
            </form>
            <form action="{{ route('items.clear') }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-md transition duration-300"
                        onclick="return confirm('Вы уверены, что хотите полностью очистить таблицу в базе данных?')">
                    Очистить все записи
                </button>
            </form>
        </div>
    </div>

    {{-- Таблица с данными из БД --}}
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <h2 class="text-xl font-semibold p-6">Данные в базе</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата создания</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($items as $item)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $item->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if ($item->status === \App\Enums\Status::Allowed)
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Allowed
                            </span>
                        @else
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Prohibited
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        В базе данных пока нет записей. Нажмите "Сгенерировать", чтобы добавить.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        {{-- Пагинация (стили подтянутся, если вы настроили их для Tailwind) --}}
        <div class="p-6">
            {{ $items->links() }}
        </div>
    </div>

</div>

</body>
</html>
