<div class="table-responsive design-product-wrapper">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle design-product-table">
        <colgroup>
            <col class="col-product">
            <col class="col-mode">
            <col class="col-qty">
            <col class="col-action">
            <col class="col-note">
        </colgroup>
        <thead>
            <tr>
                <th>Product</th>
                <th>Mode</th>
                <th>Quantity</th>
                <th>Preview / Upload</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($design->items as $item)
                @php
                    $unitName = $item->unit_name ?: $item->orderItem?->unit_name ?: '-';
                    $modeName =
                        $item->orderItem?->priceMode?->name ?:
                        ($item->orderItem?->mode ? ucfirst(str_replace('-', ' ', $item->orderItem->mode)) : null);
                @endphp
                <tr>
                    <td class="fw-semibold text-dark" title="{{ $item->product->name ?? '-' }}">
                        {{ $item->product->name ?? '-' }}
                        <small class="text-muted d-block fw-normal">Unit: {{ $unitName }}</small>
                    </td>
                    <td>
                        @if ($modeName)
                            <span class="badge bg-soft-primary text-primary">{{ $modeName }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $unitName }}</td>
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
