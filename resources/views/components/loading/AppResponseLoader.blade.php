<div
    id="truckResponseLoader"
    class="truck-response-loader"
    role="alert"
    aria-live="assertive"
    aria-busy="true"
>
    <div class="truck-response-loader__panel">

        @include('components.loading.TruckLoader')

    </div>
</div>

<style>
.truck-response-loader {
    position: fixed;
    inset: 0;
    z-index: 99999;

    display: none;
    align-items: center;
    justify-content: center;

    background: rgba(15, 23, 42, 0.28);
    backdrop-filter: blur(2px);
}

.truck-response-loader.show {
    display: flex;
}

.truck-response-loader__panel {
    padding: 20px 24px;
    border-radius: 16px;
    background: #fff;
    border: 1px solid #dee2e6;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
}
</style>

<script>
window.showTruckLoader = function () {
    const loader = document.getElementById('truckResponseLoader');

    if (loader) {
        loader.classList.add('show');
    }
};

window.hideTruckLoader = function () {
    const loader = document.getElementById('truckResponseLoader');

    if (loader) {
        loader.classList.remove('show');
    }
};
</script>
