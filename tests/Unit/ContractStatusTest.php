<?php

namespace Tests\Unit;

use App\Enums\ContractStatus;
use App\Models\ApzContract;
use PHPUnit\Framework\TestCase;

/**
 * Bazada uchraydigan holat yozilishlari bitta holatga keltiriladi (KAMCHILIKLAR B4).
 */
class ContractStatusTest extends TestCase
{
    /**
     * @dataProvider rawStatuses
     */
    public function test_raw_status_is_normalized(?string $raw, ContractStatus $expected): void
    {
        $this->assertSame($expected, ContractStatus::fromRaw($raw));
    }

    public static function rawStatuses(): array
    {
        return [
            'null' => [null, ContractStatus::InProgress],
            'empty' => ['  ', ContractStatus::InProgress],
            'amalda cyr' => ['Амалдаги', ContractStatus::InProgress],
            'bekor lat' => ['Bekor qilingan', ContractStatus::Cancelled],
            'bekor cyr' => ['Бекор қилинган', ContractStatus::Cancelled],
            'yakun lat' => ['Yakunlangan', ContractStatus::Completed],
            'yakun cyr' => ['Якунланган', ContractStatus::Completed],
            'upper' => ['YAKUNLANGAN ', ContractStatus::Completed],
        ];
    }

    public function test_form_value_round_trips_through_label(): void
    {
        foreach (ContractStatus::cases() as $status) {
            $this->assertSame($status, ContractStatus::fromRaw($status->label()));
        }
    }

    public function test_model_helpers_use_normalized_status(): void
    {
        $completed = new ApzContract(['contract_status' => 'Yakunlangan']);
        $this->assertTrue($completed->isCompleted());
        $this->assertFalse($completed->isActive());

        $cancelled = new ApzContract(['contract_status' => 'Бекор қилинган']);
        $this->assertTrue($cancelled->isCancelled());
        $this->assertFalse($cancelled->isActive());

        $this->assertTrue((new ApzContract(['contract_status' => null]))->isActive());
    }

    public function test_sql_conditions_are_mutually_exclusive(): void
    {
        $cancelled = ContractStatus::Cancelled->sqlCondition('c.contract_status');

        $this->assertStringContainsString("LIKE '%бекор%'", $cancelled);
        $this->assertStringContainsString("NOT {$cancelled}", ContractStatus::Completed->sqlCondition('c.contract_status'));
        $this->assertStringContainsString("NOT {$cancelled}", ContractStatus::InProgress->sqlCondition('c.contract_status'));
    }
}
