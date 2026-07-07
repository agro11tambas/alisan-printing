@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ecommerce Product</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('erp.ecommerce-products.index') }}">Ecommerce Product</a></li>
                <li class="breadcrumb-item">Detail</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="{{ route('erp.ecommerce-products.index') }}" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <a href="{{ route('erp.ecommerce-products.edit', $product->id) }}" class="btn btn-primary">
                        <i class="feather-edit-3 me-2"></i>
                        <span>Edit Product</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="row g-4 mb-2">
                            <div class="col-lg-3">
                                @if ($product->main_image)
                                    <div class="mb-2">
                                        <div class="fw-semibold mb-1">Image</div>
                                        <a href="{{ asset('uploads/' . $product->main_image) }}" data-lightbox="product-main">
                                            <img src="{{ asset('uploads/' . $product->main_image) }}" alt="Product Image"
                                                class="img-fluid rounded" style="width:100%;max-height:240px;object-fit:cover;">
                                        </a>
                                    </div>
                                @else
                                    <div class="border rounded d-flex align-items-center justify-content-center text-muted mb-2"
                                        style="height:180px;">
                                        No Image
                                    </div>
                                @endif
                                
                                @if ($product->main_video)
                                    <div class="mb-2">
                                        <div class="fw-semibold mb-1">Video</div>
                                        <video controls class="w-100 rounded" style="max-height:240px;object-fit:cover;">
                                            <source src="{{ asset('uploads/' . $product->main_video) }}">
                                        </video>
                                    </div>
                                @endif
                            </div>
                            <div class="col-lg-9">
                                <h5 class="fw-bold mb-3">
                                    {{ $product->title }}
                                    @if($product->is_active)
                                        <span class="badge bg-success ms-2">Active</span>
                                    @else
                                        <span class="badge bg-secondary ms-2">Inactive</span>
                                    @endif
                                </h5>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Category</div>
                                        <div>
                                            @if($product->categories->isNotEmpty())
                                                {{ $product->categories->pluck('name')->implode(', ') }}
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Unit</div>
                                        <div>{{ $product->unit?->name ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Base Price</div>
                                        <div>Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Brand</div>
                                        <div>{{ $product->brand ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Multiple Qty</div>
                                        <div>{{ $product->multiple_qty }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Minimum Qty</div>
                                        <div>{{ $product->min_qty }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="fw-semibold">Maximum Qty</div>
                                        <div>{{ $product->max_qty ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <h6 class="fw-bold mb-1">Description</h6>
                            <div class="border rounded p-2">
                                {!! nl2br(e($product->description ?? '-')) !!}
                            </div>
                        </div>

                        <div class="mb-2">
                            <h6 class="fw-bold mb-2">Variant Group</h6>

                            @forelse ($product->variantGroups as $group)
                                <div class="border rounded p-2 mb-2">
                                    <div class="fw-bold mb-2">{{ $group->name }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ERP Product</th>
                                                    <th>Price</th>
                                                    <th>Alias</th>
                                                    <th>Image</th>
                                                    <th>Video</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group->options as $option)
                                                    <tr>
                                                        <td>{{ $option->product?->name ?? '-' }}</td>
                                                        <td>Rp {{ number_format($option->price ?? 0, 0, ',', '.') }}</td>
                                                        <td>{{ $option->alias }}</td>
                                                        <td>
                                                            @if ($option->image)
                                                                <a href="{{ asset('uploads/' . $option->image) }}"
                                                                    data-lightbox="option-{{ $option->id }}">
                                                                    <img src="{{ asset('uploads/' . $option->image) }}"
                                                                        alt="Option Image" class="rounded"
                                                                        style="width:56px;height:56px;object-fit:cover;">
                                                                </a>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($option->video)
                                                                <video controls style="width:150px;max-width:100%;border-radius:8px;">
                                                                    <source src="{{ asset('uploads/' . $option->video) }}">
                                                                </video>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">Belum ada variant group.</div>
                            @endforelse
                        </div>

                        @if($product->variantCombinations->isNotEmpty())
                        <div class="mb-2 mt-4">
                            <h6 class="fw-bold mb-2">Variant Combinations</h6>
                            <div class="border rounded p-2">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product Option</th>
                                                <th>Lid Option</th>
                                                <th>Price</th>
                                                <th>Image</th>
                                                <th>Video</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($product->variantCombinations as $comb)
                                                <tr>
                                                    <td>{{ $comb->productOption?->alias ?? $comb->productOption?->product?->name ?? '-' }}</td>
                                                    <td>{{ $comb->lidOption?->alias ?? $comb->lidOption?->product?->name ?? '-' }}</td>
                                                    <td>Rp {{ number_format($comb->price ?? 0, 0, ',', '.') }}</td>
                                                    <td>
                                                        @if ($comb->image)
                                                            <a href="{{ asset('uploads/' . $comb->image) }}"
                                                                data-lightbox="comb-{{ $comb->id }}">
                                                                <img src="{{ asset('uploads/' . $comb->image) }}"
                                                                    alt="Combination Image" class="rounded"
                                                                    style="width:56px;height:56px;object-fit:cover;">
                                                            </a>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($comb->video)
                                                            <video controls style="width:150px;max-width:100%;border-radius:8px;">
                                                                <source src="{{ asset('uploads/' . $comb->video) }}">
                                                            </video>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
