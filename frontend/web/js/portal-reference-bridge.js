/** Connect the pinned Vue shell to the local, independently rendered game panels. */
import {GamePortal} from './portal-games.js?v=20261019-6';
import {GameNavigation} from './portal-game-navigation.js?v=20261019-6';

export function mountPortal(store) {
    const root = document.getElementById('portal-home');
    const current = root.querySelector('#portal-current-host');
    const history = root.querySelector('#portal-history-host');
    const status = root.querySelector('#portal-switch-status');
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
        },
        render(data) {
            if (widget) widget.update(data);
            else widget = new GamePortal(current, history, data, () => navigation?.refresh());
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
            widget?.destroy();
            pauseCanada();
        },
    };
}
