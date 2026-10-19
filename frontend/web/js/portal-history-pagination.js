/** Page through one stable database snapshot without replacing live draw panels. */
export class HistoryPagination {
    constructor(options) {
        this.options = options;
        this.root = options.root;
        const environment = options.environment || {};
        this.fetch = environment.fetch || globalThis.fetch.bind(globalThis);
        this.timers = environment.timers || globalThis;
        this.baseUrl = options.urlbase || environment.window?.location.href || globalThis.location?.href;
        this.currentGame = null;
        this.pagination = this.emptyPage();
        this.hasData = false;
        this.request = null;
        this.generation = 0;
        this.latestIssue = null;
        this.refreshQueued = false;
        this.error = '';
        this.destroyed = false;
        this.onClick = event => this.handleClick(event);
        this.onChange = event => this.handleChange(event);
        this.init();
    }

    /** Bind delegated controls once so both game layouts share the same owner. */
    init() {
        this.root.addEventListener('click', this.onClick);
        this.root.addEventListener('change', this.onChange);
        this.render();
    }

    emptyPage(pageSize = 20) {
        return {page: 1, page_size: pageSize, total: 0, total_pages: 1, snapshot: 0};
    }

    /** A game change starts a new snapshot and invalidates every earlier response. */
    selectGame(code) {
        if (this.destroyed || !code || code === this.currentGame) return;
        this.cancelRequest();
        this.currentGame = code;
        this.pagination = this.emptyPage(this.pagination.page_size);
        this.hasData = false;
        this.latestIssue = null;
        this.refreshQueued = false;
        this.error = '';
        return this.load(1, this.pagination.page_size, 0);
    }

    /** Live draws update only the latest history page, coalescing slow requests. */
    observeLatest(code, issue) {
        if (this.destroyed || code !== this.currentGame || issue === null || issue === undefined || issue === '') return;
        const nextIssue = String(issue);
        if (this.latestIssue === nextIssue) return;
        const firstObservation = this.latestIssue === null;
        this.latestIssue = nextIssue;
        if ((this.request?.page ?? this.pagination.page) !== 1) return;
        if (this.request) {
            this.refreshQueued = true;
            return;
        }
        if (firstObservation) return;
        return this.load(1, this.pagination.page_size, 0);
    }

    /** Previous and next retain the snapshot so incoming draws cannot move rows. */
    go(page) {
        if (this.destroyed || this.request || !this.hasData || !Number.isInteger(page)
            || page < 1 || page > this.pagination.total_pages || page === this.pagination.page) return;
        return this.load(page, this.pagination.page_size, this.pagination.snapshot);
    }

    handleClick(event) {
        const button = event.target?.closest?.('[data-page-action]');
        if (!button || !this.root.contains(button) || button.disabled) return;
        event.preventDefault();
        if (this.destroyed || this.request || !this.currentGame) return;
        if (button.dataset.pageAction === 'first') return this.load(1, this.pagination.page_size, 0);
        if (button.dataset.pageAction === 'previous') return this.go(this.pagination.page - 1);
        if (button.dataset.pageAction === 'next') return this.go(this.pagination.page + 1);
    }

    handleChange(event) {
        const select = event.target?.closest?.('[data-page-size]');
        if (!select || !this.root.contains(select)) return;
        const pageSize = Number(select.value);
        if (this.destroyed || this.request || !this.currentGame || ![20, 50, 100].includes(pageSize)
            || pageSize === this.pagination.page_size) {
            this.render();
            return;
        }
        return this.load(1, pageSize, 0);
    }

    /** Commit rows and pagination together; a failed request leaves both intact. */
    async load(page, pageSize, snapshot) {
        const controller = new AbortController();
        const request = {game: this.currentGame, page, pageSize, snapshot, controller, generation: ++this.generation};
        this.request = request;
        this.error = '';
        this.render();
        request.timeout = this.timers.setTimeout(() => controller.abort(), 12000);
        try {
            const url = new URL(this.options.endpoint, this.baseUrl);
            url.searchParams.set('game', request.game);
            url.searchParams.set('page', String(page));
            url.searchParams.set('page_size', String(pageSize));
            url.searchParams.set('snapshot', String(snapshot));
            const response = await this.fetch(url.href, {signal: controller.signal, cache: 'no-store', credentials: 'same-origin'});
            if (!response.ok) throw new Error('History request failed');
            const payload = await response.json();
            if (!this.isCurrent(request)) return;
            if (controller.signal.aborted || payload.code !== 200 || !this.validData(payload.data, request)) {
                throw new Error('Unexpected history response');
            }
            this.options.onPage(payload.data);
            this.pagination = {...payload.data.pagination};
            this.hasData = true;
            const issue = payload.data.list[0]?.issue ?? payload.data.list[0]?.period;
            if (page === 1 && issue !== null && issue !== undefined) {
                if (this.latestIssue === null) this.latestIssue = String(issue);
                if (String(issue) === this.latestIssue) this.refreshQueued = false;
            }
        } catch {
            if (this.isCurrent(request)) this.error = '历史记录读取失败，已保留当前页面，请重试。';
        } finally {
            this.timers.clearTimeout(request.timeout);
            if (this.isCurrent(request)) {
                this.request = null;
                this.render();
                const shouldRefresh = this.refreshQueued && this.pagination.page === 1;
                this.refreshQueued = false;
                if (shouldRefresh) this.load(1, this.pagination.page_size, 0);
            }
        }
    }

    validData(data, request) {
        const page = data?.pagination;
        return data?.game?.code === request.game && Array.isArray(data.list) && page
            && Number.isInteger(page.page) && page.page >= 1
            && page.page_size === request.pageSize
            && Number.isInteger(page.total) && page.total >= 0
            && Number.isInteger(page.total_pages) && page.total_pages >= 1
            && page.page <= page.total_pages && page.page <= request.page
            && Number.isSafeInteger(page.snapshot) && page.snapshot >= 0
            && (request.snapshot === 0 || page.snapshot === request.snapshot);
    }

    isCurrent(request) {
        return !this.destroyed && this.request === request && request.generation === this.generation;
    }

    /** Synchronize visible and hidden layouts from the last successful page. */
    render() {
        if (this.destroyed) return;
        const busy = Boolean(this.request);
        const page = this.pagination;
        this.root.querySelectorAll('[data-history-pagination]').forEach(nav => nav.setAttribute('aria-busy', String(busy)));
        this.root.querySelectorAll('[data-page-action]').forEach(button => {
            const action = button.dataset.pageAction;
            button.disabled = busy || !this.currentGame
                || (action === 'previous' && (!this.hasData || page.page <= 1))
                || (action === 'next' && (!this.hasData || page.page >= page.total_pages));
        });
        this.root.querySelectorAll('[data-page-size]').forEach(select => {
            select.value = String(this.request?.pageSize ?? page.page_size);
            select.disabled = busy || !this.currentGame;
        });
        this.root.querySelectorAll('[data-page-status]').forEach(status => {
            status.textContent = `第 ${page.page}/${page.total_pages} 页，共 ${page.total} 期${busy ? '（读取中…）' : ''}`;
        });
        this.root.querySelectorAll('[data-page-error]').forEach(error => {
            error.textContent = this.error;
            error.hidden = !this.error;
        });
    }

    cancelRequest() {
        const request = this.request;
        if (!request) return;
        this.request = null;
        this.generation++;
        this.timers.clearTimeout(request.timeout);
        request.controller.abort();
    }

    destroy() {
        if (this.destroyed) return;
        this.destroyed = true;
        this.cancelRequest();
        this.root.removeEventListener('click', this.onClick);
        this.root.removeEventListener('change', this.onChange);
    }
}
