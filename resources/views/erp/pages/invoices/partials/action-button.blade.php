
<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/invoices/edit-invoice/{{ $invoice->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <!-- <li>
                <button type="button"
                    class="dropdown-item btn-delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDeleteInvoice"
                    data-id="{{ $invoice->id }}"
                    data-name="{{ $invoice->name }}"
                    data-url="{{ url('/erp/invoices/delete/' . $invoice->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li> -->
    </ul>
</div>
