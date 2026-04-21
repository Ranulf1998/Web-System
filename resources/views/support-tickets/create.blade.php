<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Support Tickets') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <div class="font-semibold">Please fix the following:</div>
                    <ul class="list-disc ml-5 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800">Send a ticket to central owner</h3>
                    <p class="mt-1 text-sm text-gray-600">Use this for subscription, billing, or platform support concerns.</p>

                    <form method="POST" action="{{ route('support-tickets.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <input
                                type="text"
                                name="subject"
                                value="{{ old('subject') }}"
                                maxlength="150"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Short summary of your issue"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea
                                name="message"
                                rows="6"
                                maxlength="3000"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="Describe your issue in detail"
                            >{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                            Send Ticket
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Tickets</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($tickets as $ticket)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition cursor-pointer" data-ticket-id="{{ $ticket->id }}" onclick="openTicketDetail({{ json_encode($ticket) }})">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-800">{{ $ticket->subject }}</h4>
                                        <p class="mt-1 text-xs text-gray-600">
                                            Created {{ $ticket->created_at?->format('M j, Y g:i A') }}
                                        </p>
                                        @if ($ticket->resolution_note)
                                            <p class="mt-2 text-xs text-emerald-600"><strong>Admin Response:</strong> {{ \Illuminate\Support\Str::limit($ticket->resolution_note, 100) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                            @if ($ticket->status === 'open') bg-amber-100 text-amber-800
                                            @elseif ($ticket->status === 'in_progress') bg-blue-100 text-blue-800
                                            @else bg-emerald-100 text-emerald-800
                                            @endif
                                        ">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                        @if ($ticket->resolved_at)
                                            <span class="text-xs text-gray-500">Resolved {{ $ticket->resolved_at?->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-sm text-gray-500">
                                No tickets yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <dialog id="ticket-detail-modal" class="w-full max-w-2xl rounded-lg border border-gray-200 p-0 backdrop:bg-black/50">
        <div class="bg-white rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Ticket Details</h2>
                <button type="button" onclick="closeTicketDetail()" class="text-gray-500 hover:text-gray-700">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="text-xs font-semibold uppercase text-gray-500">Subject</label>
                    <p id="modal-subject" class="mt-1 text-sm font-medium text-gray-900"></p>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase text-gray-500">Status</label>
                    <p id="modal-status" class="mt-1 text-sm font-medium text-gray-900"></p>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase text-gray-500">Your Message</label>
                    <div id="modal-message" class="mt-2 rounded-md bg-gray-50 p-3 text-sm text-gray-700 whitespace-pre-wrap break-words max-h-48 overflow-y-auto border border-gray-200"></div>
                </div>

                <div id="admin-response-section" class="hidden pt-4 border-t border-gray-200">
                    <label class="text-xs font-semibold uppercase text-emerald-600">Admin Response</label>
                    <div id="modal-resolution-note" class="mt-2 rounded-md bg-emerald-50 p-3 text-sm text-emerald-900 whitespace-pre-wrap break-words max-h-48 overflow-y-auto border border-emerald-200"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 text-xs text-gray-500">
                    <div>
                        <span class="font-semibold">Created:</span>
                        <p id="modal-created" class="mt-1"></p>
                    </div>
                    <div>
                        <span class="font-semibold">Resolved:</span>
                        <p id="modal-resolved" class="mt-1"></p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeTicketDetail()" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Close
                </button>
            </div>
        </div>
    </dialog>

    <script>
        function openTicketDetail(ticket) {
            document.getElementById('modal-subject').textContent = ticket.subject;
            document.getElementById('modal-status').textContent = ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1).replace('_', ' ');
            document.getElementById('modal-message').textContent = ticket.message;
            document.getElementById('modal-created').textContent = new Date(ticket.created_at).toLocaleString();
            document.getElementById('modal-resolved').textContent = ticket.resolved_at ? new Date(ticket.resolved_at).toLocaleString() : '—';

            const adminResponseSection = document.getElementById('admin-response-section');
            if (ticket.resolution_note) {
                document.getElementById('modal-resolution-note').textContent = ticket.resolution_note;
                adminResponseSection.classList.remove('hidden');
            } else {
                adminResponseSection.classList.add('hidden');
            }

            document.getElementById('ticket-detail-modal').showModal();
        }

        function closeTicketDetail() {
            document.getElementById('ticket-detail-modal').close();
        }

        // Close modal when clicking outside
        document.getElementById('ticket-detail-modal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                this.close();
            }
        });
    </script>
</x-app-layout>
