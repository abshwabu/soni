/**
 * Analytics helper composable for Plausible and Google Analytics 4 (GA4).
 */
export function useAnalytics() {
    /**
     * Dispatch an event to configured analytics trackers.
     *
     * @param {string} eventName
     * @param {object} params
     */
    const trackEvent = (eventName, params = {}) => {
        if (typeof window === 'undefined') return;

        // 1. Plausible Analytics custom event
        if (typeof window.plausible === 'function') {
            try {
                window.plausible(eventName, { props: params });
            } catch (e) {
                console.warn('Plausible trackEvent error', e);
            }
        }

        // 2. Google Analytics 4 (GA4) event
        if (typeof window.gtag === 'function') {
            try {
                window.gtag('event', eventName, params);
            } catch (e) {
                console.warn('GA4 trackEvent error', e);
            }
        }

        // 3. Development log
        if (process.env.NODE_ENV === 'development') {
            console.log(`[Analytics] Tracked: "${eventName}"`, params);
        }
    };

    return {
        trackEvent,
    };
}
