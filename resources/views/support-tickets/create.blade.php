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
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Subject</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Resolved</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($tickets as $ticket)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $ticket->subject }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ ucfirst($ticket->status) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $ticket->created_at?->format('M j, Y g:i A') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $ticket->resolved_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-sm text-gray-500 text-center">No tickets yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
