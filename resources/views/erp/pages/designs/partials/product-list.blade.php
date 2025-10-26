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
                        @if ($item->preview_image)
                            <div class="d-flex gap-5 align-items-center">
                                <a href="{{ asset('uploads/designs/' . $item->preview_image) }}"
                                    data-lightbox="design-{{ $design->id }}"
                                    data-title="{{ $item->product->name ?? 'Preview Image' }}"
                                    class="mb-1 d-inline-block">
                                    <img src="{{ asset('uploads/designs/' . $item->preview_image) }}" width="80"
                                        height="60"
                                        style="border-radius: 8px; object-fit: cover; object-position: center;"
                                        alt="Design Preview">
                                </a>
                                <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                    data-preview="{{ $item->preview_image ? asset('uploads/designs/' . $item->preview_image) : '' }}"
                                    data-note="{{ $item->note ?? '' }}" data-bs-toggle="modal"
                                    data-bs-target="#uploadModal">
                                    <i class="feather-upload"></i> Upload
                                </button>
                            </div>
                        @else
                            {{-- Belum ada gambar --}}
                            <button class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $item->id }}"
                                data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="feather-upload"></i> Upload
                            </button>
                        @endif
                    </td>

                    {{-- 🗒️ NOTE --}}
                    <td>
                        {{ $item->note ?? '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
