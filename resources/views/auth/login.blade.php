@extends('auth.appauth')

@section('content')
    @php
        $forcedCompany = request('company');
        $theme = \App\Support\TsdOneC::themeByLogin(old('id_lk'), $forcedCompany);
    @endphp

    <style>
        :root {
            --theme-accent: {{ $theme['accent'] }};
            --theme-accent-rgb: {{ $theme['accent_rgb'] }};
            --theme-accent-soft: {{ $theme['accent_soft'] }};
            --theme-accent-soft-strong: {{ $theme['accent_soft_strong'] }};
            --theme-secondary-accent: {{ $theme['secondary_accent'] }};
            --theme-secondary-accent-rgb: {{ $theme['secondary_accent_rgb'] }};
            --theme-dark: {{ $theme['dark'] }};
            --theme-heading: {{ $theme['heading'] }};
            --theme-muted: {{ $theme['muted'] }};
            --theme-surface: {{ $theme['surface'] }};
            --theme-surface-alt: {{ $theme['surface_alt'] }};
            --theme-border: {{ $theme['border'] }};
            --theme-button-text: {{ $theme['button_text'] }};
            --theme-body-bg: {{ $theme['body_bg'] }};
            --theme-gradient-from: {{ $theme['gradient_from'] }};
            --theme-gradient-to: {{ $theme['gradient_to'] }};
        }

        body {
            background: var(--theme-body-bg);
        }

        .login-page {
            min-height: 100vh;
            padding: 20px 12px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, rgba(var(--theme-accent-rgb), 0.10), transparent 28%),
                linear-gradient(135deg, var(--theme-gradient-from), var(--theme-gradient-to), rgba(255,255,255,0.95)),
                var(--theme-body-bg);
        }

        .login-shell {
            width: 100%;
            max-width: 460px;
            margin: 0 auto;
        }

        .login-card {
            background: var(--theme-surface);
            border-radius: 20px;
            box-shadow: 0 18px 42px rgba(0, 0, 0, 0.10);
            overflow: hidden;
            border: 1px solid rgba(var(--theme-accent-rgb), 0.16);
        }

        .login-accent {
            height: 6px;
            background: linear-gradient(90deg, var(--theme-accent), var(--theme-secondary-accent));
        }

        .login-header {
            padding: 24px 22px 18px;
            text-align: center;
            border-bottom: 1px solid #eceff3;
        }

        .brand-wrap {
            margin: 0 auto 16px;
        }

        .brand-wordmark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            font-size: 38px;
            line-height: 1;
            letter-spacing: -0.03em;
            color: var(--theme-heading);
        }

        .brand-darwin-mark {
            width: 38px;
            height: 28px;
            display: inline-block;
            position: relative;
            background: var(--theme-accent);
            clip-path: polygon(0 18%, 100% 0, 100% 100%, 0 82%);
            border-radius: 2px;
        }

        .brand-darwin-mark::after {
            content: "";
            position: absolute;
            inset: 5px 8px 5px 10px;
            border-left: 2px solid rgba(255,255,255,0.85);
            border-right: 2px solid rgba(255,255,255,0.85);
        }

        .brand-darwin-text {
            color: var(--theme-secondary-accent);
            font-weight: 900;
        }

        .brand-good {
            color: var(--theme-secondary-accent);
        }

        .brand-win {
            color: var(--theme-accent);
        }

        .brand-subtitle {
            margin-top: 3px;
            font-size: 13px;
            color: var(--theme-muted);
            font-weight: 700;
        }

        .login-company-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 auto 16px;
            background: rgba(var(--theme-accent-rgb), 0.10);
            color: var(--theme-heading);
            border: 1px solid rgba(var(--theme-accent-rgb), 0.18);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 800;
        }

        .login-company-pill-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--theme-accent);
            box-shadow: 0 0 0 4px rgba(var(--theme-accent-rgb), 0.14);
        }

        .login-title {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: var(--theme-heading);
            line-height: 1.2;
        }

        .login-subtitle {
            margin-top: 7px;
            font-size: 14px;
            color: var(--theme-muted);
            font-weight: 600;
        }

        .login-body {
            padding: 22px 22px 18px;
        }

        .login-label {
            font-size: 13px;
            font-weight: 800;
            color: var(--theme-heading);
            margin-bottom: 7px;
        }

        .login-input {
            height: 54px;
            border-radius: 13px;
            border: 1px solid #d9dde2;
            box-shadow: none;
            font-size: 16px;
            font-weight: 700;
            color: var(--theme-heading);
            background: #fff;
        }

        .login-input:focus {
            border-color: var(--theme-accent);
            box-shadow: 0 0 0 0.2rem rgba(var(--theme-accent-rgb), 0.18);
        }

        .login-btn {
            width: 100%;
            height: 50px;
            border: none;
            border-radius: 13px;
            background: var(--theme-accent);
            color: var(--theme-button-text);
            font-size: 16px;
            font-weight: 900;
            transition: 0.18s ease;
            margin-top: 10px;
            box-shadow: 0 12px 24px rgba(var(--theme-accent-rgb), 0.20);
        }

        .login-btn:hover,
        .login-btn:focus {
            background: var(--theme-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .login-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            margin-bottom: 10px;
            color: var(--theme-muted);
            font-weight: 600;
            font-size: 13px;
        }

        .login-remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--theme-accent);
        }

        .invalid-feedback strong {
            color: #dc3545;
            font-size: 13px;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .login-bottom {
            padding: 0 22px 22px;
            text-align: center;
        }

        .login-bottom-subtitle {
            font-size: 12px;
            color: var(--theme-muted);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-theme-switcher {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .login-theme-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 112px;
            padding: 8px 12px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid rgba(var(--theme-accent-rgb), 0.18);
            color: var(--theme-heading);
            background: #fff;
        }

        .login-theme-link:hover {
            background: rgba(var(--theme-accent-rgb), 0.08);
            text-decoration: none;
            color: var(--theme-heading);
        }

        @media (max-width: 480px) {
            .login-page {
                padding: 12px 10px 22px;
            }

            .login-header {
                padding: 20px 16px 16px;
            }

            .login-body,
            .login-bottom {
                padding-left: 16px;
                padding-right: 16px;
            }

            .brand-wordmark {
                font-size: 32px;
            }
        }
    </style>

    <div class="login-page">
        <div class="login-shell">
            <div class="login-card">
                <div class="login-accent"></div>

                <div class="login-header">
                    <div class="brand-wrap" id="brandWrap">
                        <div class="brand-wordmark" id="brandWordmark">{!! $theme['wordmark_html'] !!}</div>
                        <div class="brand-subtitle" id="brandSubtitle">{{ $theme['wordmark_subtitle'] }}</div>
                    </div>

                    <div class="login-company-pill">
                        <span class="login-company-pill-dot"></span>
                        <span id="loginCompanyLabel">{{ $theme['company_label'] }}</span> · ТСД / склад
                    </div>

                    <h4 class="login-title">Вхід до системи ТСД</h4>
                    <div class="login-subtitle">Авторизація для {{ mb_strtolower($theme['company_label']) }} · ERP {{ strtoupper($theme['company']) }}</div>
                </div>

                <div class="login-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group">
                            <label for="id_lk" class="login-label">Логін в 1С</label>
                            <input id="id_lk"
                                   type="text"
                                   class="form-control login-input @error('id_lk') is-invalid @enderror"
                                   name="id_lk"
                                   value="{{ old('id_lk') }}"
                                   required
                                   autofocus>

                            @error('id_lk')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="login-label">Пароль в 1С</label>
                            <input id="password"
                                   type="password"
                                   class="form-control login-input @error('password') is-invalid @enderror"
                                   name="password"
                                   required
                                   autocomplete="current-password">

                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <label class="login-remember">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Запам’ятати мене</span>
                        </label>

                        <button type="submit"
                                class="login-btn ladda-button"
                                data-style="expand-right">
                            <span class="ladda-label">Увійти</span>
                            <span class="ladda-spinner"></span>
                        </button>
                    </form>
                </div>

                <div class="login-bottom">
                    <div class="login-bottom-subtitle">Тема визначається автоматично за логіном або можна вибрати вручну</div>
                    <div class="login-theme-switcher">
                        <a class="login-theme-link" href="{{ route('login', ['company' => 'darwin']) }}">Darwin</a>
                        <a class="login-theme-link" href="{{ route('login', ['company' => 'goodwin']) }}">Goodwin</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('id_lk');
            const loginCompanyLabel = document.getElementById('loginCompanyLabel');
            const brandWordmark = document.getElementById('brandWordmark');
            const brandSubtitle = document.getElementById('brandSubtitle');
            const endpoint = @json(route('login.company-theme'));
            const currentQueryCompany = @json(request('company'));

            function applyTheme(theme) {
                if (!theme) return;

                const root = document.documentElement;
                root.style.setProperty('--theme-accent', theme.accent);
                root.style.setProperty('--theme-accent-rgb', theme.accent_rgb);
                root.style.setProperty('--theme-accent-soft', theme.accent_soft);
                root.style.setProperty('--theme-accent-soft-strong', theme.accent_soft_strong);
                root.style.setProperty('--theme-secondary-accent', theme.secondary_accent);
                root.style.setProperty('--theme-secondary-accent-rgb', theme.secondary_accent_rgb);
                root.style.setProperty('--theme-dark', theme.dark);
                root.style.setProperty('--theme-heading', theme.heading);
                root.style.setProperty('--theme-muted', theme.muted);
                root.style.setProperty('--theme-surface', theme.surface);
                root.style.setProperty('--theme-surface-alt', theme.surface_alt);
                root.style.setProperty('--theme-border', theme.border);
                root.style.setProperty('--theme-button-text', theme.button_text);
                root.style.setProperty('--theme-body-bg', theme.body_bg);
                root.style.setProperty('--theme-gradient-from', theme.gradient_from);
                root.style.setProperty('--theme-gradient-to', theme.gradient_to);

                if (loginCompanyLabel) {
                    loginCompanyLabel.textContent = theme.company_label;
                }
                if (brandWordmark) {
                    brandWordmark.innerHTML = theme.wordmark_html;
                }
                if (brandSubtitle) {
                    brandSubtitle.textContent = theme.wordmark_subtitle;
                }
                const favicon = document.getElementById('dynamic-favicon');
                if (favicon && theme.favicon) {
                    favicon.setAttribute('href', theme.favicon);
                }
            }

            let timer = null;

            async function resolveThemeByLogin() {
                const idLk = (input?.value || '').trim();
                const url = new URL(endpoint, window.location.origin);
                if (idLk !== '') {
                    url.searchParams.set('id_lk', idLk);
                }
                if (currentQueryCompany) {
                    url.searchParams.set('company', currentQueryCompany);
                }

                try {
                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data && data.ok && data.theme) {
                        applyTheme(data.theme);
                    }
                } catch (e) {
                    console.warn('Theme resolve failed', e);
                }
            }

            input?.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(resolveThemeByLogin, 250);
            });

            if ((input?.value || '').trim() !== '' || currentQueryCompany) {
                resolveThemeByLogin();
            }
        });
    </script>
@endsection
