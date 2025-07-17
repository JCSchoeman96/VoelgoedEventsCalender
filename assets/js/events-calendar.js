document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let totalPages = 1;

    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    const searchBar = document.getElementById('search-bar');
    const monthFilter = document.getElementById('month-filter');
    const townFilter = document.getElementById('town-filter');

    function supportsDateInput() {
        const input = document.createElement('input');
        input.setAttribute('type', 'date');
        const value = 'not-a-date';
        input.setAttribute('value', value);
        return input.value !== value;
    }

    if (vgEvents.useDatepicker) {
        if (supportsDateInput()) {
            startDate.type = 'date';
            endDate.type = 'date';
        } else {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = vgEvents.flatpickr_css;
            document.head.appendChild(link);
            const script = document.createElement('script');
            script.src = vgEvents.flatpickr_js;
            script.onload = function () {
                if (window.flatpickr) {
                    flatpickr(startDate, { dateFormat: 'Y-m-d' });
                    flatpickr(endDate, { dateFormat: 'Y-m-d' });
                }
            };
            document.head.appendChild(script);
        }
    }

    if (vgEvents.towns && vgEvents.towns.length) {
        vgEvents.towns.forEach(function(t){
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            townFilter.appendChild(opt);
        });
    }

    if (vgEvents.months && vgEvents.months.length) {
        vgEvents.months.forEach(function(m){
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            monthFilter.appendChild(opt);
        });
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('town')) {
        townFilter.value = params.get('town');
    }
    if (params.get('month')) {
        monthFilter.value = params.get('month');
    }
    if (params.get('search')) {
        searchBar.value = params.get('search');
    }
    if (params.get('start')) {
        startDate.value = params.get('start');
    }
    if (params.get('end')) {
        endDate.value = params.get('end');
    }

    async function loadContent(postType = '', startDateVal = '', endDateVal = '', search = '', month = '', town = '', page = 1) {
        const container = document.getElementById('elementor-loop-content');
        container.innerHTML = '<p>Loading...</p>';
        try {
            const params = new URLSearchParams({
                post_types: vgEvents.post_types,
                selected_post_type: postType,
                start_date: startDateVal,
                end_date: endDateVal,
                search: search,
                month: month,
                town: town,
                template_id: vgEvents.template_id,
                paged: page,
                _wpnonce: vgEvents.nonce
            });
            const response = await fetch(vgEvents.rest_url + '?' + params.toString());
            const data = await response.json();
            container.innerHTML = data.content || '<p>No posts found.</p>';
            totalPages = data.total_pages || 1;
            currentPage = data.current_page || 1;
            updatePaginationControls();
        } catch (err) {
            console.error(err);
            container.innerHTML = '<p>Error loading content. Please try again.</p>';
        }
    }

    function updatePaginationControls() {
        document.getElementById('page-info').textContent = `${currentPage} van ${totalPages}`;
        document.getElementById('prev-page').disabled = currentPage === 1;
        document.getElementById('next-page').disabled = currentPage === totalPages;
    }

    document.getElementById('prev-page').addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            triggerLoad();
        }
    });

    document.getElementById('next-page').addEventListener('click', function () {
        if (currentPage < totalPages) {
            currentPage++;
            triggerLoad();
        }
    });

    function triggerLoad() {
        const active = document.querySelector('#post-type-filters .post-type-filter.active');
        const postType = active ? active.getAttribute('data-post-type') : '';
        loadContent(postType, startDate.value, endDate.value, searchBar.value, monthFilter.value, townFilter.value, currentPage);
    }

    document.querySelectorAll('#post-type-filters .post-type-filter').forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            currentPage = 1;
            triggerLoad();
        });
    });

    [startDate, endDate, searchBar, monthFilter, townFilter].forEach(function (el) {
        el.addEventListener('change', function () {
            currentPage = 1;
            triggerLoad();
        });
    });

    document.getElementById('reset-filters').addEventListener('click', function () {
        searchBar.value = '';
        townFilter.value = '';
        monthFilter.value = '';
        startDate.value = '';
        endDate.value = '';
        document.querySelectorAll('#post-type-filters .post-type-filter').forEach(i => i.classList.remove('active'));
        currentPage = 1;
        loadContent();
        const msg = document.getElementById('filter-reset-msg');
        msg.style.display = 'block';
        setTimeout(() => { msg.style.display = 'none'; }, 2500);
    });

    triggerLoad();
});
