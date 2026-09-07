document.addEventListener('DOMContentLoaded', function() {
    // Handle country change
    const countrySelect = document.getElementById('country_id');
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');

    // Only add event listeners if elements exist
    if (countrySelect && stateSelect && citySelect) {
        countrySelect.addEventListener('change', function() {
        const countryId = this.value;
        
        // Clear state and city selections
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        // If country is selected, fetch states
        if (countryId) {
            fetch(`/api/states/${countryId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    return response.json();
                })
                .then(states => {
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.id;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching states:', error);
                });
        }
    });

    // Handle state change
let cityRequestId = 0;

stateSelect.addEventListener('change', function() {
    const stateId = this.value;
    const requestId = ++cityRequestId;

    citySelect.innerHTML = '<option value="">Select City</option>';

    if (!stateId) {
        return;
    }

    fetch(`/api/cities/${stateId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(cities => {
        if (requestId !== cityRequestId || stateSelect.value !== stateId) {
            return;
        }

        const options = cities.map(city =>
            `<option value="${city.id}">${city.name}</option>`
        ).join('');

        citySelect.innerHTML = '<option value="">Select City</option>' + options;
    })
    .catch(error => {
        console.error('Error fetching cities:', error);
    });
});
    }
});