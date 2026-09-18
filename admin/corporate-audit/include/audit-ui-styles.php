<style>
.ca-filter-offcanvas {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    max-width: 92vw;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 18px rgba(0, 0, 0, 0.15);
    transition: right 0.3s ease;
    z-index: 1055;
    overflow: visible;
}
.ca-filter-offcanvas.show { right: 0; }
.ca-filter-offcanvas-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 1050;
}
.ca-filter-offcanvas-backdrop.show { display: block; }
.ca-filter-offcanvas .offcanvas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
}
.ca-filter-offcanvas .offcanvas-body {
    padding: 1.25rem;
    overflow-y: auto;
    max-height: calc(100vh - 130px);
}
.ca-filter-offcanvas .offcanvas-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}
.ca-filter-offcanvas .select2-container { width: 100% !important; }
body.ca-audit-filters-open .select2-container--open { z-index: 10070 !important; }
body.ca-audit-filters-open .select2-dropdown { z-index: 10071 !important; }
body.modal-open .ca-audit-select + .select2-container,
body.modal-open .select2-dropdown { z-index: 10080 !important; }
.ca-filter-summary { font-size: 0.9rem; color: #6c757d; }
.bulk-checklist-table th,
.bulk-checklist-table td { vertical-align: top !important; font-size: 0.85rem; }
.bulk-checklist-table input.form-control,
.bulk-checklist-table select.form-control,
.bulk-checklist-table textarea.form-control { font-size: 0.85rem; padding: 0.4rem 0.55rem; height: auto; }
.bulk-checklist-table textarea.bulk-checkpoint-name {
    min-height: 78px;
    resize: vertical;
    line-height: 1.4;
    white-space: pre-wrap;
    word-break: break-word;
}
.bulk-checklist-table textarea.bulk-checkpoint-desc {
    min-height: 56px;
    resize: vertical;
    line-height: 1.35;
}
.bulk-checklist-table .bulk-name-col { min-width: 300px; width: 300px; }
.bulk-checklist-table .bulk-desc-col { min-width: 200px; width: 200px; }
.bulk-checklist-table .bulk-row-num { width: 36px; text-align: center; color: #6c757d; font-weight: 600; }
.bulk-checklist-scroll { max-height: 52vh; overflow: auto; border: 1px solid #e9ecef; border-radius: 6px; }
#bulk_checklist_modal .modal-dialog { max-width: 96%; }
.modal_header { background-color: #003f88; color: #fff; }
.modal_header button { opacity: 1; color: #fff; }
</style>
<div id="ca_filter_offcanvas_backdrop" class="ca-filter-offcanvas-backdrop"></div>
