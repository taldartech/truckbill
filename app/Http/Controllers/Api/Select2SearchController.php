<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consignee;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\Truck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Select2SearchController extends Controller
{
    public function drivers(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId();
        $queryTerm = trim((string) $request->query('q', ''));
        $limit = $this->normalizeLimit($request);

        if (mb_strlen($queryTerm) < 1) {
            return $this->emptyResultsResponse();
        }

        $query = Driver::query()
            ->select(['id', 'name', 'mobile', 'license_number'])
            ->where('company_id', $companyId);

        $query->where(function ($builder) use ($queryTerm) {
            $builder->where('name', 'like', "%{$queryTerm}%")
                ->orWhere('mobile', 'like', "%{$queryTerm}%")
                ->orWhere('license_number', 'like', "%{$queryTerm}%");
        });

        $items = $query->orderBy('name')->limit($limit)->get();

        $results = $items->map(function (Driver $driver) {
            $text = $driver->name ?? 'Unknown Driver';
            if (! empty($driver->mobile)) {
                $text .= ' ('.$driver->mobile.')';
            }

            return [
                'id' => $driver->id,
                'text' => $text,
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false],
        ]);
    }

    public function truckOwners(Request $request): JsonResponse
    {
        try {
            $companyId = $this->resolveCompanyId();
            $q = trim((string) $request->query('q', ''));
            $limit = $this->normalizeLimit($request);

            if (mb_strlen($q) < 1) {
                return $this->emptyResultsResponse();
            }

            $query = Transporter::query()
                ->with('transporterMaster')
                ->where('is_owner', 1)
                ->where('company_id', $companyId)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->select('transporters.*')
                ->where(function ($builder) use ($q) {
                    $builder->where('transporter_masters.name', 'like', "{$q}%")
                        ->orWhere('transporter_masters.pan_number', 'like', "{$q}%")
                        ->orWhere('transporters.mobile_number', 'like', "{$q}%")
                        ->orWhere('transporters.contact_person_name', 'like', "{$q}%");
                })
                ->orderBy('transporter_masters.name');

            $items = $query->limit($limit)->get();

            $results = $items->map(function (Transporter $owner) {
                return [
                    'id' => $owner->id,
                    'text' => $owner->name ?: 'Not Set',
                ];
            })->values();

            return response()->json([
                'results' => $results,
                'pagination' => ['more' => false],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching truck owners: '.$e->getMessage());

            return $this->emptyResultsResponse();
        }
    }

    public function agencies(Request $request): JsonResponse
    {
        try {
            $companyId = $this->resolveCompanyId();
            $q = trim((string) $request->query('q', ''));
            $page = max(1, (int) $request->query('page', 1));
            $limit = $this->normalizeLimit($request);

            $query = Transporter::query()
                ->with('transporterMaster')
                ->where('is_owner', 0)
                ->where('company_id', $companyId)
                ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->select('transporters.*');

            if ($q !== '') {
                $query->where(function ($builder) use ($q) {
                    $builder->where('transporters.short_name', 'like', "%{$q}%")
                        ->orWhere('transporters.mobile_number', 'like', "%{$q}%")
                        ->orWhere('transporters.contact_person_name', 'like', "%{$q}%")
                        ->orWhere('transporter_masters.name', 'like', "%{$q}%")
                        ->orWhere('transporter_masters.pan_number', 'like', "%{$q}%")
                        ->orWhere('transporter_masters.email', 'like', "%{$q}%");
                });
            }

            $offset = ($page - 1) * $limit;
            $items = $query->orderBy('transporter_masters.name')->offset($offset)->limit($limit + 1)->get();
            $more = $items->count() > $limit;
            $items = $items->take($limit);

            $results = $items->map(function (Transporter $agency) {
                return [
                    'id' => $agency->id,
                    'text' => $agency->name ?: 'Not Set',
                ];
            })->values();

            return response()->json([
                'results' => $results,
                'pagination' => ['more' => $more],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching agencies: '.$e->getMessage());

            return $this->emptyResultsResponse();
        }
    }

    public function trucks(Request $request): JsonResponse
    {
        try {
            $companyId = $this->resolveCompanyId();
            $q = trim((string) $request->query('q', ''));
            $page = max(1, (int) $request->query('page', 1));
            $limit = $this->normalizeLimit($request);

            $query = Truck::query()
                ->with(['transporter.transporterMaster', 'driver'])
                ->where(function ($builder) use ($companyId) {
                    $builder->whereHas('transporter', function ($subQuery) use ($companyId) {
                        $subQuery->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                });

            if ($q !== '') {
                $query->where(function ($builder) use ($q) {
                    $builder->where('truck_number', 'like', "%{$q}%")
                        ->orWhere('truck_type', 'like', "%{$q}%")
                        ->orWhereHas('transporter.transporterMaster', function ($masterQuery) use ($q) {
                            $masterQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('pan_number', 'like', "%{$q}%");
                        })
                        ->orWhereHas('driver', function ($driverQuery) use ($q) {
                            $driverQuery->where('name', 'like', "%{$q}%");
                        });
                });
            }

            $offset = ($page - 1) * $limit;
            $items = $query->orderBy('truck_number', 'asc')->offset($offset)->limit($limit + 1)->get();
            $more = $items->count() > $limit;
            $items = $items->take($limit);

            $results = $items->map(function (Truck $truck) {
                $transporterName = $truck->transporter ? ($truck->transporter->name ?: 'No Transporter') : 'No Transporter';
                $driverName = $truck->driver ? $truck->driver->name : null;

                $text = $truck->truck_number ?: '';
                if ($driverName) {
                    $text .= ' - Driver: '.$driverName;
                }
                $text .= ' ('.$transporterName.')';

                return [
                    'id' => $truck->id,
                    'text' => $text,
                    'transporter_id' => $truck->transporter_id,
                    'transporter_name' => $transporterName,
                    'driver_name' => $driverName,
                ];
            })->values();

            return response()->json([
                'results' => $results,
                'pagination' => ['more' => $more],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching trucks: '.$e->getMessage());

            return $this->emptyResultsResponse();
        }
    }

    public function consignees(Request $request): JsonResponse
    {
        try {
            $companyId = $this->resolveCompanyId();
            $q = trim((string) $request->query('q', ''));
            $page = max(1, (int) $request->query('page', 1));
            $limit = $this->normalizeLimit($request);

            $query = Consignee::query()
                ->with(['city', 'state', 'country'])
                ->where('company_id', $companyId);

            if ($q !== '') {
                $query->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('address', 'like', "%{$q}%")
                        ->orWhere('contact_number', 'like', "%{$q}%")
                        ->orWhere('gst_number', 'like', "%{$q}%")
                        ->orWhere('pan_number', 'like', "%{$q}%")
                        ->orWhereHas('city', function ($cityQuery) use ($q) {
                            $cityQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('state', function ($stateQuery) use ($q) {
                            $stateQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('country', function ($countryQuery) use ($q) {
                            $countryQuery->where('name', 'like', "%{$q}%");
                        });
                });
            }

            $offset = ($page - 1) * $limit;
            $items = $query->orderBy('name', 'asc')->offset($offset)->limit($limit + 1)->get();
            $more = $items->count() > $limit;
            $items = $items->take($limit);

            $results = $items->map(function (Consignee $consignee) {
                return [
                    'id' => $consignee->id,
                    'text' => $consignee->name ?: 'Not Set',
                ];
            })->values();

            return response()->json([
                'results' => $results,
                'pagination' => ['more' => $more],
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching consignees: '.$e->getMessage());

            return $this->emptyResultsResponse();
        }
    }

    private function normalizeLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', 20);

        return max(1, min($limit, 50));
    }

    private function emptyResultsResponse(): JsonResponse
    {
        return response()->json([
            'results' => [],
            'pagination' => ['more' => false],
        ]);
    }

    private function resolveCompanyId(): ?int
    {
        $user = Auth::user();

        if ($user?->userRole?->name === 'super_admin') {
            return session('super_admin_company_id');
        }

        return $user?->company_id;
    }
}
