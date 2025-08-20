<template>
    <div
        class="modal fade"
        id="delete"
        tabindex="-1"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-xsm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1
                        class="text-center modal-title w-100"
                        id="exampleModalLabel"
                    >
                        Konfirmasi Hapus
                    </h1>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <img
                        id="modalImage"
                        src="{{ asset('assets/svg/delete.svg') }}"
                        alt="delete-image"
                    />

                    <p class="delete-cofirm">
                        Apakah anda Yakin untuk menghapus data ini?
                    </p>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-secondary btn_cancel"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btn_delete"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
