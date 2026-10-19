/** Keep the shared page mounted while switching only its lottery data panels. */
export class GameNavigation {
    constructor(options) {
        this.options = options;
        this.root = options.root;
        const environment = options.environment || {};
        this.window = environment.window || window;
        this.document = environment.document || document;
        this.fetch = environment.fetch || globalThis.fetch.bind(globalThis);
        this.timers = environment.timers || globalThis;
        this.links = [...this.root.querySelectorAll('.portal-game-nav a[data-game]')];
        this.games = new Set(this.links.map(link => link.dataset.game));
        this.games.add('jnd28');
        this.currentGame = 'jnd28';
        this.generation = 0;
        this.request = null;
        this.destroyed = false;
        this.validUrl = this.gameUrl('jnd28');
        this.onClick = event => this.handleClick(event);
        this.onPopState = () => {
            if (!this.isHome()) return;
            const game = new URL(this.window.location.href).searchParams.get('game') || 'jnd28';
            this.select(game, 'pop');
        };
        this.onVisibilityChange = () => {
            if (!this.document.hidden) this.refresh();
        };
        this.init();
    }

    /** Bind one navigation owner and initialize from the server's current game. */
    init() {
        this.root.addEventListener('click', this.onClick);
        this.window.addEventListener('popstate', this.onPopState);
        this.document.addEventListener('visibilitychange', this.onVisibilityChange);
        this.timer = this.timers.setInterval(() => this.refresh(), 5000);
        const initialGame = this.games.has(this.options.initialGame) ? this.options.initialGame : 'jnd28';
        if (initialGame === 'jnd28') {
            this.commitCanada();
            this.validUrl = this.window.location.href;
        } else if (this.validData(this.options.initialData, initialGame)) {
            this.commitData(this.options.initialData);
            this.validUrl = this.window.location.href;
        } else {
            this.commitCanada();
            this.select(initialGame, 'initial');
        }
    }

    /** Preserve new-tab gestures and ordinary link behavior outside this selector. */
    handleClick(event) {
        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || (event.button !== undefined && event.button !== 0)) return;
        const link = event.target?.closest?.('.portal-game-nav a[data-game]');
        if (!link || !this.root.contains(link) || link.hasAttribute('download')
            || (link.target && link.target !== '_self')) return;
        event.preventDefault();
        this.select(link.dataset.game, 'push');
    }

    /** Commit navigation only after the requested game's real data is available. */
    async select(game, mode = 'push') {
        if (this.destroyed) return;
        if (!this.games.has(game)) {
            if (mode === 'pop') {
                this.cancelRequest();
                this.restoreUrl();
            }
            this.options.onError('该彩种不存在或尚未发布。');
            return;
        }
        if (game === this.currentGame) {
            if (this.request && this.request.game !== game) {
                this.cancelRequest();
                if (mode !== 'pop') this.restoreUrl();
            }
            if (mode === 'pop') this.validUrl = this.window.location.href;
            return;
        }
        if (this.request?.navigation && this.request.game === game) return;
        this.cancelRequest();
        const url = this.gameUrl(game);
        this.options.onError('');
        if (game === 'jnd28') {
            this.commitCanada();
            this.commitUrl(url, mode);
            return;
        }
        await this.load(game, {navigation: true, mode, url});
    }

    /** Refresh only the visible active non-Canada panel without resetting its filters. */
    refresh() {
        if (this.destroyed || this.document.hidden || this.currentGame === 'jnd28' || this.request) return;
        return this.load(this.currentGame, {navigation: false});
    }

    /** Ignore stale responses even when a transport does not honor cancellation. */
    async load(game, navigation) {
        const controller = new AbortController();
        const request = {game, navigation: navigation.navigation, controller, generation: ++this.generation};
        this.request = request;
        if (request.navigation) this.options.setBusy(true);
        request.timeout = this.timers.setTimeout(() => controller.abort(), 12000);
        try {
            const url = new URL(this.options.endpoint, this.window.location.href);
            url.searchParams.set('game', game);
            const response = await this.fetch(url.href, {signal: controller.signal, cache: 'no-store', credentials: 'same-origin'});
            if (!response.ok) throw new Error('Request failed');
            const payload = await response.json();
            if (!this.isCurrent(request)) return;
            if (controller.signal.aborted) throw new Error('Request timed out');
            if (payload.code !== 200 || !this.validData(payload.data, game)) throw new Error('Unexpected game data');
            if (request.navigation) {
                this.commitData(payload.data);
                this.commitUrl(navigation.url, navigation.mode);
            } else this.options.render(payload.data);
            this.options.onError('');
        } catch {
            if (!this.isCurrent(request)) return;
            if (request.navigation && navigation.mode === 'pop') this.restoreUrl();
            if (request.navigation && navigation.mode === 'initial') this.restoreUrl();
            this.options.onError(request.navigation
                ? '彩种切换失败，已保留当前开奖结果，请点击彩种重试。'
                : '页面更新失败，正在重试；保留最近读取的数据。');
        } finally {
            this.timers.clearTimeout(request.timeout);
            if (this.isCurrent(request)) {
                this.request = null;
                if (request.navigation) this.options.setBusy(false);
            }
        }
    }

    /** Resume the reference game's existing collector only while Canada is selected. */
    commitCanada() {
        this.currentGame = 'jnd28';
        this.options.onGame('jnd28');
        this.updateLinks();
        this.options.activateCanada();
    }

    /** Hand the complete validated snapshot to the existing result renderer. */
    commitData(data) {
        this.options.deactivateCanada();
        this.options.render(data);
        this.currentGame = data.game.code;
        this.options.onGame(this.currentGame);
        this.updateLinks();
    }

    updateLinks() {
        this.links.forEach(link => {
            const selected = link.dataset.game === this.currentGame;
            link.classList.toggle('selected', selected);
            if (selected) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
        if (this.options.title) {
            const name = this.links.find(link => link.dataset.game === this.currentGame)?.textContent.trim();
            this.document.title = name ? `${name} - ${this.options.title}` : this.options.title;
        }
    }

    validData(data, game) {
        return data?.game?.code === game && Array.isArray(data.list);
    }

    isCurrent(request) {
        return !this.destroyed && this.request === request && request.generation === this.generation;
    }

    isHome() {
        const path = this.window.location.hash.split('?')[0];
        return path === '' || path === '#' || path === '#/';
    }

    gameUrl(game) {
        const url = new URL(this.window.location.href);
        url.searchParams.set('game', game);
        return url.href;
    }

    commitUrl(url, mode) {
        if (mode === 'push' && url !== this.window.location.href) {
            this.window.history.pushState(this.window.history.state, '', url);
        }
        this.validUrl = this.window.location.href;
    }

    restoreUrl() {
        this.window.history.replaceState(this.window.history.state, '', this.validUrl);
    }

    cancelRequest() {
        const request = this.request;
        if (!request) return;
        this.request = null;
        this.generation++;
        request.controller.abort();
        this.timers.clearTimeout(request.timeout);
        if (request.navigation) this.options.setBusy(false);
    }

    /** Release requests, timers and listeners when Vue leaves the home page. */
    destroy() {
        if (this.destroyed) return;
        this.destroyed = true;
        this.cancelRequest();
        this.timers.clearInterval(this.timer);
        this.root.removeEventListener('click', this.onClick);
        this.window.removeEventListener('popstate', this.onPopState);
        this.document.removeEventListener('visibilitychange', this.onVisibilityChange);
        this.options.deactivateCanada();
    }
}
