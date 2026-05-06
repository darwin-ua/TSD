@extends('auth.appauth')

@section('content')
    <style>
        body {
            background: #f5f6f7;
        }

        .login-page {
            min-height: 100vh;
            padding: 18px 12px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(135deg, rgba(255, 198, 0, 0.07), rgba(255,255,255,0.96)),
                #f5f6f7;
        }

        .login-shell {
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.10);
            overflow: hidden;
            border-top: 5px solid #f3c400;
        }

        .login-header {
            padding: 22px 18px 18px;
            text-align: center;
            border-bottom: 1px solid #eceff3;
        }

        .login-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 12px;
            border-radius: 14px;
            background: #171717;
            color: #f3c400;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-icon svg {
            width: 32px;
            height: 32px;
        }

        .login-title {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            color: #171717;
            line-height: 1.2;
        }

        .login-subtitle {
            margin-top: 5px;
            font-size: 13px;
            color: #777777;
            font-weight: 600;
        }

        .login-body {
            padding: 20px 18px 22px;
        }

        .login-label {
            font-size: 13px;
            font-weight: 800;
            color: #171717;
            margin-bottom: 7px;
        }

        .login-input {
            height: 52px;
            border-radius: 11px;
            border: 1px solid #d9dde2;
            box-shadow: none;
            font-size: 16px;
            font-weight: 700;
            color: #171717;
        }

        .login-input:focus {
            border-color: #f3c400;
            box-shadow: 0 0 0 0.2rem rgba(243, 196, 0, 0.18);
        }

        .login-btn {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: 11px;
            background: #171717;
            color: #ffffff;
            font-size: 15px;
            font-weight: 900;
            transition: 0.18s ease;
            margin-top: 8px;
        }

        .login-btn:hover,
        .login-btn:focus {
            background: #f3c400;
            color: #171717;
        }

        .invalid-feedback strong {
            color: #dc3545;
            font-size: 13px;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .login-bottom-subtitle {
            text-align: center;
            font-size: 13px;
            color: #777777;
            font-weight: 600;
            padding: 0 18px 22px;
        }

        @media (max-width: 480px) {
            .login-page {
                padding: 12px 10px 24px;
            }

            .login-header {
                padding: 20px 16px 16px;
            }

            .login-body {
                padding: 18px 16px 20px;
            }

            .login-title {
                font-size: 22px;
            }
        }
    </style>

    <div class="login-page">
        <div class="login-shell">
            <div class="login-card">

                <div class="login-header">
                    <div class="login-icon">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="currentColor"
                             class="bi bi-box-seam"
                             viewBox="0 0 16 16">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
                        </svg>
                    </div>

                    <h4 class="login-title">Склад</h4>
                    <div class="login-subtitle">Вход в систему ТСД</div>
                </div>

                <div class="login-body">
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group">
                            <label for="id_lk" class="login-label">Логин в 1С</label>
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

                        <button type="submit"
                                class="login-btn ladda-button"
                                data-style="expand-right">
                            <span class="ladda-label">{{ __('Login') }}</span>
                            <span class="ladda-spinner"></span>
                        </button>
                    </form>
                </div>
                <div class="login-bottom-subtitle">Дарвiн / Гудвiн</div>
            </div>
        </div>
    </div>
@endsection
