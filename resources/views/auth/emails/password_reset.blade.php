
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body>
    <p>Привет, {{ $user->name }},</p>
<p>Вы запросили сброс пароля на нашем сайте. Чтобы продолжить, перейдите по ссылке ниже:</p>
<a href="{{ $resetUrl }}">Сбросить пароль</a>
<p>Если вы не запрашивали сброс пароля, можете проигнорировать это сообщение.</p>
<p>С уважением,</p>
<p>Ваша команда сайта</p>
</body>
</html>

