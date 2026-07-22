@extends('layouts.admin')

@section('title', __('Import Subscribers'))
@section('page-title', __('Bulk Import Subscribers'))

@section('content')
<div class="container-fluid">
    <form id="importForm" enctype="multipart/form-data">
        @csrf
        
        <!-- Progress Steps -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="wizard-steps">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">{{ __('Upload') }}</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">{{ __('Preview') }}</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">{{ __('Map Fields') }}</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">{{ __('Configure') }}</div>
                    </div>
                    <div class="step" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-label">{{ __('Validate') }}</div>
                    </div>
                    <div class="step" data-step="6">
                        <div class="step-number">6</div>
                        <div class="step-label">{{ __('Confirm') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Step 1: Upload -->
                <div class="wizard-content" data-step="1">
                    <h5>{{ __('Upload File') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-8 offset-md-2 text-center py-5">
                            <div class="upload-area border-2 border-dashed rounded p-5" id="dropZone">
                                <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                <h4>{{ __('Drag & drop your file here') }}</h4>
                                <p class="text-muted">{{ __('or click to browse') }}</p>
                                <input type="file" name="file" id="fileInput" accept=".csv,.xlsx,.xls" class="d-none" required>
                                <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                    {{ __('Choose File') }}
                                </button>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        {{ __('Supported formats: CSV, XLS, XLSX') }}<br>
                                        {{ __('Maximum size: 50MB | Maximum rows: 500,000') }}
                                    </small>
                                </div>
                            </div>
                            
                            <div id="fileInfo" class="mt-3 d-none">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>{{ __('File selected:') }}</strong>
                                    <span id="fileName"></span>
                                    (<span id="fileSize"></span>)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb"></i> {{ __('Tips for best results') }}</h6>
                                <ul class="mb-0">
                                    <li>{{ __('First row should contain column headers') }}</li>
                                    <li>{{ __('Email addresses must be in a dedicated column') }}</li>
                                    <li>{{ __('Use ISO country codes (NP, IN, BD, etc.)') }}</li>
                                    <li>{{ __('Dates should be in YYYY-MM-DD format') }}</li>
                                    <li>{{ __('Remove any special characters from data') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Preview -->
                <div class="wizard-content d-none" data-step="2">
                    <h5>{{ __('Preview Data') }}</h5>
                    <hr>
                    
                    <div id="sheetSelector" class="mb-3 d-none">
                        <label>{{ __('Select Sheet (Excel files only)') }}</label>
                        <select name="sheet" id="sheetSelect" class="form-control w-25 d-inline-block">
                            <!-- Populated dynamically -->
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="previewTable">
                            <thead class="thead-light">
                                <!-- Populated dynamically -->
                            </thead>
                            <tbody>
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        {{ __('Showing first 10 rows. Total rows detected: ') }}
                        <strong id="totalRows">0</strong>
                    </div>
                </div>

                <!-- Step 3: Map Fields -->
                <div class="wizard-content d-none" data-step="3">
                    <h5>{{ __('Map Columns to Fields') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th width="30%">{{ __('File Column') }}</th>
                                            <th width="5%"></th>
                                            <th width="30%">{{ __('NeoGiga Field') }}</th>
                                            <th width="35%">{{ __('Sample Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mappingContainer">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="autoMapFields()">
                                <i class="fas fa-magic"></i> {{ __('Auto-Match Fields') }}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="saveMapping()">
                                <i class="fas fa-save"></i> {{ __('Save as Template') }}
                            </button>
                            <select id="loadMapping" class="form-control d-inline-block w-auto">
                                <option value="">{{ __('Load Saved Template...') }}</option>
                                <!-- Populated from saved mappings -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Configure -->
                <div class="wizard-content d-none" data-step="4">
                    <h5>{{ __('Import Configuration') }}</h5>
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>{{ __('Group Assignment') }}</h6>
                            <div class="form-group">
                                <label>{{ __('Primary Group') }}</label>
                                <select name="group_assignment" id="groupAssignment" class="form-control">
                                    <option value="auto">{{ __('Auto-assign by Country') }}</option>
                                    <option value="specific">{{ __('Assign to Specific Group') }}</option>
                                    <option value="keep">{{ __('Keep Existing Assignments') }}</option>
                                </select>
                            </div>
                            <div class="form-group d-none" id="specificGroupField">
                                <label>{{ __('Select Group') }}</label>
                                <select name="group_id" class="form-control">
                                    @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6>{{ __('Subscriber Settings') }}</h6>
                            <div class="form-group">
                                <label for="subscriber_type">{{ __('Subscriber Type') }}</label>
                                <select name="subscriber_type" id="subscriber_type" class="form-control">
                                    <option value="newsletter_subscriber">{{ __('Newsletter Subscriber') }}</option>
                                    <option value="personal_customer">{{ __('Personal Customer') }}</option>
                                    <option value="institutional_customer">{{ __('Institutional Customer') }}</option>
                                    <option value="lead">{{ __('Lead') }}</option>
                                    <option value="reseller">{{ __('Reseller') }}</option>
                                    <option value="distributor">{{ __('Distributor') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="source">{{ __('Source') }}</label>
                                <input type="text" name="source" id="source" class="form-control" 
                                       value="bulk_import" readonly>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6>{{ __('Duplicate Handling') }}</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="duplicate_action">{{ __('If email already exists') }}</label>
                                <select name="duplicate_action" id="duplicate_action" class="form-control">
                                    <option value="skip">{{ __('Skip - Keep existing record') }}</option>
                                    <option value="update">{{ __('Update - Overwrite with new data') }}</option>
                                    <option value="merge">{{ __('Merge - Fill empty fields only') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Options') }}</label>
                                <div class="form-check">
                                    <input type="checkbox" name="skip_unsubscribed" id="skip_unsubscribed" 
                                           value="1" class="form-check-input" checked>
                                    <label for="skip_unsubscribed" class="form-check-label">
                                        {{ __('Do not reactivate unsubscribed contacts') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="skip_suppressed" id="skip_suppressed" 
                                           value="1" class="form-check-input" checked>
                                    <label for="skip_suppressed" class="form-check-label">
                                        {{ __('Skip suppressed contacts') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="validate_email" id="validate_email" 
                                           value="1" class="form-check-input" checked>
                                    <label for="validate_email" class="form-check-label">
                                        {{ __('Validate email syntax') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="validate_mx" id="validate_mx" 
                                           value="1" class="form-check-input">
                                    <label for="validate_mx" class="form-check-label">
                                        {{ __('Check MX records (slower)') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Validate -->
                <div class="wizard-content d-none" data-step="5">
                    <h5>{{ __('Validation Results') }}</h5>
                    <hr>
                    
                    <div id="validationProgress" class="mb-4">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted">{{ __('Validating data...') }}</small>
                    </div>

                    <div id="validationResults" class="d-none">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 id="validCount">0</h3>
                                        <small>{{ __('Valid Rows') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3 id="duplicateCount">0</h3>
                                        <small>{{ __('Duplicates') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h3 id="invalidCount">0</h3>
                                        <small>{{ __('Invalid Emails') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 id="toImportCount">0</h3>
                                        <small>{{ __('Ready to Import') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <ul class="nav nav-tabs" id="errorTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#invalidTab">
                                        {{ __('Invalid Emails') }} (<span id="invalidTabCount">0</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#duplicateTab">
                                        {{ __('Duplicates') }} (<span id="duplicateTabCount">0</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#missingTab">
                                        {{ __('Missing Data') }} (<span id="missingTabCount">0</span>)
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content border border-top-0 p-3">
                                <div class="tab-pane fade show active" id="invalidTab">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm" id="invalidTable">
                                            <!-- Populated dynamically -->
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="duplicateTab">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm" id="duplicateTable">
                                            <!-- Populated dynamically -->
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="missingTab">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm" id="missingTable">
                                            <!-- Populated dynamically -->
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>{{ __('Note:') }}</strong>
                            {{ __('Only valid, non-duplicate rows will be imported. You can download detailed error reports after import.') }}
                        </div>
                    </div>
                </div>

                <!-- Step 6: Confirm -->
                <div class="wizard-content d-none" data-step="6">
                    <h5>{{ __('Confirm Import') }}</h5>
                    <hr>
                    
                    <div class="import-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>{{ __('Import Summary') }}</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">{{ __('File:') }}</th>
                                        <td><span id="summaryFileName"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Total Rows:') }}</th>
                                        <td><span id="summaryTotalRows"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Valid Rows:') }}</th>
                                        <td class="text-success"><span id="summaryValidRows"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('To Import:') }}</th>
                                        <td class="text-primary font-weight-bold"><span id="summaryToImport"></span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>{{ __('Configuration') }}</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th width="40%">{{ __('Group Assignment:') }}</th>
                                        <td><span id="summaryGroup"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Subscriber Type:') }}</th>
                                        <td><span id="summaryType"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Duplicate Action:') }}</th>
                                        <td><span id="summaryDuplicate"></span></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Email Validation:') }}</th>
                                        <td><span id="summaryValidation"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i>
                            <strong>{{ __('Processing Time Estimate:') }}</strong>
                            <span id="timeEstimate"></span>
                            <br>
                            <small>{{ __('Large imports are processed asynchronously. You will receive an email when complete.') }}</small>
                        </div>

                        <div class="form-check mt-3">
                            <input type="checkbox" name="confirm_import" id="confirm_import" value="1" 
                                   class="form-check-input" required>
                            <label for="confirm_import" class="form-check-label">
                                {{ __('I confirm the data is accurate and I have permission to import these contacts') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" id="prevBtn" disabled>
                    <i class="fas fa-arrow-left"></i> {{ __('Previous') }}
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn">
                    {{ __('Next') }} <i class="fas fa-arrow-right"></i>
                </button>
                <button type="submit" class="btn btn-success d-none" id="importBtn" disabled>
                    <i class="fas fa-download"></i> {{ __('Start Import') }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
    padding: 1rem;
}
.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 30px;
    left: 60%;
    width: 80%;
    height: 2px;
    background: #e9ecef;
}
.step.active:not(:last-child)::after {
    background: #4e73df;
}
.step-number {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: bold;
    font-size: 1.2rem;
}
.step.active .step-number {
    background: #4e73df;
    color: white;
}
.step.completed .step-number {
    background: #1cc88a;
    color: white;
}
.wizard-content {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.upload-area {
    cursor: pointer;
    transition: all 0.3s;
}
.upload-area:hover, .upload-area.dragover {
    background: #f8f9fa;
    border-color: #4e73df !important;
}
</style>
@endpush

@push('scripts')
<script>
let currentStep = 1;
const totalSteps = 6;
let fileData = null;
let previewData = null;
let validationResults = null;

// File upload handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    const validTypes = ['text/csv', 'application/vnd.ms-excel', 
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!validTypes.includes(file.type) && !file.name.match(/\.(csv|xlsx|xls)$/)) {
        alert('{{ __("Invalid file type. Please upload CSV or Excel file.") }}');
        return;
    }
    
    fileData = file;
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('fileInfo').classList.remove('d-none');
    
    // In production: upload file and get preview
    simulatePreview();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function simulatePreview() {
    // Simulate preview data - in production this comes from server
    previewData = {
        sheets: ['Sheet1'],
        headers: ['email', 'first_name', 'last_name', 'company', 'country'],
        rows: [
            ['john@example.com', 'John', 'Doe', 'Acme Corp', 'NP'],
            ['jane@example.com', 'Jane', 'Smith', 'Tech Ltd', 'IN'],
            ['bob@example.com', 'Bob', 'Wilson', 'Global Inc', 'BD']
        ],
        totalRows: 150
    };
    
    renderPreview();
    goToStep(2);
}

function renderPreview() {
    const table = document.getElementById('previewTable');
    let thead = '<tr>';
    previewData.headers.forEach(h => {
        thead += `<th>${h}</th>`;
    });
    thead += '</tr>';
    table.querySelector('thead').innerHTML = thead;
    
    let tbody = '';
    previewData.rows.slice(0, 10).forEach(row => {
        tbody += '<tr>';
        row.forEach(cell => {
            tbody += `<td>${cell}</td>`;
        });
        tbody += '</tr>';
    });
    table.querySelector('tbody').innerHTML = tbody;
    
    document.getElementById('totalRows').textContent = previewData.totalRows;
}

function autoMapFields() {
    // Auto-match common field names
    const mappings = {
        'email': 'email',
        'first_name': 'first_name',
        'firstname': 'first_name',
        'first name': 'first_name',
        'last_name': 'last_name',
        'lastname': 'last_name',
        'last name': 'last_name',
        'company': 'company_name',
        'company_name': 'company_name',
        'country': 'country_code',
        'country_code': 'country_code',
        'phone': 'phone',
        'city': 'city'
    };
    
    // Apply mappings
    console.log('Auto-mapping fields');
}

// Navigation
$('#nextBtn').click(function() {
    if (currentStep < totalSteps && validateStep(currentStep)) {
        goToStep(currentStep + 1);
    }
});

$('#prevBtn').click(function() {
    if (currentStep > 1) {
        goToStep(currentStep - 1);
    }
});

function goToStep(step) {
    $('.wizard-content').addClass('d-none');
    $(`.wizard-content[data-step="${step}"]`).removeClass('d-none');
    
    $('.step').removeClass('active completed');
    for (let i = 1; i < step; i++) {
        $(`.step[data-step="${i}\"]`).addClass('completed');
    }
    $(`.step[data-step="${step}\"]`).addClass('active');
    
    currentStep = step;
    $('#prevBtn').prop('disabled', step === 1);
    $('#nextBtn').toggleClass('d-none', step === totalSteps);
    $('#importBtn').toggleClass('d-none', step !== totalSteps);
    
    if (step === 5) {
        runValidation();
    }
    if (step === 6) {
        updateSummary();
    }
}

function validateStep(step) {
    if (step === 1 && !fileData) {
        alert('{{ __("Please select a file") }}');
        return false;
    }
    return true;
}

function runValidation() {
    // Simulate validation - in production this is an AJAX call
    setTimeout(() => {
        validationResults = {
            valid: 145,
            duplicates: 3,
            invalid: 2,
            toImport: 145
        };
        
        document.getElementById('validCount').textContent = validationResults.valid;
        document.getElementById('duplicateCount').textContent = validationResults.duplicates;
        document.getElementById('invalidCount').textContent = validationResults.invalid;
        document.getElementById('toImportCount').textContent = validationResults.toImport;
        
        document.getElementById('validationProgress').classList.add('d-none');
        document.getElementById('validationResults').classList.remove('d-none');
    }, 1500);
}

function updateSummary() {
    document.getElementById('summaryFileName').textContent = fileData?.name || '';
    document.getElementById('summaryTotalRows').textContent = previewData?.totalRows || 0;
    document.getElementById('summaryValidRows').textContent = validationResults?.valid || 0;
    document.getElementById('summaryToImport').textContent = validationResults?.toImport || 0;
    
    const timeEst = Math.ceil((validationResults?.toImport || 0) / 100) * 2;
    document.getElementById('timeEstimate').textContent = `${timeEst} minutes`;
}

// Form submission
$('#importForm').submit(function(e) {
    e.preventDefault();
    
    if (!$('#confirm_import').is(':checked')) {
        alert('{{ __("Please confirm the import") }}');
        return;
    }
    
    // In production: submit via AJAX and redirect to status page
    if (confirm('{{ __("Start import? This will be processed in the background.") }}')) {
        // Submit form
        alert('{{ __("Import started! You will be notified when complete.") }}');
        window.location.href = '{{ route("admin.email.subscribers.index") }}';
    }
});

// Group assignment toggle
$('#groupAssignment').change(function() {
    $('#specificGroupField').toggleClass('d-none', this.value !== 'specific');
});
</script>
@endpush
@endsection
