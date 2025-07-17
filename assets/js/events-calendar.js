document.addEventListener('DOMContentLoaded', function () {
    let currentPage = 1;
    let totalPages = 1;

    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    const searchBar = document.getElementById('search-bar');
    const monthFilter = document.getElementById('month-filter');
    const townFilter = document.getElementById('town-filter');

    if (window.jQuery) {
        jQuery(startDate).add(endDate).datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
    }

    async function loadContent(postType = '', startDateVal = '', endDateVal = '', search = '', month = '', town = '', page = 1) {
        const container = document.getElementById('elementor-loop-content');
        container.innerHTML = '<p>Loading...</p>';
        try {
            const body = new URLSearchParams({
                action: 'load_elementor_loop_content',
                nonce: vgEvents.nonce,
                post_types: JSON.stringify(vgEvents.post_types),
                selected_post_type: postType,
                start_date: startDateVal,
                end_date: endDateVal,
                search: search,
                month: month,
                town: town,
                template_id: vgEvents.template_id,
                paged: page
            });
            const response = await fetch(vgEvents.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });
            const data = await response.json();
            if (data.success) {
                container.innerHTML = data.data.content;
                totalPages = data.data.total_pages;
                currentPage = data.data.current_page;
                updatePaginationControls();
            } else {
                container.innerHTML = '<p>No posts found.</p>';
            }
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

    loadContent();
});
