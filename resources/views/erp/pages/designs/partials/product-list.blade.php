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
                    <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                    <td>
                        @php
                            $images = json_decode($item->preview_image ?? '[]', true);
                        @endphp

                        @if (!empty($images))
                            <div class="d-flex flex-wrap align-items-start gap-3">
                                @foreach ($images as $img)
                                    <div class="text-center">
                                        <a href="#" class="img-viewer" data-src="{{ asset($img['file']) }}"
                                            data-note="{{ $img['note'] ?? '' }}">
                                            <img src="{{ asset($img['file']) }}" width="100" height="80"
                                                style="border-radius:8px;object-fit:cover;border:1px solid #ddd;">
                                        </a>

                                        <p class="small text-muted mt-1">{{ $img['note'] ?? '-' }}</p>
                                    </div>
                                @endforeach

                                <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                    data-bs-toggle="modal" data-bs-target="#uploadModal">
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
