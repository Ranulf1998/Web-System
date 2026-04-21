<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id !== null, 403);
        abort_unless(auth()->user()->hasRole('Owner'), 403);

        $tickets = SupportTicket::query()
            ->where('tenant_id', (int) auth()->user()->tenant_id)
            ->latest()
            ->limit(20)
            ->get();

        return view('support-tickets.create', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->check() && auth()->user()->tenant_id !== null, 403);
        abort_unless(auth()->user()->hasRole('Owner'), 403);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $tenant = tenant();
        $user = $request->user();
        $centralUserId = DB::connection('central')
            ->table('users')
            ->where('email', (string) $user->email)
            ->value('id');

        SupportTicket::create([
            'tenant_id' => $tenant?->id ?? $user->tenant_id,
            'user_id' => $centralUserId ? (int) $centralUserId : null,
            'shop_name' => (string) ($tenant?->name ?? 'Unknown Shop'),
            'subdomain' => (string) ($tenant?->subdomain ?? ''),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
        ]);

        return redirect()->route('support-tickets.create')->with('status', 'Ticket sent to central owner.');
    }
}
