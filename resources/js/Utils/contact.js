/**
 * Format a WhatsApp redirect URL from phone number, username, or URL.
 *
 * @param {string|null} input - Raw phone number or URL
 * @param {string} [customMessage] - Optional prefilled message
 * @returns {string} Fully formed WhatsApp redirect link
 */
export function buildWhatsAppUrl(input, customMessage = '') {
    const raw = String(input || '+251911234567').trim();

    // Already a complete URL
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
        if (customMessage && !raw.includes('text=')) {
            const sep = raw.includes('?') ? '&' : '?';
            return `${raw}${sep}text=${encodeURIComponent(customMessage)}`;
        }
        return raw;
    }

    if (raw.startsWith('wa.me/')) {
        const full = `https://${raw}`;
        if (customMessage && !full.includes('text=')) {
            const sep = full.includes('?') ? '&' : '?';
            return `${full}${sep}text=${encodeURIComponent(customMessage)}`;
        }
        return full;
    }

    // Extract digits
    let digits = raw.replace(/\D/g, '');

    // Normalize Ethiopian local mobile numbers: 09... or 07... -> 2519... or 2517...
    if ((digits.startsWith('09') || digits.startsWith('07')) && digits.length === 10) {
        digits = '251' + digits.slice(1);
    } else if ((digits.startsWith('9') || digits.startsWith('7')) && digits.length === 9) {
        digits = '251' + digits;
    }

    const base = `https://wa.me/${digits}`;
    if (customMessage) {
        return `${base}?text=${encodeURIComponent(customMessage)}`;
    }
    return base;
}

/**
 * Format a Telegram redirect URL from username, phone number, or URL.
 *
 * @param {string|null} input - Username (@username, username), phone number (+251911234567, 0911234567), or link
 * @param {string} [customMessage] - Optional prefilled message
 * @returns {string} Fully formed Telegram redirect link
 */
export function buildTelegramUrl(input, customMessage = '') {
    const raw = String(input || 'soniperfume_admin').trim();

    // Already a complete URL
    if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('tg://')) {
        if (customMessage && !raw.includes('text=')) {
            const sep = raw.includes('?') ? '&' : '?';
            return `${raw}${sep}text=${encodeURIComponent(customMessage)}`;
        }
        return raw;
    }

    if (raw.startsWith('t.me/')) {
        const full = `https://${raw}`;
        if (customMessage && !full.includes('text=')) {
            const sep = full.includes('?') ? '&' : '?';
            return `${full}${sep}text=${encodeURIComponent(customMessage)}`;
        }
        return full;
    }

    const digits = raw.replace(/\D/g, '');

    // If input consists purely of phone-like characters and has >= 8 digits
    if (/^[\d\s+\-()]+$/.test(raw) && digits.length >= 8) {
        let intlDigits = digits;
        if ((digits.startsWith('09') || digits.startsWith('07')) && digits.length === 10) {
            intlDigits = '251' + digits.slice(1);
        } else if ((digits.startsWith('9') || digits.startsWith('7')) && digits.length === 9) {
            intlDigits = '251' + digits;
        }

        const url = `https://t.me/+${intlDigits}`;
        if (customMessage) {
            return `${url}?text=${encodeURIComponent(customMessage)}`;
        }
        return url;
    }

    // Otherwise, treat as Telegram username (strip leading @)
    const username = raw.replace(/^@/, '');
    const url = `https://t.me/${username}`;
    if (customMessage) {
        return `${url}?text=${encodeURIComponent(customMessage)}`;
    }
    return url;
}
