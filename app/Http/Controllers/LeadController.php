<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateOnboardingLinkRequest;
use App\Http\Requests\LeadStatusUpdateRequest;
use App\Http\Requests\RejectLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Services\LeadLifecycleService;
use App\Services\Notifications\OnboardingNotificationService;
use App\Services\OnboardingTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadLifecycleService $lifecycleService,
        private readonly OnboardingTokenService $tokenService,
        private readonly OnboardingNotificationService $onboardingNotificationService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            return $this->getLeadsData($request);
        }

        return view('leads.index');
    }

    public function store(StoreLeadRequest $request)
    {
        Lead::create([
            ...$request->validated(),
            'source' => 'manual',
        ]);

        return redirect()->route('leads.index')->with('success', 'Lead created successfully.');
    }

    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        $this->lifecycleService->ensureMutable($lead);
        $lead->update($request->validated());

        return redirect()->route('leads.index')->with('success', 'Lead updated successfully.');
    }

    public function updateStatus(LeadStatusUpdateRequest $request, Lead $lead): JsonResponse
    {
        $updated = $this->lifecycleService->transition($lead, $request->validated('status'));

        return response()->json([
            'message' => 'Lead status updated successfully.',
            'status' => $updated->status,
        ]);
    }

    public function reject(RejectLeadRequest $request, Lead $lead): JsonResponse
    {
        $updated = $this->lifecycleService->transition(
            $lead,
            Lead::STATUS_REJECTED,
            $request->validated('rejection_reason')
        );

        return response()->json([
            'message' => 'Lead rejected successfully.',
            'status' => $updated->status,
        ]);
    }

    public function generateOnboardingLink(GenerateOnboardingLinkRequest $request, Lead $lead): JsonResponse
    {
        $this->lifecycleService->ensureMutable($lead);

        if (empty($lead->email)) {
            throw ValidationException::withMessages([
                'email' => 'Lead email is required to send onboarding link.',
            ]);
        }

        $tokenPayload = $this->tokenService->generateForLead($lead, (int) Auth::id());
        $frontendBaseUrl = rtrim((string) config('services.onboarding.frontend_base_url'), '/');
        $link = $frontendBaseUrl.'/onboard/'.$tokenPayload['plain_token'];
        $this->onboardingNotificationService->sendLeadOnboardingLink($lead->email, $lead->name, $link);

        return response()->json([
            'message' => 'Onboarding link generated and sent to lead email successfully.',
            'url' => $link,
        ]);
    }

    private function getLeadsData(Request $request): JsonResponse
    {
        $query = Lead::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->string('source'));
        }
        if ($request->has('search') && ! empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('created_at');
        $total = Lead::query()->count();
        $filtered = (clone $query)->count();

        $start = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (Lead $lead, int $index) use ($start): array {
            return [
                'id' => $index + $start + 1,
                'name' => $lead->name,
                'mobile' => $lead->mobile,
                'email' => $lead->email ?: '-',
                'status' => $lead->status,
                'source' => $lead->source,
                'created_at' => $lead->created_at?->format('Y-m-d H:i:s') ?? '-',
                'actions' => $this->actionButtons($lead),
            ];
        });

        return response()->json([
            'draw' => (int) $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    private function actionButtons(Lead $lead): string
    {
        if (in_array($lead->status, [Lead::STATUS_REJECTED, Lead::STATUS_ACTIVATED], true)) {
            return '<span class="text-muted">No actions</span>';
        }

        return '<div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-edit js-lead-status" data-id="'.$lead->id.'" data-status="DEMO_SCHEDULED">Demo Scheduled</button>
            <button type="button" class="btn btn-sm btn-edit js-lead-status" data-id="'.$lead->id.'" data-status="DEMO_COMPLETED">Demo Completed</button>
            <button type="button" class="btn btn-sm btn-primary js-generate-link" data-id="'.$lead->id.'">Generate Link</button>
            <button type="button" class="btn btn-sm btn-danger js-reject-lead" data-id="'.$lead->id.'">Reject</button>
        </div>';
    }
}
