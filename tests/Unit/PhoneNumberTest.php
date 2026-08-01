<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('localNumberCases')]
    public function test_it_formats_indonesian_numbers_for_print(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::toLocalIndonesian($input));
    }

    public static function localNumberCases(): array
    {
        return [
            'country code' => ['6281266064331', '081266064331'],
            'country code with plus' => ['+62 812-6606-4331', '081266064331'],
            'subscriber number' => ['81266064331', '081266064331'],
            'local number' => ['081266064331', '081266064331'],
            'empty number' => [null, null],
        ];
    }
}
