<table class="table table-sm">
    <thead class="border-bottom mb-2">
        <tr>
            <th scope="col" width="20%">*</th>
            <th scope="col" width="40%">Alamat</th>
            <th scope="col" width="40%">Google Map</th>
        </tr>
    </thead>
    <tbody>
        @foreach($customer['addresses'] ?? [] as $address)
        <tr>
            <td>Alamat {{ $loop->iteration }}</td>
            <td>{{ $address['address'] }}</td>
            <td>
                @if ($address['google_maps'])
                <a href="{{ $address['google_maps'] }}" target="_blank">Lihat di Google Maps</a>
                @else
                <span class="text-muted">Tidak tersedia</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
