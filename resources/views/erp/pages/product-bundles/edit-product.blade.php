@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Products</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Products</li>
            <li class="breadcrumb-item">Edit Product Bundle</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex d-md-none">
                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                    <i class="feather-arrow-left me-2"></i><span>Back</span>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                <a href="/erp/products/product-bundles" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="productBundleForm">
                    <i class="feather-plus me-2"></i>
                    <span>Edit Product Bundles</span>
                </button>
            </div>
        </div>
        <div class="d-md-none d-flex align-items-center">
            <a href="javascript:void(0)" class="page-header-right-open-toggle">
                <i class="feather-align-right fs-20"></i>
            </a>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <form action="/erp/products/product-bundles/update/{{ $bundle->id }}" method="POST" id="productBundleForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        {{-- Pilih Produk --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label class="fw-semibold">Pilih Produk:</label>
                            </div>
                            <div class="col-lg-10">
                                <select class="form-select form-control max-select" multiple id="products" name="products[]">
                                    @foreach($products as $product)
                                        <option 
                                            value="{{ $product->id }}" 
                                            data-name="{{ $product->name }}"
                                            {{ in_array($product->id, $bundle->items->pluck('product_id')->toArray()) ? 'selected' : '' }}>
                                            {{ $product->name }} - {{ $product->sku }} (Rp{{ number_format($product->price) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih minimal dua produk untuk bundle</small>
                            </div>
                        </div>

                        {{-- Nama Bundle --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Name:</label>
                            </div>
                            <div class="col-lg-10">
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $bundle->name) }}" readonly>
                            </div>
                        </div>

                        {{-- SKU --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="sku" class="fw-semibold">SKU</label>
                            </div>
                            <div class="col-lg-10">
                                <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku', $bundle->sku) }}">
                            </div>
                        </div>

                        {{-- Harga --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="price" class="fw-semibold">Price</label>
                            </div>
                            <div class="col-lg-10">
                                <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $bundle->price) }}">
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#products').select2({
            placeholder: "Pilih produk untuk bundle"
        });

        // Saat halaman load, update nama bundle dari produk terpilih
        updateBundleName();

        $('#products').on('change', function() {
            updateBundleName();
        });

        function updateBundleName() {
            let selected = $('#products').find(':selected').map(function() {
                return $(this).data('name');
            }).get();
            $('#name').val(selected.join(' + '));
        }
    });
</script>
@endpush
