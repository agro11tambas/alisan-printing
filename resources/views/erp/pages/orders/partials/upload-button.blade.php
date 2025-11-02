<div class="hstack gap-2 justify-content-end">
    <a href="/erp/orders/upload-image-delivered/{{ $order->id }}" class="avatar-text avatar-md">
        <i class="feather feather-upload"></i>
    </a>
    <button type="button"
        class="avatar-text avatar-md"
        data-bs-toggle="modal"
        data-bs-target="#modalMarkAsCompletedOrder"
        data-id="{{ $order->id }}"
        data-name="{{ $order->order_number }}"
        data-url="{{ url('/erp/orders/markAsCompleted/' . $order->id) }}">
        <i class="feather feather-check"></i>
    </button>
</div>
