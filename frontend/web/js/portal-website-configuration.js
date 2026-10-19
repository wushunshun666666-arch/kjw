/** Load the site's public appearance configuration independently of draw polling. */
export function WebsiteConfiguration({request, apply, timers = globalThis}) {
    const delays = [2000, 4000, 8000, 16000, 30000];
    let completion;
    let complete;
    let attemptCount = 0;
    let timer = null;
    let stopped = false;

    /** Settle all callers and leave no configuration retry scheduled. */
    function finish(loaded) {
        stopped = true;
        if (timer !== null) timers.clearTimeout(timer);
        timer = null;
        complete?.(loaded);
    }

    /** Retry only the failed read, with one request or backoff timer at a time. */
    async function attempt() {
        attemptCount += 1;
        let response;
        try {
            response = await request();
        } catch {
            response = null;
        }
        if (stopped) return;
        if (response?.code === 200 && response.data && typeof response.data === 'object'
            && !Array.isArray(response.data) && Array.isArray(response.data.ad)) {
            apply(response.data);
            finish(true);
        } else if (attemptCount <= delays.length) {
            timer = timers.setTimeout(() => {
                timer = null;
                void attempt();
            }, delays[attemptCount - 1]);
        } else {
            finish(false);
        }
    }

    /** Repeated mounts share the same bounded configuration load. */
    function load() {
        if (!completion) {
            completion = new Promise(resolve => { complete = resolve; });
            if (stopped) complete(false);
            else void attempt();
        }
        return completion;
    }

    return {load, destroy: () => finish(false)};
}
