<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: #ffffff;
            margin: 30px auto;
            padding: 20px 30px;
            border-radius: 8px;
            max-width: 600px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .content {
            margin-top: 20px;
            color: #333;
        }

        .content p {
            font-size: 15px;
            line-height: 1.6;
        }

        .content ul {
            padding-left: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 13px;
            color: #999;
        }

        .footer a {
            color: #007BFF;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Reçu de paiement - EPAL</h1>
        </div>
        <div class="content">
            <p>Bonjour <strong>{{ $userdata?->Nom }}&nbsp;{{ $userdata?->Prenom }}</strong>,</p>
            <p>Nous vous confirmons la réception de votre paiement pour la facture suivante :</p>

            <ul>
                <li><strong>N° :</strong> {{ $facturedata->facrfe }}</li>
                <li><strong>Date :</strong> {{ \Carbon\Carbon::parse($facturedata->facdat)->format('d-m-Y') }}</li>
                <li><strong>Montant :</strong> {{ number_format($facturedata->facttc, 2, ',', ' ') }} DA</li>
            </ul>

            <p>Vous trouverez en pièce jointe le reçu au format PDF.</p>

            <p>Merci pour votre confiance,</p>
            <p>L’équipe EPAL</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} EPAL - Tous droits réservés<br>
            <a href="mailto:support@epal.dz">support@epal.dz</a>
        </div>
    </div>
</body>

</html>
