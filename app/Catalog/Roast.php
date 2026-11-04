<?php

namespace App\Catalog;

/**
 * 咖啡豆的烘焙度，由淺到深。
 */
enum Roast: string
{
    case Light = '淺焙';
    case MediumLight = '淺中焙';
    case Medium = '中焙';
    case MediumDark = '中深焙';
    case Dark = '深焙';

    /**
     * 由淺到深的級數，1 到 5。
     */
    public function level(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    public function latin(): string
    {
        return match ($this) {
            self::Light => 'LIGHT ROAST',
            self::MediumLight => 'MEDIUM LIGHT ROAST',
            self::Medium => 'MEDIUM ROAST',
            self::MediumDark => 'MEDIUM DARK ROAST',
            self::Dark => 'DARK ROAST',
        };
    }
}
