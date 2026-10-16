<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('action', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$search.'%')));
        }

        $logs = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
        ]);
    }
}
