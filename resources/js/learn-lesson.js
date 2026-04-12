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
    return clamp(0, 100, Math.round((100 * scrollTop) / scrollable));
}

/**
 * Reading / watch progress bar + debounced persistence for learner lesson pages.
 */
function initLearnLesson() {
    const root = document.querySelector('[data-learn-lesson]');
    if (!root) {
        return;
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

    let pct = clamp(0, 100, parseInt(root.dataset.initialPercent || '0', 10) || 0);
    let lastSent = pct;
    let mediaMax = 0;

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
        const s = documentScrollPercent();
        setFill(Math.max(s, mediaMax));
    }

    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize, { passive: true });
    onScrollOrResize();

    const video = root.querySelector('video');
    if (video) {
        video.addEventListener('timeupdate', () => {
            if (!video.duration || !Number.isFinite(video.duration)) {
                return;
            }
            mediaMax = Math.max(mediaMax, Math.round((100 * video.currentTime) / video.duration));
            setFill(Math.max(documentScrollPercent(), mediaMax));
        });
    }

    const audio = root.querySelector('audio');
    if (audio) {
        audio.addEventListener('timeupdate', () => {
            if (!audio.duration || !Number.isFinite(audio.duration)) {
                return;
            }
            mediaMax = Math.max(mediaMax, Math.round((100 * audio.currentTime) / audio.duration));
            setFill(Math.max(documentScrollPercent(), mediaMax));
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
