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
    </style>
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
                                <h6 class="fw-bold mb-0">Variant Group</h6>
                                <button type="button" class="btn btn-light-brand btn-sm" id="addVariantGroup">
                                    <i class="feather-plus me-2"></i>
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
                                                    'sort_order' => 0,
                                                ],
                                            ]);
                                        }
                                    @endphp

                                    <div class="variant-group-item" data-group-index="{{ $groupIndex }}"
                                        data-option-index="{{ $options->count() }}">
                                        <input type="hidden" name="variant_groups[{{ $groupIndex }}][id]"
                                            value="{{ $groupRow['id'] ?? '' }}">

                                        <div class="row g-3 mb-2">
                                            <div class="col-lg-10 field-wrapper">
                                                <label class="fw-semibold">Group Name</label>
                                                <input type="text" class="form-control variant-group-name"
                                                    name="variant_groups[{{ $groupIndex }}][name]"
                                                    value="{{ $groupRow['name'] ?? '' }}"
                                                    placeholder="Group Name">
                                            </div>
                                            <div class="col-lg-2 d-flex align-items-end justify-content-end">
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-variant-group">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered ecommerce-repeater-table mb-1">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>ERP Product</th>
                                                        <th>Price (Saved)</th>
                                                        <th>Alias</th>
                                                        <th>Image</th>
                                                        <th>Video</th>
                                                        <th class="action-column"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="variant-option-list">
                                                    @foreach ($options as $optionIndex => $optionRow)
                                                        <tr class="variant-option-row">
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
                                                            <td class="field-wrapper align-middle">
                                                                @if(isset($optionRow['price']))
                                                                    <span class="badge bg-soft-success text-success">Rp {{ $formatNumber($optionRow['price'] ?? 0) }}</span>
                                                                @else
                                                                    <span class="badge bg-soft-secondary text-secondary">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="field-wrapper">
                                                                <input type="text"
                                                                    class="form-control option-alias-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][alias]"
                                                                    value="{{ $optionRow['alias'] ?? '' }}"
                                                                    placeholder="Alias">
                                                            </td>
                                                            <td class="preview-cell">
                                                                <input type="file"
                                                                    class="form-control image-preview-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][image]"
                                                                    accept="image/*">
                                                                @if (!empty($optionRow['image']))
                                                                    <div class="mt-1 old-file-preview">
                                                                        <img src="{{ asset('uploads/' . $optionRow['image']) }}"
                                                                            alt="Option Image"
                                                                            class="file-preview-image">
                                                                    </div>
                                                                @endif
                                                                <div class="mt-1 new-image-preview-wrap"
                                                                    style="display:none;">
                                                                    <img src="#" alt="Preview"
                                                                        class="file-preview-image new-image-preview">
                                                                </div>
                                                            </td>
                                                            <td class="preview-cell">
                                                                <input type="file"
                                                                    class="form-control video-preview-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][video]"
                                                                    accept="video/*">
                                                                @if (!empty($optionRow['video']))
                                                                    <div class="mt-1 old-file-preview">
                                                                        <video controls class="file-preview-video">
                                                                            <source
                                                                                src="{{ asset('uploads/' . $optionRow['video']) }}">
                                                                        </video>
                                                                    </div>
                                                                @endif
                                                                <div class="mt-1 new-video-preview-wrap"
                                                                    style="display:none;">
                                                                    <video controls
                                                                        class="file-preview-video new-video-preview"></video>
                                                                </div>
                                                            </td>
                                                            <td class="text-center action-column">
                                                                <button type="button"
                                                                    class="btn btn-danger btn-sm remove-variant-option">
                                                                    <i class="feather-trash-2"></i>
                                                                </button>
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

                        <div class="mb-2" id="variantCombinationsSection" style="display:none;">
                            <h6 class="fw-bold mb-2">Variant Combinations (PRODUCT OPTION + LID OPTION)</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered ecommerce-repeater-table mb-1">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product Option</th>
                                            <th>Lid Option</th>
                                            <th>Price (Saved)</th>
                                            <th>Image</th>
                                            <th>Video</th>
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
        'base_price' => $item->sale_price > 0 ? $item->sale_price : $item->price,
        'conversions' => $item->unitConversions->map(fn($c) => [
            'unit_id' => $c->unit_id,
            'sale_price' => $c->sale_price,
        ])->values(),
    ])->values();
@endphp
        const erpProducts = @json($erpProductsJson);

        function getProductPrice(productId) {
            const product = erpProducts.find(p => String(p.id) === String(productId));
            if (!product) return 0;
            const unitId = $('#unit_id').val();
            if (unitId && product.conversions) {
                const conv = product.conversions.find(c => String(c.unit_id) === String(unitId));
                if (conv && parseFloat(conv.sale_price) > 0) {
                    return parseFloat(conv.sale_price);
                }
            }
            return parseFloat(product.base_price) || 0;
        }

        $(document).ready(function() {
            const form = $('#ecommerceProductForm');

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

            function formatPrice(value) {
                if (value === undefined || value === null || value === '') return '0';
                let num = parseFloat(value);
                if (isNaN(num)) num = 0;
                num = Math.floor(num);
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
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
                        oldPreview = `<div class="mt-1 old-file-preview"><img src="/uploads/${existing.image}" class="file-preview-image"></div>`;
                    }
                    
                    let oldVideoPreview = '';
                    if (existing.video) {
                        oldVideoPreview = `<div class="mt-1 old-file-preview"><video controls class="file-preview-video"><source src="/uploads/${existing.video}"></video></div>`;
                    }

                    const productLabel = escapeHtml(productAliasMap[pair.primary_product_id] || '');
                    const lidLabel = escapeHtml(lidAliasMap[pair.secondary_product_id] || '');
                    
                    const displayPrice = pair.price !== undefined && pair.price !== null ? pair.price : existing.price;
                    const priceLabel = displayPrice !== undefined && displayPrice !== null
                        ? `<span class="badge bg-soft-success text-success">Rp ${formatPrice(displayPrice)}</span>`
                        : `<span class="badge bg-soft-secondary text-secondary">-</span>`;

                    const tr = `
                        <tr>
                            <td>
                                ${hiddenId}
                                <input type="hidden" name="variant_combinations[${combIndex}][product_option_product_id]" value="${pair.primary_product_id}">
                                ${productLabel}
                            </td>
                            <td>
                                <input type="hidden" name="variant_combinations[${combIndex}][lid_option_product_id]" value="${pair.secondary_product_id}">
                                ${lidLabel}
                            </td>
                            <td class="align-middle">
                                ${priceLabel}
                            </td>
                            <td class="preview-cell">
                                <input type="file" class="form-control image-preview-input" name="variant_combinations[${combIndex}][image]" accept="image/*">
                                ${oldPreview}
                                <div class="mt-1 new-image-preview-wrap" style="display:none;">
                                    <img src="#" alt="Preview" class="file-preview-image new-image-preview">
                                </div>
                            </td>
                            <td class="preview-cell">
                                <input type="file" class="form-control video-preview-input" name="variant_combinations[${combIndex}][video]" accept="video/*">
                                ${oldVideoPreview}
                                <div class="mt-1 new-video-preview-wrap" style="display:none;">
                                    <video controls class="file-preview-video new-video-preview"></video>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(tr);
                });
            }

            function fetchSecondaryProducts() {
                const groups = getProductGroupsData();
                if (groups.length === 0) return;
                
                const productIds = groups[0].options.map(o => o.productId);
                if (productIds.length === 0) {
                    // Remove lid option group if empty
                    const lidGroup = $('.variant-group-item').eq(1);
                    if (lidGroup.length) lidGroup.remove();
                    bundlePairs = [];
                    renderCombinations();
                    return;
                }

                $.ajax({
                    url: '{{ route("erp.ecommerce-products.bundle-secondary") }}',
                    data: { 
                        product_ids: productIds,
                        unit_id: $('#unit_id').val()
                    },
                    success: function(response) {
                        const secondaries = response.secondaries || [];
                        bundlePairs = response.pairs || [];

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

                            // lidGroup.find('.variant-group-name').val('LID OPTION').prop('readonly', true);
                            lidGroup.find('.add-variant-option').hide();
                            lidGroup.find('.remove-variant-group').hide();
                            
                            const tbody = lidGroup.find('.variant-option-list');
                            const groupIndex = lidGroup.data('group-index');
                            
                            // Keep existing row values for image/video
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
                                    
                                    // Make readonly
                                    row.find('.option-product-select').css('pointer-events', 'none').css('background-color', '#e9ecef');
                                    row.find('.remove-variant-option').hide();
                                    
                                    tbody.append(row);
                                }
                                
                                // Update Price based on selected unit
                                const secPrice = getProductPrice(sec.id);
                                row.find('td:nth-child(2)').html(`<span class="badge bg-soft-success text-success">Rp ${formatPrice(secPrice)}</span>`);
                            });
                            
                            initSelect2(lidGroup);
                        }
                        renderCombinations();
                    }
                });
            }

            $(document).on('change', '.variant-group-item:first-child .option-product-select', function() {
                const tr = $(this).closest('tr');
                const productId = $(this).val();
                if (productId) {
                    const price = getProductPrice(productId);
                    tr.find('td:nth-child(2)').html(`<span class="badge bg-soft-success text-success">Rp ${formatPrice(price)}</span>`);
                } else {
                    tr.find('td:nth-child(2)').html(`<span class="badge bg-soft-secondary text-secondary">-</span>`);
                }
                
                fetchSecondaryProducts();
            });

            $(document).on('input', '.variant-group-item .option-alias-input', function() {
                renderCombinations();
            });
            
            // Refresh all variant option prices based on selected unit
            function refreshAllVariantPrices() {
                $('.variant-group-item .variant-option-row').each(function() {
                    const priceCell = $(this).find('td:nth-child(2)');
                    const productId = $(this).find('.option-product-select').val();
                    if (productId) {
                        const price = getProductPrice(productId);
                        priceCell.html(`<span class="badge bg-soft-success text-success">Rp ${formatPrice(price)}</span>`);
                    }
                });
            }

            // When unit changes, refresh ALL prices + re-fetch combinations
            $(document).on('change', '#unit_id', function() {
                refreshAllVariantPrices();
                fetchSecondaryProducts();
            });

            // Call on load
            setTimeout(() => {
                refreshAllVariantPrices();
                fetchSecondaryProducts();
            }, 500);



            function optionRowTemplate(groupIndex, optionIndex) {
                return `
                    <tr class="variant-option-row">
                        <td class="field-wrapper">
                            <input type="hidden" name="variant_groups[${groupIndex}][options][${optionIndex}][id]" value="">
                            <select class="form-control select2-field option-product-select" name="variant_groups[${groupIndex}][options][${optionIndex}][product_id]">
                                <option value="">Choose Product</option>
                                ${optionsHtml(erpProducts)}
                            </select>
                        </td>
                        <td class="field-wrapper align-middle">
                            <span class="badge bg-soft-secondary text-secondary">-</span>
                        </td>
                        <td class="field-wrapper">
                            <input type="text" class="form-control option-alias-input" name="variant_groups[${groupIndex}][options][${optionIndex}][alias]" placeholder="Alias">
                        </td>
                        <td class="preview-cell">
                            <input type="file" class="form-control image-preview-input" name="variant_groups[${groupIndex}][options][${optionIndex}][image]" accept="image/*">
                            <div class="mt-1 new-image-preview-wrap" style="display:none;">
                                <img src="#" alt="Preview" class="file-preview-image new-image-preview">
                            </div>
                        </td>
                        <td class="preview-cell">
                            <input type="file" class="form-control video-preview-input" name="variant_groups[${groupIndex}][options][${optionIndex}][video]" accept="video/*">
                            <div class="mt-1 new-video-preview-wrap" style="display:none;">
                                <video controls class="file-preview-video new-video-preview"></video>
                            </div>
                        </td>
                        <td class="text-center action-column">
                            <button type="button" class="btn btn-danger btn-sm remove-variant-option">
                                <i class="feather-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            function groupTemplate(groupIndex) {
                return `
                    <div class="variant-group-item" data-group-index="${groupIndex}" data-option-index="1">
                        <input type="hidden" name="variant_groups[${groupIndex}][id]" value="">

                        <div class="row g-3 mb-2">
                            <div class="col-lg-10 field-wrapper">
                                <label class="fw-semibold">Group Name</label>
                                <input type="text" class="form-control variant-group-name" name="variant_groups[${groupIndex}][name]" placeholder="LID OPTION">
                            </div>
                            <div class="col-lg-2 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-danger btn-sm remove-variant-group">
                                    <i class="feather-trash-2"></i>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered ecommerce-repeater-table mb-1">
                                <thead class="table-light">
                                    <tr>
                                        <th>ERP Product</th>
                                        <th>Alias</th>
                                        <th>Image</th>
                                        <th>Video</th>
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

                if (!file) {
                    wrap.hide();
                    cell.find('.old-file-preview').show();
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.attr('src', e.target.result);
                    wrap.show();
                    cell.find('.old-file-preview').hide();
                };

                reader.readAsDataURL(file);
            }

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
            });

            $(document).on('click', '.remove-variant-group', function() {
                $(this).closest('.variant-group-item').remove();
            });

            $(document).on('click', '.remove-variant-option', function() {
                $(this).closest('.variant-option-row').remove();
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
