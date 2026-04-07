@extends('layouts.app')

@section('title', 'Edit '.$record->name)

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">Edit space</h1>
    <p class="mt-2 text-sm text-stone-600">
        Public learner registration: <a href="{{ $record->publicUrl('register') }}" class="font-medium text-teal-700 hover:underline break-all">{{ $record->publicUrl('register') }}</a>
    </p>

    <form method="POST" action="{{ route('platform.tenants.update', ['adminTenant' => $record->id]) }}" class="mt-8 max-w-2xl space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="block text-sm font-medium text-stone-700">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name', $record->name) }}" required
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-stone-700">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug', $record->slug) }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-sm text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-stone-700">Status</label>
            <select id="status" name="status"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                <option value="active" @selected(old('status', $record->status) === 'active')>active</option>
                <option value="suspended" @selected(old('status', $record->status) === 'suspended')>suspended</option>
            </select>
        </div>
        <div>
            <label for="custom_domain" class="block text-sm font-medium text-stone-700">Custom domain (future)</label>
            <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain', $record->custom_domain) }}"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>
        <div>
            <label for="branding_json" class="block text-sm font-medium text-stone-700">Branding JSON</label>
            <p class="text-xs text-stone-500">Keys like <code>primary</code>, <code>accent</code>, <code>welcome_headline</code>, <code>logo_url</code>.</p>
            <textarea id="branding_json" name="branding_json" rows="10"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-xs text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">{{ old('branding_json', json_encode($record->branding ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </div>
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Save</button>
    </form>
@endsection
