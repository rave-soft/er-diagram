<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Schema;

/**
 * @skiptests
 */
enum FilterLevelEnum: int
{
    case FirstLevel = 1;
    case SecondLevel = 2;
    case Regular = 100;

    public static function tryBetter(self $filterLevel, int $level): self
    {
        if ($filterLevel->value < $level) {
            return $filterLevel;
        }
        return self::tryFrom($level) ?? $filterLevel;
    }
}
