<?php

namespace Tests\Unit;

use App\Exports\BaseExcelExport;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class BaseExcelExportStyleTest extends TestCase
{
    public function test_zebra_striping_uses_one_conditional_rule_for_the_whole_range(): void
    {
        $export = new class extends BaseExcelExport
        {
            public function makeTestSheet(): Worksheet
            {
                $sheet = $this->makeSheet('Test', true);
                $this->writeHeader($sheet, ['A', 'B']);

                for ($row = 2; $row <= 101; $row++) {
                    $this->writeRow($sheet, $row, [$row, 'value']);
                }

                $this->finalizeSheet($sheet, 2, 101);

                return $sheet;
            }

            protected function build(): void {}
        };

        $rules = $export->makeTestSheet()
            ->getStyle('A2:B101')
            ->getConditionalStyles();

        $this->assertCount(1, $rules);
        $this->assertSame(Conditional::CONDITION_EXPRESSION, $rules[0]->getConditionType());
        $this->assertSame(['MOD(ROW(),2)=0'], $rules[0]->getConditions());
    }
}
