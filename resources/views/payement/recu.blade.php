<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>e-paiement | EPAL</title>
</head>

<body>
  <div>
    <img src="{{ public_path('dist/img/img055.png') }}" style="width:600px; height:150px;">
  </div>

  <h1 style="text-align: center;">
    <span>Re&ccedil;u de Paiement&nbsp; en Ligne<br /></span>
  </h1>

  <h2 style="text-align: left;">
    <span>
      <span style="text-decoration: underline;">Re&ccedil;u N&deg;:</span>
      <strong>{{ $order->recuId ?? '' }}</strong>
    </span>
  </h2>

  <table style="width: 100%; border-collapse: collapse; border-style: hidden;">
    <tbody>
      <tr>
        <td style="width: 25%;">Nom Client:</td>
        <td style="width: 38.2743%;">{{ $facture->trsnom ?? '' }}</td>
        <td style="width: 36.7257%;">Compte N&deg;: {{ $order->trscod ?? '' }}</td>
      </tr>
      <tr>
        <td>FACTURE N°:</td>
        <td>{{ $order->facrfe ?? '' }}</td> 
        <td>Type Facture: {{ $order->domcod ?? '' }}</td>
      </tr>
      <tr>
        <td>Etablie le:</td>
        <td>
          {{ isset($facture->facdat) ? date_format(date_create($facture->facdat), 'd-m-Y') : '' }}
        </td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <p><em><strong>Informations sur la Transaction:</strong></em></p>
  <p>Date et Heure de la Transaction: {{ $dateValable ?? '' }}</p>
  <p>Mode de Paiement: CARTE CIB/Edhahabia</p>
  <p>N&deg; Ordre: {{ $order->OrderNumber ?? '' }}</p>
  <p>N&deg; Transaction: {{ $order->orderid ?? '' }}</p>
  <p>N&deg; Autorisation: {{ $order->approvalCode ?? '' }}</p>
  <p>Cardholder Name: {{ $order->cardholderName ?? '' }}</p>
  <p>Montant Pay&eacute; (TTC): 
    {{ isset($order->Amount) ? number_format($order->Amount/100, 2, ",", ".") . ' DA' : '' }}
  </p>

  <br>
  <p style="font-weight: bold;">
    En cas de problème de paiement, veuillez contacter la SATIM sur le numéro vert : 3020
    <img src="{{ public_path('dist/img/satim.png') }}" style="float:right;" alt="SATIM">
  </p>
</body>
</html>
