<?php

namespace App\Http\Controllers;

use App\Models\GoodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Imports\GoodsTypeImport;
use Illuminate\Support\Facades\Validator;

class GoodsTypeController extends Controller
{

    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getGoodsTypesData($request);
        }

        return view('master.goods-types');
    }

    private function getGoodsTypesData(Request $request)
    {
        $query = GoodType::where('user_id', Auth::user()->company->user_id);

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where('name', 'like', "%{$searchValue}%");
        }

        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name column
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                    }
                }
            }
        }

        // Handle sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];

            switch ($orderColumn) {
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }

        // Get total count before pagination
        $totalRecords = GoodType::where('user_id', Auth::user()->company->user_id)->count();
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $goodsTypes = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $goodsTypes->map(function ($goodsType) {
            return [
                'id' => $goodsType->id,
                'name' => $goodsType->name,
                'actions' => $this->getActionButtons($goodsType)
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    private function getActionButtons($goodsType)
{
    $editUrl = route('master.goods-types.edit', $goodsType->id);
    $deleteUrl = route('master.goods-types.destroy', $goodsType->id);

    return '
        <div class="app-index-table-actions">

            <a href="' . $editUrl . '"
                class="btn btn-sm btn-icon btn-action-edit"
                title="Edit Goods Type">
                <i class="bi bi-pencil"></i>
            </a>

            <form action="' . $deleteUrl . '"
                method="POST"
                class="good-type-delete-form"
                style="display:inline-block;">

                ' . csrf_field() . '
                ' . method_field('DELETE') . '

                <button type="button"
                    class="btn btn-sm btn-icon btn-action-delete good-type-delete-btn"
                    title="Delete Goods Type"
                    data-bs-toggle="modal"
                    data-bs-target="#goodTypeDeleteModal"
                    data-good-type-id="' . $goodsType->id . '"
                    data-good-type-name="' . e($goodsType->name ?? '-') . '">

                    <i class="bi bi-trash"></i>
                </button>

            </form>

        </div>
    ';
}

    public function create()
    {

        return view('master.goods-types-form', ['goodsType' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('good_types', 'name')
                    ->where('user_id', Auth::user()->company->user_id),
            ],
        ]);


        $validated['user_id'] = Auth::user()->company->user_id;
        $goodsType = GoodType::create($validated);
        if ($request->ajax() || $request->has('from_modal')) {
            return response()->json([
                'success' => true,
                'message' => 'Goods type created successfully',
                'goodsType' => $goodsType
            ]);
        }
        return redirect()->route('master.goods-types.index')->with('success', 'Goods type created successfully');
    }


    public function bulkUpload(Request $request)
{
    $request->validate([
        'file' => [
            'required',
            'file',
            'max:10240',
        ],
    ]);

    try {
        $import = new GoodsTypeImport();

        \Maatwebsite\Excel\Facades\Excel::import(
            $import,
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => $import->imported . ' goods type(s) imported successfully.',
            'imported' => $import->imported,
            'failed' => count($import->errors),
            'errors' => $import->errors,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Bulk upload failed.',
            'error' => $e->getMessage(),
        ], 422);
    }
}


public function bulkUploadTemplate()
{
    $headers = [
        'Name',
    ];

    $callback = function () use ($headers) {
        $file = fopen('php://output', 'w');

        fputcsv($file, $headers);

        fclose($file);
    };

    return response()->streamDownload($callback, 'goods_type_template.csv', [
        'Content-Type' => 'text/csv',
    ]);
}

    public function edit(GoodType $goodsType)
    {

        return view('master.goods-types-form', compact('goodsType'));
    }

    public function update(Request $request, GoodType $goodsType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $goodsType->update($validated);
        return redirect()->route('master.goods-types.index')->with('success', 'Goods type updated successfully');
    }

   public function destroy(Request $request, GoodType $goodsType)
{
    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $goodsType->deleted_by = Auth::id();
    $goodsType->deleted_remark = $request->input('delete_remark');
    $goodsType->save();

    $goodsType->delete();

    return redirect()
        ->route('master.goods-types.index')
        ->with('success', 'Good type deleted successfully!');
}
}
