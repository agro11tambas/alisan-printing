<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Secondary Product</th>
                <th>SKU</th>
                <th>Bundle Units</th>
                <th width="120" class="text-center">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($secondaryBundles as $item)
                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $item['secondary_name'] }}
                    </td>

                    <td>
                        {{ $item['sku'] }}
                    </td>

                    <td>
                        {!! $item['bundle_units'] !!}
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <a href="{{ url('/erp/products/product-bundles/edit-product-bundle/' . $item['id']) }}"
                                class="btn btn-warning btn-sm">
                                <i class="feather-edit-3"></i>
                            </a>

                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#modalDeleteProduct" data-id="{{ $item['id'] }}"
                                data-name="{{ $item['secondary_name'] }}"
                                data-url="{{ url('/erp/products/product-bundles/delete/' . $item['id']) }}">
                                <i class="feather-trash-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        Belum ada secondary product
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
