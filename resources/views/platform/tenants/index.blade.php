@extends('layouts.app')

@section('title', 'Platform · Spaces')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Spaces (tenants)</h1>
        <a href="{{ route('platform.tenants.create') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">New space</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-50 text-xs font-medium uppercase tracking-wide text-stone-600">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Learner URL</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($tenants as $t)
                    <tr>
                        <td class="px-4 py-3 font-medium text-stone-900">{{ $t->name }}</td>
                        <td class="px-4 py-3 text-stone-600"><code>{{ $t->slug }}</code></td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $t->status === 'active' ? 'bg-teal-100 text-teal-900' : 'bg-amber-100 text-amber-900' }}">{{ $t->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-500">
                            <a href="{{ $t->publicUrl('register') }}" class="text-teal-700 hover:underline break-all">register</a>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('platform.tenants.edit', ['adminTenant' => $t->id]) }}" class="text-teal-700 hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $tenants->links() }}</div>
@endsection
