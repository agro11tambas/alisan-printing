<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DesignItemController;
use App\Models\CustomerDesign;
use App\Models\Customers;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Modul Design bisa memakai ulang design yang sudah diunggah customer:
 * operator memilih satu dari katalog, bukan upload ulang.
 *
 * Yang dijaga di sini adalah aturan yang tidak boleh longgar — satu design
 * item hanya memakai satu design, klien hanya mengirim id design + indeks
 * gambar (path file selalu dibaca ulang dari database), dan design milik
 * customer lain harus ditolak.
 */
class CustomerDesignAttachTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('design_number');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('design_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_id');
            $table->text('preview_image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_designs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('title');
            $table->text('notes')->nullable();
            $table->text('images')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach (['customer_designs', 'design_items', 'designs', 'orders', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function makeDesignItem(Customers $customer): DesignItem
    {
        $order = Order::forceCreate(['customer_id' => $customer->id]);
        $design = Design::forceCreate(['order_id' => $order->id, 'design_number' => 'DSG-'.$order->id]);

        return DesignItem::forceCreate(['design_id' => $design->id]);
    }

    private function attach(int $designItemId, array $payload)
    {
        return (new DesignItemController())->attachCustomerDesign(
            Request::create('/', 'POST', $payload),
            $designItemId
        );
    }

    public function test_design_terpasang_dengan_path_dari_database(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $item = $this->makeDesignItem($customer);

        $catalog = CustomerDesign::create([
            'customer_id' => $customer->id,
            'title' => 'Logo Depan',
            'images' => [
                ['file' => 'uploads/customer-designs/a.png', 'note' => 'depan'],
                ['file' => 'uploads/customer-designs/b.png', 'note' => ''],
            ],
        ]);

        $response = $this->attach($item->id, [
            'design_id' => $catalog->id,
            'index' => 0,
            'note' => 'catatan operator',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            ['file' => 'uploads/customer-designs/a.png', 'note' => 'catatan operator'],
        ], json_decode($item->fresh()->preview_image, true));
    }

    public function test_catatan_kosong_jatuh_ke_judul_design(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $item = $this->makeDesignItem($customer);

        $catalog = CustomerDesign::create([
            'customer_id' => $customer->id,
            'title' => 'Logo Depan',
            'images' => [['file' => 'uploads/customer-designs/b.png', 'note' => '']],
        ]);

        $this->attach($item->id, ['design_id' => $catalog->id, 'index' => 0]);

        $images = json_decode($item->fresh()->preview_image, true);

        $this->assertSame('Logo Depan', $images[0]['note']);
    }

    public function test_path_file_kiriman_klien_diabaikan(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $item = $this->makeDesignItem($customer);

        $catalog = CustomerDesign::create([
            'customer_id' => $customer->id,
            'title' => 'Logo Depan',
            'images' => [['file' => 'uploads/customer-designs/a.png', 'note' => '']],
        ]);

        $this->attach($item->id, [
            'design_id' => $catalog->id,
            'index' => 0,
            'file' => '../../.env',
        ]);

        $images = json_decode($item->fresh()->preview_image, true);

        $this->assertSame('uploads/customer-designs/a.png', $images[0]['file']);
    }

    public function test_design_milik_customer_lain_ditolak(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $lain = Customers::create(['name' => 'Toko Lain']);
        $item = $this->makeDesignItem($customer);

        $milikLain = CustomerDesign::create([
            'customer_id' => $lain->id,
            'title' => 'Punya Orang',
            'images' => [['file' => 'uploads/customer-designs/rahasia.png', 'note' => '']],
        ]);

        $response = $this->attach($item->id, ['design_id' => $milikLain->id, 'index' => 0]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertNull($item->fresh()->preview_image);
    }

    public function test_indeks_gambar_di_luar_jangkauan_ditolak(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $item = $this->makeDesignItem($customer);

        $catalog = CustomerDesign::create([
            'customer_id' => $customer->id,
            'title' => 'Logo Depan',
            'images' => [['file' => 'uploads/customer-designs/a.png', 'note' => '']],
        ]);

        $response = $this->attach($item->id, ['design_id' => $catalog->id, 'index' => 5]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertNull($item->fresh()->preview_image);
    }

    public function test_design_item_hanya_menyimpan_satu_design(): void
    {
        $customer = Customers::create(['name' => 'Toko Maju']);
        $item = $this->makeDesignItem($customer);
        $item->forceFill([
            'preview_image' => json_encode([['file' => 'uploads/designs/lama.png', 'note' => 'preview lama']]),
        ])->save();

        $catalog = CustomerDesign::create([
            'customer_id' => $customer->id,
            'title' => 'Logo Depan',
            'images' => [
                ['file' => 'uploads/customer-designs/a.png', 'note' => 'a'],
                ['file' => 'uploads/customer-designs/b.png', 'note' => 'b'],
            ],
        ]);

        $this->attach($item->id, ['design_id' => $catalog->id, 'index' => 0]);

        $images = json_decode($item->fresh()->preview_image, true);
        $this->assertCount(1, $images);
        $this->assertSame('uploads/customer-designs/a.png', $images[0]['file']);

        // Pilihan berikutnya menggantikan, bukan menumpuk.
        $this->attach($item->id, ['design_id' => $catalog->id, 'index' => 1]);

        $images = json_decode($item->fresh()->preview_image, true);
        $this->assertCount(1, $images);
        $this->assertSame('uploads/customer-designs/b.png', $images[0]['file']);
    }
}
