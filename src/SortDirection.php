<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public static function normalize(self|string $direction): self
    {
        if ($direction instanceof self) {
            return $direction;
        }

        return self::tryFrom(strtolower($direction)) ?? self::Asc;
    }

    public function toggle(): self
    {
        return $this === self::Asc ? self::Desc : self::Asc;
    }
}
