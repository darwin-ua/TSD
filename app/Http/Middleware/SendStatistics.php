<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SendStatistics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Получаем текущее количество запросов из сессии
        $requestCount = session('request_count', 0);

        // Проверяем, что запросов было отправлено не более одного раза
        if ($requestCount < 1) {
            $statisticsData = [
                [
                    'ip' => $request->ip(),
                    'event_id' => mt_rand(5, 15)
                ],
            ];

            $response = Http::withToken('00bfb1b61d5be4e997abd77f16fce984')
                ->post('http://127.0.0.1:3000/api/statistics', $statisticsData);

            // Увеличиваем счетчик запросов
            $requestCount++;

            // Сохраняем количество запросов в сессии
            session(['request_count' => $requestCount]);

            if (!$response->successful()) {
                return response('Ошибка при отправке запроса на сервер Node.js', 500);
            }
        }

        // Добавляем количество запросов в заголовок ответа
        $response = $next($request);
        return $response->header('X-Request-Count', $requestCount);
    }
}
