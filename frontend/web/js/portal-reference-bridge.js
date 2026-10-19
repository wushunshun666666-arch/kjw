/** Connect the pinned Vue shell to the local, independently rendered game panels. */
import {GamePortal} from './portal-games.js?v=20261019-8';
import {GameNavigation} from './portal-game-navigation.js?v=20261019-8';
import {HistoryPagination} from './portal-history-pagination.js?v=20261019-8';
import {updateSharedDataStatus} from './portal-shared-status.js?v=20261019-8';

export function mountPortal(store) {
    const root = document.getElementById('portal-home');
    const current = root.querySelector('#portal-current-host');
    const history = root.querySelector('#portal-history-host');
    const status = root.querySelector('#portal-switch-status');
    const sharedStatus = root.querySelector('#portal-shared-status');
    let widget;
    let navigation;
    function pauseCanada() {
        store.portal_active = false;
        clearInterval(store.timer);
        store.timer = false;
    }
    function showStatus(message) {
        status.textContent = message;
        status.hidden = !message;
    }
    store.portal_error = message => {
        if (store.selected_game === 'jnd28') showStatus(message);
    };
    store.history_locked = true;
    store.history_page = 1;
    const pagination = new HistoryPagination({
        root,
        endpoint: window.portalHistoryEndpoint,
        onPage(data) {
            root.classList.toggle('portal-history-past', data.pagination.page > 1);
            if (data.game.code === 'jnd28') {
                store.result = data.list;
                store.history_page = data.pagination.page;
            } else widget?.updateHistory(data.list);
        },
    });
    store.history_refresh = data => {
        if (store.selected_game !== 'jnd28') return;
        updateSharedDataStatus(sharedStatus, data);
        pagination.observeLatest('jnd28', data.list[0]?.period);
    };
    const initialGame = new URL(location.href).searchParams.get('game') || 'jnd28';
    store.selected_game = initialGame;
    navigation = new GameNavigation({
        root,
        initialGame,
        initialData: window.portalInitialData?.game.code === initialGame ? window.portalInitialData : null,
        endpoint: window.portalGameEndpoint,
        title: window.portalTitle,
        onGame(code) {
            const dialog = history.querySelector('#portal-scratch-dialog');
            if (store.selected_game !== code && dialog.open) dialog.close();
            store.selected_game = code;
            if (code === 'jnd28') updateSharedDataStatus(sharedStatus, null);
            root.classList.remove('portal-history-past');
            if (code === 'jnd28') {
                store.result = [];
                store.history_page = 1;
            }
            pagination.selectGame(code);
            if (code !== 'jnd28') pagination.observeLatest(code, widget?.data.list[0]?.issue);
        },
        render(data) {
            updateSharedDataStatus(sharedStatus, data);
            if (widget) widget.update(data);
            else widget = new GamePortal(current, history, data, () => navigation?.refresh());
            pagination.observeLatest(data.game.code, data.list[0]?.issue);
        },
        setBusy(busy) {
            if (busy) showStatus('正在切换彩种…');
            else if (status.textContent === '正在切换彩种…') showStatus('');
        },
        onError(message) { showStatus(message); },
        activateCanada() { store.portal_active = true; store.cycle(); },
        deactivateCanada: pauseCanada,
    });
    store.init();
    return {
        destroy() {
            navigation.destroy();
            pagination.destroy();
            store.history_refresh = null;
            store.portal_error = null;
            store.history_locked = false;
            widget?.destroy();
            pauseCanada();
        },
    };
}
