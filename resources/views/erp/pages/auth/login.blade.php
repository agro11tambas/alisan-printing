<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login ERP Alisan">
    <title>Alisan</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f6fa;
            color: #283142;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: #f5f6fa;
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
        }

        .login-card {
            position: relative;
            width: min(100%, 430px);
            padding: 40px;
            border: 1px solid #e8eaf0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 12px 32px rgba(30, 41, 59, .08);
        }

        h1 {
            margin: 0 0 18px;
            font-size: 20px;
            line-height: 1.35;
        }

        .field {
            width: 100%;
            height: 46px;
            margin-bottom: 12px;
            padding: 10px 13px;
            border: 1px solid #d9dde7;
            border-radius: 6px;
            background: #fff;
            color: #283142;
            font: inherit;
            outline: none;
        }

        .field:focus {
            border-color: #3454d1;
            box-shadow: 0 0 0 3px rgba(52, 84, 209, .12);
        }

        .submit {
            width: 100%;
            min-height: 46px;
            border: 0;
            border-radius: 6px;
            background: #3454d1;
            color: #fff;
            font: 600 16px/1 inherit;
            cursor: pointer;
        }

        .submit:hover {
            background: #2d49b8;
        }

        .error {
            margin: 0 0 14px;
            padding: 10px 12px;
            border-radius: 6px;
            background: #fff1f2;
            color: #be123c;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>

<body>
    <main class="auth-shell">
        <section class="login-card" aria-labelledby="login-title">
            <h1 id="login-title">Login</h1>

            @if ($errors->any())
                <div class="error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form action="/login" method="POST">
                @csrf
                <input
                    class="field"
                    type="text"
                    name="username"
                    id="username"
                    value="{{ old('username') }}"
                    placeholder="Username"
                    autocomplete="username"
                    required
                    autofocus>
                <input
                    class="field"
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required>
                <button class="submit" type="submit">Login</button>
            </form>
        </section>
    </main>
</body>

</html>
