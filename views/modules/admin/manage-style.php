<style>
  .manage-page {
    max-width: 1440px;
    margin: 0 auto;
  }

  .manage-toolbar {
    display: grid;
    grid-template-columns: minmax(260px, 1fr) minmax(180px, 220px);
    gap: 1rem;
    align-items: end;
  }

  .manage-table th {
    white-space: nowrap;
    color: var(--bs-secondary-color);
    font-size: 0.8125rem;
    text-transform: uppercase;
  }

  .manage-table td {
    vertical-align: middle;
  }

  .salary-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .salary-form-wide {
    grid-column: 1 / -1;
  }

  .tariff-toolbar,
  .tariff-import-grid {
    display: grid;
    grid-template-columns: minmax(220px, 0.8fr) minmax(260px, 1fr) auto;
    gap: 1rem;
    align-items: end;
  }

  .tariff-import-panel {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    padding: 1rem;
    background: var(--bs-tertiary-bg);
  }

  .tariff-import-grid {
    grid-template-columns: repeat(4, minmax(150px, 1fr)) minmax(160px, auto);
    margin-top: 1rem;
  }

  .tariff-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 38px;
    margin-bottom: 0;
  }

  #companyManageMap {
    width: 100%;
    height: 360px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  #manageCompanyEditMap {
    width: 100%;
    height: min(58vh, 620px);
    min-height: 480px;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .manage-company-edit-modal.swal2-popup {
    width: min(1500px, 98vw) !important;
    height: 94vh !important;
    max-height: 94vh !important;
    padding: 1.25rem 1.5rem !important;
    display: flex !important;
    flex-direction: column !important;
  }

  .manage-company-edit-modal .swal2-title {
    flex: 0 0 auto;
  }

  .manage-company-edit-modal .swal2-html-container {
    flex: 1 1 auto;
    width: 100% !important;
    margin: 1rem 0 0 !important;
    max-height: none !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    padding: 0 0.5rem !important;
  }

  .manage-company-edit-modal .swal2-actions {
    flex: 0 0 auto;
    margin-top: 1rem !important;
  }

  .manage-company-edit-modal .manage-edit-map-panel {
    margin-bottom: 1.25rem;
  }

  .manage-company-map-search {
    max-width: 760px;
  }

  @media (max-width: 767.98px) {
    .manage-toolbar {
      grid-template-columns: 1fr;
    }

    .salary-form-grid {
      grid-template-columns: 1fr;
    }

    .tariff-toolbar,
    .tariff-import-grid {
      grid-template-columns: 1fr;
    }

    #companyManageMap {
      height: 320px;
    }

    #manageCompanyEditMap {
      height: 48vh;
      min-height: 320px;
    }

    .manage-company-edit-modal.swal2-popup {
      width: 98vw !important;
      height: 94vh !important;
      padding: 1rem !important;
    }

    .manage-company-map-search {
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
    }

    .manage-company-map-search .btn {
      grid-column: 1 / -1;
      width: 100%;
      margin-left: 0 !important;
      border-radius: 0.375rem !important;
      margin-top: 0.5rem;
    }
  }

  @media (min-width: 576px) {
    .tariff-page .tariff-toolbar {
      grid-template-columns: minmax(220px, 0.8fr) minmax(260px, 1fr) auto !important;
    }

    .tariff-page .tariff-import-grid {
      grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) !important;
    }

    .tariff-page .tariff-toolbar-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      white-space: nowrap;
    }

    .tariff-page .tariff-import-grid .btn {
      min-width: 150px;
    }
  }

  @media (max-width: 575.98px) {
    .tariff-page .tariff-toolbar,
    .tariff-page .tariff-import-grid {
      grid-template-columns: 1fr !important;
    }

    .tariff-page .tariff-toolbar-actions,
    .tariff-page .tariff-toolbar-actions .btn,
    .tariff-page .tariff-import-grid .btn {
      width: 100%;
    }
  }
</style>
