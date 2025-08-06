<?php

namespace App\Services;

use App\Models\Proforma\Tarif;
use Carbon\Carbon;

class ProformaTarifCalculator
{
    private function getTarif($prscod)
    {
        return Tarif::where('prscod', $prscod)->value('prspun') ?? 0;
    }

    private function getLibelle($prscod)
    {
        return Tarif::where('prscod', $prscod)->value('prslib') ?? '';
    }

    private function calculerTranche(array $tranches, int $nbJours, int $quantite, string $type = '')
    {
        $details = [];
        $total = 0;

        // Si la durée dépasse la dernière borne max, appliquer uniquement le tarif de la dernière tranche
        $derniereTranche = end($tranches);

        if ($nbJours >= $derniereTranche['min']) {
            $tarif = $derniereTranche['tarif'];
            $montant = $nbJours * $tarif * $quantite;

            $details[] = [
                'prscod' => $derniereTranche['code'],
                'libelle' => $this->getLibelle($derniereTranche['code']),
                'duree' => $nbJours,
                'quantite' => $quantite,
                'pu' => $tarif,
                'montant' => $montant,
               
            ];

            $total = $montant;
        } else {
            $joursRestants = $nbJours;

            foreach ($tranches as $tranche) {
                if ($joursRestants <= 0 || $tranche['min'] > $nbJours) break;

                $borneMin = $tranche['min'];
                $borneMax = min($tranche['max'], $nbJours);

                $joursDansTranche = max(0, $borneMax - $borneMin + 1);
                $joursDansTranche = min($joursDansTranche, $joursRestants);

                if ($joursDansTranche > 0) {
                    $tarif = $tranche['tarif'];
                    $montant = $joursDansTranche * $tarif * $quantite;

                    $details[] = [
                        'prscod' => $tranche['code'],
                        'libelle' => $this->getLibelle($tranche['code']),
                        'duree' => $nbJours,
                        'quantite' => $quantite,
                        'pu' => $tarif,
                        'montant' => $montant,
                    ];

                    $total += $montant;
                    $joursRestants -= $joursDansTranche;
                }
            }
        }

        return ['total' => $total, 'details' => $details];
    }

    public function calculerFraisSejour($dateDebut, $dateFin, $nbc20P, $nbc40P)
    {
        $debut = Carbon::createFromFormat('Y-m-d', $dateDebut);
        $fin = Carbon::createFromFormat('Y-m-d', $dateFin);
        $nbJours = $debut->diffInDays($fin) + 1;

        $tranches20P = [
            ['min' => 1, 'max' => 3, 'code' => 'TRA20P', 'tarif' => $this->getTarif('TRA20P')],
            ['min' => 4, 'max' => 15, 'code' => 'S1J20P', 'tarif' => $this->getTarif('S1J20P')],
            ['min' => 16, 'max' => 25, 'code' => 'S2J20P', 'tarif' => $this->getTarif('S2J20P')],
            ['min' => 26, 'max' => 35, 'code' => 'S3J20P', 'tarif' => $this->getTarif('S3J20P')],
            ['min' => 36, 'max' => PHP_INT_MAX, 'code' => 'S4J20P', 'tarif' => $this->getTarif('S4J20P')],
        ];

        $tranches40P = [
            ['min' => 1, 'max' => 3, 'code' => 'TRA40P', 'tarif' => $this->getTarif('TRA40P')],
            ['min' => 4, 'max' => 15, 'code' => 'S1J40P', 'tarif' => $this->getTarif('S1J40P')],
            ['min' => 16, 'max' => 25, 'code' => 'S2J40P', 'tarif' => $this->getTarif('S2J40P')],
            ['min' => 26, 'max' => 35, 'code' => 'S3J40P', 'tarif' => $this->getTarif('S3J40P')],
            ['min' => 36, 'max' => PHP_INT_MAX, 'code' => 'S4J40P', 'tarif' => $this->getTarif('S4J40P')],
        ];

        $total = 0;
        $details = [];

        if ($nbc20P > 0) {
            $r = $this->calculerTranche($tranches20P, $nbJours, $nbc20P, '20P');
            $total += $r['total'];
            $details = array_merge($details, $r['details']);
        }

        if ($nbc40P > 0) {
            $r = $this->calculerTranche($tranches40P, $nbJours, $nbc40P, '40P');
            $total += $r['total'];
            $details = array_merge($details, $r['details']);
        }

        return [
            'dateDebut' => $debut->toDateString(),
            'dateFin' => $fin->toDateString(),
            'nb_jours' => $nbJours,
            'total' => $total,
            'details' => $details,
        ];
    }

    public function calculerGardiennage($dateDebut, $dateFin, $nbc20P, $nbc40P)
    {
         try {
           $debut = Carbon::createFromFormat('Y-m-d', $dateDebut);
            $fin = Carbon::createFromFormat('Y-m-d', $dateFin);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Format de date invalide. Format attendu : YYYY-MM-DD');
        }
        
        $nbJours = $debut->diffInDays($fin) + 1;
        $nbConteneurs = $nbc20P + $nbc40P;

        $tranches = [
            ['min' => 1,  'max' => 10,  'code' => 'GD0110', 'tarif' => $this->getTarif('GD0110')],
            ['min' => 11, 'max' => 21,  'code' => 'GD1121', 'tarif' => $this->getTarif('GD1121')],
            ['min' => 22, 'max' => PHP_INT_MAX, 'code' => 'GDS021', 'tarif' => $this->getTarif('GDS021')],
        ];

        return $this->calculerTranche($tranches, $nbJours, $nbConteneurs, 'GARD');
    }

    public function calculerAutres($nbc20P, $nbc40P)
    {
        $details = [];
        $total = 0;
        $nbCamions = $nbc20P + $nbc40P;

        $tarifAccesCamion = $this->getTarif('ACCCAM');
        $tarifAcconage20P = $this->getTarif('AC20PU');
        $tarifAcconage40P = $this->getTarif('AC40PU');
        $tarifCDC20 = $this->getTarif('CDC20A');
        $tarifCDC40 = $this->getTarif('CDC40A');

        //ACCES CAMION 
        $details[] = [
            'prscod' => 'ACCCAM',
            'libelle' => $this->getLibelle('ACCCAM'),
            'quantite' => $nbCamions,
            'duree'=> 1,
            'pu' => $tarifAccesCamion,
            'montant' => $nbCamions * $tarifAccesCamion,
        ];

        //ACCONAGE
        $details[] = [
            'prscod' => 'AC20PU',
            'libelle' => $this->getLibelle('AC20PU'),
            'quantite' => $nbc20P,
            'duree'=> 1,
            'pu' => $tarifAcconage20P,
            'montant' => $nbc20P * $tarifAcconage20P,
        ];
        $details[] = [
            'prscod' => 'AC40PU',
            'libelle' => $this->getLibelle('AC40PU'),
            'quantite' => $nbc40P,
            'duree'=> 1,
            'pu' => $tarifAcconage40P,
            'montant' => $nbc40P * $tarifAcconage40P,
        ];

        //chargement /déchargement
        $details[] = [
            'prscod' => 'CDC20A',
            'libelle' => $this->getLibelle('CDC20A'),
            'quantite' => $nbc20P,
            'duree'=> 1,
            'pu' => $tarifCDC20,
            'montant' => $nbc20P * $tarifCDC20,
        ];
        $details[] = [
            'prscod' => 'CDC40A',
            'libelle' => $this->getLibelle('CDC40A'),
            'quantite' => $nbc40P,
            'duree'=> 1,
            'pu' => $tarifCDC40,
            'montant' => $nbc40P * $tarifCDC40,
        ];

        $total += $nbCamions * $tarifAccesCamion;
        $total += $nbc20P * $tarifAcconage20P + $nbc40P * $tarifAcconage40P;
        $total += $nbc20P * $tarifCDC20 + $nbc40P * $tarifCDC40;

        return ['total' => $total, 'details' => $details];
    }

    public function calculerScanner($nbc20P, $nbc40P)
    {   
        $details = [];
        $total = 0;

        $tarifScanner20P = $this->getTarif('CRX20P');
        $tarifScanner40P = $this->getTarif('CRX40P');

        if($nbc20P>0)
        {
            $details[] = [
                'prscod' => 'CRX20P',
                'libelle' => $this->getLibelle('CRX20P'),
                'quantite' => $nbc20P,
                'duree'=> 1,
                'pu' => $tarifScanner20P,
                'montant' => $nbc20P * $tarifScanner20P,
            ];
        }
        if($nbc40P>0)
        {
            $details[] = [
                'prscod' => 'CRX40P',
                'libelle' => $this->getLibelle('CRX40P'),
                'quantite' => $nbc40P,
                'duree'=> 1,
                'pu' => $tarifScanner40P,
                'montant' => $nbc40P * $tarifScanner40P,
            ];
        }

        $total += $nbc20P * $tarifScanner20P+$nbc40P * $tarifScanner40P;
        return ['total' => $total, 'details' => $details];
    }

    public function calculerFacture($dateDebut, $dateFin, $nbc20P, $nbc40P, $scan)
    {
        //init
        $sejour=[];
        $gardiennage=[];
        $autres=[];
        $scanner=[];

        //call
        $sejour = $this->calculerFraisSejour($dateDebut, $dateFin, $nbc20P, $nbc40P);
        $gardiennage = $this->calculerGardiennage($dateDebut, $dateFin, $nbc20P, $nbc40P);
        $autres = $this->calculerAutres($nbc20P, $nbc40P);

        if ($scan === true) {
            $scanner = $this->calculerScanner($nbc20P, $nbc40P);
            $totalHT = $sejour['total'] + $gardiennage['total'] + $autres['total'] + $scanner['total'];
            $details = array_merge($sejour['details'], $gardiennage['details'], $autres['details'], $scanner['details']);
        } else {
            $totalHT = $sejour['total'] + $gardiennage['total'] + $autres['total'];
            $details = array_merge($sejour['details'], $gardiennage['details'], $autres['details']);
        }

        return [
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'nbc20P' => $nbc20P,
            'nbc40P' => $nbc40P,
            'totalHT' => $totalHT,
            'details' => $details,
        ];
    }
}