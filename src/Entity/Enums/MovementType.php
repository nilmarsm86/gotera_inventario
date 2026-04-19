<?php

namespace App\Entity\Enums;

use App\Entity\Traits\EnumsTrait;

enum MovementType: string
{
    use EnumsTrait;

    case Null = '';
    case Entrance = '1';
    case Departure = '2';
    case AdjustmentEntrance = '3';
    case AdjustmentDeparture = '4';

    public const array CHOICES = [self::Entrance, self::Departure, self::AdjustmentEntrance, self::AdjustmentDeparture];

    public static function getLabelFrom(\BackedEnum|string $enum): string
    {
        if (is_string($enum)) {
            $enum = self::from($enum);
        }

        return match ($enum) {
            self::Entrance => 'Entrada',// translate
            self::Departure => 'Salida',// translate
            self::AdjustmentEntrance => 'Ajuste Entrada',// translate
            self::AdjustmentDeparture => 'Ajuste Salida',// translate
            default => '-Seleccione-',// translate
        };
    }
}
