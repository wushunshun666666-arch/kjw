/** Use the server clock and monotonic elapsed time, independently of the visitor's clock. */
export function countdownState(period, elapsedMilliseconds = 0) {
    if (!period || !Number.isFinite(period.server_time) || !Number.isFinite(period.next_draw_at)) {
        return {kind: 'unknown', text: '等待开奖时间'};
    }
    const remaining = Math.max(0, Math.ceil(period.next_draw_at - period.server_time - elapsedMilliseconds / 1000));
    if (remaining === 0) return {kind: 'waiting', text: '正在开奖，等待最新数据'};
    const minutes = String(Math.floor(remaining / 60));
    const seconds = String(remaining % 60).padStart(2, '0');
    return {kind: 'counting', minutes, seconds, text: `${minutes} 分 ${seconds} 秒`};
}

/** Keep the visible countdown and the scratch-card footer on one clock. */
export function CountdownPanel(root, onTick, onExpire) {
    let period = null;
    let startedAt = 0;
    let expiredDeadline = null;
    let timer;
    const digits = root.querySelector('[data-region="countdown-digits"]');
    const status = root.querySelector('[data-region="countdown-state"]');

    function tick() {
        const state = countdownState(period, performance.now() - startedAt);
        digits.hidden = state.kind !== 'counting';
        status.hidden = state.kind === 'counting';
        if (state.kind === 'counting') {
            root.querySelector('[data-time="minutes"]').textContent = state.minutes;
            root.querySelector('[data-time="seconds"]').textContent = state.seconds;
        } else status.textContent = state.text;
        onTick(state.text);
        if (state.kind === 'waiting' && expiredDeadline !== period.next_draw_at) {
            expiredDeadline = period.next_draw_at;
            onExpire();
        }
    }

    function update(nextPeriod) {
        period = nextPeriod;
        startedAt = performance.now();
        tick();
    }

    function init() { timer = setInterval(tick, 250); }
    function destroy() { clearInterval(timer); }
    init();
    return {update, destroy};
}

/** Reveal only the selected game's stored current draw, keeping scratch progress between polls. */
export function ScratchCardPanel(trigger, dialog, makeBall) {
    const surface = dialog.querySelector('[data-scratch="surface"]');
    const canvas = dialog.querySelector('[data-scratch="canvas"]');
    const context = canvas.getContext('2d', {willReadFrequently: true});
    const listeners = new AbortController();
    const options = {signal: listeners.signal};
    const cover = new Image();
    let latest = null;
    let game = null;
    let key = '';
    let pointer = null;
    let lastPoint = null;
    let scratched = false;
    let revealed = false;
    let resizeObserver;

    function reveal() {
        revealed = true;
        canvas.hidden = true;
        surface.dataset.revealed = 'true';
    }

    function paintCover() {
        context.globalCompositeOperation = 'source-over';
        context.fillStyle = '#bcbcbc';
        context.fillRect(0, 0, canvas.width, canvas.height);
        if (cover.complete && cover.naturalWidth) context.drawImage(cover, 0, 0, canvas.width, canvas.height);
        else {
            context.fillStyle = '#ffdf2c';
            context.font = 'bold 28px Arial, Microsoft YaHei, sans-serif';
            context.textAlign = 'center';
            context.textBaseline = 'middle';
            context.fillText('刮开查看开奖结果', canvas.width / 2, canvas.height / 2);
        }
    }

    function resize() {
        if (!dialog.open) return;
        const width = Math.round(surface.clientWidth);
        const height = Math.round(surface.clientHeight);
        if (!width || !height || (canvas.width === width && canvas.height === height)) return;
        const saved = document.createElement('canvas');
        saved.width = canvas.width;
        saved.height = canvas.height;
        saved.getContext('2d').drawImage(canvas, 0, 0);
        canvas.width = width;
        canvas.height = height;
        if (scratched) context.drawImage(saved, 0, 0, width, height);
        else paintCover();
    }

    function reset() {
        pointer = null;
        lastPoint = null;
        scratched = false;
        revealed = false;
        canvas.hidden = false;
        surface.dataset.revealed = 'false';
        resize();
        paintCover();
    }

    function render() {
        dialog.querySelector('[data-scratch="issue"]').textContent = latest.issue;
        const numbers = dialog.querySelector('[data-scratch="numbers"]');
        numbers.replaceChildren(...latest.balls.map(value => makeBall(value, game.type === 'racing')));
        numbers.classList.toggle('portal-scratch-racing', game.type === 'racing');
        reset();
    }

    function open() {
        if (!latest || dialog.open) return;
        dialog.showModal();
        render();
    }

    function close() {
        pointer = null;
        if (dialog.open) dialog.close();
    }

    function update(data) {
        const row = data.list[0] || null;
        const nextKey = row ? `${data.game.code}:${row.issue}:${row.balls.join(',')}` : '';
        latest = row;
        game = data.game;
        trigger.disabled = !latest;
        if (!latest) close();
        else if (dialog.open && nextKey !== key) render();
        key = nextKey;
    }

    function scratch(event) {
        if (pointer !== event.pointerId || revealed) return;
        const bounds = canvas.getBoundingClientRect();
        const point = {x: (event.clientX - bounds.left) * canvas.width / bounds.width,
            y: (event.clientY - bounds.top) * canvas.height / bounds.height};
        context.globalCompositeOperation = 'destination-out';
        context.lineWidth = 40;
        context.lineCap = 'round';
        context.beginPath();
        context.moveTo(lastPoint?.x ?? point.x, lastPoint?.y ?? point.y);
        context.lineTo(point.x, point.y);
        context.stroke();
        context.beginPath();
        context.arc(point.x, point.y, 20, 0, Math.PI * 2);
        context.fill();
        lastPoint = point;
        scratched = true;
    }

    function finish(event) {
        if (pointer !== event.pointerId) return;
        pointer = null;
        lastPoint = null;
        if (canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId);
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        let cleared = 0;
        for (let index = 3; index < pixels.length; index += 4) if (pixels[index] < 128) cleared++;
        if (cleared / (pixels.length / 4) >= 0.6) reveal();
    }

    function setCountdown(text) { dialog.querySelector('[data-scratch="countdown"]').textContent = `下一期：${text}`; }

    function init() {
        trigger.addEventListener('click', open, options);
        dialog.querySelector('[data-scratch="reset"]').addEventListener('click', reset, options);
        dialog.querySelector('[data-scratch="close"]').addEventListener('click', close, options);
        dialog.addEventListener('click', event => { if (event.target === dialog) close(); }, options);
        dialog.addEventListener('close', () => { pointer = null; }, options);
        canvas.addEventListener('pointerdown', event => {
            if (event.button !== 0 || pointer !== null) return;
            event.preventDefault();
            pointer = event.pointerId;
            canvas.setPointerCapture(pointer);
            scratch(event);
        }, options);
        canvas.addEventListener('pointermove', scratch, options);
        canvas.addEventListener('pointerup', finish, options);
        canvas.addEventListener('pointercancel', finish, options);
        canvas.addEventListener('lostpointercapture', () => { pointer = null; lastPoint = null; }, options);
        canvas.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); reveal(); }
        }, options);
        cover.addEventListener('load', () => { if (dialog.open && !scratched && !revealed) paintCover(); }, options);
        cover.src = canvas.dataset.coverUrl;
        resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(surface);
    }

    function destroy() {
        close();
        listeners.abort();
        resizeObserver.disconnect();
    }

    init();
    return {update, setCountdown, destroy};
}
