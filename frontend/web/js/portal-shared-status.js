/** Display server-declared cached data without modifying the draw panels. */
export function updateSharedDataStatus(element, data) {
    if (!element) return;
    const shared = data?.shared_data;
    if (!shared || typeof shared !== 'object' || Array.isArray(shared) || shared.stale !== true) {
        element.textContent = '';
        element.hidden = true;
        return;
    }

    let message = '共享开奖数据连接暂时中断，当前显示缓存记录';
    const timestamp = shared.fetched_at;
    if (Number.isSafeInteger(timestamp) && timestamp >= 0) {
        const date = new Date(timestamp * 1000);
        if (Number.isFinite(date.getTime())) {
            const parts = new Intl.DateTimeFormat('zh-CN', {
                timeZone: 'Asia/Shanghai', year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
            }).formatToParts(date);
            const fields = Object.fromEntries(parts.map(part => [part.type, part.value]));
            message += `（更新于 ${fields.year}-${fields.month}-${fields.day} ${fields.hour}:${fields.minute}:${fields.second} 北京时间）`;
        }
    }
    element.textContent = message;
    element.hidden = false;
}
