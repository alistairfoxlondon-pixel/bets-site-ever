<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['features'] = $this->features($request);

        Plan::create($data);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request, $plan);

        $data['features'] = $this->features($request);

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:600'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'price_monthly_bdt' => ['required', 'numeric', 'min:0'],
            'price_yearly_bdt' => ['required', 'numeric', 'min:0'],
            'credits' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_sites' => ['required', 'integer', 'min:1', 'max:10000'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function features(Request $request): array
    {
        $features = $request->input('features', []);

        if (is_string($features)) {
            $features = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $features) ?: []));
        }

        return array_values(array_filter((array) $features, fn ($feature) => filled($feature)));
    }

    private function uniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'plan';
        $slug = $base;
        $i = 2;

        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
