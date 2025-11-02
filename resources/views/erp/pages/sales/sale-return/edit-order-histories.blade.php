@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Sale Return</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Sale Return</li>
            <li class="breadcrumb-item">Edit History</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: "{{ session('error') }}",
    });
</script>
@endif

<div class="main-content">
    <div class="row align-items-baseline">
        <div class="col-xxl-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Edit History - Sale Return #{{ $saleReturn->order_number }}</h5>
                </div>
                <div class="card-body">
                    @forelse($histories as $history)
                    <div class="mb-4 border rounded">
                        <div class="d-flex justify-content-between align-items-center bg-light p-2">
                            <span>
                                <strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($history->edited_at)->format('d-m-Y H:i') }}
                                | <strong>Oleh:</strong> {{ $history->user->name ?? 'System' }}
                            </span>
                        </div>
                        <div class="p-3">
                            <p class="text-danger"><strong>Catatan:</strong> {{ $history->text ?? '-' }}</p>
                            <div class="table-responsive">
                                @php
                                $saleReturnChanges = $history->changes['order'] ?? [];
                                $itemChanges = $history->changes['items'] ?? [];
                                @endphp

                                {{-- Order Changes --}}
                                @if(!empty($saleReturnChanges['new']))
                                <h6>Perubahan Order</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Old</th>
                                            <th>New</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($history->changes['order']['new'] as $field => $newValue)
                                        @if($field === 'customer_id')
                                        <tr>
                                            <td>Customer</td>
                                            <td class="text-danger">
                                                {{ \App\Models\Customers::find($saleReturnChanges['old'][$field])->name ?? '-' }}
                                            </td>
                                            <td class="text-success">
                                                {{ \App\Models\Customers::find($newValue)->name ?? '-' }}
                                            </td>
                                        </tr>
                                        @else
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                            <td class="text-danger">{{ number_format($saleReturnChanges['old'][$field], 0, ',', '.') }}</td>
                                            <td class="text-success">{{ number_format($newValue, 0, ',', '.') }}</td>
                                        </tr>
                                        @endif
                                        @endforeach

                                    </tbody>
                                </table>
                                @endif

                                {{-- Item Changes --}}
                                @if(!empty($itemChanges))
                                <h6 class="mt-4">Perubahan Items</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Aksi</th>
                                            <th>Detail Perubahan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itemChanges as $item)
                                        <tr>
                                            <td>{{ $item['product'] }}</td>
                                            <td>
                                                @if($item['action'] === 'added')
                                                <span class="badge bg-soft-success text-success">Ditambahkan</span>
                                                @elseif($item['action'] === 'removed')
                                                <span class="badge bg-soft-danger text-danger">Dihapus</span>
                                                @else
                                                <span class="badge bg-soft-warning text-warning">Diupdate</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['action'] === 'updated' && !empty($item['fields']))
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Field</th>
                                                            <th>Old</th>
                                                            <th>New</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item['fields'] as $field => $values)
                                                        <tr>
                                                            <td>{{ ucfirst(str_replace('_',' ', $field)) }}</td>
                                                            <td class="text-danger">{{ number_format($values['old'], 0, ',', '.') }}</td>
                                                            <td class="text-success">{{ number_format($values['new'], 0, ',', '.') }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                @else
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Field</th>
                                                            <th>Old</th>
                                                            <th>New</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Quantity</td>
                                                            <td class="text-danger">{{ number_format($item['old_quantity'], 0, ',', '.') }}</td>
                                                            <td class="text-success">{{ number_format($item['new_quantity'], 0, ',', '.') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total</td>
                                                            <td class="text-danger">{{ number_format($item['old_total'], 0, ',', '.') }}</td>
                                                            <td class="text-success">{{ number_format($item['new_total'], 0, ',', '.') }}</td>
                                                        </tr>
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
                    <p class="text-muted">Belum ada edit history untuk order ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
