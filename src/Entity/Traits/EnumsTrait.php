<?php

namespace App\Entity\Traits;

trait EnumsTrait
{
    /**
     * Get all possible values.
     *
     * @return array<mixed>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all values for select.
     *
     * @return array<mixed>
     */
    public static function forSelect(): array
    {
        return array_combine(
            array_column(self::cases(), 'name'),// option label
            array_column(self::cases(), 'value')// option value
        );
    }

    /**
     * Function fot EnumType.
     */
    public static function getValue(): callable
    {
        //        return static function (): \Closure {
        //            return fn (?\BackedEnum $choice): ?string => (null === $choice) ? null : (string) $choice->value;
        //        };
        return fn (?\BackedEnum $choice): ?string => null === $choice ? null : (string) $choice->value;
    }

    /**
     * Label tag for EnumType.
     */
    public static function getLabel(): callable
    {
        return fn (\BackedEnum $choice) => self::getLabelFrom($choice);
    }
}
