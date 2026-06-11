<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransporterMasterRequest;
use App\Http\Requests\UpdateTransporterMasterRequest;
use App\Models\Country;
use App\Models\TransporterMaster;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransporterMasterController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->getTransporterMastersData($request);
        }

        return view('master.transporter-masters');
    }

    public function create(): View
    {
        return view('master.transporter-masters-form', [
            'transporterMaster' => null,
            'countries' => Country::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTransporterMasterRequest $request): RedirectResponse
    {
        TransporterMaster::query()->create($request->prepareTransporterMasterPayload());

        return redirect()
            ->route('master.transporter-masters.index')
            ->with('success', 'Transporter master created successfully.');
    }

    public function edit(TransporterMaster $transporterMaster): View
    {
        $transporterMaster->load(['city', 'state', 'country']);

        return view('master.transporter-masters-form', [
            'transporterMaster' => $transporterMaster,
            'countries' => Country::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateTransporterMasterRequest $request, TransporterMaster $transporterMaster): RedirectResponse
    {
        $payload = $request->prepareTransporterMasterPayload();
        $transporterMaster->update($payload);

        User::query()
            ->where('transporter_master_id', $transporterMaster->id)
            ->update([
                'name' => $payload['name'],
                'email' => $payload['email'],
            ]);

        return redirect()
            ->route('master.transporter-masters.index')
            ->with('success', 'Transporter master updated successfully.');
    }

    public function destroy(TransporterMaster $transporterMaster): RedirectResponse
    {
        $linkedTransporters = $transporterMaster->transporters()->count();

        if ($linkedTransporters > 0) {
            return redirect()
                ->route('master.transporter-masters.index')
                ->with('error', "Cannot delete this master record because it is linked to {$linkedTransporters} transporter registration(s).");
        }

        $transporterMaster->delete();

        return redirect()
            ->route('master.transporter-masters.index')
            ->with('success', 'Transporter master deleted successfully.');
    }

    private function getTransporterMastersData(Request $request): JsonResponse
    {
        $query = TransporterMaster::query()
            ->with(['city', 'state', 'country'])
            ->withCount('transporters');

        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($builder) use ($searchValue): void {
                $builder->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('pan_number', 'like', "%{$searchValue}%")
                    ->orWhere('gst_number', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('order')) {
            $orderColumn = (int) $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir', 'asc');

            $columnMap = [
                0 => 'id',
                1 => 'name',
                2 => 'email',
                3 => 'pan_number',
                4 => 'gst_number',
                5 => 'pan_active',
            ];

            $query->orderBy($columnMap[$orderColumn] ?? 'id', $orderDirection);
        } else {
            $query->orderByDesc('id');
        }

        $totalRecords = TransporterMaster::query()->count();
        $filteredRecords = (clone $query)->count();

        $masters = $query
            ->skip((int) ($request->input('start') ?? 0))
            ->take((int) ($request->input('length') ?? 10))
            ->get();

        $data = $masters->map(function (TransporterMaster $master): array {
            $location = collect([
                $master->city?->name,
                $master->state?->name,
                $master->country?->name,
            ])->filter()->implode(', ');

            return [
                'id' => $master->id,
                'name' => $master->name,
                'email' => $master->email,
                'pan_number' => $master->pan_number,
                'gst_number' => $master->gst_number ?: '-',
                'pan_active' => $master->pan_active ? 'Yes' : 'No',
                'location' => $location !== '' ? $location : '-',
                'transporters_count' => $master->transporters_count,
                'actions' => $this->getActionButtons($master),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons(TransporterMaster $master): string
    {
        $editUrl = route('master.transporter-masters.edit', $master->id);
        $deleteUrl = route('master.transporter-masters.destroy', $master->id);

        return '
            <div class="d-flex gap-1 justify-content-center">
                <a href="'.$editUrl.'" class="btn btn-sm btn-edit" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this transporter master?\')">
                    '.csrf_field().'
                    '.method_field('DELETE').'
                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        ';
    }
}
