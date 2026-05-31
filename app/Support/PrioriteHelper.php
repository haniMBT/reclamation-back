<?php

namespace App\Support;

class PrioriteHelper
{
    public const NORMAL = 'normal';
    public const IMPORTANT = 'important';
    public const URGENT = 'urgent';
    public const CRITIQUE = 'critique';

    public const DEFAULT = self::NORMAL;

    /**
     * Liste des niveaux valides avec leur poids et couleur.
     * Poids: plus élevé = plus prioritaire.
     */
    public const LEVELS = [
        self::NORMAL    => ['weight' => 1, 'label' => 'Normal',    'color' => 'blue-grey'],
        self::IMPORTANT => ['weight' => 2, 'label' => 'Important', 'color' => 'amber'],
        self::URGENT    => ['weight' => 3, 'label' => 'Urgent',    'color' => 'orange'],
        self::CRITIQUE  => ['weight' => 4, 'label' => 'Critique',  'color' => 'red'],
    ];

    public static function values(): array
    {
        return array_keys(self::LEVELS);
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && array_key_exists($value, self::LEVELS);
    }

    public static function normalize(?string $value): string
    {
        return self::isValid($value) ? $value : self::DEFAULT;
    }

    public static function weight(?string $value): int
    {
        $key = self::normalize($value);
        return self::LEVELS[$key]['weight'];
    }

    /**
     * Retourne la liste pour les selects côté UI.
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::LEVELS as $value => $meta) {
            $out[] = [
                'value'  => $value,
                'label'  => $meta['label'],
                'weight' => $meta['weight'],
                'color'  => $meta['color'],
            ];
        }
        return $out;
    }
}
