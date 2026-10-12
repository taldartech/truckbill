document.addEventListener('DOMContentLoaded', function () {
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    if (!countrySelect || !stateSelect || !citySelect) {
        return;
    }

    let cityRequestId = 0;

    const resetSelect = (select, placeholder) => {
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
    };

    const appendOptions = (select, items) => {
        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });
    };

    countrySelect.addEventListener('change', function () {
        const countryId = this.value;

        resetSelect(stateSelect, 'Select State');
        resetSelect(citySelect, 'Select City');

        if (!countryId) {
            return;
        }

        fetch(`/api/states/${countryId}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((states) => {
                appendOptions(stateSelect, states);
            })
            .catch((error) => {
                console.error('Error fetching states:', error);
            });
    });

    stateSelect.addEventListener('change', function () {
        const stateId = this.value;
        const requestId = ++cityRequestId;

        resetSelect(citySelect, 'Select City');

        if (!stateId) {
            return;
        }

        fetch(`/api/cities/${stateId}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((cities) => {
                if (requestId !== cityRequestId || stateSelect.value !== stateId) {
                    return;
                }

                appendOptions(citySelect, cities);
            })
            .catch((error) => {
                console.error('Error fetching cities:', error);
            });
    });
});
