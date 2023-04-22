
const getURLParams = () => {
    return new URLSearchParams(window.location.search);
};

const getFormSubmitDataSenderListener = (form) => (e) => {
    e.preventDefault();
    const formData = new FormData(form);

    let data = {};
    for (const [key, value] of formData.entries()) {
        if (Object.hasOwn(data, key)) {
            data[key] += ',' + value;
        }
        else {
            data[key] = value;
        }
    }
    const params = getURLParams();
    for (const [key, value] of Object.entries(data)) {
        params.set(key, value);
    }
    console.log(params.toString());
    window.location.search = params;
};


// Handle sortable column click
const getColumnSortBtnClickListener = () => (e) => {
    const btn = e.target;
    if (btn.hasAttribute('data-sort') && btn.hasAttribute('data-col')) {

        const col = btn.getAttribute('data-col');
        const params = getURLParams();
        const replaceRegex = new RegExp('((?:^|,)' + col + '\.)([^,]*)(,|$)');

        let sorting = params.get('sorting');
        if (sorting != null && sorting !== '') {
            let replaced = false;
            sorting = sorting.replace(replaceRegex,
                (match, p1, state, p2) => {
                    replaced = true;
                    if (state !== '0') {
                        return '';
                    }
                    return p1 + '1' + p2;
                });
            if (!replaced) {
                sorting += ',' + col + '.0';
            }
        }
        else {
            sorting = col + '.0';
        }

        params.set('sorting', sorting);
        window.location.search = params;
    }
};
export { getFormSubmitDataSenderListener, getColumnSortBtnClickListener };
