<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .code {
            background-color: #e9ecef;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 3px;
            margin: 20px 0;
            border-radius: 5px;
            color: #007bff;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Réinitialisation de mot de passe</h1>
        <p>EPAL - Entreprise Portuaire d'Alger</p>
    </div>

    <div class="content">
        <p>Bonjour {{ $prenom }},</p>
        
        <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte EPAL.</p>
        
        <p>Voici votre code de vérification :</p>
        
        <div class="code">{{ $code }}</div>
        
        <div class="warning">
            <strong>Important :</strong> Ce code est valable pendant 15 minutes seulement. 
            Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.
        </div>
        
        <p>Pour des raisons de sécurité, ne partagez jamais ce code avec personne.</p>
        
        <p>Cordialement,<br>
        L'équipe EPAL</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Entreprise Portuaire d'Alger - Tous droits réservés</p>
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
    </div>
</body>
</html>