<div class="table-responsive">
    <table class="table bg-transparent table-sm table-bordered mb-0 align-middle">
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
                    <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $images = json_decode($item->preview_image ?? '[]', true);
                        @endphp

                        @if (!empty($images))
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @foreach ($images as $img)
                                    <a href="{{ asset('uploads/designs/' . $img) }}"
                                        data-lightbox="design-{{ $design->id }}"
                                        data-title="{{ $item->product->name ?? 'Preview Image' }}" class="d-inline-block">
                                        <img src="{{ asset('uploads/designs/' . $img) }}" width="80" height="60"
                                            style="border-radius:8px;object-fit:cover;object-position:center;border:1px solid #ddd;">
                                    </a>
                                @endforeach

                                <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                    data-note="{{ $item->note ?? '' }}" data-bs-toggle="modal"
                                    data-bs-target="#uploadModal">
                                    <i class="feather-upload"></i> Upload
                                </button>
                            </div>
                        @else
                            <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="feather-upload"></i> Upload
                            </button>
                        @endif
                    </td>

                    <td>
                        {{ $item->note ?? '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
