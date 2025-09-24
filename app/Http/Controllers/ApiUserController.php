<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ApiUserController extends Controller
{

    public function store(Request $request)
    {
        try {
            $rawJson = $request->getContent();
            Log::info("📦 Сырое значение text: " . $rawJson);

            // Убираем неразрывные пробелы и другие невидимые символы
            $cleanText = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', ' ', $rawJson);
            $cleanText = preg_replace('/"phone"\s*:\s*([\d\s]+)/u', '"phone":"$1"', $cleanText);

            // Пробуем декодировать
            $decoded = json_decode($cleanText, true);

            // Если внутри — строка, пробуем второй раз
            if (is_array($decoded) && isset($decoded[0]) && is_string($decoded[0])) {
                $decoded = json_decode($decoded[0], true);
            }

            // Убедимся, что это массив объектов
            if (!is_array($decoded) || !isset($decoded[0])) {
                throw new \Exception('❌ Некорректный формат JSON');
            }

            $createdUsers = [];

            foreach ($decoded as $item) {
                try {
                    $user = \App\Models\User::create([
                        'role_id'    => $item['role_id'] ?? null,
                        'id_lk'      => (string)($item['id_lk'] ?? '0'),
                        'name'       => $item['name'],
                        'group' => (string)($item['group'] ?? '1'),
                        'email'      => $item['email'] ?? 'no@email.com',
                        'phone'      => preg_replace('/\s+/', '', $item['phone'] ?? ''),
                        'code_part'  => $item['code_part'] ?? null,
                        'password'   => \Illuminate\Support\Facades\Hash::make($item['password'] ?? '111111'),
                        'usertype'   => ($item['usertype'] ?? '') === 'Дилери:Дарвін' ? 2 : 1,
                    ]);

                    $createdUsers[] = [
                        'id'     => $user->id,
                        'id_lk'  => $user->id_lk,
                        'email'  => $user->email,
                    ];

                    Log::info("✅ Пользователь создан: ID {$user->id}, name: {$user->name}");
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('❌ Ошибка базы данных', ['message' => $e->getMessage(), 'data' => $item]);
                } catch (\Exception $e) {
                    Log::error('❗ Общая ошибка', ['message' => $e->getMessage(), 'data' => $item]);
                }
            }

            return response()->json([
                'message' => '✅ Пользователи успешно созданы',
                'users'   => $createdUsers,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('❌ Ошибка парсинга из буфера', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Ошибка парсинга'], 500);
        }
    }

}

