@extends('auth.appauth')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-6 col-sm-8">
                <div id="login" style="border-radius: 5px; margin-top:50px;">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
                        </svg>

                        <h4 class="mt-2">
                           Склад
                        </h4>
                    </div>

                    <div class="form-group text-center"></div>
                    <hr>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="form-group">
                            <label for="id_lk">Логин в 1С</label>
                            <input id="id_lk" type="text" class="form-control @error('id_lk') is-invalid @enderror" name="id_lk" value="{{ old('id_lk') }}" required autofocus>
                            @error('id_lk')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Пароль в 1С</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
&nbsp;&nbsp;
                        <button type="submit" class="btn_full ladda-button" style="background-color: #5a5b5d;" data-style="expand-right">
                            <span class="ladda-label">{{ __('Login') }}</span><span class="ladda-spinner"></span>
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
