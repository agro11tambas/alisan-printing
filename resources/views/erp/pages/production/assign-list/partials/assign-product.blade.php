<div class="table-responsive">
    <table class="table table-small table-bordered">
        <thead>
            <tr>
                <th style="width: 20%;">Operator</th>
                <th style="width: 15%;">Preview</th>
                <th style="width: 20%;">Product</th>
                <th style="width: 20%;">Assigned</th>
                {{-- <th style="width: 10%;">Defect Product</th>
                <th style="width: 10%;">Reject Product</th> --}}
                <th style="width: 10%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assigns as $assign)
                @php
                    $images = json_decode(optional($assign->progressItem->designItem)->preview_image ?? '[]', true);
                @endphp
                <tr>
                    <td>
                        <span class="fw-bold text-dark">
                            @if ($assign->operator)
                                {{ $assign->operator->name }}
                            @else
                                -
                            @endif
                        </span>
                    </td>
                    {{-- PREVIEW BUTTON --}}
                    <td>
                        @if (!empty($images))
                            <button class="btn btn-sm btn-outline-info preview-btn"
                                data-images='@json($images)'
                                data-product="{{ $assign->progressItem?->product?->name ?? '-' }}">
                                <i class="feather-eye me-1"></i> Preview
                            </button>
                        @else
                            <span class="text-muted small fst-italic">No preview</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold text-dark">{{ $assign->progressItem?->product?->name ?? '-' }}
                        </span>
                    </td>
                    <td><span
                            class="fw-bold text-success">{{ number_format($assign->assigned_quantity, 0, ',', '.') }}</span>
                        {{-- /<span class="fw-bold text-primary">{{ number_format($assign->assigned_quantity, 0, ',', '.') }}</span> --}}
                    </td>
                    {{-- <td><span class="fw-bold text-danger">{{ number_format($assign->defect_quantity, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-warning">{{ number_format($assign->reject_quantity, 0, ',', '.') }}</span></td> --}}
                    <td><span class="fw-bold text-dark">{{ $assign->note ?? '-' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
