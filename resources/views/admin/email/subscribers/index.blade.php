@extends('admin.layout')

@section('title', __('Subscribers'))
@section('page-title', __('Email Subscribers'))

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ __('All Subscribers') }}</h5>
                <small class="text-muted">{{ number_format($subscribers->total()) }} {{ __('subscribers found') }}</small>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.email.subscribers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('Add Subscriber') }}
                </a>
                <a href="{{ route('admin.email.subscribers.import') }}" class="btn btn-success">
                    <i class="fas fa-file-import"></i> {{ __('Import') }}
                </a>
                <a href="{{ route('admin.email.subscribers.export') }}" class="btn btn-info">
                    <i class="fas fa-download"></i> {{ __('Export') }}
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.email.subscribers.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" 
                               placeholder="{{ __('Search email, name...') }}" 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="subscribed" {{ request('status') == 'subscribed' ? 'selected' : '' }}>
                                {{ __('Subscribed') }}
                            </option>
                            <option value="unsubscribed" {{ request('status') == 'unsubscribed' ? 'selected' : '' }}>
                                {{ __('Unsubscribed') }}
                            </option>
                            <option value="bounced" {{ request('status') == 'bounced' ? 'selected' : '' }}>
                                {{ __('Bounced') }}
                            </option>
                            <option value="complained" {{ request('status') == 'complained' ? 'selected' : '' }}>
                                {{ __('Complained') }}
                            </option>
                            <option value="suppressed" {{ request('status') == 'suppressed' ? 'selected' : '' }}>
                                {{ __('Suppressed') }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="country" class="form-control">
                            <option value="">{{ __('All Countries') }}</option>
                            @foreach($countries as $code => $name)
                            <option value="{{ $code }}" {{ request('country') == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="group_id" class="form-control">
                            <option value="">{{ __('All Groups') }}</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> {{ __('Filter') }}
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.email.subscribers.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>

            <!-- Subscribers Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Groups') }}</th>
                            <th>{{ __('Engagement') }}</th>
                            <th>{{ __('Last Activity') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscribers as $subscriber)
                        <tr>
                            <td>
                                <input type="checkbox" class="subscriber-checkbox" 
                                       value="{{ $subscriber->id }}" data-email="{{ $subscriber->email }}">
                            </td>
                            <td>
                                <strong>{{ $subscriber->email }}</strong>
                                @if($subscriber->email_verified_at)
                                <br><small class="text-success"><i class="fas fa-check-circle"></i> Verified</small>
                                @endif
                            </td>
                            <td>
                                {{ $subscriber->full_name ?: 'N/A' }}
                                @if($subscriber->job_title)
                                <br><small class="text-muted">{{ $subscriber->job_title }}</small>
                                @endif
                            </td>
                            <td>{{ $subscriber->company_name ?: '—' }}</td>
                            <td>
                                @if($subscriber->country_code)
                                <span class="flag-icon flag-icon-{{ strtolower($subscriber->country_code) }}"></span>
                                {{ $subscriber->country_code }}
                                @else
                                —
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $subscriber->status_color }}">
                                    {{ ucfirst($subscriber->status) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ str_replace('_', ' ', $subscriber->subscriber_type) }}</small>
                            </td>
                            <td>
                                @foreach($subscriber->groups->take(2) as $group)
                                <span class="badge badge-secondary">{{ $group->name }}</span>
                                @endforeach
                                @if($subscriber->groups->count() > 2)
                                <small class="text-muted">+{{ $subscriber->groups->count() - 2 }}</small>
                                @endif
                            </td>
                            <td>
                                @if($subscriber->engagement_score >= 80)
                                <span class="text-success"><i class="fas fa-fire"></i> High</span>
                                @elseif($subscriber->engagement_score >= 50)
                                <span class="text-warning"><i class="fas fa-minus"></i> Medium</span>
                                @else
                                <span class="text-muted"><i class="fas fa-snowflake"></i> Low</span>
                                @endif
                            </td>
                            <td>
                                @if($subscriber->last_clicked_at)
                                <small title="{{ __('Last Click') }}">
                                    <i class="fas fa-mouse-pointer"></i> {{ $subscriber->last_clicked_at->diffForHumans() }}
                                </small>
                                @elseif($subscriber->last_opened_at)
                                <small title="{{ __('Last Open') }}">
                                    <i class="fas fa-envelope-open"></i> {{ $subscriber->last_opened_at->diffForHumans() }}
                                </small>
                                @elseif($subscriber->last_email_sent_at)
                                <small title="{{ __('Last Sent') }}">
                                    <i class="fas fa-paper-plane"></i> {{ $subscriber->last_email_sent_at->diffForHumans() }}
                                </small>
                                @else
                                <small class="text-muted">Never</small>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.email.subscribers.show', $subscriber) }}" 
                                       class="btn btn-outline-primary" title="{{ __('View') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.email.subscribers.edit', $subscriber) }}" 
                                       class="btn btn-outline-secondary" title="{{ __('Edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-dark dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" 
                                                   onclick="event.preventDefault(); document.getElementById('unsubscribe-form-{{ $subscriber->id }}').submit();">
                                                    <i class="fas fa-ban"></i> {{ __('Unsubscribe') }}
                                                </a>
                                                <form id="unsubscribe-form-{{ $subscriber->id }}" 
                                                      action="{{ route('admin.email.subscribers.unsubscribe', $subscriber) }}" 
                                                      method="POST" style="display:none;">
                                                    @csrf
                                                    @method('PATCH')
                                                </form>
                                            </li>
                                            @can('email.subscribers.delete')
                                            <li>
                                                <a class="dropdown-item text-danger" href="#"
                                                   onclick="confirmDelete({{ $subscriber->id }})">
                                                    <i class="fas fa-trash"></i> {{ __('Delete') }}
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">{{ __('No subscribers found') }}</p>
                                <a href="{{ route('admin.email.subscribers.create') }}" class="btn btn-primary">
                                    {{ __('Add Your First Subscriber') }}
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <form method="POST" action="{{ route('admin.email.subscribers.bulk-action') }}" id="bulkActionForm">
                        @csrf
                        <input type="hidden" name="action" id="bulkAction">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                    onclick="setBulkAction('unsubscribe')" disabled id="bulkUnsubscribe">
                                {{ __('Unsubscribe Selected') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="setBulkAction('delete')" disabled id="bulkDelete">
                                {{ __('Delete Selected') }}
                            </button>
                        </div>
                    </form>
                </div>
                <div>
                    {{ $subscribers->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.subscriber-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
    updateBulkButtons();
});

// Individual checkbox change
document.querySelectorAll('.subscriber-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkButtons);
});

function updateBulkButtons() {
    const checked = document.querySelectorAll('.subscriber-checkbox:checked').length;
    document.getElementById('bulkUnsubscribe').disabled = checked === 0;
    document.getElementById('bulkDelete').disabled = checked === 0;
}

function setBulkAction(action) {
    if (!confirm(`Are you sure you want to ${action} selected subscribers?`)) return;
    
    const checked = document.querySelectorAll('.subscriber-checkbox:checked');
    if (checked.length === 0) return;
    
    const form = document.getElementById('bulkActionForm');
    document.getElementById('bulkAction').value = action;
    
    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });
    
    form.submit();
}

function confirmDelete(id) {
    if (confirm('{{ __("Are you sure you want to delete this subscriber? This action cannot be undone.") }}')) {
        // Submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('admin.email.subscribers.index') }}/${id}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
