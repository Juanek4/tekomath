// Attach the session token only to this tutor's same-origin mutation endpoints.
(() => {
    const apiRoot = new URL('../../api/', document.currentScript.src);
    const original = window.fetch.bind(window);
    let tokenPromise;
    window.fetch = async (input, init = {}) => {
        const url = new URL(input instanceof Request ? input.url : String(input), location.href);
        const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
        if (url.origin === location.origin && url.pathname.startsWith(apiRoot.pathname)
            && /\/(sessions|chat|diagnostic)\.php$/.test(url.pathname)
            && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            if (!tokenPromise) tokenPromise = original(new URL('security.php', apiRoot), {credentials:'same-origin', cache:'no-store'})
                .then(async response => {
                    const result = await response.json();
                    if (!response.ok || !result.token) throw new Error(result.error || 'Iniciá sesión nuevamente.');
                    return result.token;
                }).catch(error => {tokenPromise = null; throw error;});
            const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
            headers.set('X-Teko-CSRF', await tokenPromise);
            init = {...init, headers};
        }
        return original(input, init);
    };
})();
