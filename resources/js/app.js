import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Sortable = Sortable;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/**
 * Fayl yükləyici. Faylı 5 MB-lıq hissələrlə serverə göndərir,
 * server isə OneDrive qoşulubsa hissələri birbaşa OneDrive-a ötürür.
 * Hazır olan faylların id-ləri formaya gizli input kimi əlavə olunur.
 */
Alpine.data('uploader', (opts = {}) => ({
    files: [],
    busy: false,
    dragging: false,
    multiple: opts.multiple ?? true,
    autosubmit: opts.autosubmit ?? false,
    accept: opts.accept ?? '',
    pick() { this.$refs.input.click(); },
    drop(e) { this.dragging = false; this.add(e.dataTransfer.files); },
    add(list) {
        const arr = Array.from(list || []);
        if (!this.multiple) { this.files = []; arr.splice(1); }
        arr.forEach((f) => this.files.push({ file: f, name: f.name, size: f.size, progress: 0, status: 'waiting', id: null, error: null }));
        this.$refs.input.value = '';
        this.run();
    },
    human(b) { const u = ['B', 'KB', 'MB', 'GB']; let i = 0; while (b >= 1024 && i < 3) { b /= 1024; i++; } return b.toFixed(i ? 1 : 0) + ' ' + u[i]; },
    remove(i) { this.files.splice(i, 1); },
    get done() { return this.files.length > 0 && this.files.every((f) => f.status === 'done' || f.status === 'error'); },
    get ids() { return this.files.filter((f) => f.id).map((f) => f.id); },
    async run() {
        if (this.busy) return;
        this.busy = true;
        for (const item of this.files) {
            if (item.status !== 'waiting') continue;
            item.status = 'uploading';
            try { item.id = await this.upload(item); item.status = 'done'; }
            catch (e) { item.status = 'error'; item.error = e.message || 'Xəta'; }
        }
        this.busy = false;
        if (this.autosubmit && this.done && this.ids.length) {
            this.$nextTick(() => this.$el.closest('form').requestSubmit());
        }
    },
    async upload(item) {
        const start = await fetch(opts.startUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
            body: JSON.stringify({ name: item.name, size: item.size, mime: item.file.type, brand_id: (opts.brandSelect ? document.querySelector(opts.brandSelect)?.value : opts.brandId) || null, scope: opts.scope || 'files', sub: opts.sub || null }),
        });
        const s = await start.json().catch(() => ({}));
        if (!start.ok) throw new Error(s.message || 'Yükləmə başlamadı');
        const chunk = s.chunk_size;
        let offset = 0;
        while (offset < item.size) {
            const part = item.file.slice(offset, offset + chunk);
            let res, data, attempt = 0;
            while (true) {
                try {
                    res = await fetch(opts.chunkUrl.replace('__ID__', s.id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf(), 'X-Offset': String(offset), 'Content-Type': 'application/octet-stream', Accept: 'application/json' },
                        body: part,
                    });
                    data = await res.json().catch(() => ({}));
                    if (res.ok) break;
                    throw new Error(data.message || 'Hissə yüklənmədi');
                } catch (e) {
                    if (++attempt >= 3) throw e;
                    await new Promise((r) => setTimeout(r, 1500 * attempt));
                }
            }
            offset += part.size;
            item.progress = Math.round((offset / item.size) * 100);
            if (data.done) return data.attachment.id;
        }
        throw new Error('Yükləmə tamamlanmadı');
    },
}));

/** Bildiriş zəngi: hər 30 saniyədən bir yoxlayır */
Alpine.data('notifications', (url) => ({
    open: false, count: 0, items: [],
    init() { this.load(); setInterval(() => document.visibilityState === 'visible' && this.load(), 30000); },
    async load() {
        try {
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            if (r.ok) { const d = await r.json(); this.count = d.count; this.items = d.items; }
        } catch (e) { /* şəbəkə xətası: növbəti dəfə */ }
    },
}));

/** Kanban: kartları sütunlar arasında sürüşdürmək */
Alpine.data('kanban', (moveUrl) => ({
    init() {
        this.$el.querySelectorAll('[data-column]').forEach((col) => {
            Sortable.create(col, {
                group: 'tasks', animation: 150, ghostClass: 'opacity-40',
                onEnd: (evt) => {
                    const to = evt.to;
                    const id = evt.item.dataset.id;
                    const order = Array.from(to.querySelectorAll('[data-id]')).map((el) => el.dataset.id);
                    fetch(moveUrl.replace('__ID__', id), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                        body: JSON.stringify({ status: to.dataset.column, order }),
                    }).then((r) => { if (!r.ok) location.reload(); });
                    this.$el.querySelectorAll('[data-count]').forEach((c) => {
                        c.textContent = this.$el.querySelector(`[data-column="${c.dataset.count}"]`).querySelectorAll('[data-id]').length;
                    });
                },
            });
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
