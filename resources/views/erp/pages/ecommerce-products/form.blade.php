@php
    $isEdit = isset($product);
    $action = $isEdit ? route('erp.ecommerce-products.update', $product->id) : route('erp.ecommerce-products.store');
    $formatNumber = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        $rawValue = trim((string) $value);
        $normalizedValue = preg_match('/^\d{1,3}(\.\d{3})+$/', $rawValue)
            ? str_replace('.', '', $rawValue)
            : $rawValue;

        return number_format((float) $normalizedValue, 0, ',', '.');
    };

    $oldGroups = old('variant_groups');

    if ($oldGroups !== null) {
        $groupRows = collect($oldGroups);
    } elseif ($isEdit) {
        $groupRows = $product->variantGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'options' => $group->options
                    ->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'alias' => $option->alias,
                            'product_id' => $option->product_id,
                            'price' => $option->price,
                            'extra_price' => $option->extra_price,
                            'image' => $option->image,
                            'video' => $option->video,
                            'is_active' => $option->is_active,
                            'allow_without_lid' => $option->allow_without_lid,
                            'sort_order' => $option->sort_order,
                        ];
                    })
                    ->toArray(),
            ];
        });
    } else {
        $groupRows = collect([
            [
                'name' => '',
                'options' => [
                    [
                        'alias' => '',
                        'product_id' => null,
                        'extra_price' => 0,
                        'is_active' => true,
                        'allow_without_lid' => true,
                        'sort_order' => 0,
                    ],
                ],
            ],
        ]);
    }

    $oldCombinations = old('variant_combinations');
    if ($oldCombinations !== null) {
        $combinations = collect($oldCombinations);
    } elseif ($isEdit) {
        $combinations = $product->variantCombinations->map(function ($comb) {
            return [
                'id' => $comb->id,
                'product_option_product_id' => $comb->productOption?->product_id,
                'lid_option_product_id' => $comb->lidOption?->product_id,
                'price' => $comb->price,
                'image' => $comb->image,
                'is_active' => $comb->is_active,
            ];
        });
    } else {
        $combinations = collect([]);
    }
@endphp

@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Validasi gagal',
                html: @json($errors->all()).join('<br>'),
            });
        });
    </script>
@endif

@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: @json(session('error')),
            });
        });
    </script>
@endif

@push('styles')
    <style>
        .ecommerce-repeater-table th,
        .ecommerce-repeater-table td {
            min-width: 150px;
            vertical-align: top;
        }

        .ecommerce-repeater-table th.action-column,
        .ecommerce-repeater-table td.action-column {
            min-width: 70px;
            width: 70px;
        }

        .ecommerce-repeater-table th.sort-column,
        .ecommerce-repeater-table td.sort-column {
            min-width: 110px;
            width: 110px;
        }

        .variant-group-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .file-preview-image {
            max-width: 90px;
            border-radius: 8px;
        }

        .file-preview-video {
            width: 140px;
            max-width: 100%;
            border-radius: 8px;
        }

        .select2-selection.is-invalid {
            border-color: #dc3545 !important;
        }

        .ecommerce-delete-modal .modal-content {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.22);
        }

        .ecommerce-delete-modal .modal-header,
        .ecommerce-delete-modal .modal-footer {
            border: 0;
        }

        .ecommerce-delete-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-size: 28px;
        }
    </style>
@endpush

@push('modals')
    <div class="modal fade ecommerce-delete-modal" id="modalDeleteVariant" tabindex="-1"
        aria-labelledby="deleteVariantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0">
                    <span></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <span class="ecommerce-delete-icon mb-3">
                        <i class="feather-trash-2"></i>
                    </span>
                    <h4 class="fw-bold mb-2" id="deleteVariantModalLabel">Hapus Variant?</h4>
                    <p class="text-muted mb-0" id="deleteVariantMessage">
                        Variant yang dipilih akan dihapus dari product ini.
                    </p>
                </div>
                <div class="modal-footer px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteVariant">
                        <i class="feather-trash-2 me-2"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

<div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <form action="{{ $action }}" method="POST" id="ecommerceProductForm" enctype="multipart/form-data">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="card-body">
                        <div class="mb-2">
                            <h6 class="fw-bold mb-2">General Information</h6>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="category_ids" class="fw-semibold">Category</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    @php
                                        $selectedCategories = old('category_ids', isset($product) ? $product->categories->pluck('id')->toArray() : []);
                                    @endphp
                                    <select
                                        class="form-control select2-field @error('category_ids') is-invalid @enderror"
                                        id="category_ids" name="category_ids[]" multiple="multiple">
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ in_array($category->id, $selectedCategories) ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="unit_id" class="fw-semibold">Unit</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <select class="form-control select2-field @error('unit_id') is-invalid @enderror"
                                        id="unit_id" name="unit_id">
                                        <option value="">Choose Unit</option>
                                        @foreach ($productUnits as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ (int) old('unit_id', $product->unit_id ?? 0) === (int) $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Mode <span class="text-danger">*</span></label>
                                    <small class="text-muted d-block">Website Variant</small>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    @php
                                        $defaultModeIds = isset($product)
                                            ? $product->priceModes->pluck('id')->all()
                                            : $priceModes->pluck('id')->all();
                                        $selectedModeIds = array_map('intval', old('price_mode_ids', $defaultModeIds));
                                    @endphp
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($priceModes as $priceMode)
                                            <label class="d-flex align-items-center gap-2 border rounded px-3 py-2 mb-0 bg-white">
                                                <input type="checkbox" class="form-check-input mt-0 ecommerce-price-mode"
                                                    name="price_mode_ids[]" value="{{ $priceMode->id }}"
                                                    data-mode-slug="{{ $priceMode->slug }}"
                                                    {{ in_array((int) $priceMode->id, $selectedModeIds, true) ? 'checked' : '' }}>
                                                <span class="fw-semibold">{{ $priceMode->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-1">Pilih Mode yang boleh dibeli customer untuk seluruh variant produk dan bundle.</small>
                                    @error('price_mode_ids')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('price_mode_ids.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="title" class="fw-semibold">Title</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                                        id="title" name="title" value="{{ old('title', $product->title ?? '') }}"
                                        placeholder="Title">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            @if($isEdit)
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Base Price</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <input type="text" class="form-control" value="Rp {{ $formatNumber($product->price) }}" readonly disabled>
                                    <small class="text-muted">Auto-calculated from lowest variant option price.</small>
                                </div>
                            </div>
                            @endif


                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="is_active" class="fw-semibold">Status</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label for="main_image" class="fw-semibold">Main Image</label>
                                </div>
                                <div class="col-lg-10 field-wrapper preview-cell">
                                    <input type="file"
                                        class="form-control image-preview-input @error('main_image') is-invalid @enderror"
                                        id="main_image" name="main_image" accept="image/*">
                                    @error('main_image')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    @if ($isEdit && $product->main_image)
                                        <div class="mt-1 old-file-preview">
                                            <img src="{{ asset('uploads/' . $product->main_image) }}"
                                                alt="Product Image" class="file-preview-image">
                                        </div>
                                    @endif

                                    <div class="mt-1 new-image-preview-wrap" style="display:none;">
                                        <img src="#" alt="Preview" class="file-preview-image new-image-preview">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label for="gallery_images" class="fw-semibold">Gallery Images</label>
                                </div>
                                <div class="col-lg-10 field-wrapper preview-cell">
                                    <input type="file"
                                        class="form-control"
                                        id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                                    <small class="text-muted">You can upload multiple photos for the gallery.</small>
                                    @error('gallery_images')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    @if ($isEdit && $product->galleryImages->count() > 0)
                                        <div class="mt-2 row">
                                            @foreach($product->galleryImages as $img)
                                                <div class="col-auto position-relative mb-2">
                                                    <img src="{{ $img->image_url }}" alt="Gallery Image" class="file-preview-image border rounded" style="width:100px; height:100px; object-fit:cover;">
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="delete_gallery_images[]" value="{{ $img->id }}" id="del_img_{{ $img->id }}">
                                                        <label class="form-check-label text-danger" style="font-size:12px;" for="del_img_{{ $img->id }}">Delete</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    <div class="mt-2 row" id="gallery_preview_container"></div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label for="main_video" class="fw-semibold">Main Video</label>
                                </div>
                                <div class="col-lg-10 field-wrapper preview-cell">
                                    <input type="file"
                                        class="form-control video-preview-input @error('main_video') is-invalid @enderror"
                                        id="main_video" name="main_video" accept="video/*">
                                    @error('main_video')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    @if ($isEdit && $product->main_video)
                                        <div class="mt-1 old-file-preview">
                                            <video controls class="file-preview-video">
                                                <source src="{{ asset('uploads/' . $product->main_video) }}">
                                            </video>
                                        </div>
                                    @endif

                                    <div class="mt-1 new-video-preview-wrap" style="display:none;">
                                        <video controls class="file-preview-video new-video-preview"></video>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label for="description" class="fw-semibold">Description</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <textarea name="description" id="description" rows="5"
                                        class="form-control @error('description') is-invalid @enderror" placeholder="Description">{{ old('description', $product->description ?? '') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <h6 class="fw-bold mb-2">Order Setting</h6>

                            <div class="row g-3">
                                <div class="col-lg-4 field-wrapper">
                                    <label for="multiple_qty" class="fw-semibold">Multiple Qty <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control numeric-format @error('multiple_qty') is-invalid @enderror" id="multiple_qty"
                                        name="multiple_qty"
                                        value="{{ $formatNumber(old('multiple_qty', $product->multiple_qty ?? '')) }}">
                                    @error('multiple_qty')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-4 field-wrapper">
                                    <label for="min_qty" class="fw-semibold">Minimum Qty <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control numeric-format @error('min_qty') is-invalid @enderror" id="min_qty"
                                        name="min_qty" value="{{ $formatNumber(old('min_qty', $product->min_qty ?? '')) }}">
                                    @error('min_qty')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-lg-4 field-wrapper">
                                    <label for="max_qty" class="fw-semibold">Maximum Qty <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control numeric-format @error('max_qty') is-invalid @enderror" id="max_qty"
                                        name="max_qty" value="{{ $formatNumber(old('max_qty', $product->max_qty ?? '')) }}">
                                    @error('max_qty')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-list-plus me-2"></i>Variant Groups</h6>
                                <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm rounded-pill px-3" id="addVariantGroup">
                                    <i class="feather-plus me-1"></i>
                                    Add Variant Group
                                </button>
                            </div>

                            <div id="variantGroupList">
                                @foreach ($groupRows as $groupIndex => $groupRow)
                                    @php
                                        $options = collect($groupRow['options'] ?? []);

                                        if ($options->isEmpty()) {
                                            $options = collect([
                                                [
                                                    'alias' => '',
                                                    'product_id' => null,
                                                    'extra_price' => 0,
                                                    'is_active' => true,
                                                    'sort_order' => 0,
                                                ],
                                            ]);
                                        }
                                    @endphp

                                    <div class="variant-group-item mb-4 border border-start border-4 border-primary rounded p-3 shadow-sm bg-white" data-group-index="{{ $groupIndex }}"
                                        data-option-index="{{ $options->count() }}">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-layer me-2"></i>{{ $groupRow['name'] }}</h6>
                                            <input type="hidden" name="variant_groups[{{ $groupIndex }}][id]"
                                            value="{{ $groupRow['id'] ?? '' }}">
                                            @if($groupIndex !== 1)
                                            <button type="button"
                                                    class="btn btn-danger btn-sm remove-variant-group">
                                                <i class="feather-trash-2"></i>
                                            </button>
                                            @endif
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-lg-12 field-wrapper">
                                                <label class="fw-semibold mb-1">Group Name</label>
                                                <input type="text" class="form-control variant-group-name"
                                                    name="variant_groups[{{ $groupIndex }}][name]"
                                                    value="{{ $groupRow['name'] ?? '' }}"
                                                    placeholder="Group Name">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered ecommerce-repeater-table mb-1">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>ERP Product</th>
                                                        <th>Alias</th>
                                                        @if ($groupIndex === 0)
                                                            <th>Tanpa Tutup</th>
                                                        @endif
                                                        <th>Is Active</th>
                                                        <th class="action-column"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="variant-option-list">
                                                    @foreach ($options as $optionIndex => $optionRow)
                                                        <tr class="variant-option-row" data-option-index="{{ $optionIndex }}">
                                                            <td class="field-wrapper">
                                                                <input type="hidden"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][id]"
                                                                    value="{{ $optionRow['id'] ?? '' }}">
                                                                <select
                                                                    class="form-control select2-field option-product-select"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][product_id]">
                                                                    <option value="">Choose Product</option>
                                                                    @foreach ($erpProducts as $erpProduct)
                                                                        <option value="{{ $erpProduct->id }}"
                                                                            {{ (int) ($optionRow['product_id'] ?? 0) === (int) $erpProduct->id ? 'selected' : '' }}>
                                                                            {{ $erpProduct->name }} -
                                                                            {{ $erpProduct->sku }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>

                                                            <td class="field-wrapper">
                                                                <input type="text"
                                                                    class="form-control option-alias-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][alias]"
                                                                    value="{{ $optionRow['alias'] ?? '' }}"
                                                                    placeholder="Alias">
                                                            </td>
                                                            @if ($groupIndex === 0)
                                                                <td class="text-center align-middle">
                                                                    <div class="d-flex justify-content-center gap-2">
                                                                        <div class="form-check mb-0">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][allow_without_lid]"
                                                                                id="allow_without_lid_on_{{ $groupIndex }}_{{ $optionIndex }}" value="1"
                                                                                {{ !isset($optionRow['allow_without_lid']) || filter_var($optionRow['allow_without_lid'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                                                            <label class="form-check-label" for="allow_without_lid_on_{{ $groupIndex }}_{{ $optionIndex }}">ON</label>
                                                                        </div>
                                                                        <div class="form-check mb-0">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][allow_without_lid]"
                                                                                id="allow_without_lid_off_{{ $groupIndex }}_{{ $optionIndex }}" value="0"
                                                                                {{ isset($optionRow['allow_without_lid']) && !filter_var($optionRow['allow_without_lid'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                                                            <label class="form-check-label" for="allow_without_lid_off_{{ $groupIndex }}_{{ $optionIndex }}">OFF</label>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            @endif
                                                            <td class="text-center align-middle">
                                                                <div class="form-check form-switch d-inline-block">
                                                                    <input type="hidden" name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][is_active]" value="0">
                                                                    <input class="form-check-input" type="checkbox" name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][is_active]" value="1" {{ !isset($optionRow['is_active']) || $optionRow['is_active'] ? 'checked' : '' }}>
                                                                </div>
                                                            </td>
                                                            <td class="text-center action-column">
                                                                @if($groupIndex !== 1)
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm remove-variant-option">
                                                                    <i class="feather-trash-2"></i>
                                                                </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <button type="button"
                                            class="btn btn-outline-primary btn-sm add-variant-option">
                                            <i class="feather-plus me-2"></i>
                                            Add Option
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4 mt-4 border border-start border-4 border-warning rounded p-3 shadow-sm bg-white" id="primaryImagesSection">
                            <h6 class="fw-bold mb-3 text-warning" style="color: #d97706 !important;"><i class="bx bx-images me-2"></i>Primary Variant Images</h6>
                            <div class="row g-3" id="primaryImagesList">
                                @foreach ($groupRows[0]['options'] ?? [] as $optionIndex => $optionRow)
                                    <div class="col-lg-2 col-md-3 col-sm-4 primary-image-card" data-option-index="{{ $optionIndex }}">
                                        <div class="card shadow-sm border mb-0 image-upload-zone" tabindex="0" style="position: relative; overflow: hidden; outline: none; transition: 0.2s;">
                                            <div class="card-body p-2 text-center preview-cell">
                                                <h6 class="mb-2 primary-image-title fw-bold" style="font-size: 13px; position: relative; z-index: 2;">{{ $optionRow['alias'] ?: 'Option ' . ($optionIndex + 1) }}</h6>
                                                
                                                <input type="file" class="image-preview-input" 
                                                    name="variant_groups[0][options][{{ $optionIndex }}][image]" accept="image/*"
                                                    style="display: none;">
                                                
                                                <div class="upload-placeholder mt-2" style="{{ !empty($optionRow['image']) ? 'display:none;' : '' }}">
                                                    <i class="bx bx-cloud-upload text-muted mb-1" style="font-size: 2rem;"></i>
                                                    <p class="mb-0 text-muted" style="font-size: 11px;">Click to Paste<br>Dbl-Click to Browse</p>
                                                </div>

                                                @if (!empty($optionRow['image']))
                                                    <div class="mt-2 old-file-preview" style="position: relative; z-index: 2;">
                                                        <img src="{{ asset('uploads/' . $optionRow['image']) }}" alt="Option Image" class="file-preview-image w-100 rounded" style="max-height:100px; object-fit:cover;">
                                                    </div>
                                                @endif
                                                <div class="mt-2 new-image-preview-wrap" style="display:none; position: relative; z-index: 2;">
                                                    <img src="#" alt="Preview" class="file-preview-image new-image-preview w-100 rounded" style="max-height:100px; object-fit:cover;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-4 border border-start border-4 border-success rounded p-3 shadow-sm bg-white" id="variantCombinationsSection" style="display:none;">
                            <h6 class="fw-bold mb-3 text-success"><i class="bx bx-git-merge me-2"></i>Variant Combinations (PRODUCT OPTION + LID OPTION)</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered ecommerce-repeater-table mb-1">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Product Option + Lid Option</th>
                                            <th>Mode Prices</th>
                                            <th>Image</th>
                                            <th>Is Active</th>
                                        </tr>
                                    </thead>
                                    <tbody id="variantCombinationsList">
                                        <!-- Rendered by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
@php
    $erpProductsJson = $erpProducts->map(fn($item) => [
        'id' => $item->id,
        'text' => trim($item->name . ' - ' . $item->sku),

    ])->values();
@endphp
        const erpProducts = @json($erpProductsJson);


        function selectedWebsiteModes() {
            return $('.ecommerce-price-mode:checked').map((_, element) => element.dataset.modeSlug).get();
        }

        function formatPrice(value) {
            if (value === undefined || value === null || value === '') return '0';
            let num = parseFloat(value);
            if (isNaN(num)) num = 0;
            num = Math.floor(num);
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function renderModePrices(prices, fallbackPrice = 0) {
            const selectedModes = selectedWebsiteModes();
            const visiblePrices = Array.isArray(prices)
                ? prices.filter(price => selectedModes.includes(price.slug))
                : [];
            if (visiblePrices.length) {
                return visiblePrices.map(price =>
                    `<span class="badge bg-soft-success text-success me-1 mb-1">${price.name}: Rp ${formatPrice(price.price)}</span>`
                ).join('');
            }
            return fallbackPrice > 0
                ? `<span class="badge bg-soft-success text-success">Polos: Rp ${formatPrice(fallbackPrice)}</span>`
                : `<span class="badge bg-soft-secondary text-secondary">-</span>`;
        }


        $(document).ready(function() {
            if (window.__erpEcommerceProductFormInitialized) {
                return;
            }

            window.__erpEcommerceProductFormInitialized = true;

            const form = $('#ecommerceProductForm');
            const removalModalElement = document.getElementById('modalDeleteVariant');
            const removalModal = removalModalElement
                ? bootstrap.Modal.getOrCreateInstance(removalModalElement)
                : null;
            const removalMessage = document.getElementById('deleteVariantMessage');
            const removalConfirmButton = document.getElementById('confirmDeleteVariant');
            let pendingRemoval = null;

            removalConfirmButton?.addEventListener('click', function() {
                if (!pendingRemoval) return;

                const callback = pendingRemoval;
                pendingRemoval = null;
                removalModal?.hide();
                callback();
            });

            removalModalElement?.addEventListener('hidden.bs.modal', function() {
                pendingRemoval = null;
            });

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function slugify(value) {
                return value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-');
            }

            function formatNumber(value) {
                const digits = String(value ?? '').replace(/\D/g, '');

                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function optionsHtml(items, selectedValue = '') {
                return items.map(item => {
                    const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
                    return `<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.text)}</option>`;
                }).join('');
            }

            function initSelect2(context) {
                $(context).find('select.select2-field').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) return;

                    $(this).select2({
                        width: '100%',
                        dropdownParent: form,
                        minimumResultsForSearch: 0
                    });
                });
            }

            function refreshVariantProductOptions(group) {
                const $group = $(group);
                const selectedProductIds = $group.find('.option-product-select').map(function() {
                    return String($(this).val() || '');
                }).get().filter(Boolean);

                $group.find('.option-product-select').each(function() {
                    const $select = $(this);
                    const currentValue = String($select.val() || '');
                    const unavailableIds = new Set(
                        selectedProductIds.filter(productId => productId !== currentValue)
                    );
                    const availableProducts = erpProducts.filter(product => {
                        const productId = String(product.id);
                        return productId === currentValue || !unavailableIds.has(productId);
                    });

                    $select.html(
                        `<option value="">Choose Product</option>${optionsHtml(availableProducts, currentValue)}`
                    );
                    $select.val(currentValue);
                });
            }

            function confirmRemoval(message, onConfirmed) {
                if (!removalModal || !removalMessage) return;

                pendingRemoval = onConfirmed;
                removalMessage.textContent = message;
                removalModal.show();
            }

            function getNextIndex(selector, dataName) {
                const indexes = $(selector).map(function() {
                    return parseInt($(this).data(dataName), 10);
                }).get().filter(Number.isFinite);

                return indexes.length ? Math.max(...indexes) + 1 : 0;
            }

                        const combinationsData = @json($combinations ?? []);
            let combinationsState = {}; // { "prodId_lidId": {id, image_url} }
            combinationsData.forEach(c => {
                if (c.product_option_product_id && c.lid_option_product_id) {
                    combinationsState[`${c.product_option_product_id}_${c.lid_option_product_id}`] = c;
                }
            });

            let nextGroupIndex = getNextIndex('.variant-group-item', 'group-index');

            function getProductGroupsData() {
                const groups = [];
                $('.variant-group-item').each(function() {
                    const group = $(this);
                    const groupName = group.find('.variant-group-name').val() || '';
                    const options = [];
                    group.find('.variant-option-row').each(function() {
                        const productSelect = $(this).find('.option-product-select');
                        const alias = $(this).find('.option-alias-input').val() || '';
                        const productId = productSelect.val();
                        const productName = productSelect.find('option:selected').text();
                        if (productId) {
                            options.push({ productId, productName, alias });
                        }
                    });
                    groups.push({ name: groupName, options });
                });
                return groups;
            }

            // Store bundle pairs from API
            let bundlePairs = [];
            let secondaryFetchController = null;
            let activeSecondaryRequestKey = null;
            let secondaryFetchTimer = null;
            let lastCompletedSecondaryRequestKey = null;

            function requestDiagnosticsEnabled() {
                try {
                    return window.localStorage.getItem('erp_debug_requests') === '1';
                } catch (error) {
                    return false;
                }
            }

            function renderCombinations() {
                const groups = getProductGroupsData();
                if (groups.length < 2 || bundlePairs.length === 0) {
                    $('#variantCombinationsSection').hide();
                    return;
                }

                const productGroup = groups[0];
                const lidGroup = groups[1];

                if (productGroup.options.length === 0 || lidGroup.options.length === 0) {
                    $('#variantCombinationsSection').hide();
                    return;
                }

                // Build lookup maps for aliases
                const productAliasMap = {};
                productGroup.options.forEach(o => {
                    productAliasMap[o.productId] = o.alias || o.productName;
                });
                const lidAliasMap = {};
                lidGroup.options.forEach(o => {
                    lidAliasMap[o.productId] = o.alias || o.productName;
                });

                // Filter pairs: only those where both primary AND secondary exist in our current groups
                const validPairs = bundlePairs.filter(pair => {
                    return productAliasMap[pair.primary_product_id] !== undefined
                        && lidAliasMap[pair.secondary_product_id] !== undefined;
                });

                if (validPairs.length === 0) {
                    $('#variantCombinationsSection').hide();
                    return;
                }

                $('#variantCombinationsSection').show();
                const tbody = $('#variantCombinationsList');
                tbody.empty();

                validPairs.forEach((pair, combIndex) => {
                    const key = `${pair.primary_product_id}_${pair.secondary_product_id}`;
                    const existing = combinationsState[key] || {};
                    const hiddenId = existing.id ? `<input type="hidden" name="variant_combinations[${combIndex}][id]" value="${existing.id}">` : '';
                    
                    let oldPreview = '';
                    if (existing.image) {
                        oldPreview = `<div class="mt-2 old-file-preview" style="position: relative; z-index: 2;"><img src="/uploads/${existing.image}" class="file-preview-image w-100 rounded" style="max-height:80px; object-fit:cover;"></div>`;
                    }

                    const productLabel = escapeHtml(productAliasMap[pair.primary_product_id] || '');
                    const lidLabel = escapeHtml(lidAliasMap[pair.secondary_product_id] || '');
                    
                    const displayPrice = pair.price !== undefined && pair.price !== null ? pair.price : existing.price;
                    const priceLabel = renderModePrices(pair.mode_prices, Number(displayPrice) || 0);

                    const tr = `
                        <tr>
                            <td>
                                ${hiddenId}
                                <input type="hidden" name="variant_combinations[${combIndex}][product_option_product_id]" value="${pair.primary_product_id}">
                                <input type="hidden" name="variant_combinations[${combIndex}][lid_option_product_id]" value="${pair.secondary_product_id}">
                                ${productLabel} <span class="text-muted mx-1">+</span> ${lidLabel}
                            </td>
                            <td class="align-middle">
                                ${priceLabel}
                            </td>
                            <td class="preview-cell image-upload-zone text-center" tabindex="0" style="position: relative; overflow: hidden; outline: none; transition: 0.2s; min-width: 120px; padding: 0.5rem; border-radius: 4px; border: 1px dashed #ced4da;">
                                <input type="file" class="image-preview-input" name="variant_combinations[${combIndex}][image]" accept="image/*"
                                                    style="display: none;">
                                                
                                <div class="upload-placeholder mt-2" style="${existing.image ? 'display:none;' : ''}">
                                    <i class="bx bx-cloud-upload text-muted mb-1" style="font-size: 1.5rem;"></i>
                                    <p class="mb-0 text-muted" style="font-size: 10px;">Paste / Dbl-Click</p>
                                </div>

                                ${oldPreview}
                                <div class="mt-2 new-image-preview-wrap" style="display:none; position: relative; z-index: 2;">
                                    <img src="#" alt="Preview" class="file-preview-image new-image-preview w-100 rounded" style="max-height:80px; object-fit:cover;">
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <div class="form-check form-switch d-inline-block">
                                    <input type="hidden" name="variant_combinations[${combIndex}][is_active]" value="0">
                                    <input class="form-check-input" type="checkbox" name="variant_combinations[${combIndex}][is_active]" value="1" ${existing.is_active !== false && existing.is_active !== 0 && existing.is_active !== "0" ? 'checked' : ''}>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(tr);
                });
            }

            function scheduleSecondaryProductsFetch(delay = 150) {
                clearTimeout(secondaryFetchTimer);
                secondaryFetchTimer = setTimeout(fetchSecondaryProducts, delay);
            }

            async function fetchSecondaryProducts() {
                const groups = getProductGroupsData();
                if (groups.length === 0) return;

                const productIds = groups[0].options.map(o => o.productId);
                if (productIds.length === 0) {
                    secondaryFetchController?.abort();
                    secondaryFetchController = null;
                    activeSecondaryRequestKey = null;
                    lastCompletedSecondaryRequestKey = null;

                    const lidGroup = $('.variant-group-item').eq(1);
                    if (lidGroup.length) lidGroup.remove();
                    bundlePairs = [];
                    renderCombinations();
                    return;
                }

                const unitId = $('#unit_id').val() || '';
                const requestKey = JSON.stringify({
                    productIds: productIds.map(String).sort(),
                    unitId: String(unitId)
                });

                if (
                    requestKey === lastCompletedSecondaryRequestKey
                    || (requestKey === activeSecondaryRequestKey && secondaryFetchController)
                ) {
                    return;
                }

                secondaryFetchController?.abort();
                const controller = new AbortController();
                secondaryFetchController = controller;
                activeSecondaryRequestKey = requestKey;

                const params = new URLSearchParams();
                productIds.forEach(productId => params.append('product_ids[]', productId));
                if (unitId) {
                    params.set('unit_id', unitId);
                }
                $('.ecommerce-price-mode:checked').each(function() {
                    params.append('price_mode_ids[]', this.value);
                });

                const combinationsSection = document.getElementById('variantCombinationsSection');
                combinationsSection?.setAttribute('aria-busy', 'true');

                const requestStartedAt = performance.now();
                if (requestDiagnosticsEnabled()) {
                    console.debug('[ERP fetch:start]', requestKey);
                }

                try {
                    const response = await fetch(
                        `{{ route("erp.ecommerce-products.bundle-secondary") }}?${params.toString()}`,
                        {
                            signal: controller.signal,
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();

                    if (controller !== secondaryFetchController) {
                        return;
                    }

                    const secondaries = payload.secondaries || [];
                    bundlePairs = payload.pairs || [];
                    lastCompletedSecondaryRequestKey = requestKey;

                    if (secondaries.length === 0) {
                        const lidGroup = $('.variant-group-item').eq(1);
                        if (lidGroup.length) lidGroup.remove();
                    } else {
                        let lidGroup = $('.variant-group-item').eq(1);
                        if (!lidGroup.length) {
                            $('#variantGroupList').append(groupTemplate(nextGroupIndex));
                            lidGroup = $('.variant-group-item').last();
                            nextGroupIndex++;
                        }

                        lidGroup.find('.add-variant-option').hide();
                        lidGroup.find('.remove-variant-group').hide();

                        const tbody = lidGroup.find('.variant-option-list');
                        const groupIndex = lidGroup.data('group-index');
                        const existingRows = {};

                        tbody.find('.variant-option-row').each(function() {
                            const prodId = $(this).find('.option-product-select').val();
                            if (prodId) {
                                existingRows[prodId] = $(this).detach();
                            } else {
                                $(this).remove();
                            }
                        });

                        secondaries.forEach((sec, idx) => {
                            let row;
                            if (existingRows[sec.id]) {
                                row = existingRows[sec.id];
                                tbody.append(row);
                            } else {
                                row = $(optionRowTemplate(groupIndex, idx));
                                row.find('.option-product-select').val(sec.id);
                                row.find('.option-product-select')
                                    .css('pointer-events', 'none')
                                    .css('background-color', '#e9ecef');
                                row.find('.remove-variant-option').hide();
                                tbody.append(row);
                            }

                            row.find('input, select, textarea').each(function() {
                                let name = $(this).attr('name');
                                if (name) {
                                    name = name.replace(/\[options\]\[\d+\]/, '[options][' + idx + ']');
                                    $(this).attr('name', name);
                                }
                            });


                        });

                        initSelect2(lidGroup);
                    }

                    try {
                        renderCombinations();
                    } catch (renderError) {
                        console.error('Gagal render variant combinations:', renderError);
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Gagal memuat secondary product:', error);
                    }
                } finally {
                    if (requestDiagnosticsEnabled()) {
                        console.debug(
                            '[ERP fetch:end]',
                            requestKey,
                            Math.round(performance.now() - requestStartedAt) + 'ms',
                            controller.signal.aborted ? 'aborted' : 'settled'
                        );
                    }

                    if (controller === secondaryFetchController) {
                        secondaryFetchController = null;
                        activeSecondaryRequestKey = null;
                        combinationsSection?.removeAttribute('aria-busy');
                    }
                }
            }
            $(document).on('change', '.variant-group-item:first-child .option-product-select', function() {
                scheduleSecondaryProductsFetch();
            });

            $(document).on('change', '.option-product-select', function() {
                refreshVariantProductOptions($(this).closest('.variant-group-item'));
            });

            $(document).on('input', '.variant-group-item[data-group-index="0"] .option-alias-input', function() {
                const tr = $(this).closest('tr');
                const optIndex = tr.data('option-index');
                const val = $(this).val() || ('Option ' + (optIndex + 1));
                $(`.primary-image-card[data-option-index="${optIndex}"] .primary-image-title`).text(val);
            });

            $(document).on('input', '.variant-group-item .option-alias-input', function() {
                renderCombinations();
            });

            $(document).on('change', '.variant-group-item .form-check-input', function() {
                const isChecked = $(this).is(':checked');
                const tr = $(this).closest('.variant-option-row');
                const productId = tr.find('.option-product-select').val();
                
                if (productId) {
                    $('#variantCombinationsList tr').each(function() {
                        const primaryId = $(this).find('input[name$="[product_option_product_id]"]').val();
                        const secondaryId = $(this).find('input[name$="[lid_option_product_id]"]').val();
                        
                        if (String(primaryId) === String(productId) || String(secondaryId) === String(productId)) {
                            $(this).find('input[type="checkbox"][name^="variant_combinations"]').prop('checked', isChecked);
                        }
                    });
                }
            });
            

            // When unit changes, refresh ALL prices + re-fetch combinations
            $(document).on('change', '#unit_id', function() {
                scheduleSecondaryProductsFetch();
            });
            $(document).on('change', '.ecommerce-price-mode', function() {
                scheduleSecondaryProductsFetch();
            });

            // Call on load
            secondaryFetchTimer = setTimeout(() => {
                fetchSecondaryProducts();
            }, 500);



            function optionRowTemplate(groupIndex, optionIndex) {
                return `
                    <tr class="variant-option-row" data-option-index="${optionIndex}">
                        <td class="field-wrapper">
                            <input type="hidden" name="variant_groups[${groupIndex}][options][${optionIndex}][id]" value="">
                            <select class="form-control select2-field option-product-select" name="variant_groups[${groupIndex}][options][${optionIndex}][product_id]">
                                <option value="">Choose Product</option>
                                ${optionsHtml(erpProducts)}
                            </select>
                        </td>

                        <td class="field-wrapper">
                            <input type="text" class="form-control option-alias-input" name="variant_groups[${groupIndex}][options][${optionIndex}][alias]" placeholder="Alias">
                        </td>
                        ${groupIndex === 0 ? `
                        <td class="text-center align-middle">
                            <div class="d-flex justify-content-center gap-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="variant_groups[${groupIndex}][options][${optionIndex}][allow_without_lid]" id="allow_without_lid_on_${groupIndex}_${optionIndex}" value="1" checked>
                                    <label class="form-check-label" for="allow_without_lid_on_${groupIndex}_${optionIndex}">ON</label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="variant_groups[${groupIndex}][options][${optionIndex}][allow_without_lid]" id="allow_without_lid_off_${groupIndex}_${optionIndex}" value="0">
                                    <label class="form-check-label" for="allow_without_lid_off_${groupIndex}_${optionIndex}">OFF</label>
                                </div>
                            </div>
                        </td>
                        ` : ''}
                        <td class="text-center align-middle">
                            <div class="form-check form-switch d-inline-block">
                                <input type="hidden" name="variant_groups[${groupIndex}][options][${optionIndex}][is_active]" value="0">
                                <input class="form-check-input" type="checkbox" name="variant_groups[${groupIndex}][options][${optionIndex}][is_active]" value="1" checked>
                            </div>
                        </td>
                        <td class="text-center action-column">
                            ${groupIndex !== 1 ? `
                            <button type="button" class="btn btn-danger btn-sm remove-variant-option">
                                <i class="feather-trash-2"></i>
                            </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }

            function primaryImageCardTemplate(optionIndex, title) {
                return `
                    <div class="col-lg-2 col-md-3 col-sm-4 primary-image-card" data-option-index="${optionIndex}">
                        <div class="card shadow-sm border mb-0 image-upload-zone" tabindex="0" style="position: relative; overflow: hidden; outline: none; transition: 0.2s;">
                            <div class="card-body p-2 text-center preview-cell">
                                <h6 class="mb-2 primary-image-title fw-bold" style="font-size: 13px; position: relative; z-index: 2;">${escapeHtml(title)}</h6>
                                
                                <input type="file" class="image-preview-input" 
                                    name="variant_groups[0][options][${optionIndex}][image]" accept="image/*"
                                    style="display: none;">
                                
                                <div class="upload-placeholder mt-2">
                                    <i class="bx bx-cloud-upload text-muted mb-1" style="font-size: 2rem;"></i>
                                    <p class="mb-0 text-muted" style="font-size: 11px;">Click to Paste<br>Dbl-Click to Browse</p>
                                </div>

                                <div class="mt-2 new-image-preview-wrap" style="display:none; position: relative; z-index: 2;">
                                    <img src="#" alt="Preview" class="file-preview-image new-image-preview w-100 rounded" style="max-height:100px; object-fit:cover;">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            function groupTemplate(groupIndex) {
                return `
                    <div class="variant-group-item mb-4 border border-start border-4 border-primary rounded p-3 shadow-sm bg-white" data-group-index="${groupIndex}" data-option-index="1">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-primary"><i class="bx bx-layer me-2"></i>Variant Group ${groupIndex + 1}</h6>
                            <input type="hidden" name="variant_groups[${groupIndex}][id]" value="">
                            ${groupIndex !== 1 ? `
                            <button type="button" class="btn btn-danger btn-sm remove-variant-group">
                                <i class="feather-trash-2"></i>
                            </button>
                            ` : ''}
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-12 field-wrapper">
                                <label class="fw-semibold">Group Name</label>
                                <input type="text" class="form-control variant-group-name" name="variant_groups[${groupIndex}][name]" placeholder="LID OPTION">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered ecommerce-repeater-table mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th>ERP Product</th>
                                        <th>Alias</th>
                                        ${groupIndex === 0 ? '<th>Tanpa Tutup</th>' : ''}
                                        <th>Is Active</th>
                                        <th class="action-column"></th>
                                    </tr>
                                </thead>
                                <tbody class="variant-option-list">
                                    ${optionRowTemplate(groupIndex, 0)}
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm add-variant-option">
                            <i class="feather-plus me-2"></i>
                            Add Option
                        </button>
                    </div>
                `;
            }

            function clearErrors() {
                $('.client-error').remove();
                $('.is-invalid').removeClass('is-invalid');
                $('.select2-selection').removeClass('is-invalid');
            }

            function showError(field, message) {
                const $field = $(field);
                const wrapper = $field.closest('.field-wrapper').length ? $field.closest('.field-wrapper') : $field
                    .closest('td, .col-lg-10, .col-lg-4, .col-lg-3, .col-lg-2');

                $field.addClass('is-invalid');

                if ($field.hasClass('select2-hidden-accessible')) {
                    $field.next('.select2').find('.select2-selection').addClass('is-invalid');
                }

                wrapper.find('> .client-error').remove();
                wrapper.append(`<div class="invalid-feedback d-block client-error">${escapeHtml(message)}</div>`);
            }

            function previewImage(input) {
                const file = input.files[0];
                const cell = $(input).closest('.preview-cell');
                const wrap = cell.find('.new-image-preview-wrap');
                const preview = cell.find('.new-image-preview');
                const placeholder = cell.find('.upload-placeholder');

                if (!file) {
                    wrap.hide();
                    const oldPreview = cell.find('.old-file-preview');
                    if (oldPreview.length > 0) {
                        oldPreview.show();
                    } else {
                        placeholder.show();
                    }
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.attr('src', e.target.result);
                    cell.find('.old-file-preview').hide();
                    placeholder.hide();
                    wrap.show();
                };

                reader.readAsDataURL(file);
            }

            $(document).on('click', '.image-upload-zone', function(e) {
                $(this).focus();
            });

            $(document).on('dblclick', '.image-upload-zone', function(e) {
                $(this).find('input[type="file"]').click();
            });

            // Drag and Drop visual feedback & drop handling
            $(document).on('dragover', '.image-upload-zone', function(e) {
                e.preventDefault();
                $(this).addClass('border-primary bg-light');
            });
            $(document).on('dragleave', '.image-upload-zone', function(e) {
                $(this).removeClass('border-primary bg-light');
            });
            $(document).on('drop', '.image-upload-zone', function(e) {
                e.preventDefault();
                $(this).removeClass('border-primary bg-light');
                $(this).focus();
                
                const file = e.originalEvent.dataTransfer.files[0];
                if (file && file.type.indexOf("image") === 0) {
                    const input = $(this).find('input[type="file"]')[0];
                    if (input) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        $(input).trigger('change');
                    }
                }
            });

            // Handle Paste
            $(document).on('paste', '.image-upload-zone', function(e) {
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                let file = null;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type.indexOf("image") === 0) {
                        file = items[i].getAsFile();
                        break;
                    }
                }
                if (file) {
                    e.preventDefault();
                    const input = $(this).find('input[type="file"]')[0];
                    if (input) {
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        $(input).trigger('change');
                    }
                }
            });

            function previewVideo(input) {
                const file = input.files[0];
                const cell = $(input).closest('.preview-cell');
                const wrap = cell.find('.new-video-preview-wrap');
                const preview = cell.find('.new-video-preview');

                if (!file) {
                    wrap.hide();
                    cell.find('.old-file-preview').show();
                    return;
                }

                preview.attr('src', URL.createObjectURL(file));
                wrap.show();
                cell.find('.old-file-preview').hide();
            }

            $('#title').on('input', function() {
                $('#slug').val(slugify(this.value));
            });

            $(document).on('select2:open', function() {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            $(document).on('change', '.image-preview-input', function() {
                previewImage(this);
            });

            $(document).on('change', '.video-preview-input', function() {
                previewVideo(this);
            });

            $('#gallery_images').on('change', function() {
                const container = $('#gallery_preview_container');
                container.empty();
                
                const files = this.files;
                if (files) {
                    $.each(files, function(i, file) {
                        if (file && file.type.indexOf('image') === 0) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const html = `
                                    <div class="col-auto mb-2">
                                        <img src="${e.target.result}" class="file-preview-image border rounded" style="width:100px; height:100px; object-fit:cover;">
                                    </div>
                                `;
                                container.append(html);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });

            $(document).on('input', '.numeric-format', function() {
                this.value = formatNumber(this.value);
            });

            $('#addVariantGroup').on('click', function() {
                $('#variantGroupList').append(groupTemplate(nextGroupIndex));
                const newGroup = $(`.variant-group-item[data-group-index="${nextGroupIndex}"]`);

                initSelect2(newGroup);
                nextGroupIndex++;
            });

            $(document).on('click', '.add-variant-option', function() {
                const group = $(this).closest('.variant-group-item');
                const groupIndex = group.data('group-index');
                const optionIndex = parseInt(group.data('option-index'), 10) || 0;

                group.find('.variant-option-list').append(optionRowTemplate(groupIndex, optionIndex));
                group.data('option-index', optionIndex + 1);

                initSelect2(group.find('.variant-option-list tr:last'));
                refreshVariantProductOptions(group);

                if (groupIndex === 0) {
                    $('#primaryImagesList').append(primaryImageCardTemplate(optionIndex, 'Option ' + (optionIndex + 1)));
                }
            });

            $(document).on('click', '.remove-variant-group', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const group = $(e.currentTarget).closest('.variant-group-item');
                confirmRemoval('Apakah Anda yakin ingin menghapus Variant Group ini?', function() {
                    group.remove();
                    renderCombinations();
                });
            });

            $(document).on('click', '.remove-variant-option', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const tr = $(e.currentTarget).closest('.variant-option-row');
                const group = tr.closest('.variant-group-item');
                const groupIndex = group.data('group-index');
                const optionIndex = tr.data('option-index');

                confirmRemoval('Apakah Anda yakin ingin menghapus opsi ini?', function() {
                    tr.remove();
                    refreshVariantProductOptions(group);

                    if (groupIndex === 0) {
                        $(`.primary-image-card[data-option-index="${optionIndex}"]`).remove();
                        scheduleSecondaryProductsFetch();
                    } else {
                        renderCombinations();
                    }
                });
            });

            $('.variant-group-item').each(function() {
                refreshVariantProductOptions(this);
            });

            form.on('submit', function(e) {
                clearErrors();

                let isValid = true;

                if (!$('#category_ids').val() || $('#category_ids').val().length === 0) {
                    showError($('#category_ids'), 'Minimal satu Category wajib dipilih.');
                    isValid = false;
                }

                if (!$('#unit_id').val()) {
                    showError($('#unit_id'), 'Unit wajib dipilih.');
                    isValid = false;
                }

                if (!$('#title').val().trim()) {
                    showError($('#title'), 'Title wajib diisi.');
                    isValid = false;
                }

                if (!$('#multiple_qty').val().trim()) {
                    showError($('#multiple_qty'), 'Multiple Qty wajib diisi.');
                    isValid = false;
                }

                if (!$('#min_qty').val().trim()) {
                    showError($('#min_qty'), 'Minimum Qty wajib diisi.');
                    isValid = false;
                }

                if (!$('#max_qty').val().trim()) {
                    showError($('#max_qty'), 'Maximum Qty wajib diisi.');
                    isValid = false;
                }

                if ($('.variant-group-item').length < 1) {
                    $('#variantGroupList').append(
                        '<div class="invalid-feedback d-block client-error">Minimal satu Variant Group wajib dibuat.</div>'
                    );
                    isValid = false;
                }

                $('.variant-group-item').each(function() {
                    const group = $(this);
                    const groupName = group.find('.variant-group-name');

                    if (!groupName.val().trim()) {
                        showError(groupName, 'Nama Variant Group wajib diisi.');
                        isValid = false;
                    }

                    if (group.find('.variant-option-row').length < 1) {
                        group.append(
                            '<div class="invalid-feedback d-block client-error">Minimal satu Variant Option wajib dibuat.</div>'
                        );
                        isValid = false;
                    }

                    group.find('.variant-option-row').each(function() {
                        const row = $(this);
                        const product = row.find('.option-product-select');
                        const alias = row.find('.option-alias-input');

                        if (!product.val()) {
                            showError(product, 'ERP Product wajib dipilih.');
                            isValid = false;
                        }

                        if (!alias.val().trim()) {
                            showError(alias, 'Alias wajib diisi.');
                            isValid = false;
                        }
                    });
                });

                if (!isValid) {
                    e.preventDefault();

                    const firstError = $('.is-invalid, .client-error').first();

                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 120
                        }, 300);
                    }
                }
            });

            $('.numeric-format').each(function() {
                this.value = formatNumber(this.value);
            });

            initSelect2(document);
        });
    </script>
@endpush
