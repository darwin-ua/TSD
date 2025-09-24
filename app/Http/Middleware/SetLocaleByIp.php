<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetLocaleByIp
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        Log::info("IP Address: {$ip}");

        $cacheKey = "locale_for_ip_{$ip}";
        if (Cache::has($cacheKey)) {
            $locale = Cache::get($cacheKey);
            Log::info("Cached Locale: {$locale}");
            app()->setLocale($locale);
            return $next($request);
        }
        $response = Http::get("https://ipapi.co/{$ip}/json/");

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['country_code'])) {
                $countryCode = strtoupper($data['country_code']);

                $locale = $this->getLocaleByCountryCode($countryCode);

                // Cache the locale
                Cache::put($cacheKey, $locale, now()->addDay());

                app()->setLocale($locale);
            } else {
                app()->setLocale('en');
            }
        } else {
            app()->setLocale('en');
        }

        return $next($request);
    }

    private function getLocaleByCountryCode($countryCode)
    {
        $locales = [
            'UA' => 'ua',
            'EN' => 'en',
            'RU' => 'ru',
            'PL' => 'pl',
        ];

        return $locales[$countryCode] ?? 'en';
    }
}
