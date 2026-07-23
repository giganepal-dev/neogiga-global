@extends('admin.layout')

@section('title', 'Chat - NeoGiga Admin')

@section('content')
<div class="container-fluid" id="chat-app">
    <div class="row h-100 g-0">
        <!-- Sidebar -->
        <div class="col-md-4 col-lg-3 border-end bg-white">
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Messages</h5>
                    <span class="badge bg-primary" id="unread-badge">{{ $unreadCount }}</span>
                </div>
                
                <!-- Search -->
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="chat-search" placeholder="Search conversations...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <!-- New Conversation Button -->
                <button class="btn btn-primary btn-sm w-100 mt-2" data-bs-toggle="modal" data-bs-target="#newConversationModal">
                    <i class="bi bi-plus-circle"></i> New Conversation
                </button>
            </div>

            <!-- Filters -->
            <div class="p-2 border-bottom">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="direct">Direct</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="support">Support</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="group">Groups</button>
                </div>
            </div>

            <!-- Conversations List -->
            <div class="overflow-auto" style="height: calc(100vh - 250px);" id="conversations-list">
                @forelse($conversations as $participant)
                    @php
                        $conversation = $participant->conversation;
                        $otherParticipant = $conversation->participants->firstWhere('user_id', '!=', auth()->id());
                        $lastMessage = $conversation->latestMessage;
                    @endphp
                    <a href="{{ route('admin.chat.show', $conversation->uuid) }}" 
                       class="list-group-item list-group-item-action p-3 conversation-item {{ request()->route('conversation') == $conversation->uuid ? 'active' : '' }}"
                       data-conversation-id="{{ $conversation->id }}">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-shrink-0">
                                @if($conversation->type === 'direct' && $otherParticipant)
                                    <img src="{{ $otherParticipant->user->profile_photo_url ?? '/images/avatar.png' }}" 
                                         alt="{{ $otherParticipant->user->name }}" 
                                         class="rounded-circle" width="40" height="40">
                                @elseif($conversation->type === 'support')
                                    <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-life-preserver"></i>
                                    </div>
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-people"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-truncate" style="max-width: 150px;">
                                        @if($conversation->subject)
                                            {{ $conversation->subject }}
                                        @elseif($conversation->type === 'direct' && $otherParticipant)
                                            {{ $otherParticipant->user->name }}
                                        @elseif($conversation->type === 'support')
                                            Support Ticket
                                        @else
                                            Group Chat
                                        @endif
                                    </h6>
                                    <small class="text-muted">{{ $conversation->last_message_at?->diffForHumans() }}</small>
                                </div>
                                <p class="mb-0 text-muted text-truncate small" style="max-width: 180px;">
                                    @if($lastMessage)
                                        @if($lastMessage->is_deleted)
                                            <em>Message deleted</em>
                                        @else
                                            {{ Str::limit($lastMessage->body, 40) }}
                                        @endif
                                    @endif
                                </p>
                                @if($participant->unread_count > 0)
                                    <span class="badge bg-primary rounded-pill">{{ $participant->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-square-text display-4"></i>
                        <p class="mt-3">No conversations yet</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="col-md-8 col-lg-9">
            <div class="d-flex flex-column h-100">
                <!-- Welcome Message -->
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <div class="text-center">
                        <i class="bi bi-chat-dots display-1"></i>
                        <h4 class="mt-3">Welcome to NeoGiga Chat</h4>
                        <p>Select a conversation from the sidebar or start a new one</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConversationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#direct-tab">Direct Message</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#support-tab">Support Ticket</button>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <!-- Direct Message Tab -->
                    <div class="tab-pane fade show active" id="direct-tab">
                        <form action="{{ route('admin.chat.direct.create') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="recipient_id" class="form-label">Recipient</label>
                                <select class="form-select" name="recipient_id" id="recipient_id" required>
                                    <option value="">Select a user...</option>
                                    @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                        @if($user->id !== auth()->id())
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Start Conversation</button>
                        </form>
                    </div>
                    <!-- Support Ticket Tab -->
                    <div class="tab-pane fade" id="support-tab">
                        <form action="{{ route('admin.chat.support.create') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="support_subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" id="support_subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="support_category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="support_category" required>
                                    <option value="support">Technical Support</option>
                                    <option value="sales">Sales Inquiry</option>
                                    <option value="billing">Billing</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="support_priority" class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="support_priority">
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="support_message" class="form-label">Message</label>
                                <textarea class="form-control" name="message" id="support_message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Ticket</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $csp_nonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('chat-search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                fetch(`{{ route('admin.chat.search') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        // Update conversations list with search results
                        console.log('Search results:', data.conversations);
                    });
            }, 300);
        }
    });

    // Filter buttons
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            // Implement filtering logic here
        });
    });

    // Poll for new messages every 30 seconds
    setInterval(() => {
        fetch(`{{ route('admin.chat.unread') }}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('unread-badge').textContent = data.count;
            });
    }, 30000);
});
</script>
@endpush

@push('styles')
<style nonce="{{ $csp_nonce ?? '' }}">
    .conversation-item:hover {
        background-color: #f8f9fa;
    }
    .conversation-item.active {
        background-color: #0d6efd;
        color: white;
    }
    .conversation-item.active .text-muted {
        color: rgba(255,255,255,0.7) !important;
    }
</style>
@endpush
@endsection
