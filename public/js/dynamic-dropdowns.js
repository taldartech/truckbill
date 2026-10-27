(function () {
    let countrySelect = null;
    let stateSelect = null;
    let citySelect = null;
    let cityRequestId = 0;

    const resetSelect = (select, placeholder) => {
        if (!select) return;
        select.innerHTML = '';
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
    };

    const appendOptions = (select, items) => {
        if (!select || !Array.isArray(items)) return;
        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });
    };

    function loadStates(countryId, targetStateId) {
        if (!stateSelect) {
            stateSelect = document.getElementById('state_id');
            citySelect = document.getElementById('city_id');
        }
        if (!stateSelect) return Promise.resolve([]);

        if (!countryId) {
            resetSelect(stateSelect, 'Select State');
            resetSelect(citySelect, 'Select City');
            return Promise.resolve([]);
        }

        const sId = targetStateId || stateSelect.dataset.restoreVal || stateSelect.value;
        const cacheKey = 'tb_states_' + countryId;

        const applyStates = function (states) {
            stateSelect.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = 'Select State';
            stateSelect.appendChild(defaultOpt);

            appendOptions(stateSelect, states);
            if (sId) {
                const matchedOpt = stateSelect.querySelector(`option[value="${CSS.escape(sId)}"]`);
                if (matchedOpt) {
                    stateSelect.value = sId;
                    delete stateSelect.dataset.restoreVal;
                    stateSelect.dispatchEvent(new Event('input', { bubbles: true }));
                    stateSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) window.jQuery(stateSelect).trigger('change');
                }
            }
            return states;
        };

        try {
            const cached = sessionStorage.getItem(cacheKey);
            if (cached) {
                const states = JSON.parse(cached);
                if (Array.isArray(states) && states.length > 0) {
                    return Promise.resolve(applyStates(states));
                }
            }
        } catch (e) {}

        return fetch(`/api/states/${encodeURIComponent(countryId)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => (response.ok ? response.json() : []))
            .then((states) => {
                try { sessionStorage.setItem(cacheKey, JSON.stringify(states)); } catch (e) {}
                return applyStates(states);
            })
            .catch((error) => {
                console.error('Error fetching states:', error);
                return [];
            });
    }

    function loadCities(stateId, targetCityId) {
        if (!citySelect) {
            citySelect = document.getElementById('city_id');
            stateSelect = document.getElementById('state_id');
        }
        if (!citySelect) return Promise.resolve([]);
        const requestId = ++cityRequestId;

        if (!stateId) {
            resetSelect(citySelect, 'Select City');
            return Promise.resolve([]);
        }

        const cId = targetCityId || citySelect.dataset.restoreVal || citySelect.value;
        const cacheKey = 'tb_cities_' + stateId;

        const applyCities = function (cities) {
            if (requestId !== cityRequestId || (stateSelect && stateSelect.value && stateSelect.value !== String(stateId))) {
                return cities;
            }

            citySelect.innerHTML = '';
            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = 'Select City';
            citySelect.appendChild(defaultOpt);

            appendOptions(citySelect, cities);
            if (cId) {
                const matchedOpt = citySelect.querySelector(`option[value="${CSS.escape(cId)}"]`);
                if (matchedOpt) {
                    citySelect.value = cId;
                    delete citySelect.dataset.restoreVal;
                    citySelect.dispatchEvent(new Event('input', { bubbles: true }));
                    citySelect.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.jQuery) window.jQuery(citySelect).trigger('change');
                }
            }
            return cities;
        };

        try {
            const cached = sessionStorage.getItem(cacheKey);
            if (cached) {
                const cities = JSON.parse(cached);
                if (Array.isArray(cities) && cities.length > 0) {
                    return Promise.resolve(applyCities(cities));
                }
            }
        } catch (e) {}

        return fetch(`/api/cities/${encodeURIComponent(stateId)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => (response.ok ? response.json() : []))
            .then((cities) => {
                try { sessionStorage.setItem(cacheKey, JSON.stringify(cities)); } catch (e) {}
                return applyCities(cities);
            })
            .catch((error) => {
                console.error('Error fetching cities:', error);
                return [];
            });
    }

    function loadCascade(countryId, targetStateId, targetCityId) {
        if (!countrySelect) {
            countrySelect = document.getElementById('country_id');
            stateSelect = document.getElementById('state_id');
            citySelect = document.getElementById('city_id');
        }
        if (!countrySelect || !stateSelect) return Promise.resolve();

        if (countryId && countrySelect.value !== String(countryId)) {
            countrySelect.value = countryId;
        }

        if (targetStateId && stateSelect) {
            stateSelect.dataset.restoreVal = targetStateId;
        }
        if (targetCityId && citySelect) {
            citySelect.dataset.restoreVal = targetCityId;
        }

        // Parallel fetch: fetch states and cities simultaneously
        const pStates = loadStates(countryId, targetStateId);
        const pCities = (targetStateId && citySelect) ? loadCities(targetStateId, targetCityId) : Promise.resolve([]);
        return Promise.all([pStates, pCities]);
    }

    window.DynamicDropdowns = {
        loadStates: loadStates,
        loadCities: loadCities,
        loadCascade: loadCascade,
        resetSelect: resetSelect,
        appendOptions: appendOptions
    };

    function init() {
        countrySelect = document.getElementById('country_id');
        stateSelect = document.getElementById('state_id');
        citySelect = document.getElementById('city_id');

        if (!countrySelect || !stateSelect || !citySelect) {
            return;
        }

        // Avoid duplicate listener attachments
        if (countrySelect.dataset.dynamicDropdownsInit === '1') {
            return;
        }
        countrySelect.dataset.dynamicDropdownsInit = '1';

        countrySelect.addEventListener('change', function () {
            resetSelect(stateSelect, 'Select State');
            resetSelect(citySelect, 'Select City');
            loadStates(this.value);
        });

        stateSelect.addEventListener('change', function () {
            resetSelect(citySelect, 'Select City');
            loadCities(this.value);
        });

        if (window.jQuery) {
            window.jQuery(countrySelect).on('change.dynamicDropdowns', function (e) {
                if (e.isTrigger) {
                    resetSelect(stateSelect, 'Select State');
                    resetSelect(citySelect, 'Select City');
                    loadStates(this.value);
                }
            });
            window.jQuery(stateSelect).on('change.dynamicDropdowns', function (e) {
                if (e.isTrigger) {
                    resetSelect(citySelect, 'Select City');
                    loadCities(this.value);
                }
            });
        }

        // Auto-initialize if country is already selected (e.g. from draft or server old())
        // but states are not yet populated
        if (countrySelect.value && stateSelect.options.length <= 1) {
            const targetState = stateSelect.dataset.restoreVal || stateSelect.value;
            const targetCity = citySelect.dataset.restoreVal || citySelect.value;
            loadCascade(countrySelect.value, targetState, targetCity);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
