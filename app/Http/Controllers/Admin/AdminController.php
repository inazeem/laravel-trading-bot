<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'recent_users' => User::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function clearCaches()
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            // Reset Spatie permission cache if available
            Artisan::call('permission:cache-reset');

            return redirect()->back()->with('success', 'All caches cleared successfully.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to clear caches: ' . $e->getMessage());
        }
    }
}
