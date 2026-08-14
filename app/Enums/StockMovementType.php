<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
        };
    }

    /**
     * The sign this movement type contributes to the derived stock balance.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Entrada => 1,
            self::Salida => -1,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
