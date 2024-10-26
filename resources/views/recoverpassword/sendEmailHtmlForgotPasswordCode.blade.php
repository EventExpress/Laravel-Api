<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Recuperar Senha - EventExpress</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            color: #333;
        }
        h1 {
            font-size: 24px;
            color: #007BFF;
        }
        p {
            margin-bottom: 15px;
        }
        .code {
            font-weight: bold;
            font-size: 18px;
            color: #D9534F; /* Cor do código */
        }
        .footer {
            margin-top: 30px;
            font-style: italic;
        }
    </style>
</head>

<body>

<h1>Recuperação de Senha</h1>

<p>Prezado(a) {{$user->name}},</p>

<p>Esperamos que esta mensagem o encontre bem.</p>

<p>Para proceder com a recuperação da sua senha do EventExpress, solicitamos que utilize o código de verificação abaixo:</p>

<p class="code">{{ $code }}</p>

<p>Em atenção às normas de segurança, informamos que este código será válido apenas até as <strong>{{ $formattedTime }}</strong> do dia <strong>{{ $formattedDate }}</strong>. Caso o prazo tenha expirado, pedimos que solicite um novo código.</p>

<p class="footer">Agradecemos pela sua compreensão.</p>

<p class="footer">Atenciosamente,<br>Equipe de Desenvolvimento EventExpress</p>

</body>

</html>
