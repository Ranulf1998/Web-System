<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Logs') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('accountability.index') }}" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label for="staff_id" class="block text-sm font-medium text-gray-700">Staff Filter</label>
                            <select id="staff_id" name="staff_id" class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Staff</option>
                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}" @selected((int) $staffId === $member->id)>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="action" class="block text-sm font-medium text-gray-700">Action Filter</label>
                            <select id="action" name="action" class="mt-1 block w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Actions</option>
                                @foreach ($actions as $actionOption)
                                    <option value="{{ $actionOption }}" @selected($action === $actionOption)>
                                        {{ $actionOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Apply
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Staff Performance</h3>
                    <div class="overflow-x-auto overflow-y-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sales</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($staff as $member)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $member->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $member->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $member->orders_count }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) ($member->orders_sum_total ?? 0), 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No staff found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Staff Activity</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($activities as $activity)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->created_at?->setTimezone('Asia/Manila')?->format('M j, Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->created_at?->setTimezone('Asia/Manila')?->format('g:i A') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $activity->user?->name ?? 'System' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->action }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $activity->description }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No activity yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $activities->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
