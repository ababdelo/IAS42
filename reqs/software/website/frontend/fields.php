<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();
$pageTitle = 'fields';
$extraCss = '<link rel="stylesheet" href="/assets/css/fields.css">';
$extraJs  = '<script src="/assets/js/fields.js" defer></script>';
require_once __DIR__ . "/components/sideBar.php";
?>
<div class="fields-wrapper">
    <div class="page-header-actions">
        <div>
            <h2 data-i18n="fields.page.title">My Fields</h2>
            <span class="text-muted" data-i18n="fields.page.subtitle">Manage your farms and agricultural zones</span>
        </div>
        <button type="button" class="btn-primary" id="openFieldModalBtn">
            <i class="ri-add-line"></i> <span data-i18n="fields.page.add_field">Add New Field</span>
        </button>
    </div>

    <!-- MVP Single Farm View -->
    <div class="farms-list">
        <article class="farm-row-card">
            <div class="farm-row__icon bg-gradient-1">
                <i class="ri-landscape-line"></i>
            </div>
            
            <div class="farm-row__core">
                <div class="farm-row__info">
                    <h3>Oasis Farm</h3>
                    <span class="location"><i class="ri-map-pin-line"></i> Ouarzazate, Morocco</span>
                </div>
                
                <div class="farm-row__stats">
                    <div class="stat-item">
                        <span class="stat-label" data-i18n="fields.profile.total_area">Total Area</span>
                        <div class="stat-val"><strong>2.0</strong> <small data-i18n="fields.units.hectares">ha</small></div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-label" data-i18n="fields.profile.active_nodes">Active Nodes</span>
                        <div class="stat-val"><strong>1</strong> <small data-i18n="fields.profile.node">Node</small></div>
                    </div>
                </div>
            </div>
            <div class="farm-row__actions">
                <div class="icon-actions">
                    <button class="action-btn edit" data-i18n-title="fields.actions.edit" title="Edit"><i class="ri-edit-line"></i></button>
                    <button class="action-btn delete" data-i18n-title="fields.actions.delete" title="Delete"><i class="ri-delete-bin-line"></i></button>
                </div>
                <a href="/sectors?field=1" class="btn-enter">
                    <span data-i18n="fields.actions.manage_zones">Manage Zones</span> <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </article>
    </div>
</div>

<!-- Add/Edit Field Modal -->
<div class="modal-overlay" id="fieldModal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header-accent bg-gradient-1"></div>
        <div class="modal-header">
            <div class="modal-title-group">
                <div class="modal-icon"><i class="ri-landscape-line"></i></div>
                <h3 data-i18n="fields.field_modal.title">Register New Field</h3>
            </div>
            <button type="button" class="close-modal close-field-modal" aria-label="Close"><i class="ri-close-line"></i></button>
        </div>
        
        <form class="modal-form" id="fieldForm">
            <p class="modal-description" data-i18n="fields.field_modal.description">Enter the details of your new agricultural zone to start tracking its data.</p>
            
            <div class="input-group">
                <label data-i18n="fields.field_modal.name">Field Name</label>
                <div class="input-modern">
                    <i class="ri-edit-2-line"></i>
                    <!-- Added data-i18n-placeholder here -->
                    <input type="text" placeholder="e.g. South Farm" data-i18n-placeholder="fields.field_modal.name_ph" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label data-i18n="fields.field_modal.location">Location (City)</label>
                    <div class="input-modern">
                        <i class="ri-map-pin-line"></i>
                        <input type="text" placeholder="e.g. Agadir" data-i18n-placeholder="fields.field_modal.location_ph" required>
                    </div>
                </div>
                <div class="input-group">
                    <label data-i18n="fields.field_modal.area">Total Area (ha)</label>
                    <div class="input-modern">
                        <i class="ri-ruler-line"></i>
                        <input type="number" step="0.1" placeholder="0.0" data-i18n-placeholder="fields.field_modal.area_ph" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel close-field-modal" data-i18n="global.cancel">Cancel</button>
                <button type="submit" class="btn-submit"><span data-i18n="global.save">Save Field</span> <i class="ri-check-line"></i></button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . "/components/footer.php"; ?>
