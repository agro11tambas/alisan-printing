<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SaleListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceImageUploadTest extends TestCase
{
    public function test_invoice_image_upload_uses_cached_service_configuration(): void
    {
        config()->set('services.image_upload.token', 'configured-token');
        config()->set('services.image_upload.url', 'https://images.example.test/upload');

        Http::fake([
            'https://images.example.test/upload' => Http::response([
                'url' => 'https://images.example.test/invoice.jpg',
                'filename' => 'invoice.jpg',
            ]),
        ]);

        $request = Request::create('/erp/invoice/convert-to-image', 'POST', [
            'image' => 'data:image/jpeg;base64,dGVzdA==',
            'order_id' => 10,
            'order_number' => 'INV-001',
        ]);

        $response = (new SaleListController)->convertToImage($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://images.example.test/upload'
            && $request->hasHeader('X-Upload-Token', 'configured-token')
            && $request['invoice_number'] === 'INV-001'
        );
    }

    /**
     * Upload ini terjadi di tengah request user, jadi user ikut menunggu.
     * Timeout 30 detik membuat ERP terasa nge-buffer belasan detik kalau
     * server gambar tersendat, tanpa jejak apa pun di grafik CPU Hostinger.
     */
    public function test_invoice_upload_timeouts_stay_short_enough_for_a_user_request(): void
    {
        $this->assertLessThanOrEqual(
            15,
            config('services.image_upload.timeout'),
            'Timeout upload gambar tidak boleh lebih dari 15 detik.'
        );

        $this->assertLessThanOrEqual(
            5,
            config('services.image_upload.connect_timeout'),
            'connectTimeout harus pendek supaya masalah jaringan gagal cepat.'
        );
    }

    public function test_invoice_image_upload_reports_missing_configuration(): void
    {
        config()->set('services.image_upload.token', null);
        config()->set('services.image_upload.url', 'https://images.example.test/upload');

        $request = Request::create('/erp/invoice/convert-to-image', 'POST', [
            'image' => 'data:image/jpeg;base64,dGVzdA==',
            'order_id' => 10,
            'order_number' => 'INV-001',
        ]);

        $response = (new SaleListController)->convertToImage($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
        Http::assertNothingSent();
    }
}
