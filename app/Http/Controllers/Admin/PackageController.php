<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Package;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PackageController extends Controller
{
    public function index(): View
    {
        return view('admin.saas.packages', [
            'packages' => Package::query()->with('features', 'limits')->orderBy('sort_order')->get(),
            'features' => Feature::query()->where('active', true)->orderBy('slug')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $attributes = $this->validated($request);
        $package = Package::query()->create($attributes);
        $package->features()->sync($this->featureSync($request));
        $this->syncLimits($package, $request->input('limits', ''));
        $audit->log('saas.package_created', $request->user(), $package);

        return back()->with('status', 'package-created');
    }

    public function update(Request $request, Package $package, AuditLogger $audit): RedirectResponse
    {
        $package->fill($this->validated($request, $package))->save();
        $package->features()->sync($this->featureSync($request));
        $this->syncLimits($package, $request->input('limits', ''));
        $audit->log('saas.package_updated', $request->user(), $package);

        return back()->with('status', 'package-updated');
    }

    public function duplicate(Request $request, Package $package, AuditLogger $audit): RedirectResponse
    {
        $copy = $package->replicate();
        $copy->name = $package->name.' Copy';
        $copy->slug = $package->slug.'-copy-'.Str::lower(Str::random(4));
        $copy->active = false;
        $copy->save();
        $copy->features()->sync($package->features->pluck('id')->mapWithKeys(fn ($id): array => [$id => ['enabled' => true]])->all());

        foreach ($package->limits as $limit) {
            $copy->limits()->create(['key' => $limit->key, 'value' => $limit->value]);
        }

        $audit->log('saas.package_duplicated', $request->user(), $copy, ['source_package_id' => $package->id]);

        return back()->with('status', 'package-duplicated');
    }

    private function validated(Request $request, ?Package $package = null): array
    {
        $slugRule = $package ? 'unique:packages,slug,'.$package->id : 'unique:packages,slug';

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160', $slugRule],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', 'in:monthly,quarterly,yearly,lifetime'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'landing_page_slug' => ['nullable', 'string', 'max:160'],
            'active' => ['nullable', 'boolean'],
            'public' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
        ]);

        $attributes['slug'] = $attributes['slug'] ?: str_replace('-', '.', Str::slug($attributes['name']));
        $attributes['active'] = (bool) ($attributes['active'] ?? false);
        $attributes['public'] = (bool) ($attributes['public'] ?? false);
        $attributes['featured'] = (bool) ($attributes['featured'] ?? false);
        $attributes['currency'] = strtoupper($attributes['currency']);

        return $attributes;
    }

    private function featureSync(Request $request): array
    {
        return collect($request->input('features', []))
            ->mapWithKeys(fn ($id): array => [(int) $id => ['enabled' => true]])
            ->all();
    }

    private function syncLimits(Package $package, string $limits): void
    {
        $package->limits()->delete();

        foreach (preg_split('/\r\n|\r|\n/', $limits) ?: [] as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if ($key !== '' && $value !== '') {
                $package->limits()->create(['key' => $key, 'value' => $value]);
            }
        }
    }
}
