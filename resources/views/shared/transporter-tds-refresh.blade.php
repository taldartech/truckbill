@if ($hasTdsMismatch)
    @if (($part ?? 'both') === 'alert' || ($part ?? 'both') === 'both')
        <div class="alert alert-warning mt-2 mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            The stored TDS % ({{ number_format($storedTdsPercentage, 2) }}%) does not match the current calculated rate
            ({{ number_format($calculatedTdsPercentage, 2) }}%) based on master PAN status and declaration.
            <button type="button" class="btn btn-sm btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#tdsRefreshModal">
                Refresh TDS %
            </button>
        </div>
    @endif

    @if (($part ?? 'both') === 'modal' || ($part ?? 'both') === 'both')
        <div class="modal fade" id="tdsRefreshModal" tabindex="-1" aria-labelledby="tdsRefreshModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title" id="tdsRefreshModalLabel">
                            <i class="fas fa-sync-alt me-2"></i>Confirm TDS Refresh
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please confirm you want to refresh the TDS percentage for this {{ strtolower($entityLabel) }}.</p>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="fw-bold">Previous TDS %:</td>
                                <td>{{ number_format($storedTdsPercentage, 2) }}%</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">New TDS %:</td>
                                <td>{{ number_format($calculatedTdsPercentage, 2) }}%</td>
                            </tr>
                        </table>
                        <p class="text-muted small mb-0 mt-3">
                            Eligible freight bookings will also be recalculated with the new TDS rate.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ $refreshRoute }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-sync-alt me-1"></i>Confirm Refresh
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
