import axios from 'axios';

function clamp(n, min, max) {
    return Math.min(max, Math.max(min, n));
}

function documentScrollPercent() {
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    if (scrollable <= 0) {
        return 100;
    }
    return clamp(Math.round((100 * scrollTop) / scrollable), 0, 100);
}

function initLearnStudio(root) {
    const fullscreenBtn = root.querySelector('[data-studio-fullscreen]');
    const notesPanel = root.querySelector('[data-studio-notes]');
    const toggleNotesBtn = root.querySelector('[data-studio-toggle-notes]');
    const image = root.querySelector('[data-studio-image]');
    const zoomIn = root.querySelector('[data-studio-zoom-in]');
    const zoomOut = root.querySelector('[data-studio-zoom-out]');
    const zoomReset = root.querySelector('[data-studio-zoom-reset]');

    let scale = 1;

    function applyZoom() {
        if (!image) {
            return;
        }
        image.style.transform = `scale(${scale})`;
        if (zoomReset) {
            zoomReset.textContent = `${Math.round(scale * 100)}%`;
        }
    }

    function setScale(next) {
        scale = clamp(next, 0.5, 4);
        applyZoom();
    }

    zoomIn?.addEventListener('click', () => setScale(scale + 0.25));
    zoomOut?.addEventListener('click', () => setScale(scale - 0.25));
    zoomReset?.addEventListener('click', () => setScale(1));

    if (image) {
        image.addEventListener(
            'wheel',
            (event) => {
                if (!event.ctrlKey && !event.metaKey) {
                    return;
                }
                event.preventDefault();
                setScale(scale + (event.deltaY < 0 ? 0.1 : -0.1));
            },
            { passive: false },
        );
    }

    async function toggleFullscreen() {
        if (!document.fullscreenElement) {
            try {
                await root.requestFullscreen();
            } catch {
                /* ignore — browser may block */
            }
            return;
        }
        try {
            await document.exitFullscreen();
        } catch {
            /* ignore */
        }
    }

    function syncFullscreenLabel() {
        if (!fullscreenBtn) {
            return;
        }
        fullscreenBtn.textContent = document.fullscreenElement === root ? 'Exit fullscreen' : 'Fullscreen';
    }

    fullscreenBtn?.addEventListener('click', () => {
        toggleFullscreen();
    });
    document.addEventListener('fullscreenchange', syncFullscreenLabel);

    toggleNotesBtn?.addEventListener('click', () => {
        if (!notesPanel) {
            return;
        }
        notesPanel.classList.toggle('hidden');
        toggleNotesBtn.setAttribute(
            'aria-pressed',
            notesPanel.classList.contains('hidden') ? 'false' : 'true',
        );
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !document.fullscreenElement) {
            const exit = root.querySelector('a[href]');
            if (exit instanceof HTMLAnchorElement && exit.textContent?.includes('Exit studio')) {
                /* Let browser handle Escape for fullscreen; Escape outside FS stays on page */
            }
        }
        if ((event.key === 'f' || event.key === 'F') && !(event.target instanceof HTMLInputElement) && !(event.target instanceof HTMLTextAreaElement)) {
            if (root.hasAttribute('data-learn-studio')) {
                event.preventDefault();
                toggleFullscreen();
            }
        }
        if (image && (event.key === '=' || event.key === '+') && !(event.target instanceof HTMLInputElement) && !(event.target instanceof HTMLTextAreaElement)) {
            setScale(scale + 0.25);
        }
        if (image && event.key === '-' && !(event.target instanceof HTMLInputElement) && !(event.target instanceof HTMLTextAreaElement)) {
            setScale(scale - 0.25);
        }
        if (image && event.key === '0' && !(event.target instanceof HTMLInputElement) && !(event.target instanceof HTMLTextAreaElement)) {
            setScale(1);
        }
    });
}

/**
 * Reading / watch progress bar + debounced persistence for learner lesson pages.
 */
function initLearnLesson() {
    const root = document.querySelector('[data-learn-lesson]');
    if (!root) {
        return;
    }

    if (root.hasAttribute('data-learn-studio')) {
        initLearnStudio(root);
    }

    const url = root.dataset.progressUrl;
    const fill = document.getElementById('lesson-progress-fill');
    const bar = document.getElementById('lesson-reading-progress');
    if (!url || !fill || !bar) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf instanceof HTMLMetaElement && csrf.content) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.content;
    }

    let pct = clamp(parseInt(root.dataset.initialPercent || '0', 10) || 0, 0, 100);
    let lastSent = pct;
    let mediaMax = 0;
    const isStudio = root.hasAttribute('data-learn-studio');

    const setFill = (p) => {
        if (root.dataset.completed === '1') {
            p = 100;
        }
        p = clamp(p, 0, 100);
        pct = Math.max(pct, p);
        fill.style.width = `${pct}%`;
        bar.setAttribute('aria-valuenow', String(Math.round(pct)));
    };

    setFill(pct);

    function onScrollOrResize() {
        if (isStudio) {
            setFill(Math.max(mediaMax, pct));
            return;
        }
        const s = documentScrollPercent();
        setFill(Math.max(s, mediaMax));
    }

    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    onScrollOrResize();

    if (isStudio && root.dataset.lessonType === 'image') {
        setFill(Math.max(pct, 25));
    }

    const video = root.querySelector('video');
    if (video) {
        video.addEventListener('timeupdate', () => {
            if (!video.duration || !Number.isFinite(video.duration)) {
                return;
            }
            mediaMax = Math.max(mediaMax, Math.round((100 * video.currentTime) / video.duration));
            setFill(Math.max(isStudio ? 0 : documentScrollPercent(), mediaMax));
        });
    }

    const audio = root.querySelector('audio');
    if (audio) {
        audio.addEventListener('timeupdate', () => {
            if (!audio.duration || !Number.isFinite(audio.duration)) {
                return;
            }
            mediaMax = Math.max(mediaMax, Math.round((100 * audio.currentTime) / audio.duration));
            setFill(Math.max(isStudio ? 0 : documentScrollPercent(), mediaMax));
        });
    }

    let timer = null;
    function schedulePing() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => {
            if (root.dataset.completed === '1') {
                return;
            }
            const p = Math.round(pct);
            if (p <= lastSent) {
                return;
            }
            lastSent = p;
            const payload = { content_progress_percent: p };
            if (video && Number.isFinite(video.currentTime)) {
                payload.position_seconds = Math.floor(video.currentTime);
            } else if (audio && Number.isFinite(audio.currentTime)) {
                payload.position_seconds = Math.floor(audio.currentTime);
            }
            axios
                .post(url, payload, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                .catch(() => {
                    /* ignore */
                });
        }, 2000);
    }

    window.addEventListener('scroll', schedulePing, { passive: true });
    window.addEventListener('resize', schedulePing, { passive: true });
    if (video) {
        video.addEventListener('timeupdate', schedulePing, { passive: true });
    }
    if (audio) {
        audio.addEventListener('timeupdate', schedulePing, { passive: true });
    }
    if (isStudio) {
        schedulePing();
    }

    const form = document.getElementById('lesson-progress-form');
    if (form) {
        form.addEventListener('submit', () => {
            const cppInput = document.getElementById('lesson-form-content-progress');
            const posInput = document.getElementById('lesson-form-position-seconds');
            if (cppInput) {
                cppInput.value = String(Math.round(pct));
            }
            if (posInput) {
                let sec = 0;
                if (video && Number.isFinite(video.currentTime)) {
                    sec = Math.floor(video.currentTime);
                } else if (audio && Number.isFinite(audio.currentTime)) {
                    sec = Math.floor(audio.currentTime);
                }
                posInput.value = String(sec);
            }
        });
    }
}

export { initLearnLesson };
