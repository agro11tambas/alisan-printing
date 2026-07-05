@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase</li>
                <li class="breadcrumb-item">Edit History</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row align-items-baseline">
            <div class="col-xxl-12 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Edit History - Purchase #{{ $purchase->purchase_number }}</h5>
                    </div>
                    <div class="card-body">

                        @php
                            function formatChangeValue($val)
                            {
                                if (is_array($val)) {
                                    return json_encode($val, JSON_UNESCAPED_UNICODE);
                                }
                                return $val ?? '-';
                            }
                        @endphp

                        @forelse($histories as $history)
                            <div class="mb-2 border rounded">
                                <div class="d-flex justify-content-between align-items-center bg-light p-1">
                                    <span>
                                        <strong>Tanggal:</strong>
                                        {{ \Carbon\Carbon::parse($history->edited_at)->format('d-m-Y H:i') }}
                                        | <strong>Oleh:</strong> {{ $history->user->name ?? 'System' }}
                                    </span>
                                </div>
                                <div class="p-2">
                                    <p class="text-danger"><strong>Catatan:</strong> {{ $history->text ?? '-' }}</p>
                                    <div class="table-responsive">
                                        @php
                                            $purchaseChanges = $history->changes['purchase'] ?? [];
                                            $itemChanges = $history->changes['items'] ?? [];
                                        @endphp

                                        {{-- Purchase Changes --}}
                                        @if (!empty($purchaseChanges['new']))
                                            <h6>Perubahan Purchase</h6>
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Field</th>
                                                        <th>Old</th>
                                                        <th>New</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($purchaseChanges['new'] as $field => $newValue)
                                                        @if ($field === 'supplier_id')
                                                            <tr>
                                                                <td>Supplier</td>
                                                                <td class="text-danger">
                                                                    {{ \App\Models\Supplier::find($purchaseChanges['old'][$field] ?? null)->name ?? '-' }}
                                                                </td>
                                                                <td class="text-success">
                                                                    {{ \App\Models\Supplier::find($newValue ?? null)->name ?? '-' }}
                                                                </td>
                                                            </tr>
                                                        @else
                                                            <tr>
                                                                <td>{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                                                <td class="text-danger">
                                                                    {{ number_format($purchaseChanges['old'][$field], 0, ',', '.') }}
                                                                </td>
                                                                <td class="text-success">
                                                                    {{ number_format($newValue, 0, ',', '.') }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        @endif

                                        {{-- Item Changes --}}
                                        @if (!empty($itemChanges))
                                            <h6 class="mt-2">Perubahan Items</h6>
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Produk</th>
                                                        <th>Aksi</th>
                                                        <th>Detail Perubahan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($itemChanges as $item)
                                                        <tr>
                                                            <td>{{ $item['product'] }}</td>
                                                            <td>
                                                                @if ($item['action'] === 'added')
                                                                    <span
                                                                        class="badge bg-soft-success text-success">Ditambahkan</span>
                                                                @elseif($item['action'] === 'removed')
                                                                    <span
                                                                        class="badge bg-soft-danger text-danger">Dihapus</span>
                                                                @else
                                                                    <span
                                                                        class="badge bg-soft-warning text-warning">Diupdate</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($item['action'] === 'updated')
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Field</th>
                                                                                <th>Old</th>
                                                                                <th>New</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($item['new'] as $field => $newValue)
                                                                                @if ($field !== 'product')
                                                                                    <tr>
                                                                                        <td>{{ ucfirst(str_replace('_', ' ', $field)) }}
                                                                                        </td>
                                                                                        <td class="text-danger">
                                                                                            {{ isset($item['old'][$field]) ? number_format((float) $item['old'][$field], 0, ',', '.') : '-' }}
                                                                                        </td>
                                                                                        <td class="text-success">
                                                                                            {{ number_format((float) $newValue, 0, ',', '.') }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @elseif($item['action'] === 'added')
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Field</th>
                                                                                <th>Value</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($item['new'] as $field => $val)
                                                                                @if ($field !== 'product')
                                                                                    <tr>
                                                                                        <td>{{ ucfirst(str_replace('_', ' ', $field)) }}
                                                                                        </td>
                                                                                        <td class="text-success">
                                                                                            {{ number_format((float) $val, 0, ',', '.') }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @elseif($item['action'] === 'removed')
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Field</th>
                                                                                <th>Value</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($item['old'] as $field => $val)
                                                                                @if ($field !== 'product')
                                                                                    <tr>
                                                                                        <td>{{ ucfirst(str_replace('_', ' ', $field)) }}
                                                                                        </td>
                                                                                        <td class="text-danger">
                                                                                            {{ number_format((float) $val, 0, ',', '.') }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                @endif

                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">Belum ada edit history untuk purchase ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
