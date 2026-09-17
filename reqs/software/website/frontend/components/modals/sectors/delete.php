<div
    class="modal-overlay sector-modal-overlay"
    id="deleteSectorModal"
    aria-hidden="true">
    <div
        class="modal-content sector-modal sector-modal--delete"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteSectorModalTitle">
        <button
            type="button"
            class="sector-delete-close close-delete-modal"
            aria-label="Close"
            title="Close"
            data-i18n-title="sectors.modal.common.close_title">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <form
            class="sector-delete-form"
            id="deleteSectorForm"
            method="POST"
            action="">
            <input
                type="hidden"
                name="action"
                value="delete_sector">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

            <input
                type="hidden"
                name="sector_id"
                id="deleteSectorId"
                value="">

            <div class="sector-delete-dialog">
                <h3
                    id="deleteSectorModalTitle"
                    class="sector-delete-title">
                    Delete <span id="deleteSectorName">Sector</span>?
                </h3>


                <div class="sector-danger-banner">
                    <div
                        class="sector-danger-banner__icon"
                        aria-hidden="true">
                        <i class="ri-alarm-warning-line"></i>
                    </div>


                    <p
                        class="sector-danger-banner__text"
                        data-i18n="sectors.modal.delete.warning_text">
                        <span
                            class="sector-delete-warning-title"
                            data-i18n="sectors.modal.delete.warning_title">
                            This action is irreversible!
                        </span>
                        Deleting this sector permanently removes the sector,
                        its assigned IoT node, and all telemetry associated
                        with that node. This action cannot be undone.
                    </p>
                </div>

                <div class="sector-delete-password">
                    <label for="deleteSectorPassword">
                        <span
                            data-i18n="sectors.modal.delete.password">
                            Enter your password to confirm
                        </span>

                        <span
                            class="required"
                            aria-hidden="true">
                            *
                        </span>
                    </label>

                    <div class="password-field">
                        <input
                            id="deleteSectorPassword"
                            type="password"
                            name="password"
                            class="standard-input"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            data-i18n-placeholder="sectors.modal.delete.password_placeholder"
                            required>

                        <button
                            type="button"
                            class="toggle-password"
                            data-target="deleteSectorPassword"
                            aria-label="Show password"
                            title="Show password"
                            data-i18n-title="sectors.modal.delete.show_password">
                            <i
                                class="fa-regular fa-eye"
                                aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="sector-modal-footer sector-delete-footer">
                <button
                    type="button"
                    class="btn-cancel-solid close-delete-modal"
                    data-i18n="sectors.modal.common.cancel">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-delete-solid">
                    <i
                        class="ri-delete-bin-line"
                        aria-hidden="true"></i>

                    <span
                        data-i18n="sectors.modal.delete.submit">
                        Delete Sector
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
