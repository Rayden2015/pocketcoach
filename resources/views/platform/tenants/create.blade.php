@extends('layouts.app')

@section('title', 'New space')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">Create space (platform)</h1>
    <p class="mt-2 text-sm text-stone-600">Shell tenant only — a coach should self-serve via “Create a space” or you assign staff in the database for now.</p>

    <form method="POST" action="{{ route('platform.tenants.store') }}" class="mt-8 max-w-lg space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-stone-700">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-stone-700">Slug</label>
            <input id="slug" type="text" name="slug" value="{{ old('slug') }}" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-sm text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-stone-700">Status</label>
            <select id="status" name="status"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                <option value="active" @selected(old('status', 'active') === 'active')>active</option>
                <option value="suspended" @selected(old('status') === 'suspended')>suspended</option>
            </select>
        </div>
        <div>
            <label for="custom_domain" class="block text-sm font-medium text-stone-700">Custom domain (future)</label>
            <input id="custom_domain" type="text" name="custom_domain" value="{{ old('custom_domain') }}"
                class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                placeholder="coach.example.com">
        </div>
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700">Create</button>
    </form>
@endsection
