@extends('auth.appauth')

@section('content')
    <style>
        body {
            background: #f5f6f7;
            font-family: Arial, sans-serif;
        }

        .darwin-login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            background:
                linear-gradient(135deg, rgba(255, 198, 0, 0.08), rgba(255,255,255,0.95)),
                #f5f6f7;
        }

        .darwin-login-card {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 18px;
            padding: 38px 36px 34px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
            border-top: 5px solid #f3c400;
        }

        .darwin-logo-box {
            width: 74px;
            height: 74px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: #151515;
            color: #f3c400;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.22);
        }

        .darwin-logo-box svg {
            width: 42px;
            height: 42px;
        }

        .darwin-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #171717;
            margin-bottom: 5px;
        }

        .darwin-subtitle {
            text-align: center;
            font-size: 15px;
            color: #777777;
            margin-bottom: 18px;
        }

        .darwin-divider {
            width: 140px;
            height: 1px;
            background: #f3c400;
            margin: 0 auto 28px;
            position: relative;
            opacity: 0.65;
        }

        .darwin-divider:after {
            content: "";
            width: 8px;
            height: 8px;
            background: #f3c400;
            border-radius: 50%;
            position: absolute;
            top: -4px;
            left: 50%;
            transform: translateX(-50%);
        }

        .darwin-form .form-group {
            margin-bottom: 18px;
        }

        .darwin-form label {
            font-size: 14px;
            font-weight: 600;
            color: #222222;
            margin-bottom: 7px;
        }

        .darwin-form .form-control {
            height: 50px;
            border-radius: 10px;
            border: 1px solid #d9dde2;
            font-size: 15px;
            padding: 10px 14px;
            box-shadow: none;
            background: #ffffff;
        }

        .darwin-form .form-control:focus {
            border-color: #f3c400;
            box-shadow: 0 0 0 0.2rem rgba(243, 196, 0, 0.18);
        }

        .darwin-login-btn {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 10px;
            background: #171717;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            transition: 0.2s ease;
            margin-top: 8px;
            cursor: pointer;
        }

        .darwin-login-btn:hover {
            background: #f3c400;
            color: #171717;
        }

        .darwin-footer-text {
            margin-top: 22px;
            text-align: center;
            font-size: 12px;
            color: #999999;
        }

        .darwin-footer-text a {
            color: inherit;
            text-decoration: none;
        }


    </style>

    <div class="darwin-login-wrap">
        <div class="darwin-login-card">

            <div class="darwin-logo-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
                </svg>
            </div>

            <div class="darwin-title">Склад</div>
            <div class="darwin-subtitle">Вхід у складську систему</div>
            <div class="darwin-divider"></div>

            <form method="POST" action="{{ route('login') }}" class="darwin-form">
                @csrf

                <div class="form-group">
                    <label for="id_lk">Логин в 1С</label>
                    <input id="id_lk"
                           type="text"
                           class="form-control @error('id_lk') is-invalid @enderror"
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
                    <label for="password">Пароль в 1С</label>
                    <input id="password"
                           type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           name="password"
                           required
                           autocomplete="current-password">

                    @error('password')
                    <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                    @enderror
                </div>

                <button type="submit" class="darwin-login-btn ladda-button" data-style="expand-right">
                    <span class="ladda-label">Войти</span>
                    <span class="ladda-spinner"></span>
                </button>
            </form>

            <div class="darwin-footer-text">
                Darwin / Goodwin · склад ·
                developed by <a href="https://eventhes.com" target="_blank" rel="noopener noreferrer">
                    Eventhes
                </a>
            </div>

        </div>
    </div>
@endsection
