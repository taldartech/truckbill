@php
    /*
    |--------------------------------------------------------------------------
    | DEFAULT VALUES
    |--------------------------------------------------------------------------
    */

    $modalId = $modalId ?? 'bulkUploadModal';
    $uploadUrl = $uploadUrl ?? '#';
    $templateUrl = $templateUrl ?? '#';
    $title = $title ?? 'Bulk Upload';
    $recordName = $recordName ?? 'records';

    $requiredColumns = $requiredColumns ?? [];
    $optionalColumns = $optionalColumns ?? [];

    if (
        empty($requiredColumns) &&
        empty($optionalColumns) &&
        !empty($columns)
    ) {
        $requiredColumns = collect($columns)
            ->filter(function ($column) {
                return !empty($column['required']);
            })
            ->values()
            ->all();

        $optionalColumns = collect($columns)
            ->filter(function ($column) {
                return empty($column['required']);
            })
            ->values()
            ->all();
    }

    $requiredColumns = is_array($requiredColumns)
        ? $requiredColumns
        : $requiredColumns->toArray();

    $optionalColumns = is_array($optionalColumns)
        ? $optionalColumns
        : $optionalColumns->toArray();
@endphp


<style>
    /*
    |--------------------------------------------------------------------------
    | BULK UPLOAD MODAL
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .bulk-upload-modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
    }

    #{{ $modalId }} .bulk-upload-header {
        background: linear-gradient(90deg, #e4f8ef, #eef6ff);
        border-bottom: 1px solid #dfe7ec;
        padding: 14px 20px;
    }

    #{{ $modalId }} .bulk-upload-title {
        font-size: 18px;
        font-weight: 700;
        color: #172033;
        margin: 0;
    }

    #{{ $modalId }} .bulk-upload-title i {
        color: #20b46b;
    }

    #{{ $modalId }} .bulk-template-btn {
        background: #20c56b;
        border-color: #20c56b;
        color: #fff;
        font-weight: 600;
        border-radius: 10px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }

    #{{ $modalId }} .bulk-template-btn:hover {
        background: #18ae5d;
        border-color: #18ae5d;
        color: #fff;
    }

    /*
    |--------------------------------------------------------------------------
    | INFO
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .bulk-upload-info {
        background: #dff7ff;
        border: 1px solid #a8e7f5;
        color: #075b76;
        border-radius: 7px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }

    /*
    |--------------------------------------------------------------------------
    | COLUMN GUIDE
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .guide-wrapper {
        margin-bottom: 22px;
    }

    #{{ $modalId }} .guide-title {
        font-size: 17px;
        font-weight: 700;
        margin-bottom: 12px;
        color: #172033;
    }

    #{{ $modalId }} .guide-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        align-items: start;
    }

    #{{ $modalId }} .guide-column {
        min-width: 0;
    }

    #{{ $modalId }} .guide-heading {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 8px;
        padding: 9px 12px;
        border-radius: 7px 7px 0 0;
    }

    #{{ $modalId }} .required-heading {
        background: #fff0f1;
        color: #a51d2d;
        border: 1px solid #f1c4c8;
        border-bottom: 0;
    }

    #{{ $modalId }} .optional-heading {
        background: #eef7ff;
        color: #1261a0;
        border: 1px solid #c7e2f8;
        border-bottom: 0;
    }

    #{{ $modalId }} .guide-table {
        margin-bottom: 0;
        border-color: #d9e0e6;
        font-size: 14px;
    }

    #{{ $modalId }} .guide-table th {
        background: #f4f7fa;
        font-weight: 700;
        color: #172033;
        white-space: nowrap;
    }

    #{{ $modalId }} .guide-table td {
        vertical-align: middle;
    }

    #{{ $modalId }} .required-badge,
    #{{ $modalId }} .optional-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 7px;
        border-radius: 20px;
        white-space: nowrap;
    }

    #{{ $modalId }} .required-badge {
        background: #dc3545;
        color: #fff;
    }

    #{{ $modalId }} .optional-badge {
        background: #6c757d;
        color: #fff;
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD SECTION
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .upload-section-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #172033;
    }

    #{{ $modalId }} .file-input-wrapper input[type="file"] {
        width: 100%;
        border: 1px solid #d7dee5;
        border-radius: 7px;
        padding: 9px;
        background: #fff;
    }

    #{{ $modalId }} .file-help {
        margin-top: 7px;
        font-size: 13px;
        color: #667085;
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRESS
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .bulk-progress-section {
        display: none;
    }

    #{{ $modalId }} .bulk-progress-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    #{{ $modalId }} .bulk-progress {
        height: 30px;
        border-radius: 4px;
        overflow: hidden;
    }

    #{{ $modalId }} .bulk-progress-bar {
        font-weight: 600;
    }

    /*
    |--------------------------------------------------------------------------
    | RESULT
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .bulk-result {
        display: none;
        margin-top: 18px;
    }

    #{{ $modalId }} .success-summary {
        display: none;
    }

    #{{ $modalId }} .error-summary {
        display: none;
    }

    #{{ $modalId }} .row-errors-wrapper {
        display: none;
        margin-top: 15px;
    }

    #{{ $modalId }} .row-errors-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #a51d2d;
    }

    #{{ $modalId }} .row-errors-table {
        margin-bottom: 0;
        font-size: 14px;
    }

    #{{ $modalId }} .row-errors-table th {
        background: #fff0f1;
        color: #8d1826;
        font-weight: 700;
    }

    #{{ $modalId }} .row-errors-table td {
        vertical-align: top;
    }

    #{{ $modalId }} .error-row-number {
        font-weight: 700;
        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | FOOTER
    |--------------------------------------------------------------------------
    */

    #{{ $modalId }} .bulk-upload-footer {
        border-top: 1px solid #dfe5ea;
        padding: 14px 20px;
    }

    #{{ $modalId }} .bulk-cancel-btn {
        background: #eef1f4;
        border: 1px solid #d5dce2;
        color: #20b46b;
        font-weight: 600;
        border-radius: 8px;
    }

    #{{ $modalId }} .bulk-upload-btn {
        background: #20b46b;
        border-color: #20b46b;
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
    }

    #{{ $modalId }} .bulk-upload-btn:hover {
        background: #18a85e;
        border-color: #18a85e;
        color: #fff;
    }

    /*
    |--------------------------------------------------------------------------
    | MOBILE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 768px) {
        #{{ $modalId }} .guide-columns {
            grid-template-columns: 1fr;
        }

        #{{ $modalId }} .modal-header,
        #{{ $modalId }} .bulk-upload-header {
            padding: 10px 12px !important;
            gap: 8px;
        }

        #{{ $modalId }} .modal-title,
        #{{ $modalId }} .bulk-upload-title {
            font-size: 13.5px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            flex: 1 1 auto;
        }

        #{{ $modalId }} .modal-header .d-flex {
            gap: 6px !important;
            flex-shrink: 0;
        }

        #{{ $modalId }} .bulk-template-btn {
            font-size: 11.5px !important;
            padding: 4px 8px !important;
            border-radius: 6px !important;
            white-space: nowrap !important;
            display: inline-flex !important;
            align-items: center !important;
            line-height: 1.2 !important;
        }

        #{{ $modalId }} .bulk-template-btn i {
            font-size: 11px !important;
            margin-right: 3px !important;
        }

        #{{ $modalId }} .modal-header .btn-close {
            font-size: 11px !important;
            padding: 4px !important;
            margin: 0 !important;
            flex-shrink: 0;
        }
    }
</style>


<div class="modal fade"
     id="{{ $modalId }}"
     tabindex="-1"
     aria-labelledby="{{ $modalId }}Label"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content bulk-upload-modal-content">

            {{-- ============================================================
                 HEADER
            ============================================================= --}}

      <div class="modal-header bulk-upload-header px-3 py-2 px-md-4 py-md-3">

    <h4 class="modal-title bulk-upload-title fw-bold mb-0">
        <i class="fas fa-upload me-2 text-success"></i>
        {{ \Illuminate\Support\Str::startsWith($title ?? '', 'Bulk Upload') ? ($title ?? '') : 'Bulk Upload ' . ($title ?? '') }}
    </h4>

    <div class="d-flex align-items-center ms-auto gap-2 gap-md-3">

        <a href="{{ $templateUrl }}" class="btn bulk-template-btn">
            <i class="fas fa-download me-1"></i>Download Template
        </a>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="modal"
                aria-label="Close">
        </button>

    </div>

</div>
            {{-- ============================================================
                 BODY
            ============================================================= --}}

            <div class="modal-body">

                {{-- INFO --}}

                <div class="bulk-upload-info">

                    <i class="fas fa-info-circle me-2"></i>

                    Upload an Excel or CSV file to import multiple
                    {{ $recordName }} at once.

                </div>


                {{-- ========================================================
                     COLUMN GUIDE
                ========================================================= --}}

                <div id="{{ $modalId }}ColumnGuide"
                     class="guide-wrapper">

                    <div class="guide-title">

                        <i class="fas fa-table me-2 text-success"></i>

                        Column Guide

                    </div>


                    <div class="guide-columns">

                        {{-- =================================================
                             REQUIRED FIELDS
                        ================================================== --}}

                        <div class="guide-column required-column">

                            <div class="guide-heading required-heading">

                                <i class="fas fa-asterisk me-1"></i>

                                Required Fields

                            </div>

                            <div class="table-responsive">

                                <table class="table table-bordered guide-table">

                                    <thead>

                                        <tr>

                                            <th style="width: 50%;">
                                                Column Name
                                            </th>

                                            <th>
                                                Expected Value / Format
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @forelse ($requiredColumns as $column)

                                            <tr>

                                                <td>

                                                    <span class="fw-semibold">
                                                        {{ $column['name'] ?? '-' }}
                                                    </span>


                                                </td>

                                                <td>
                                                    {{ $column['format'] ?? '-' }}
                                                </td>

                                            </tr>

                                        @empty

                                            <tr>

                                                <td colspan="2"
                                                    class="text-center text-muted">

                                                    No required fields configured.

                                                </td>

                                            </tr>

                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        </div>


                        {{-- =================================================
                             OPTIONAL FIELDS
                        ================================================== --}}

                        <div class="guide-column optional-column">

                            <div class="guide-heading optional-heading">

                                <i class="fas fa-circle-info me-1"></i>

                                Optional Fields

                            </div>

                            <div class="table-responsive">

                                <table class="table table-bordered guide-table">

                                    <thead>

                                        <tr>

                                            <th style="width: 50%;">
                                                Column Name
                                            </th>

                                            <th>
                                                Expected Value / Format
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @forelse ($optionalColumns as $column)

                                            <tr>

                                                <td>

                                                    <span class="fw-semibold">
                                                        {{ $column['name'] ?? '-' }}
                                                    </span>


                                                </td>

                                                <td>
                                                    {{ $column['format'] ?? '-' }}
                                                </td>

                                            </tr>

                                        @empty

                                            <tr>

                                                <td colspan="2"
                                                    class="text-center text-muted">

                                                    No optional fields configured.

                                                </td>

                                            </tr>

                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- ========================================================
                     UPLOAD SECTION
                ========================================================= --}}

                <div id="{{ $modalId }}UploadSection">

                    <div class="upload-section-title">

                        <i class="fas fa-file-upload me-2 text-success"></i>

                        Choose File

                    </div>


                    <div class="file-input-wrapper">

                        <input type="file"
                               id="{{ $modalId }}File"
                               accept=".xlsx,.xls,.csv">

                    </div>


                    <div class="file-help">

                        <i class="fas fa-info-circle me-1"></i>

                        Allowed file types:
                        .xlsx, .xls, .csv
                        (Maximum 10 MB)

                    </div>

                </div>


                {{-- ========================================================
                     PROGRESS SECTION
                ========================================================= --}}

                <div id="{{ $modalId }}ProgressSection"
                     class="bulk-progress-section">

                    <div class="bulk-progress-title">

                        Upload Progress

                    </div>


                    <div class="progress bulk-progress">

                        <div id="{{ $modalId }}ProgressBar"
                             class="progress-bar bg-primary bulk-progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar"
                             style="width: 0%;">

                            0%

                        </div>

                    </div>


                    <div id="{{ $modalId }}ProgressText"
                         class="mt-2 text-muted">

                        Uploading file...

                    </div>

                </div>


                {{-- ========================================================
                     RESULT SECTION
                ========================================================= --}}

                <div id="{{ $modalId }}Result"
                     class="bulk-result">


                    {{-- SUCCESS SUMMARY --}}

                    <div id="{{ $modalId }}SuccessSummary"
                         class="alert alert-success success-summary">

                    </div>


                    {{-- ERROR SUMMARY --}}

                    <div id="{{ $modalId }}ErrorSummary"
                         class="alert alert-danger error-summary">

                    </div>


                    {{-- ROW-WISE ERRORS --}}

                    <div id="{{ $modalId }}RowErrors"
                         class="row-errors-wrapper">

                        <div class="row-errors-title">

                            <i class="fas fa-exclamation-triangle me-1"></i>

                            Row-wise Errors

                        </div>


                        <div class="table-responsive">

                            <table class="table table-bordered row-errors-table">

                                <thead>

                                    <tr>

                                        <th style="width: 120px;">
                                            Row
                                        </th>

                                        <th>
                                            Error
                                        </th>

                                    </tr>

                                </thead>

                                <tbody id="{{ $modalId }}ErrorRows">

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ============================================================
                 FOOTER
            ============================================================= --}}

            <div class="modal-footer bulk-upload-footer">

                <button type="button"
                        class="btn bulk-cancel-btn"
                        data-bs-dismiss="modal">

                    <i class="fas fa-times me-1"></i>

                    Cancel

                </button>


                <button type="button"
                        id="{{ $modalId }}UploadBtn"
                        class="btn bulk-upload-btn">

                    <i class="fas fa-upload me-1"></i>

                    Upload

                </button>

            </div>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const modal = document.getElementById(@json($modalId));

    if (!modal) {
        return;
    }

    const modalId = @json($modalId);

    const uploadUrl = @json($uploadUrl);

    const fileInput =
        document.getElementById(modalId + 'File');

    const uploadBtn =
        document.getElementById(modalId + 'UploadBtn');

    const columnGuide =
        document.getElementById(modalId + 'ColumnGuide');

    const uploadSection =
        document.getElementById(modalId + 'UploadSection');

    const progressSection =
        document.getElementById(modalId + 'ProgressSection');

    const progressBar =
        document.getElementById(modalId + 'ProgressBar');

    const progressText =
        document.getElementById(modalId + 'ProgressText');

    const resultBox =
        document.getElementById(modalId + 'Result');

    const successSummary =
        document.getElementById(modalId + 'SuccessSummary');

    const errorSummary =
        document.getElementById(modalId + 'ErrorSummary');

    const rowErrors =
        document.getElementById(modalId + 'RowErrors');

    const errorRows =
        document.getElementById(modalId + 'ErrorRows');


    if (
        !fileInput ||
        !uploadBtn ||
        !columnGuide ||
        !uploadSection ||
        !progressSection ||
        !progressBar ||
        !progressText ||
        !resultBox ||
        !successSummary ||
        !errorSummary ||
        !rowErrors ||
        !errorRows
    ) {
        return;
    }


    let xhr = null;


    /*
    |--------------------------------------------------------------------------
    | RESET MODAL
    |--------------------------------------------------------------------------
    */

    function resetModal() {

        if (xhr) {
            try {
                xhr.abort();
            } catch (e) {}
            xhr = null;
        }

        fileInput.value = '';

        columnGuide.style.display = 'block';

        uploadSection.style.display = 'block';

        progressSection.style.display = 'none';

        resultBox.style.display = 'none';

        successSummary.style.display = 'none';

        errorSummary.style.display = 'none';

        rowErrors.style.display = 'none';

        successSummary.innerHTML = '';

        errorSummary.innerHTML = '';

        errorRows.innerHTML = '';

        progressBar.style.width = '0%';

        progressBar.textContent = '0%';

        progressText.textContent = 'Uploading file...';

        uploadBtn.disabled = false;

        uploadBtn.innerHTML =
            '<i class="fas fa-upload me-1"></i> Upload';
    }


    /*
    |--------------------------------------------------------------------------
    | MODAL OPEN
    |--------------------------------------------------------------------------
    */

    modal.addEventListener('show.bs.modal', function () {
        resetModal();
    });


    /*
    |--------------------------------------------------------------------------
    | MODAL CLOSE
    |--------------------------------------------------------------------------
    */

    modal.addEventListener('hidden.bs.modal', function () {
        resetModal();
    });


    /*
    |--------------------------------------------------------------------------
    | FILE SELECTED
    |--------------------------------------------------------------------------
    */

    fileInput.addEventListener('change', function () {

        resultBox.style.display = 'none';

        successSummary.style.display = 'none';

        errorSummary.style.display = 'none';

        rowErrors.style.display = 'none';

        errorRows.innerHTML = '';

    });


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value ?? '';

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE ERROR MESSAGE
    |--------------------------------------------------------------------------
    */

    function normalizeErrorMessages(errorData) {

        let messages = [];


        /*
        | Array:
        |
        | [
        |   "Name is required",
        |   "Mobile is invalid"
        | ]
        */

        if (Array.isArray(errorData)) {

            errorData.forEach(function (item) {

                if (
                    typeof item === 'object' &&
                    item !== null
                ) {

                    if (Array.isArray(item.errors)) {

                        item.errors.forEach(function (error) {
                            messages.push(String(error));
                        });

                    } else if (item.error) {

                        messages.push(String(item.error));

                    } else if (item.message) {

                        messages.push(String(item.message));

                    } else {

                        messages.push(
                            JSON.stringify(item)
                        );
                    }

                } else {

                    messages.push(String(item));

                }

            });

        }

        /*
        | Single string
        */

        else if (
            typeof errorData === 'string'
        ) {

            messages.push(errorData);

        }

        /*
        | Laravel validation object:
        |
        | {
        |   mobile: [
        |      "The mobile field is required."
        |   ]
        | }
        */

        else if (
            typeof errorData === 'object' &&
            errorData !== null
        ) {

            Object.keys(errorData).forEach(function (key) {

                const fieldErrors = errorData[key];

                if (Array.isArray(fieldErrors)) {

                    fieldErrors.forEach(function (error) {

                        messages.push(
                            String(error)
                        );

                    });

                } else {

                    messages.push(
                        String(fieldErrors)
                    );

                }

            });

        }


        return messages;
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW IMPORT RESULT
    |--------------------------------------------------------------------------
    */

    function showImportResult(result) {

        resultBox.style.display = 'block';

        successSummary.style.display = 'none';

        errorSummary.style.display = 'none';

        rowErrors.style.display = 'none';

        errorRows.innerHTML = '';


        /*
        |--------------------------------------------------------------------------
        | RESPONSE VALUES
        |--------------------------------------------------------------------------
        */

        const imported =
            Number(result.imported ?? result.successful ?? 0);

        const total =
            Number(result.total ?? result.totalRows ?? 0);


        let rawErrors =
            result.errors ??
            result.row_errors ??
            result.rowErrors ??
            [];


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE ERROR FORMAT
        |--------------------------------------------------------------------------
        */

        let errors = [];


        /*
        | Format:
        |
        | errors: [
        |   {
        |      row: 2,
        |      errors: [...]
        |   }
        | ]
        */

        if (Array.isArray(rawErrors)) {

            rawErrors.forEach(function (item) {

                if (
                    typeof item === 'object' &&
                    item !== null
                ) {

                    const rowNumber =
                        item.row ??
                        item.row_number ??
                        item.rowNumber ??
                        '-';


                    let messages =
                        item.errors ??
                        item.error ??
                        item.message ??
                        [];


                    messages =
                        normalizeErrorMessages(messages);


                    if (messages.length === 0) {

                        messages.push(
                            'Invalid row data.'
                        );

                    }


                    errors.push({

                        row: rowNumber,

                        errors: messages

                    });

                } else {

                    errors.push({

                        row: '-',

                        errors: [
                            String(item)
                        ]

                    });

                }

            });

        }


        /*
        |--------------------------------------------------------------------------
        | ERROR OBJECT
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | errors: {
        |    2: ["Name is required"],
        |    3: ["Mobile already exists"]
        | }
        |
        */

        else if (
            typeof rawErrors === 'object' &&
            rawErrors !== null
        ) {

            Object.keys(rawErrors).forEach(function (rowKey) {

                let messages =
                    normalizeErrorMessages(
                        rawErrors[rowKey]
                    );


                if (messages.length === 0) {

                    messages.push(
                        'Invalid row data.'
                    );

                }


                errors.push({

                    row: rowKey,

                    errors: messages

                });

            });

        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS SUMMARY
        |--------------------------------------------------------------------------
        */

        if (imported > 0) {

            successSummary.style.display = 'block';

            successSummary.innerHTML =

                '<i class="fas fa-check-circle me-1"></i>' +

                '<strong>' +
                imported +
                '</strong> row(s) imported successfully.' +

                (
                    total > 0
                        ? ' Out of ' + total + ' row(s).'
                        : ''
                );
        }


        /*
        |--------------------------------------------------------------------------
        | ERROR SUMMARY
        |--------------------------------------------------------------------------
        */

        if (errors.length > 0) {

            errorSummary.style.display = 'block';

            errorSummary.innerHTML =

                '<i class="fas fa-circle-exclamation me-1"></i>' +

                '<strong>' +
                errors.length +
                '</strong> row(s) have errors.';


            rowErrors.style.display = 'block';


            /*
            |--------------------------------------------------------------------------
            | SHOW EACH ROW ERROR
            |--------------------------------------------------------------------------
            */

            errors.forEach(function (item) {

                const rowNumber =
                    escapeHtml(item.row);


                const messages =
                    Array.isArray(item.errors)
                        ? item.errors
                        : [item.errors];


                let errorMessage = '';


                messages.forEach(function (message) {

                    errorMessage +=

                        '<div class="mb-1">' +

                        '<i class="fas fa-times-circle me-1"></i>' +

                        escapeHtml(message) +

                        '</div>';

                });


                errorRows.innerHTML += `

                    <tr>

                        <td class="error-row-number">
                            Row ${rowNumber}
                        </td>

                        <td class="text-danger">
                            ${errorMessage}
                        </td>

                    </tr>

                `;

            });

        }


        /*
        |--------------------------------------------------------------------------
        | NOTHING IMPORTED + NO ERRORS
        |--------------------------------------------------------------------------
        */

        if (
            imported === 0 &&
            errors.length === 0
        ) {

            errorSummary.style.display = 'block';

            errorSummary.innerHTML =

                '<i class="fas fa-circle-exclamation me-1"></i>' +

                'No {{ $recordName }} records were imported.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD BUTTON
    |--------------------------------------------------------------------------
    */

    uploadBtn.addEventListener('click', function () {

        /*
        |--------------------------------------------------------------------------
        | NO FILE
        |--------------------------------------------------------------------------
        */

        if (!fileInput.files.length) {

            resultBox.style.display = 'block';

            errorSummary.style.display = 'block';

            errorSummary.innerHTML =

                '<i class="fas fa-circle-exclamation me-1"></i>' +

                'Please select a file first.';

            return;
        }


        const file = fileInput.files[0];


        /*
        |--------------------------------------------------------------------------
        | FILE SIZE
        |--------------------------------------------------------------------------
        */

        if (file.size > 10 * 1024 * 1024) {

            resultBox.style.display = 'block';

            errorSummary.style.display = 'block';

            errorSummary.innerHTML =

                '<i class="fas fa-circle-exclamation me-1"></i>' +

                'File size must not exceed 10 MB.';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FILE TYPE
        |--------------------------------------------------------------------------
        */

        const allowedExtensions = [
            'xlsx',
            'xls',
            'csv'
        ];


        const extension =
            file.name
                .split('.')
                .pop()
                .toLowerCase();


        if (
            !allowedExtensions.includes(extension)
        ) {

            resultBox.style.display = 'block';

            errorSummary.style.display = 'block';

            errorSummary.innerHTML =

                '<i class="fas fa-circle-exclamation me-1"></i>' +

                'Invalid file type. Please upload .xlsx, .xls or .csv file.';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | SHOW PROGRESS
        |--------------------------------------------------------------------------
        */

        columnGuide.style.display = 'none';

        uploadSection.style.display = 'none';

        progressSection.style.display = 'block';

        resultBox.style.display = 'none';

        uploadBtn.disabled = true;

        uploadBtn.innerHTML =
            '<i class="fas fa-spinner fa-spin me-1"></i> Uploading...';


        progressBar.style.width = '0%';

        progressBar.textContent = '0%';

        progressText.textContent =
            'Uploading file...';


        /*
        |--------------------------------------------------------------------------
        | FORM DATA
        |--------------------------------------------------------------------------
        */

        const formData = new FormData();

        formData.append('file', file);


        /*
        |--------------------------------------------------------------------------
        | XHR
        |--------------------------------------------------------------------------
        */

        xhr = new XMLHttpRequest();


        xhr.open(
            'POST',
            uploadUrl,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | CSRF
        |--------------------------------------------------------------------------
        */

        const csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            );


        if (csrfToken) {

            xhr.setRequestHeader(
                'X-CSRF-TOKEN',
                csrfToken.getAttribute('content')
            );

        }


        xhr.setRequestHeader(
            'X-Requested-With',
            'XMLHttpRequest'
        );


        xhr.setRequestHeader(
            'Accept',
            'application/json'
        );


        /*
        |--------------------------------------------------------------------------
        | UPLOAD PROGRESS
        |--------------------------------------------------------------------------
        */

        xhr.upload.addEventListener(
            'progress',
            function (event) {

                if (event.lengthComputable) {

                    const percent =
                        Math.round(
                            (event.loaded / event.total) * 100
                        );


                    progressBar.style.width =
                        percent + '%';


                    progressBar.textContent =
                        percent + '%';

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | REQUEST COMPLETE
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'load',
            function () {

                let response = {};


                try {

                    response =
                        JSON.parse(
                            xhr.responseText
                        );

                } catch (error) {

                    response = {

                        success: false,

                        message:
                            'Invalid server response.'

                    };

                }


                /*
                |--------------------------------------------------------------------------
                | UPLOAD COMPLETE
                |--------------------------------------------------------------------------
                */

                progressBar.style.width =
                    '100%';

                progressBar.textContent =
                    '100%';


                progressSection.style.display =
                    'block';


                /*
                |--------------------------------------------------------------------------
                | SUCCESS / PARTIAL SUCCESS
                |--------------------------------------------------------------------------
                */

                if (
                    xhr.status >= 200 &&
                    xhr.status < 300
                ) {

                    progressText.textContent =
                        'Import completed.';


                    uploadBtn.innerHTML =
                   '<i class="fas fa-upload me-1"></i> Upload';


                    showImportResult(response);


                    /*
                    |--------------------------------------------------------------------------
                    | REFRESH DATATABLE
                    |--------------------------------------------------------------------------
                    |
                    | Reload current page after result is shown.
                    | This is intentionally delayed so user can see errors.
                    |
                    */

                    setTimeout(function () {

                        if (
                            typeof window.jQuery !== 'undefined'
                        ) {

                            const tables =
                                document.querySelectorAll(
                                    'table.dataTable'
                                );


                            tables.forEach(function (table) {

                                try {

                                    if (
                                        $.fn.DataTable.isDataTable(table)
                                    ) {

                                        $(table)
                                            .DataTable()
                                            .ajax
                                            .reload(
                                                null,
                                                false
                                            );

                                    }

                                } catch (e) {}

                            });

                        }

                    }, 1500);


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | SERVER ERROR / VALIDATION ERROR
                |--------------------------------------------------------------------------
                */

                progressText.textContent =
                    'Import failed.';


                uploadBtn.innerHTML =
                    '<i class="fas fa-upload me-1"></i> Upload';


                resultBox.style.display =
                    'block';


                errorSummary.style.display =
                    'block';


                let serverMessage =
                    response.message ||
                    'Something went wrong while uploading.';


                /*
                | Laravel ValidationException
                */

                if (
                    response.errors &&
                    typeof response.errors === 'object' &&
                    !Array.isArray(response.errors)
                ) {

                    const validationMessages =
                        normalizeErrorMessages(
                            response.errors
                        );


                    if (
                        validationMessages.length > 0
                    ) {

                        serverMessage =
                            validationMessages.join('<br>');

                    }

                }


                errorSummary.innerHTML =

                    '<i class="fas fa-circle-exclamation me-1"></i>' +

                    serverMessage;

            }
        );


        /*
        |--------------------------------------------------------------------------
        | NETWORK ERROR
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'error',
            function () {

                progressSection.style.display =
                    'block';


                progressText.textContent =
                    'Upload failed.';


                uploadBtn.innerHTML =
                    '<i class="fas fa-upload me-1"></i> Upload';


                resultBox.style.display =
                    'block';


                errorSummary.style.display =
                    'block';


                errorSummary.innerHTML =

                    '<i class="fas fa-circle-exclamation me-1"></i>' +

                    'Network error occurred while uploading the file.';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | UPLOAD CANCELLED
        |--------------------------------------------------------------------------
        */

        xhr.addEventListener(
            'abort',
            function () {

                progressSection.style.display =
                    'none';


                uploadBtn.disabled = false;

                uploadBtn.innerHTML =
                    '<i class="fas fa-upload me-1"></i> Upload';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SEND
        |--------------------------------------------------------------------------
        */

        xhr.send(formData);

    });

});
</script>