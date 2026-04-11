import './bootstrap';

function initNotificationsBell() {
    const root = document.getElementById('pc-notifications-bell');
    if (!root || !window.axios) {
        return;
    }

    const unreadUrl = root.dataset.unreadUrl;
    const listUrl = root.dataset.listUrl;
    const readAllUrl = root.dataset.readAllUrl;
    const readOneBase = root.dataset.readOneBase?.replace(/\/$/, '') ?? '';

    const toggleBtn = root.querySelector('[data-bell-toggle]');
    const panel = root.querySelector('[data-bell-panel]');
    const listEl = root.querySelector('[data-bell-list]');
    const emptyEl = root.querySelector('[data-bell-empty]');
    const badge = root.querySelector('[data-unread-badge]');
    const markAllBtn = root.querySelector('[data-mark-all-read]');

    if (!toggleBtn || !panel || !listEl || !badge || !markAllBtn || !unreadUrl || !listUrl || !readAllUrl) {
        return;
    }

    let open = false;

    function setOpen(next) {
        open = next;
        panel.hidden = !open;
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            loadList();
        }
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function refreshUnread() {
        try {
            const { data } = await window.axios.get(unreadUrl);
            const n = typeof data?.count === 'number' ? data.count : 0;
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : String(n);
                badge.classList.remove('hidden');
                badge.classList.add('flex');
            } else {
                badge.textContent = '';
                badge.classList.add('hidden');
                badge.classList.remove('flex');
            }
        } catch {
            /* ignore */
        }
    }

    const emptyDefault = emptyEl?.textContent?.trim() ?? "You're all caught up.";

    async function loadList() {
        listEl.innerHTML = '';
        emptyEl?.classList.add('hidden');
        if (emptyEl) {
            emptyEl.textContent = emptyDefault;
        }
        try {
            const { data } = await window.axios.get(listUrl);
            const items = Array.isArray(data?.data) ? data.data : [];
            if (items.length === 0) {
                emptyEl?.classList.remove('hidden');
                return;
            }
            emptyEl?.classList.add('hidden');
            for (const row of items) {
                const li = document.createElement('li');
                li.setAttribute('role', 'menuitem');
                const unread = row.read_at == null;
                const title = row.title ?? 'Notification';
                const preview = row.preview ? `<p class="mt-0.5 line-clamp-2 text-xs text-slate-500">${escapeHtml(row.preview)}</p>` : '';
                const time = row.created_at
                    ? `<p class="mt-1 text-[10px] text-slate-400">${escapeHtml(new Date(row.created_at).toLocaleString())}</p>`
                    : '';
                li.className = `border-b border-slate-50 last:border-0 ${unread ? 'bg-teal-50/40' : ''}`;
                li.innerHTML = `
                    <button type="button" class="w-full px-3 py-2.5 text-left hover:bg-slate-50" data-nid="${escapeHtml(row.id)}" data-url="${row.url ? escapeHtml(row.url) : ''}">
                        <p class="text-sm font-medium text-slate-800">${escapeHtml(title)}</p>
                        ${preview}
                        ${time}
                    </button>`;
                listEl.appendChild(li);
            }
        } catch {
            emptyEl?.classList.remove('hidden');
            if (emptyEl) {
                emptyEl.textContent = 'Could not load notifications.';
            }
        }
    }

    async function markReadAndGo(id, url) {
        try {
            await window.axios.patch(`${readOneBase}/${encodeURIComponent(id)}`);
        } catch {
            /* still navigate */
        }
        await refreshUnread();
        if (url) {
            window.location.assign(url);
        } else {
            setOpen(false);
        }
    }

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        setOpen(!open);
    });

    markAllBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        markAllBtn.disabled = true;
        try {
            await window.axios.post(readAllUrl);
            await refreshUnread();
            await loadList();
        } finally {
            markAllBtn.disabled = false;
        }
    });

    listEl.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-nid]');
        if (!btn) {
            return;
        }
        const id = btn.getAttribute('data-nid');
        const url = btn.getAttribute('data-url') || '';
        if (id) {
            markReadAndGo(id, url);
        }
    });

    document.addEventListener('click', (e) => {
        if (open && !root.contains(e.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && open) {
            setOpen(false);
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshUnread();
        }
    });

    refreshUnread();
    setInterval(refreshUnread, 60000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotificationsBell);
} else {
    initNotificationsBell();
}
