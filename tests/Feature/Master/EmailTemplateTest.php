<?php

namespace Tests\Feature\Master;

use App\Jobs\SendTemplateEmailJob;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\EmailDispatchLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\UserRole;
use App\Services\EmailTemplateMailSender;
use App\Services\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    private function makeUser(string $role): User
    {
        $roleId = UserRole::where('name', $role)->value('id');

        $company = Company::create([
            'name' => 'Template Co ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $user = User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);

        $company->update(['user_id' => $user->id]);

        return $user;
    }

    public function test_master_admin_can_view_email_templates_index(): void
    {
        $master = $this->makeUser('master_admin');
        $company = $master->company;

        $template = EmailTemplate::create([
            'key' => 'welcome_email',
            'name' => 'Welcome Email',
            'company_id' => $company->id,
            'subject' => 'Welcome to {{ companyName }}',
            'body_html' => '<p>Hello {{ name }}</p>',
            'allowed_variables' => ['name', 'companyName'],
            'allowed_roles' => ['admin'],
            'is_active' => true,
        ]);

        // Seed some dispatch logs for today
        EmailDispatchLog::create([
            'email_template_id' => $template->id,
            'company_id' => $company->id,
            'event_key' => 'welcome_email',
            'recipient_email' => 'client@example.com',
            'subject_snapshot' => 'Welcome',
            'body_snapshot' => 'Hello',
            'status' => 'sent',
            'delivered_at' => now(),
            'queued_at' => now(),
        ]);
        EmailDispatchLog::create([
            'email_template_id' => $template->id,
            'company_id' => $company->id,
            'event_key' => 'welcome_email',
            'recipient_email' => 'fail@example.com',
            'subject_snapshot' => 'Welcome',
            'body_snapshot' => 'Hello',
            'status' => 'failed',
            'delivered_at' => null,
            'queued_at' => now(),
        ]);

        $response = $this->actingAs($master)->get(route('email_templates.index'));

        $response->assertOk()
            ->assertViewIs('email_templates.index')
            ->assertViewHas('templates')
            ->assertViewHas('todaySummary')
            ->assertViewHas('companyStats');

        $summary = $response->viewData('todaySummary');
        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['sent']);
        $this->assertSame(1, $summary['failed']);
        $this->assertSame(1, $summary['delivered']);
    }

    public function test_master_admin_can_view_create_page(): void
    {
        $master = $this->makeUser('master_admin');

        $response = $this->actingAs($master)->get(route('email_templates.create'));

        $response->assertOk()
            ->assertViewIs('email_templates.create')
            ->assertViewHas('template')
            ->assertViewHas('availableVariables')
            ->assertViewHas('availableRoles')
            ->assertViewHas('companies');
    }

    public function test_master_admin_can_store_email_template_with_payload_normalization(): void
    {
        $master = $this->makeUser('master_admin');

        $response = $this->actingAs($master)->post(route('email_templates.store'), [
            'key' => 'invoice_created',
            'name' => 'Invoice Created Notification',
            'subject' => 'New Invoice #{{ invoiceNumber }}',
            'body_html' => '<p>Dear {{ customerName }}, invoice generated.</p>',
            'allowed_variables' => ['invoiceNumber', 'customerName', 'customerName'], // duplicate to test unique
            'allowed_roles' => ['admin', 'super_admin', 'admin'],
            'send_to_transporter' => '1',
            'send_to_customer' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('email_templates.index'))
            ->assertSessionHas('success', 'Email template created successfully.');

        $this->assertDatabaseHas('email_templates', [
            'key' => 'invoice_created',
            'name' => 'Invoice Created Notification',
            'is_active' => true,
            'send_to_transporter' => true,
            'send_to_customer' => true,
        ]);

        $template = EmailTemplate::where('key', 'invoice_created')->first();
        $this->assertSame(['invoiceNumber', 'customerName'], $template->allowed_variables);
        $this->assertSame(['admin', 'super_admin'], $template->allowed_roles);
    }

    public function test_store_deactivates_transporter_and_customer_flags_when_inactive(): void
    {
        $master = $this->makeUser('master_admin');

        $response = $this->actingAs($master)->post(route('email_templates.store'), [
            'key' => 'inactive_notice',
            'name' => 'Inactive Notice',
            'subject' => 'Notice',
            'body_html' => '<p>Notice</p>',
            'send_to_transporter' => '1',
            'send_to_customer' => '1',
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('email_templates.index'));

        $this->assertDatabaseHas('email_templates', [
            'key' => 'inactive_notice',
            'is_active' => false,
            'send_to_transporter' => false,
            'send_to_customer' => false,
        ]);
    }

    public function test_store_validates_required_fields_and_unique_key(): void
    {
        $master = $this->makeUser('master_admin');

        // Missing required fields
        $response = $this->actingAs($master)->post(route('email_templates.store'), []);
        $response->assertSessionHasErrors(['key', 'name', 'subject', 'body_html']);

        // Duplicate key validation
        EmailTemplate::create([
            'key' => 'unique_key_test',
            'name' => 'Existing',
            'subject' => 'Sub',
            'body_html' => '<p>Body</p>',
        ]);

        $dupResponse = $this->actingAs($master)->post(route('email_templates.store'), [
            'key' => 'unique_key_test',
            'name' => 'Duplicate Key',
            'subject' => 'Sub',
            'body_html' => '<p>Body</p>',
        ]);
        $dupResponse->assertSessionHasErrors(['key']);
    }

    public function test_master_admin_can_view_edit_page(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'lr_dispatched',
            'name' => 'LR Dispatched',
            'subject' => 'LR #{{ lrNumber }} Dispatched',
            'body_html' => '<p>LR {{ lrNumber }} on vehicle {{ truckNumber }}</p>',
            'allowed_variables' => ['lrNumber', 'truckNumber'],
        ]);

        $response = $this->actingAs($master)->get(route('email_templates.edit', $template));

        $response->assertOk()
            ->assertViewIs('email_templates.edit')
            ->assertViewHas('template', $template)
            ->assertViewHas('availableVariables')
            ->assertViewHas('availableRoles')
            ->assertViewHas('companies');
    }

    public function test_master_admin_can_update_email_template(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'payment_receipt',
            'name' => 'Payment Receipt',
            'subject' => 'Old Subject',
            'body_html' => '<p>Old Body</p>',
            'is_active' => true,
        ]);

        $response = $this->actingAs($master)->put(route('email_templates.update', $template), [
            'key' => 'payment_receipt',
            'name' => 'Payment Receipt Updated',
            'subject' => 'Updated: Payment #{{ voucherNumber }} Received',
            'body_html' => '<p>Thank you for payment of {{ amount }}.</p>',
            'allowed_variables' => ['voucherNumber', 'amount'],
            'allowed_roles' => ['admin'],
            'is_active' => '1',
            'send_to_customer' => '1',
        ]);

        $response->assertRedirect(route('email_templates.index'))
            ->assertSessionHas('success', 'Email template updated successfully.');

        $template->refresh();
        $this->assertSame('Payment Receipt Updated', $template->name);
        $this->assertSame('Updated: Payment #{{ voucherNumber }} Received', $template->subject);
        $this->assertSame(['voucherNumber', 'amount'], $template->allowed_variables);
    }

    public function test_master_admin_can_preview_email_template(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'preview_test_template',
            'name' => 'Preview Test',
            'subject' => 'Hello {{ name }}',
            'body_html' => '<p>Welcome {{ name }} to {{ companyName }}</p>',
            'allowed_variables' => ['name', 'companyName'],
        ]);

        $response = $this->actingAs($master)->get(route('email_templates.preview', $template));

        $response->assertOk()
            ->assertViewIs('email_templates.preview')
            ->assertViewHas('email_template')
            ->assertViewHas('renderedBody')
            ->assertViewHas('sampleVariables')
            ->assertViewHas('detectedVariables');

        $this->assertSame(['name', 'companyName'], $response->viewData('detectedVariables'));
    }

    public function test_master_admin_can_send_test_email_successfully(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'test_send_email',
            'name' => 'Test Send',
            'subject' => 'Test Subject {{ name }}',
            'body_html' => '<p>Test Body {{ name }}</p>',
            'allowed_variables' => ['name'],
            'is_active' => true,
        ]);

        $this->mock(EmailTemplateMailSender::class, function ($mock) use ($template) {
            $mock->shouldReceive('sendTemplate')
                ->once()
                ->with(
                    \Mockery::on(fn ($t) => $t->id === $template->id),
                    'testrecipient@example.com',
                    ['name' => 'Sample Name'],
                    \Mockery::subset([
                        'triggered_by_type' => 'user',
                        'source' => 'admin_template_test',
                    ])
                );
        });

        $response = $this->actingAs($master)->post(route('email_templates.send-test', $template), [
            'test_email' => 'testrecipient@example.com',
        ]);

        $response->assertRedirect(route('email_templates.preview', $template))
            ->assertSessionHas('success', 'Test email sent successfully to testrecipient@example.com');
    }

    public function test_send_test_email_fails_validation_when_template_has_unallowed_variables(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'unallowed_vars_template',
            'name' => 'Unallowed Vars',
            'subject' => 'Subject',
            'body_html' => '<p>Hello {{ unallowed_var_x }} and {{ allowed_var }}</p>',
            'allowed_variables' => ['allowed_var'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($master)->post(route('email_templates.send-test', $template), [
            'test_email' => 'valid@example.com',
        ]);

        $response->assertSessionHasErrors(['test_email']);
    }

    public function test_send_test_email_validates_email_format(): void
    {
        $master = $this->makeUser('master_admin');

        $template = EmailTemplate::create([
            'key' => 'invalid_email_test',
            'name' => 'Invalid Email Test',
            'subject' => 'Subject',
            'body_html' => '<p>Body</p>',
            'allowed_variables' => [],
        ]);

        $response = $this->actingAs($master)->post(route('email_templates.send-test', $template), [
            'test_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['test_email']);
    }

    public function test_non_master_admin_cannot_access_email_templates(): void
    {
        $admin = $this->makeUser('admin');

        $template = EmailTemplate::create([
            'key' => 'restricted_template',
            'name' => 'Restricted',
            'subject' => 'Restricted',
            'body_html' => '<p>Restricted</p>',
        ]);

        $this->actingAs($admin)->get(route('email_templates.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('email_templates.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('email_templates.store'), [
            'key' => 'attempt',
            'name' => 'Attempt',
            'subject' => 'Attempt',
            'body_html' => '<p>Attempt</p>',
        ])->assertForbidden();
        $this->actingAs($admin)->get(route('email_templates.edit', $template))->assertForbidden();
        $this->actingAs($admin)->put(route('email_templates.update', $template), [
            'key' => 'attempt_update',
            'name' => 'Attempt Update',
            'subject' => 'Attempt Update',
            'body_html' => '<p>Attempt Update</p>',
        ])->assertForbidden();
        $this->actingAs($admin)->get(route('email_templates.preview', $template))->assertForbidden();
        $this->actingAs($admin)->post(route('email_templates.send-test', $template), [
            'test_email' => 'admin@example.com',
        ])->assertForbidden();
    }

    // ==========================================
    // EmailTemplateRenderer Tests
    // ==========================================

    public function test_renderer_replaces_placeholders_with_variables(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'Hello {{ name }}, welcome to {{ company }}!';
        $variables = [
            'name' => 'Rajesh',
            'company' => 'Transport Co',
        ];

        $result = $renderer->render($template, $variables);

        $this->assertSame('Hello Rajesh, welcome to Transport Co!', $result);
    }

    public function test_renderer_escapes_html_entities_in_values(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'Welcome <p>{{ user_input }}</p>';
        $variables = [
            'user_input' => '<script>alert("xss")</script> & "quotes"',
        ];

        $result = $renderer->render($template, $variables);

        $this->assertSame('Welcome <p>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt; &amp; &quot;quotes&quot;</p>', $result);
    }

    public function test_renderer_handles_missing_variables_as_empty_string(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'ID: {{ id }}, Code: {{ code }}';
        $variables = [
            'id' => 101,
        ];

        $result = $renderer->render($template, $variables);

        $this->assertSame('ID: 101, Code: ', $result);
    }

    public function test_renderer_handles_whitespace_inside_braces(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'Values: {{   first_name  }} and {{last_name}}';
        $variables = [
            'first_name' => 'Amit',
            'last_name' => 'Sharma',
        ];

        $result = $renderer->render($template, $variables);

        $this->assertSame('Values: Amit and Sharma', $result);
    }

    public function test_renderer_extract_variables_finds_unique_keys(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'Hello {{ name }}, order {{ order_id }} for {{ name }} at {{ date }}.';

        $keys = $renderer->extractVariables($template);

        $this->assertSame(['name', 'order_id', 'date'], $keys);
    }

    public function test_renderer_extract_variables_returns_empty_when_no_placeholders(): void
    {
        $renderer = new EmailTemplateRenderer();
        $template = 'Plain email without curly braces or {{ 123invalid }}.';

        $keys = $renderer->extractVariables($template);

        $this->assertSame([], $keys);
    }

    // ==========================================
    // EmailTemplateMailSender Tests
    // ==========================================

    private function getMailSender(): EmailTemplateMailSender
    {
        Config::set('app.name', 'TruckBill');
        return new EmailTemplateMailSender(new EmailTemplateRenderer());
    }

    public function test_mail_sender_throws_exception_when_template_inactive(): void
    {
        $template = EmailTemplate::create([
            'key' => 'inactive_tpl',
            'name' => 'Inactive Template',
            'subject' => 'Subject',
            'body_html' => '<p>Body</p>',
            'is_active' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email template is inactive.');

        $this->getMailSender()->sendTemplate($template, 'user@example.com');
    }

    public function test_mail_sender_filters_variables_and_dispatches_jobs_for_valid_emails(): void
    {
        Queue::fake();

        $template = EmailTemplate::create([
            'key' => 'order_placed',
            'name' => 'Order Placed',
            'subject' => 'Order #{{ order_id }}',
            'body_html' => '<p>Hello {{ name }}, secret: {{ secret }}</p>',
            'allowed_variables' => ['name', 'order_id'],
            'is_active' => true,
        ]);

        $variables = [
            'name' => 'Vikas',
            'order_id' => 'ORD-123',
            'secret' => 'SHOULD_BE_FILTERED_OUT',
        ];

        $recipients = [
            'valid1@example.com',
            'not-an-email',
            12345,
            'valid2@example.com',
        ];

        $meta = [
            'company_id' => 10,
            'triggered_by_type' => 'user',
            'triggered_by_id' => 1,
            'source' => 'manual_test',
        ];

        $this->getMailSender()->sendTemplate($template, $recipients, $variables, $meta);

        $this->assertDatabaseCount('email_dispatch_logs', 2);

        $log1 = EmailDispatchLog::where('recipient_email', 'valid1@example.com')->first();
        $this->assertNotNull($log1);
        $this->assertSame(10, $log1->company_id);
        $this->assertSame('order_placed', $log1->event_key);
        $this->assertSame('Order #ORD-123 || TruckBill', $log1->subject_snapshot);
        $this->assertSame('<p>Hello Vikas, secret: </p>', $log1->body_snapshot);
        $this->assertSame('queued', $log1->status);
        $this->assertSame('user', $log1->triggered_by_type);
        $this->assertSame(1, $log1->triggered_by_id);
        $this->assertSame('manual_test', $log1->source);

        $this->assertDatabaseHas('email_dispatch_logs', [
            'recipient_email' => 'valid2@example.com',
        ]);

        Queue::assertPushed(SendTemplateEmailJob::class, 2);
    }

    public function test_mail_sender_uses_template_company_id_and_single_string_recipient(): void
    {
        Queue::fake();

        $company = Company::create([
            'name' => 'Test Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
        ]);

        $template = EmailTemplate::create([
            'key' => 'single_recipient_tpl',
            'name' => 'Single Recipient',
            'company_id' => $company->id,
            'subject' => 'Welcome',
            'body_html' => '<p>Hello</p>',
            'is_active' => true,
        ]);

        $this->getMailSender()->sendTemplate($template, 'single@example.com');

        $this->assertDatabaseHas('email_dispatch_logs', [
            'company_id' => $company->id,
            'recipient_email' => 'single@example.com',
            'triggered_by_type' => 'system',
            'source' => 'system',
        ]);

        Queue::assertPushed(SendTemplateEmailJob::class, 1);
    }

    public function test_mail_sender_with_subject_brand_suffix_scenarios(): void
    {
        Queue::fake();
        $sender = $this->getMailSender();

        // 1. Subject already ending with ' || TruckBill'
        $template1 = EmailTemplate::create([
            'key' => 'branded_already',
            'name' => 'Branded',
            'subject' => 'Invoice Ready || TruckBill',
            'body_html' => '<p>Invoice</p>',
            'is_active' => true,
        ]);
        $sender->sendTemplate($template1, 'b1@example.com');
        $log1 = EmailDispatchLog::where('recipient_email', 'b1@example.com')->first();
        $this->assertSame('Invoice Ready || TruckBill', $log1->subject_snapshot);

        // 2. Subject ending with brand name directly
        $template2 = EmailTemplate::create([
            'key' => 'brand_at_end',
            'name' => 'Brand End',
            'subject' => 'Welcome to TruckBill!',
            'body_html' => '<p>Welcome</p>',
            'is_active' => true,
        ]);
        $sender->sendTemplate($template2, 'b2@example.com');
        $log2 = EmailDispatchLog::where('recipient_email', 'b2@example.com')->first();
        $this->assertSame('Welcome to TruckBill!', $log2->subject_snapshot);

        // 3. Empty subject returns brand
        $template3 = EmailTemplate::create([
            'key' => 'empty_subject',
            'name' => 'Empty Subject',
            'subject' => '',
            'body_html' => '<p>Empty</p>',
            'is_active' => true,
        ]);
        $sender->sendTemplate($template3, 'b3@example.com');
        $log3 = EmailDispatchLog::where('recipient_email', 'b3@example.com')->first();
        $this->assertSame('TruckBill', $log3->subject_snapshot);

        // 4. When app.name config is empty string
        Config::set('app.name', '');
        $template4 = EmailTemplate::create([
            'key' => 'empty_config_brand',
            'name' => 'Empty Config Brand',
            'subject' => 'Payment Reminder',
            'body_html' => '<p>Pay</p>',
            'is_active' => true,
        ]);
        $sender->sendTemplate($template4, 'b4@example.com');
        $log4 = EmailDispatchLog::where('recipient_email', 'b4@example.com')->first();
        $this->assertSame('Payment Reminder || TruckBill', $log4->subject_snapshot);
    }

    public function test_mail_sender_send_by_key_throws_exception_when_template_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email template not found for key: non_existent_key');

        $this->getMailSender()->sendByKey('non_existent_key', 'user@example.com');
    }

    public function test_mail_sender_send_by_key_throws_exception_when_template_is_inactive(): void
    {
        EmailTemplate::create([
            'key' => 'inactive_key_test',
            'name' => 'Inactive',
            'subject' => 'Sub',
            'body_html' => '<p>Body</p>',
            'is_active' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email template not found for key: inactive_key_test');

        $this->getMailSender()->sendByKey('inactive_key_test', 'user@example.com');
    }

    public function test_mail_sender_send_by_key_successfully_dispatches(): void
    {
        Queue::fake();

        EmailTemplate::create([
            'key' => 'dispatch_by_key',
            'name' => 'Dispatch By Key',
            'subject' => 'Hello {{ name }}',
            'body_html' => '<p>Hello {{ name }}</p>',
            'allowed_variables' => ['name'],
            'is_active' => true,
        ]);

        $this->getMailSender()->sendByKey('dispatch_by_key', 'client@example.com', ['name' => 'Rohan']);

        $this->assertDatabaseHas('email_dispatch_logs', [
            'event_key' => 'dispatch_by_key',
            'recipient_email' => 'client@example.com',
            'subject_snapshot' => 'Hello Rohan || TruckBill',
        ]);

        Queue::assertPushed(SendTemplateEmailJob::class, 1);
    }

    // ==========================================
    // SendTemplateEmailJob Tests
    // ==========================================

    public function test_send_template_email_job_returns_early_when_dispatch_log_not_found(): void
    {
        $job = new SendTemplateEmailJob(
            emailDispatchLogId: 999999,
            recipient: 'nobody@example.com',
            subject: 'Test Subject',
            renderedBody: '<p>Test Body</p>'
        );

        $job->handle();

        $this->assertDatabaseMissing('email_dispatch_logs', [
            'id' => 999999,
        ]);
    }

    public function test_send_template_email_job_successfully_sends_email_and_marks_log_sent(): void
    {
        $log = EmailDispatchLog::create([
            'recipient_email' => 'client@example.com',
            'subject_snapshot' => 'Test Subject',
            'body_snapshot' => '<p>Hello World</p>',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $job = new SendTemplateEmailJob(
            emailDispatchLogId: $log->id,
            recipient: 'client@example.com',
            subject: 'Test Subject',
            renderedBody: '<p>Hello World</p>'
        );

        $job->handle();

        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->error_message);
    }

    public function test_send_template_email_job_catches_exception_and_marks_log_failed(): void
    {
        $log = EmailDispatchLog::create([
            'recipient_email' => 'fail@example.com',
            'subject_snapshot' => 'Failure Subject',
            'body_snapshot' => '<p>Failure Body</p>',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP transport connection refused'));

        $job = new SendTemplateEmailJob(
            emailDispatchLogId: $log->id,
            recipient: 'fail@example.com',
            subject: 'Failure Subject',
            renderedBody: '<p>Failure Body</p>'
        );

        $exceptionCaught = false;
        try {
            $job->handle();
        } catch (\RuntimeException $e) {
            $exceptionCaught = true;
            $this->assertSame('SMTP transport connection refused', $e->getMessage());
        }

        $this->assertTrue($exceptionCaught, 'Expected RuntimeException was not thrown.');

        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertSame('SMTP transport connection refused', $log->error_message);
    }
}

