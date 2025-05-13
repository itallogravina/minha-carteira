<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Aplicação Logada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <h1>Bem-vindo!</h1>
    <p>Você está logado com sucesso na aplicação.</p>

    <?php if (isset($userName) && $userName): ?>
        <p><strong>Usuário:</strong> <?= esc($userName) ?></p>
    <?php endif; ?>

    <?php if (isset($userEmail) && $userEmail): ?>
        <p><strong>Email:</strong> <?= esc($userEmail) ?></p>
    <?php endif; ?>

    <hr>
    <p><a href="/auth/logout">Sair (Logout)</a></p>
</div>

</body>
</html>