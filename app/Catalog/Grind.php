<?php

namespace App\Catalog;

/**
 * 研磨方式：下單後才依顧客選擇研磨，不是規格，不影響價格與庫存（ADR 0001）。
 */
enum Grind: string
{
    case WholeBean = 'whole_bean';
    case PourOver = 'pour_over';
    case Espresso = 'espresso';
    case FrenchPress = 'french_press';

    public function label(): string
    {
        return match ($this) {
            self::WholeBean => '原豆',
            self::PourOver => '手沖',
            self::Espresso => '義式',
            self::FrenchPress => '法式濾壓',
        };
    }

    /**
     * 選項按鈕上的研磨粗細說明。
     */
    public function hint(): string
    {
        return match ($this) {
            self::WholeBean => '不研磨',
            self::PourOver => '中細研磨',
            self::Espresso => '極細研磨',
            self::FrenchPress => '粗研磨',
        };
    }
}
