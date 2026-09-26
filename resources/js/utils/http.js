// Minimal JSON POST helper for write actions that must not trigger an Inertia page visit
// (a full visit would reset any comment/post list already scrolled past its first page via
// <InfiniteScroll>, since a plain visit only returns page one).
//
// Laravel issues the XSRF-TOKEN cookie automatically on every response for routes in the
// `web` middleware group; reading it back here is all VerifyCsrfToken needs from a JS client
// that isn't going through Inertia's own (CSRF-aware) request layer.
function xsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`POST ${url} failed with ${response.status}`);
    }

    return response.json();
}
