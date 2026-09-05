@php
    $designs = $customer->designs;
@endphp

@if ($designs->isEmpty())
    <div class="text-muted small py-2">
        Belum ada design untuk customer ini.
    </div>
@else
    <div class="table-responsive customer-design-wrapper">
        <table class="table bg-transparent table-small table-bordered mb-0 align-middle customer-design-table">
            <colgroup>
                <col class="col-title">
                <col class="col-image">
                <col class="col-note">
                <col class="col-action">
            </colgroup>
            <thead>
                <tr>
                    <th>Judul Design</th>
                    <th>Design</th>
                    <th>Catatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($designs as $design)
                    @php
                        $images = $design->imageList();
                    @endphp
                    <tr>
                        <td class="fw-semibold text-dark">
                            {{ $design->title }}
                            <small class="text-muted d-block fw-normal">
                                {{ $design->created_at?->format('d M Y H:i') ?? '-' }}
                            </small>
                        </td>
                        <td>
                            @if (empty($images))
                                <span class="text-muted small">Tidak ada gambar</span>
                            @else
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @foreach (array_slice($images, 0, 4) as $image)
                                        <img src="{{ asset($image['file']) }}" class="cd-thumb cd-view"
                                            data-id="{{ $design->id }}" data-title="{{ $design->title }}"
                                            data-customer="{{ $customer->name }}"
                                            data-images='@json($images)'
                                            title="{{ $image['note'] ?: $design->title }}">
                                    @endforeach
                                    @if (count($images) > 4)
                                        <span class="badge bg-soft-dark text-dark">+{{ count($images) - 4 }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted small">{{ $design->notes ?: '-' }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary cd-edit"
                                    data-id="{{ $design->id }}" title="Edit design" aria-label="Edit design">
                                    <i class="feather-edit-2"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger cd-delete"
                                    data-id="{{ $design->id }}" data-name="{{ $design->title }}"
                                    title="Hapus design" aria-label="Hapus design">
                                    <i class="feather-trash-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
