<?php
if (!isset($fieldId, $cropFiles) || !is_array($cropFiles)) {
    throw new RuntimeException('Add sector modal requires $fieldId and $cropFiles.');
}
?>

<div class="modal-overlay sector-modal-overlay" id="addSectorModal" aria-hidden="true">
    <div class="modal-content sector-modal" role="dialog" aria-modal="true" aria-labelledby="addSectorModalTitle">
        <div class="sector-modal-header">
            <h3 id="addSectorModalTitle" data-i18n="sectors.modal.add.title">Add New Sector</h3>
            <button
                type="button"
                class="sector-modal-close close-modal"
                aria-label="Close"
                title="Close"
                data-i18n-title="sectors.modal.common.close_title"
            >
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>

        <form
            class="sector-modal-form"
            id="addSectorForm"
            method="POST"
            action="/sectors?field=<?= (int)$fieldId ?>"
            enctype="multipart/form-data"
        >
            <input type="hidden" name="action" value="add_sector">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="sector_image" id="addSelectedImage" value="">
            <input type="hidden" name="image_mode" id="addImageMode" value="default">

            <div class="sector-form-field">
                <label for="addSectorName">
                    <span data-i18n="sectors.modal.add.sector_name">Sector name</span>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input
                    id="addSectorName"
                    type="text"
                    name="sector_name"
                    class="standard-input"
                    placeholder="e.g. North Orchard"
                    data-i18n-placeholder="sectors.modal.add.sector_name_placeholder"
                    maxlength="255"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="sector-form-row sector-form-row--triple">
                <div class="sector-form-field">
                    <label for="addNodeId">
                        <span data-i18n="sectors.modal.add.node_id">ESP node ID</span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="addNodeId"
                        type="text"
                        name="node_id"
                        class="standard-input"
                        placeholder="e.g. A84F92"
                        data-i18n-placeholder="sectors.modal.add.node_id_placeholder"
                        maxlength="50"
                        autocomplete="off"
                        autocapitalize="characters"
                        required
                    >
                </div>

                <div class="sector-form-field">
                    <label for="addMacAddress">
                        <span data-i18n="sectors.modal.add.mac_address">MAC address</span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="addMacAddress"
                        type="text"
                        name="mac_address"
                        class="standard-input"
                        placeholder="AA:BB:CC:DD:EE:FF"
                        data-i18n-placeholder="sectors.modal.add.mac_address_placeholder"
                        maxlength="17"
                        inputmode="text"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        required
                    >
                </div>

                <div class="sector-form-field">
                    <label for="addCropSelect">
                        <span data-i18n="sectors.modal.add.crop">Crop</span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>
                    <select id="addCropSelect" name="crop_type" class="standard-input custom-select" required>
                        <option value="" selected disabled data-i18n="sectors.modal.add.crop_placeholder">Choose a crop</option>
                        <?php foreach ($cropFiles as $crop): ?>
                            <?php $cropLabel = ucwords(str_replace('-', ' ', (string)$crop)); ?>
                            <option value="<?= htmlspecialchars((string)$crop, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($cropLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="other" data-i18n="sectors.modal.add.custom_crop_option">Custom crop</option>
                    </select>
                </div>
            </div>

            <div class="sector-form-field custom-crop-field" id="addCustomCropField" hidden>
                <label for="addCustomCrop">
                    <span data-i18n="sectors.modal.add.custom_crop_name">Custom crop name</span>
                    <span class="required" aria-hidden="true">*</span>
                </label>
                <input
                    id="addCustomCrop"
                    type="text"
                    name="custom_crop"
                    class="standard-input"
                    placeholder="e.g. Dragon fruit"
                    data-i18n-placeholder="sectors.modal.add.custom_crop_name_placeholder"
                    maxlength="100"
                    autocomplete="off"
                    disabled
                >
            </div>

            <section class="sector-image-picker" aria-labelledby="addSectorImageTitle">
                <div class="sector-image-picker-header">
                    <h4 id="addSectorImageTitle" data-i18n="sectors.modal.add.image_title">Sector image</h4>
                </div>

                <div class="sector-image-tabs" role="tablist" aria-label="Sector image source">
                    <button
                        type="button"
                        class="sector-image-tab is-active"
                        id="addDefaultImagesTab"
                        role="tab"
                        aria-selected="true"
                        aria-controls="addDefaultImagesPanel"
                        data-image-tab="default"
                        data-i18n="sectors.modal.add.default_images"
                    >Default images</button>
                    <button
                        type="button"
                        class="sector-image-tab"
                        id="addUploadImageTab"
                        role="tab"
                        aria-selected="false"
                        aria-controls="addUploadImagePanel"
                        data-image-tab="upload"
                        data-i18n="sectors.modal.add.upload_image"
                    >Upload image</button>
                </div>

                <div
                    class="sector-image-panel is-active"
                    id="addDefaultImagesPanel"
                    role="tabpanel"
                    aria-labelledby="addDefaultImagesTab"
                    data-image-panel="default"
                >
                    <div class="default-images-grid" id="addImgGrid">
                        <?php foreach ($cropFiles as $crop): ?>
                            <?php
                            $cropFile = (string)$crop . '.webp';
                            $cropLabel = ucwords(str_replace('-', ' ', (string)$crop));
                            ?>
                            <button
                                type="button"
                                class="grid-img-card"
                                data-image-file="<?= htmlspecialchars($cropFile, ENT_QUOTES, 'UTF-8') ?>"
                                data-crop-value="<?= htmlspecialchars((string)$crop, ENT_QUOTES, 'UTF-8') ?>"
                                aria-pressed="false"
                            >
                                <img
                                    src="/assets/imgs/plants/<?= htmlspecialchars($cropFile, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($cropLabel, ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy"
                                >
                                <span><?= htmlspecialchars($cropLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div
                    class="sector-image-panel"
                    id="addUploadImagePanel"
                    role="tabpanel"
                    aria-labelledby="addUploadImageTab"
                    data-image-panel="upload"
                    hidden
                >
                    <div
                        class="sector-upload-dropzone"
                        id="addUploadDropzone"
                        role="button"
                        tabindex="0"
                        aria-describedby="addUploadHint"
                    >
                        <input
                            id="addSectorImageFile"
                            type="file"
                            name="sector_image_file"
                            accept="image/jpeg,image/png,image/webp"
                            hidden
                        >

                        <div class="sector-upload-empty" id="addUploadEmpty">
                            <span class="sector-upload-dropzone__icon" aria-hidden="true">
                                <i class="ri-upload-cloud-2-line"></i>
                            </span>
                            <strong data-i18n="sectors.modal.add.upload_drop_title">Drop an image here</strong>
                            <span data-i18n="sectors.modal.add.upload_drop_or">or click to browse</span>
                            <small id="addUploadHint" data-i18n="sectors.modal.add.upload_hint">JPG, PNG or WebP · maximum 2 MB</small>
                        </div>

                        <div class="sector-upload-preview" id="addUploadPreview" hidden>
                            <div class="sector-upload-preview__image-wrap">
                                <img id="addUploadPreviewImage" src="" alt="">
                            </div>
                            <div class="sector-upload-preview__meta">
                                <strong id="addUploadFileName"></strong>
                                <span data-i18n="sectors.modal.add.upload_ready">Image ready to upload</span>
                            </div>
                            <button
                                type="button"
                                class="sector-upload-preview__remove"
                                id="addRemoveUpload"
                                aria-label="Remove selected image"
                                title="Remove selected image"
                                data-i18n-title="sectors.modal.add.remove_image_title"
                            >
                                <i class="ri-close-line" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="sector-modal-footer">
                <button type="button" class="btn-cancel-solid close-modal" data-i18n="sectors.modal.common.cancel">Cancel</button>
                <button type="submit" class="btn-submit-solid">
                    <span data-i18n="sectors.modal.add.submit">Add Sector</span>
                    <i class="ri-check-line" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>
</div>
