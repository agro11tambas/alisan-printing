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
                'name' => 'PRODUCT OPTION',
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
                                    <label for="category_id" class="fw-semibold">Category</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <select
                                        class="form-control select2-field @error('category_id') is-invalid @enderror"
                                        id="category_id" name="category_id">
                                        <option value="">Choose Category</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ (int) old('category_id', $product->category_id ?? 0) === (int) $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
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

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="slug" class="fw-semibold">Slug</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                        id="slug" name="slug" value="{{ old('slug', $product->slug ?? '') }}"
                                        placeholder="slug">
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="brand" class="fw-semibold">Brand</label>
                                </div>
                                <div class="col-lg-10 field-wrapper">
                                    <input type="text" class="form-control @error('brand') is-invalid @enderror"
                                        id="brand" name="brand" value="{{ old('brand', $product->brand ?? '') }}"
                                        placeholder="Brand">
                                    @error('brand')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                            <img src="{{ asset('storage/' . $product->main_image) }}"
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
                                    <label for="multiple_qty" class="fw-semibold">Multiple Qty</label>
                                    <input type="text" class="form-control numeric-format" id="multiple_qty"
                                        name="multiple_qty"
                                        value="{{ $formatNumber(old('multiple_qty', $product->multiple_qty ?? 1)) }}">
                                </div>
                                <div class="col-lg-4 field-wrapper">
                                    <label for="min_qty" class="fw-semibold">Minimum Qty</label>
                                    <input type="text" class="form-control numeric-format" id="min_qty"
                                        name="min_qty" value="{{ $formatNumber(old('min_qty', $product->min_qty ?? 1)) }}">
                                </div>
                                <div class="col-lg-4 field-wrapper">
                                    <label for="max_qty" class="fw-semibold">Maximum Qty</label>
                                    <input type="text" class="form-control numeric-format" id="max_qty"
                                        name="max_qty" value="{{ $formatNumber(old('max_qty', $product->max_qty ?? '')) }}">
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
                                                    placeholder="PRODUCT OPTION">
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
                                                        <th>Alias</th>
                                                        <th>Extra Price</th>
                                                        <th>Image</th>
                                                        <th>Video</th>
                                                        <th class="sort-column">Sort</th>
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
                                                            <td class="field-wrapper">
                                                                <input type="text"
                                                                    class="form-control option-alias-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][alias]"
                                                                    value="{{ $optionRow['alias'] ?? '' }}"
                                                                    placeholder="Alias">
                                                            </td>
                                                            <td class="field-wrapper">
                                                                <input type="text"
                                                                    class="form-control numeric-format"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][extra_price]"
                                                                    value="{{ $formatNumber($optionRow['extra_price'] ?? 0) }}">
                                                            </td>
                                                            <td class="preview-cell">
                                                                <input type="file"
                                                                    class="form-control image-preview-input"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][image]"
                                                                    accept="image/*">
                                                                @if (!empty($optionRow['image']))
                                                                    <div class="mt-1 old-file-preview">
                                                                        <img src="{{ asset('storage/' . $optionRow['image']) }}"
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
                                                                                src="{{ asset('storage/' . $optionRow['video']) }}">
                                                                        </video>
                                                                    </div>
                                                                @endif
                                                                <div class="mt-1 new-video-preview-wrap"
                                                                    style="display:none;">
                                                                    <video controls
                                                                        class="file-preview-video new-video-preview"></video>
                                                                </div>
                                                            </td>
                                                            <td class="sort-column">
                                                                <input type="text" class="form-control numeric-format"
                                                                    name="variant_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][sort_order]"
                                                                    value="{{ $formatNumber($optionRow['sort_order'] ?? 0) }}">
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

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const erpProducts = @json(
            $erpProducts->map(fn($item) => [
                        'id' => $item->id,
                        'text' => trim($item->name . ' - ' . $item->sku),
                    ])->values());

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

            let nextGroupIndex = getNextIndex('.variant-group-item', 'group-index');

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
                        <td class="field-wrapper">
                            <input type="text" class="form-control option-alias-input" name="variant_groups[${groupIndex}][options][${optionIndex}][alias]" placeholder="Alias">
                        </td>
                        <td class="field-wrapper">
                            <input type="text" class="form-control numeric-format" name="variant_groups[${groupIndex}][options][${optionIndex}][extra_price]" value="0">
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
                        <td class="sort-column">
                            <input type="text" class="form-control numeric-format" name="variant_groups[${groupIndex}][options][${optionIndex}][sort_order]" value="0">
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
                                        <th>Extra Price</th>
                                        <th>Image</th>
                                        <th>Video</th>
                                        <th class="sort-column">Sort</th>
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

                if (!$('#category_id').val()) {
                    showError($('#category_id'), 'Category wajib dipilih.');
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
