{{-- Pembungkus tipis supaya pemanggil lama tetap jalan tanpa diubah. --}}
@include('erp.pages.inventory.stock-in.partials.image-capture-editor', [
    'key' => 'waybill',
    'field' => 'waybill_image',
    'label' => 'Surat jalan',
    'capture' => $capture ?? false,
    'formId' => $formId ?? 'stockInForm',
    'slots' => $slots ?? 1,
])
