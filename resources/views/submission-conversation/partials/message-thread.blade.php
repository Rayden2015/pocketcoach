@php
    use Illuminate\Support\Str;
@endphp

<section class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="text-sm font-bold text-stone-900">Messages</h2>
    <p class="mt-1 text-xs text-stone-600">Reply to continue the thread. Indented messages are replies to a specific message.</p>

    <ul class="mt-6 space-y-4">
        @forelse ($messages as $msg)
            @php
                $depth = $messageDepth[$msg->id] ?? 0;
                $pad = min($depth, 6) * 1.25;
            @endphp
            <li id="message-{{ $msg->id }}" class="rounded-xl border border-stone-100 bg-stone-50/50 p-4" style="margin-left: {{ $pad }}rem">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-sm font-medium text-stone-900">{{ $msg->user?->name ?? 'User' }}</p>
                    <time class="text-xs text-stone-500" datetime="{{ $msg->created_at?->toIso8601String() }}">{{ $msg->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</time>
                </div>
                @if ($msg->parent_id && ($parent = $messageById->get($msg->parent_id)))
                    <p class="mt-2 text-xs text-stone-500">Replying to {{ $parent->user?->name ?? 'message' }}</p>
                @endif
                <div class="prose prose-sm prose-stone mt-2 max-w-none text-stone-800">
                    {!! Str::markdown($msg->body) !!}
                </div>
                <button type="button" class="reply-toggle mt-3 text-xs font-medium text-teal-700 hover:underline" data-parent="{{ $msg->id }}">Reply</button>
            </li>
        @empty
            <li class="rounded-lg border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-stone-500">No messages yet. Start the conversation below.</li>
        @endforelse
    </ul>

    <form method="POST" action="{{ $formAction }}" class="conversation-reply-form mt-8 space-y-3 border-t border-stone-100 pt-6" id="main-reply-form">
        @csrf
        <input type="hidden" name="parent_id" value="" id="reply-parent-id">
        <p class="hidden text-xs text-teal-800" id="replying-banner">Replying to a message — <button type="button" class="font-medium underline" id="cancel-reply">Cancel</button></p>
        <div class="flex items-center gap-1.5">
            <label for="reply-body" class="text-sm font-medium text-stone-700">Your message</label>
            <button
                type="button"
                class="inline-flex shrink-0 rounded-full p-0.5 text-stone-400 transition-colors hover:text-stone-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/35"
                title="Press Enter to send · Shift+Enter for a new line"
                aria-label="Keyboard shortcuts: Press Enter to send. Shift+Enter for a new line."
            >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </button>
        </div>
        <textarea id="reply-body" name="body" rows="4" required
            class="w-full rounded-xl border border-stone-300 px-3 py-2 text-stone-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/25"
            placeholder="Write a message…"></textarea>
        @error('body')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('parent_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="rounded-full bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">Send</button>
    </form>
</section>

<script>
document.querySelectorAll('.reply-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var id = this.getAttribute('data-parent');
        document.getElementById('reply-parent-id').value = id;
        document.getElementById('replying-banner').classList.remove('hidden');
        document.getElementById('reply-body').focus();
        document.getElementById('main-reply-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
});
document.getElementById('cancel-reply')?.addEventListener('click', function () {
    document.getElementById('reply-parent-id').value = '';
    document.getElementById('replying-banner').classList.add('hidden');
});

(function () {
    var ta = document.getElementById('reply-body');
    var form = document.getElementById('main-reply-form');
    if (!ta || !form) {
        return;
    }
    ta.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') {
            return;
        }
        if (e.shiftKey) {
            return;
        }
        e.preventDefault();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
</script>
