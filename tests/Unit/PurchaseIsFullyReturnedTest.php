<?php

namespace Tests\Unit;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PurchaseIsFullyReturnedTest extends TestCase
{
    public function test_it_uses_preloaded_items_without_querying_the_database(): void
    {
        $purchase = new Purchase;
        $purchase->setRelation('purchaseItems', new Collection([
            new PurchaseItem(['quantity' => 6]),
            new PurchaseItem(['quantity' => 4]),
        ]));

        $return = new PurchaseReturn;
        $return->setRelation('items', new Collection([
            new PurchaseReturnItem(['quantity' => 10]),
        ]));
        $purchase->setRelation('purchaseReturn', new Collection([$return]));

        self::assertTrue($purchase->is_fully_returned);
    }

    public function test_it_reports_partial_return_as_not_fully_returned(): void
    {
        $purchase = new Purchase;
        $purchase->setRelation('purchaseItems', new Collection([
            new PurchaseItem(['quantity' => 10]),
        ]));

        $return = new PurchaseReturn;
        $return->setRelation('items', new Collection([
            new PurchaseReturnItem(['quantity' => 3]),
        ]));
        $purchase->setRelation('purchaseReturn', new Collection([$return]));

        self::assertFalse($purchase->is_fully_returned);
    }
}
