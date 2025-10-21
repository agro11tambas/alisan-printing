<div class="table-responsive">
    <table class="table bg-transparent table-sm table-bordered mb-0 align-middle">
        <thead>
            <tr class="text-center">
                <th style="width:30%">Product</th>
                {{-- <th style="width:20%">Verified</th> --}}
                <th style="width:30%">Preview / Upload</th>
                <th style="width:20%">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($design->items as $item)
                <tr>
                    {{-- 🧱 PRODUCT --}}
                    <td class="fw-semibold text-dark">
                        {{ $item->product->name ?? '-' }}
                    </td>

                    {{-- 🟢 VERIFICATION STATUS --}}
                    {{-- <td class="text-center">
                        @if ($item->verification_status === 'approved')
                            <span class="badge bg-soft-success text-success">Verified</span>
                        @elseif($item->verification_status === 'rejected')
                            <span class="badge bg-soft-danger text-danger">Rejected</span>
                        @else
                            <span class="badge bg-soft-warning text-warning">Pending</span>
                        @endif
                    </td> --}}

                    {{-- 🖼️ PREVIEW & UPLOAD --}}
                    <td>
                        @if ($item->preview_image)
                            {{-- Jika sudah ada gambar --}}
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
                                    data-bs-toggle="modal" data-bs-target="#uploadModal">
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
