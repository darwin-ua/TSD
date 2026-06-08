<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TsdOneC
{
    public static function erpCompany(?User $user = null): string
    {
        $user = $user ?: auth()->user();

        if (!$user) {
            return 'darwin';
        }

        try {
            if (Schema::hasColumn('users', 'erp_company')) {
                $value = self::normalizeCompany((string) ($user->erp_company ?? ''));
                if ($value !== null) {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            // Не валим запрос, если Schema недоступна при раннем старте приложения.
        }

        $value = self::normalizeCompany(implode(' ', [
            (string) ($user->organization ?? ''),
            (string) ($user->group ?? ''),
            (string) ($user->type_company ?? ''),
        ]));

        return $value ?? 'darwin';
    }

    public static function erpCompanyByLogin(?string $idLk = null): string
    {
        $idLk = trim((string) $idLk);

        if ($idLk === '') {
            return 'darwin';
        }

        $user = User::query()->where('id_lk', $idLk)->first();

        return self::erpCompany($user);
    }

    public static function normalizeCompany(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (
            str_contains($value, 'goodw')
            || str_contains($value, 'гудв')
            || $value === '2'
        ) {
            return 'goodwin';
        }

        if (
            str_contains($value, 'darw')
            || str_contains($value, 'darv')
            || str_contains($value, 'дарв')
            || $value === '1'
        ) {
            return 'darwin';
        }

        return null;
    }

    public static function theme(?User $user = null, ?string $forcedCompany = null): array
    {
        $company = $forcedCompany
            ? (self::normalizeCompany($forcedCompany) ?? 'darwin')
            : self::erpCompany($user);

        if ($company === 'goodwin') {
            return [
                'company' => 'goodwin',
                'company_label' => 'Гудвін',
                'title_company' => 'Goodwin',
                'subtitle' => 'Завод світлопрозорих конструкцій',
                'accent' => '#0b63ae',
                'accent_rgb' => '11, 99, 174',
                'accent_soft' => '#e8f1fb',
                'accent_soft_strong' => '#d4e6f8',
                'secondary_accent' => '#f28c28',
                'secondary_accent_rgb' => '242, 140, 40',
                'dark' => '#0f3d67',
                'heading' => '#1c2430',
                'muted' => '#657184',
                'surface' => '#ffffff',
                'surface_alt' => '#f5f9fd',
                'border' => '#dce8f3',
                'button_text' => '#ffffff',
                'body_bg' => '#f3f6fa',
                'gradient_from' => 'rgba(11, 99, 174, 0.09)',
                'gradient_to' => 'rgba(242, 140, 40, 0.05)',
                'wordmark_html' => '<span class="brand-good">Good</span><span class="brand-win">win</span>',
                'wordmark_subtitle' => 'Завод світлопрозорих конструкцій',
                'footer_mark' => 'GOODWIN ERP / ТСД',
                'favicon' => asset('favicon-goodwin.svg'),
            ];
        }

        return [
            'company' => 'darwin',
            'company_label' => 'Дарвін',
            'title_company' => 'DARWIN',
            'subtitle' => 'Складські операції',
            'accent' => '#92cc00',
            'accent_rgb' => '146, 204, 0',
            'accent_soft' => '#eef8cf',
            'accent_soft_strong' => '#e4f1b2',
            'secondary_accent' => '#5f6368',
            'secondary_accent_rgb' => '95, 99, 104',
            'dark' => '#5f6368',
            'heading' => '#262a2e',
            'muted' => '#70757b',
            'surface' => '#ffffff',
            'surface_alt' => '#f8faf5',
            'border' => '#dfe6d2',
            'button_text' => '#ffffff',
            'body_bg' => '#f5f7f2',
            'gradient_from' => 'rgba(146, 204, 0, 0.09)',
            'gradient_to' => 'rgba(95, 99, 104, 0.05)',
            'wordmark_html' => '<span class="brand-darwin-mark"></span><span class="brand-darwin-text">DARWIN</span>',
            'wordmark_subtitle' => 'Віконні системи',
            'footer_mark' => 'DARWIN ERP / ТСД',
            'favicon' => asset('favicon-darwin.svg'),
        ];
    }

    public static function themeByLogin(?string $idLk = null, ?string $forcedCompany = null): array
    {
        $company = $forcedCompany
            ? (self::normalizeCompany($forcedCompany) ?? 'darwin')
            : self::erpCompanyByLogin($idLk);

        return self::theme(null, $company);
    }

    public static function baseUrl(?User $user = null): string
    {
        $company = self::erpCompany($user);

        $url = $company === 'goodwin'
            ? env('TSD_GOODWIN_BASE_URL', 'http://185.112.41.230/PROD_Goodwin/hs/tsd')
            : env('TSD_DARWIN_BASE_URL', 'http://192.168.170.105/PROD_copy/hs/tsd');

        return rtrim($url, '/');
    }

    public static function url(string $method, ?User $user = null): string
    {
        return self::baseUrl($user) . '/' . ltrim($method, '/');
    }

    public static function login(?User $user = null): string
    {
        $user = $user ?: auth()->user();

        return (string) (($user && !empty($user->name))
            ? $user->name
            : env('TSD_LOGIN', 'КучеренкоД'));
    }

    public static function password(?User $user = null): string
    {
        $user = $user ?: auth()->user();

        return (string) (($user && !empty($user->parol_1c))
            ? $user->parol_1c
            : env('TSD_PASSWORD', 'NitraPa$$@0@!'));
    }

    public static function diagnostics(?User $user = null): array
    {
        $user = $user ?: auth()->user();
        $theme = self::theme($user);

        return [
            'erp_company'  => self::erpCompany($user),
            'base_url'     => self::baseUrl($user),
            'user_id'      => $user->id ?? null,
            'user_name'    => $user->name ?? null,
            'organization' => $user->organization ?? null,
            'group'        => $user->group ?? null,
            'theme'        => $theme['company'],
        ];
    }
}
