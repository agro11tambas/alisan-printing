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
                                        <div class="fw-semibold">Mode (Website Variant)</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @forelse ($product->priceModes as $priceMode)
                                                <span class="badge bg-soft-primary text-primary">{{ $priceMode->name }}</span>
                                            @empty
                                                <span class="text-muted">-</span>
                                            @endforelse
                                        </div>
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

                        <div class="mb-4">
                            <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-list-plus me-2"></i>Variant Groups</h6>

                            @forelse ($product->variantGroups as $groupIndex => $group)
                                <div class="mb-4 border border-start border-4 border-primary rounded p-3 shadow-sm bg-white">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="bx bx-layer me-2"></i>{{ $group->name }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ERP Product</th>
                                                    <th>Alias</th>
                                                    @if ($groupIndex === 0)
                                                        <th>Tanpa Tutup</th>
                                                    @endif
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group->options as $option)
                                                    <tr>
                                                        <td>{{ $option->product?->name ?? '-' }}</td>
                                                        <td>{{ $option->alias }}</td>
                                                        @if ($groupIndex === 0)
                                                            <td>
                                                                <span class="badge {{ $option->allow_without_lid ? 'bg-success' : 'bg-secondary' }}">
                                                                    {{ $option->allow_without_lid ? 'ON' : 'OFF' }}
                                                                </span>
                                                            </td>
                                                        @endif
                                                        <td>
                                                            @if($option->is_active)
                                                                <span class="badge bg-success">Active</span>
                                                            @else
                                                                <span class="badge bg-secondary">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <!-- Image removed from table -->
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

                        @if($product->variantGroups->isNotEmpty() && $product->variantGroups->first()->options->isNotEmpty())
                        <div class="mb-4 mt-4 border border-start border-4 border-warning rounded p-3 shadow-sm bg-white">
                            <h6 class="fw-bold mb-3 text-warning" style="color: #d97706 !important;"><i class="bx bx-images me-2"></i>Primary Variant Images</h6>
                            <div class="row g-3">
                                @foreach ($product->variantGroups->first()->options as $optionIndex => $optionRow)
                                    <div class="col-lg-2 col-md-3 col-sm-4">
                                        <div class="card shadow-sm border mb-0 text-center p-2">
                                            <h6 class="mb-2 fw-bold" style="font-size: 13px;">{{ $optionRow->alias ?: 'Option ' . ($optionIndex + 1) }}</h6>
                                            @if ($optionRow->image)
                                                <a href="{{ asset('uploads/' . $optionRow->image) }}" data-lightbox="primary-{{ $optionRow->id }}">
                                                    <img src="{{ asset('uploads/' . $optionRow->image) }}" alt="Option Image" class="w-100 rounded" style="max-height:100px; object-fit:cover;">
                                                </a>
                                            @else
                                                <div class="text-muted d-flex align-items-center justify-content-center" style="height: 100px; background-color: #f8f9fa; border-radius: 4px;">No Image</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($product->variantCombinations->isNotEmpty())
                        <div class="mb-4 mt-4 border border-start border-4 border-success rounded p-3 shadow-sm bg-white">
                            <h6 class="fw-bold mb-3 text-success"><i class="bx bx-git-merge me-2"></i>Variant Combinations (PRODUCT OPTION + LID OPTION)</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Product Option + Lid Option</th>
                                            <th>Mode Prices</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                            @foreach ($product->variantCombinations as $comb)
                                                <tr>
                                                    <td>{{ $comb->productOption?->alias ?? $comb->productOption?->product?->name ?? '-' }} <span class="text-muted mx-1">+</span> {{ $comb->lidOption?->alias ?? $comb->lidOption?->product?->name ?? '-' }}</td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @forelse (($comb->mode_prices ?? []) as $modePrice)
                                                                <span class="badge bg-soft-success text-success">
                                                                    {{ $modePrice['name'] }}: Rp {{ number_format($modePrice['price'], 0, ',', '.') }}
                                                                </span>
                                                            @empty
                                                                <span class="text-muted">-</span>
                                                            @endforelse
                                                        </div>
                                                    </td>
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
                                                        @if($comb->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
