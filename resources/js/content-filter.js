const debounce = (callback, delay = 350) => {
    let timer;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => callback(...args), delay);
    };
};

const initialiseContentFilter = () => {
    const content = document.querySelector('[data-content-filter]');

    if (!content) {
        return;
    }

    const endpoint = content.dataset.url;
    const groupId = content.dataset.groupId || '';
    const search = document.getElementById('search');
    const sort = document.getElementById('sort');
    const groups = document.getElementById('groups');
    const loading = document.getElementById('loadingImage');
    const errorMessage = document.getElementById('content-filter-error');
    let activeController;

    const load = async (requestedUrl = endpoint) => {
        activeController?.abort();
        const requestController = new AbortController();
        activeController = requestController;

        const url = new URL(requestedUrl, window.location.origin);
        if (sort?.value) url.searchParams.set('sort', sort.value);
        if (groups?.value) url.searchParams.set('groups', groups.value);
        if (search?.value.trim()) url.searchParams.set('search', search.value.trim());
        if (groupId) url.searchParams.set('group_id', groupId);

        errorMessage?.setAttribute('hidden', '');
        loading?.removeAttribute('hidden');
        content.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const html = await response.text();

            if (activeController === requestController) {
                content.innerHTML = html;
            }
        } catch (error) {
            if (error.name !== 'AbortError' && activeController === requestController) {
                if (errorMessage) {
                    errorMessage.textContent = 'Assignments could not be loaded. Please try again.';
                    errorMessage.removeAttribute('hidden');
                }
            }
        } finally {
            if (activeController === requestController) {
                loading?.setAttribute('hidden', '');
                content.removeAttribute('aria-busy');
            }
        }
    };

    const delayedLoad = debounce(() => load(), 350);
    search?.addEventListener('input', delayedLoad);
    sort?.addEventListener('change', () => load());
    groups?.addEventListener('change', () => load());

    content.addEventListener('click', (event) => {
        const link = event.target.closest('#pagination a');
        if (!link) return;
        event.preventDefault();
        load(link.href);
    });
};

document.addEventListener('DOMContentLoaded', initialiseContentFilter);
