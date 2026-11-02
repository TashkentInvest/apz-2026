<?php

namespace App\Enums;

/**
 * Shartnoma holati (KAMCHILIKLAR B4).
 *
 * Bazadagi contract_status turli yozilishda keladi: "Bekor qilingan", "Бекор қилинган",
 * "Yakunlangan", "Якунланган", "Амалдаги" yoki bo'sh. Holat shu yerda bir xil qoida bilan aniqlanadi.
 */
enum ContractStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    private const CANCELLED_MARKERS = ['bekor', 'бекор', 'cancel'];
    private const COMPLETED_MARKERS = ['yakun', 'якун', 'tugal', 'complete'];

    public static function fromRaw(?string $raw): self
    {
        $value = mb_strtolower(trim((string) $raw));
        if ($value === '') {
            return self::InProgress;
        }

        foreach (self::CANCELLED_MARKERS as $marker) {
            if (str_contains($value, $marker)) {
                return self::Cancelled;
            }
        }

        foreach (self::COMPLETED_MARKERS as $marker) {
            if (str_contains($value, $marker)) {
                return self::Completed;
            }
        }

        return self::InProgress;
    }

    /** Interfeysda ko'rsatiladigan va formadan bazaga yoziladigan qiymat. */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Амалдаги',
            self::Completed => 'Якунланган',
            self::Cancelled => 'Бекор қилинган',
        };
    }

    /** fromRaw() bilan bir xil natija beradigan MySQL sharti. $column ishonchli ichki qiymat bo'lishi kerak. */
    public function sqlCondition(string $column): string
    {
        $expr = "LOWER(TRIM(COALESCE({$column}, '')))";
        $like = fn (array $markers) => '(' . implode(' OR ', array_map(fn ($m) => "{$expr} LIKE '%{$m}%'", $markers)) . ')';
        $cancelled = $like(self::CANCELLED_MARKERS);
        $completed = $like(self::COMPLETED_MARKERS);

        return match ($this) {
            self::Cancelled => $cancelled,
            self::Completed => "(NOT {$cancelled} AND {$completed})",
            self::InProgress => "(NOT {$cancelled} AND NOT {$completed})",
        };
    }
}
