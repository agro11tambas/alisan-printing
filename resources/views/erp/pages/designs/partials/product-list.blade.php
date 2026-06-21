<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Preview / Upload</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($design->items as $item)
                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $item->product->name ?? '-' }}
                    </td>
                    <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit_name ?? '-' }}</td>
                    <td>
                        @php
                            $images = json_decode($item->preview_image ?? '[]', true);
                        @endphp

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if (!empty($images))
                                <button class="btn btn-sm btn-outline-info preview-btn"
                                    data-images='@json($images)'
                                    data-product="{{ $item->product->name ?? '-' }}"
                                    data-order_note="{{ $design->order->notes ?? '-' }}">
                                    <i class="feather-eye"></i> Preview
                                </button>
                            @endif

                            <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                data-order_note="{{ $design->order->notes ?? '-' }}" data-bs-toggle="modal"
                                data-bs-target="#uploadModal">
                                <i class="feather-upload"></i> Upload
                            </button>
                        </div>
                    </td>
                    <td>
                        {{ $item->note ?? '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
