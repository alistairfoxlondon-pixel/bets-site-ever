<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = Audit::query()->with('user');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('domain', 'like', '%'.$search.'%')
                ->orWhere('url', 'like', '%'.$search.'%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->string('mode')->toString());
        }

        $audits = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.audits.index', [
            'audits' => $audits,
        ]);
    }

    public function show(Audit $audit): View
    {
        $audit->load('user', 'findings');

        return view('admin.audits.show', [
            'audit' => $audit,
            'categories' => $audit->categoryScores(),
        ]);
    }
}
