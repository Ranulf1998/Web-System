<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountabilityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage users');
        
        $this->middleware(function ($request, $next) {
            abort_unless(tenant()->max_users === null || tenant()->max_users > 1, 403, 'User logs are not available on your current plan.');
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $staffId = $request->integer('staff_id');
        $action = $request->string('action')->trim()->toString();

        $staff = User::with('roles')
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->orderBy('name')
            ->get();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $activities = ActivityLog::with('user')
            ->when($staffId, fn ($query) => $query->where('user_id', $staffId))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('accountability.index', [
            'staff' => $staff,
            'actions' => $actions,
            'activities' => $activities,
            'staffId' => $staffId,
            'action' => $action,
        ]);
    }
}
