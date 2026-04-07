<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\Company;
use App\Models\EmailDispatchLog;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateMailSender;
use App\Services\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function index(): View
    {
        $templates = EmailTemplate::query()
            ->with('company:id,name')
            ->orderBy('name')
            ->get();

        $today = now()->startOfDay();

        $todaySummary = [
            'total' => EmailDispatchLog::query()->whereDate('created_at', $today)->count(),
            'sent' => EmailDispatchLog::query()->whereDate('created_at', $today)->where('status', 'sent')->count(),
            'failed' => EmailDispatchLog::query()->whereDate('created_at', $today)->where('status', 'failed')->count(),
            'delivered' => EmailDispatchLog::query()->whereDate('created_at', $today)->whereNotNull('delivered_at')->count(),
        ];

        $companyStats = EmailDispatchLog::query()
            ->with('company:id,name')
            ->whereDate('created_at', $today)
            ->selectRaw('company_id, COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw('SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as delivered_count')
            ->groupBy('company_id')
            ->orderByDesc('total_count')
            ->get();

        return view('email_templates.index', compact('templates', 'todaySummary', 'companyStats'));
    }

    public function create(): View
    {
        return view('email_templates.create', [
            'template' => new EmailTemplate,
            'availableVariables' => $this->availableVariables(),
            'availableRoles' => $this->availableRoles(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        EmailTemplate::query()->create(
            $this->payload(
                $request->validated(),
                $request->boolean('is_active'),
                $request->boolean('send_to_transporter'),
                $request->boolean('send_to_customer')
            )
        );

        return redirect()
            ->route('email_templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit(EmailTemplate $email_template): View
    {
        return view('email_templates.edit', [
            'template' => $email_template,
            'availableVariables' => $this->availableVariables(),
            'availableRoles' => $this->availableRoles(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $email_template): RedirectResponse
    {
        $email_template->update(
            $this->payload(
                $request->validated(),
                $request->boolean('is_active'),
                $request->boolean('send_to_transporter'),
                $request->boolean('send_to_customer')
            )
        );

        return redirect()
            ->route('email_templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function preview(EmailTemplate $email_template): View
    {
        $sampleVariables = [];
        foreach (($email_template->allowed_variables ?? []) as $key) {
            $sampleVariables[$key] = 'Sample '.ucfirst($key);
        }

        $renderedBody = $this->renderer->render($email_template->body_html, $sampleVariables);
        $detectedVariables = $this->renderer->extractVariables($email_template->body_html);

        return view('email_templates.preview', compact('email_template', 'renderedBody', 'sampleVariables', 'detectedVariables'));
    }

    public function sendTest(Request $request, EmailTemplate $email_template): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $sampleVariables = [];
        foreach (($email_template->allowed_variables ?? []) as $key) {
            $sampleVariables[$key] = 'Sample '.ucfirst($key);
        }

        $detectedVariables = $this->renderer->extractVariables($email_template->body_html);
        $notAllowed = array_diff($detectedVariables, $email_template->allowed_variables ?? []);
        if ($notAllowed !== []) {
            throw ValidationException::withMessages([
                'test_email' => 'Template contains variables not in allowed list: '.implode(', ', $notAllowed),
            ]);
        }

        $this->mailSender->sendTemplate($email_template, $validated['test_email'], $sampleVariables, [
            'triggered_by_type' => 'user',
            'triggered_by_id' => Auth::id(),
            'source' => 'admin_template_test',
        ]);

        return redirect()
            ->route('email_templates.preview', $email_template)
            ->with('success', 'Test email sent successfully to '.$validated['test_email']);
    }

    private function payload(array $validated, bool $isActive, bool $sendToTransporter, bool $sendToCustomer): array
    {
        $validated['allowed_variables'] = array_values(array_unique($validated['allowed_variables'] ?? []));
        $validated['allowed_roles'] = array_values(array_unique($validated['allowed_roles'] ?? []));
        $validated['send_to_transporter'] = $isActive ? $sendToTransporter : false;
        $validated['send_to_customer'] = $isActive ? $sendToCustomer : false;
        $validated['is_active'] = $isActive;

        return $validated;
    }

    private function availableVariables(): array
    {
        return [
            'name',
            'companyName',
            'lrNumber',
            'truckNumber',
            'date',
            'dateTime',
            'lrCreateDate',
            'status',
            'downloadUrl',
            'supportEmail',
            'welcomeLink',
            'resetLink',
            'voucherNumber',
        ];
    }

    private function availableRoles(): array
    {
        return [
            'super_admin',
            'admin',
            'moderator',
            'accountant',
            'master_admin',
        ];
    }
}
