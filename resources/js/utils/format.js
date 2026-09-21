const locale = () => (typeof document !== 'undefined' && document.documentElement.lang) || 'en';

const UNITS = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

// "2 hours ago", "yesterday", "hace 3 días"... Intl handles the wording for the page language.
export function timeAgo(iso) {
    const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000);
    const rtf = new Intl.RelativeTimeFormat(locale(), { numeric: 'auto' });

    for (const [unit, size] of UNITS) {
        if (Math.abs(seconds) >= size) {
            return rtf.format(Math.trunc(seconds / size), unit);
        }
    }

    return rtf.format(0, 'second');
}

// Short preview of an article for feeds: one line of text, cut at a word boundary when possible.
export function excerpt(text, max = 180) {
    const flat = text.replace(/\s+/g, ' ').trim();
    if (flat.length <= max) return flat;

    const cut = flat.slice(0, max);
    const lastSpace = cut.lastIndexOf(' ');

    return (lastSpace > max * 0.6 ? cut.slice(0, lastSpace) : cut).trimEnd() + '…';
}

export const formatCount = (n) => new Intl.NumberFormat(locale()).format(n);
