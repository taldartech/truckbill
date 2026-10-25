<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Ledger;
use App\Models\Pump;
use App\Models\SubscriptionPayment;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\UpcomingSubscription;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CoreModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
        UserRole::firstOrCreate(['id' => 5], ['name' => 'driver']);
    }

    /**
     * 1. UpcomingSubscription model tests
     */
    public function test_upcoming_subscription_relations_and_casts(): void
    {
        $company = Company::create(['name' => 'Upcoming Sub Co']);
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'subscription',
            'status' => 'paid',
            'subtotal' => 5000.00,
            'total_amount' => 5900.00,
        ]);

        $upcoming = new UpcomingSubscription([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'included_branches' => 3,
            'users_per_branch' => 5,
            'included_lr_credits' => 100,
            'included_trucks' => 10,
            'status' => 'queued',
            'catalog_snapshot' => ['plan' => 'growth', 'price' => 5000],
            'promo_snapshot' => ['code' => 'DIWALI2026', 'discount' => 10],
        ]);
        $upcoming->starts_on = '2026-11-01';
        $upcoming->ends_on = '2027-11-01';
        $upcoming->save();

        $this->assertInstanceOf(Company::class, $upcoming->company);
        $this->assertEquals($company->id, $upcoming->company->id);

        $this->assertInstanceOf(SubscriptionPayment::class, $upcoming->payment);
        $this->assertEquals($payment->id, $upcoming->payment->id);

        $this->assertIsArray($upcoming->catalog_snapshot);
        $this->assertEquals('growth', $upcoming->catalog_snapshot['plan']);

        $this->assertIsArray($upcoming->promo_snapshot);
        $this->assertEquals('DIWALI2026', $upcoming->promo_snapshot['code']);
    }

    /**
     * 2. UserRole model tests
     */
    public function test_user_role_creation_and_attributes(): void
    {
        $role = UserRole::create(['name' => 'operations_manager']);
        $this->assertEquals('user_roles', $role->getTable());
        $this->assertTrue($role->timestamps);
        $this->assertEquals('operations_manager', $role->name);
        $this->assertDatabaseHas('user_roles', ['name' => 'operations_manager']);
    }

    /**
     * 3. User model: Relations, portal consignee, and consignee access
     */
    public function test_user_relations_and_consignee_methods(): void
    {
        $company = Company::create(['name' => 'User Relations Co']);
        $master = TransporterMaster::create(['name' => 'User Transporter Master', 'pan_number' => 'ABCDE1234F']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main Hub',
        ]);
        $role = UserRole::where('name', 'admin')->first();

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test_user_rel@example.test',
            'password' => bcrypt('secret123'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'transporter_master_id' => $master->id,
            'user_role_id' => $role->id,
        ]);

        // Relations
        $this->assertInstanceOf(Company::class, $user->company);
        $this->assertEquals($company->id, $user->company->id);

        $this->assertInstanceOf(Branch::class, $user->branch);
        $this->assertEquals($branch->id, $user->branch->id);

        $this->assertInstanceOf(TransporterMaster::class, $user->transporterMaster);
        $this->assertEquals($master->id, $user->transporterMaster->id);

        $this->assertInstanceOf(UserRole::class, $user->userRole);
        $this->assertEquals('admin', $user->userRole->name);

        // ownedCompanies relation
        $ownedCo = Company::create(['name' => 'Owned Subsidiary', 'user_id' => $user->id]);
        $this->assertTrue($user->ownedCompanies->contains('id', $ownedCo->id));

        // Consignees relation and allowedConsigneeIds
        $consignee1 = Consignee::create(['company_id' => $company->id, 'name' => 'Client One']);
        $consignee2 = Consignee::create(['company_id' => $company->id, 'name' => 'Client Two']);
        $user->consignees()->attach([$consignee1->id, $consignee2->id]);

        $allowedIds = $user->allowedConsigneeIds();
        $this->assertCount(2, $allowedIds);
        $this->assertContains($consignee1->id, $allowedIds);
        $this->assertContains($consignee2->id, $allowedIds);

        // portalConsignee without session -> null
        session()->forget('portal_consignee_id');
        $this->assertNull($user->portalConsignee());

        // portalConsignee with non-matching session id -> null
        session(['portal_consignee_id' => 999999]);
        $this->assertNull($user->portalConsignee());

        // portalConsignee with assigned session id -> returns consignee
        session(['portal_consignee_id' => $consignee1->id]);
        $portalConsignee = $user->portalConsignee();
        $this->assertNotNull($portalConsignee);
        $this->assertEquals($consignee1->id, $portalConsignee->id);
    }

    /**
     * User model: effective company_id and permission helpers
     */
    public function test_user_effective_company_id_and_permissions(): void
    {
        $superAdminRole = UserRole::where('name', 'super_admin')->first();
        $adminRole = UserRole::where('name', 'admin')->first();
        $moderatorRole = UserRole::where('name', 'moderator')->first();
        $masterAdminRole = UserRole::where('name', 'master_admin')->first();
        $driverRole = UserRole::where('name', 'driver')->first();

        $company = Company::create(['name' => 'Company Direct']);
        $otherCompany = Company::create(['name' => 'Company Other']);

        // 1. User with explicit company_id
        $companyUser = User::create([
            'name' => 'Company Admin',
            'email' => 'admin_direct@example.test',
            'password' => 'secret123',
            'company_id' => $company->id,
            'user_role_id' => $adminRole->id,
        ]);
        $this->assertEquals($company->id, $companyUser->company_id);
        $this->assertTrue($companyUser->canAccessCompanyPanel());
        $this->assertTrue($companyUser->canActAsAdmin());

        // 2. Super admin with null company_id and session
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.test',
            'password' => 'secret123',
            'company_id' => null,
            'user_role_id' => $superAdminRole->id,
        ]);

        session()->forget('super_admin_company_id');
        $this->assertNull($superAdmin->company_id);
        $this->assertFalse($superAdmin->canAccessCompanyPanel());
        $this->assertFalse($superAdmin->canActAsAdmin());

        session(['super_admin_company_id' => $company->id]);
        $this->assertEquals($company->id, $superAdmin->company_id);
        $this->assertTrue($superAdmin->canAccessCompanyPanel());
        $this->assertTrue($superAdmin->canActAsAdmin());

        // 3. Super admin role resolved via DB fallback when relation is not preloaded
        $superAdminFallback = new User([
            'name' => 'Super Admin Fallback',
            'email' => 'superadmin_fb@example.test',
            'password' => 'secret123',
            'company_id' => null,
            'user_role_id' => $superAdminRole->id,
        ]);
        $superAdminFallback->setRelation('userRole', null);
        $this->assertEquals($company->id, $superAdminFallback->company_id);

        // 4. Non-super-admin with null company_id does not inherit session company
        $driverUser = User::create([
            'name' => 'Driver User',
            'email' => 'driver@example.test',
            'password' => 'secret123',
            'company_id' => null,
            'user_role_id' => $driverRole->id,
        ]);
        $this->assertNull($driverUser->company_id);
        $this->assertFalse($driverUser->canAccessCompanyPanel());
        $this->assertFalse($driverUser->canActAsAdmin());

        // 5. Moderator permissions
        $moderator = User::create([
            'name' => 'Moderator User',
            'email' => 'mod@example.test',
            'password' => 'secret123',
            'company_id' => $otherCompany->id,
            'user_role_id' => $moderatorRole->id,
        ]);
        $this->assertTrue($moderator->canAccessCompanyPanel());
        $this->assertFalse($moderator->canActAsAdmin());

        // 6. Master admin permissions
        $masterAdmin = User::create([
            'name' => 'Master Admin User',
            'email' => 'master@example.test',
            'password' => 'secret123',
            'company_id' => $otherCompany->id,
            'user_role_id' => $masterAdminRole->id,
        ]);
        $this->assertFalse($masterAdmin->canAccessCompanyPanel());
        $this->assertTrue($masterAdmin->canActAsAdmin());
    }

    /**
     * User model: Notifications and attribute casts
     */
    public function test_user_password_reset_notification_and_casts(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Notify User',
            'email' => 'notify_me@example.test',
            'password' => 'plain_text_password',
            'is_blocked' => 1,
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
        ]);

        $user->sendPasswordResetNotification('reset-token-xyz-123');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            return $notification->token === 'reset-token-xyz-123';
        });

        $this->assertTrue($user->is_blocked);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->policy_accepted_at);
        $this->assertNotEquals('plain_text_password', $user->password);
    }

    /**
     * 4. VoucherLinked model tests
     */
    public function test_voucher_linked_relations_and_badges(): void
    {
        $company = Company::create(['name' => 'Voucher Linked Co']);
        $pump = Pump::create([
            'pump_name' => 'Highway Fuel Station',
            'contact_name' => 'Fuel Manager',
            'contact_number' => '9876543210',
        ]);

        $masterOwner = new TransporterMaster(['name' => 'Owner Transporter']);
        $transporterOwner = new Transporter(['is_owner' => true]);
        $transporterOwner->setRelation('transporterMaster', $masterOwner);

        $masterFleet = new TransporterMaster(['name' => 'Fleet Logistics Ltd']);
        $transporterFleet = new Transporter(['is_owner' => false]);
        $transporterFleet->setRelation('transporterMaster', $masterFleet);

        $fbOwner = new FreightBooking(['company_id' => $company->id]);
        $fbOwner->setRelation('transporter', $transporterOwner);

        $fbFleet = new FreightBooking(['company_id' => $company->id]);
        $fbFleet->setRelation('transporter', $transporterFleet);

        $fbNoTransporter = new FreightBooking(['company_id' => $company->id]);
        $fbNoTransporter->setRelation('transporter', null);

        // VoucherLinked model instance
        $linked = new VoucherLinked([
            'company_id' => $company->id,
            'pump_id' => $pump->id,
            'payment_status' => 'pending',
            'amount' => 1500.00,
        ]);

        // Static validStatuses
        $this->assertContains('pending', VoucherLinked::$validStatuses);
        $this->assertContains('in progress', VoucherLinked::$validStatuses);
        $this->assertContains('paid', VoucherLinked::$validStatuses);

        // Relations return belongsTo
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $linked->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $linked->freight_booking());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $linked->pump());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $linked->transporter());

        // Payment status badges
        $linked->payment_status = 'pending';
        $this->assertStringContainsString('badge bg-warning', $linked->getPaymentStatusBadge());
        $this->assertStringContainsString('Pending', $linked->getPaymentStatusBadge());

        $linked->payment_status = 'in-progress';
        $this->assertStringContainsString('badge bg-info', $linked->getPaymentStatusBadge());
        $this->assertStringContainsString('In Progress', $linked->getPaymentStatusBadge());

        $linked->payment_status = 'paid';
        $this->assertStringContainsString('badge bg-success', $linked->getPaymentStatusBadge());
        $this->assertStringContainsString('Paid', $linked->getPaymentStatusBadge());

        $linked->payment_status = 'unknown_status';
        $this->assertStringContainsString('badge bg-secondary', $linked->getPaymentStatusBadge());
        $this->assertStringContainsString('Unknown', $linked->getPaymentStatusBadge());

        // Transporter badges
        $linked->setRelation('freight_booking', null);
        $this->assertEquals('<span class="text-muted">-</span>', $linked->getTransporterBadge());

        $linked->setRelation('freight_booking', $fbNoTransporter);
        $this->assertEquals('<span class="text-muted">-</span>', $linked->getTransporterBadge());

        $linked->setRelation('freight_booking', $fbOwner);
        $ownerBadge = $linked->getTransporterBadge();
        $this->assertStringContainsString('badge bg-success', $ownerBadge);
        $this->assertStringContainsString('bi bi-truck', $ownerBadge);

        $linked->setRelation('freight_booking', $fbFleet);
        $fleetBadge = $linked->getTransporterBadge();
        $this->assertStringContainsString('badge bg-primary', $fleetBadge);
        $this->assertStringContainsString('bi bi-building', $fleetBadge);
    }

    /**
     * 5. VoucherPayment model tests
     */
    public function test_voucher_payment_auto_voucher_number_and_relations(): void
    {
        $reflection = new \ReflectionMethod(VoucherPayment::class, 'fireModelEvent');
        $reflection->setAccessible(true);

        // Auto generation of voucher_number when empty
        $payment1 = new VoucherPayment();
        $this->assertNull($payment1->voucher_number);
        $reflection->invoke($payment1, 'creating');
        $this->assertNotEmpty($payment1->voucher_number);
        $this->assertStringStartsWith('VP-', $payment1->voucher_number);

        // Explicit voucher_number preserved
        $payment2 = new VoucherPayment(['voucher_number' => 'VP-CUSTOM-1234']);
        $reflection->invoke($payment2, 'creating');
        $this->assertEquals('VP-CUSTOM-1234', $payment2->voucher_number);

        // Relations return belongsTo instances
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->pump());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->bankLedger());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->freight_booking());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->voucher_linked());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment1->transporter());
    }

    /**
     * VoucherPayment: Badges and audit log methods
     */
    public function test_voucher_payment_badges_and_audit_methods(): void
    {
        $company = Company::create(['name' => 'VP Badges Co']);

        $masterOwner = new TransporterMaster(['name' => 'Trucker Ram']);
        $transporterOwner = new Transporter(['is_owner' => true]);
        $transporterOwner->setRelation('transporterMaster', $masterOwner);

        $masterFleet = new TransporterMaster(['name' => 'Apex Trans Corp']);
        $transporterFleet = new Transporter(['is_owner' => false]);
        $transporterFleet->setRelation('transporterMaster', $masterFleet);

        $fbOwner = new FreightBooking(['company_id' => $company->id]);
        $fbOwner->setRelation('transporter', $transporterOwner);

        $fbFleet = new FreightBooking(['company_id' => $company->id]);
        $fbFleet->setRelation('transporter', $transporterFleet);

        $fbNoTransporter = new FreightBooking(['company_id' => $company->id]);
        $fbNoTransporter->setRelation('transporter', null);

        $vp = new VoucherPayment(['voucher_number' => 'VP-000088']);

        // Static validStatuses
        $this->assertContains('pending', VoucherPayment::$validStatuses);
        $this->assertContains('in progress', VoucherPayment::$validStatuses);
        $this->assertContains('paid', VoucherPayment::$validStatuses);

        // Status badges
        $vp->payment_status = 'pending';
        $this->assertStringContainsString('badge bg-warning', $vp->getPaymentStatusBadge());

        $vp->payment_status = 'in-progress';
        $this->assertStringContainsString('badge bg-info', $vp->getPaymentStatusBadge());

        $vp->payment_status = 'paid';
        $this->assertStringContainsString('badge bg-success', $vp->getPaymentStatusBadge());

        $vp->payment_status = 'cancelled';
        $this->assertStringContainsString('badge bg-secondary', $vp->getPaymentStatusBadge());

        // Transporter badges
        $vp->setRelation('freight_booking', null);
        $this->assertEquals('<span class="text-muted">-</span>', $vp->getTransporterBadge());

        $vp->setRelation('freight_booking', $fbNoTransporter);
        $this->assertEquals('<span class="text-muted">-</span>', $vp->getTransporterBadge());

        $vp->setRelation('freight_booking', $fbOwner);
        $ownerBadge = $vp->getTransporterBadge();
        $this->assertStringContainsString('badge bg-success', $ownerBadge);
        $this->assertStringContainsString('bi bi-truck', $ownerBadge);

        $vp->setRelation('freight_booking', $fbFleet);
        $fleetBadge = $vp->getTransporterBadge();
        $this->assertStringContainsString('badge bg-primary', $fleetBadge);
        $this->assertStringContainsString('bi bi-building', $fleetBadge);

        // Audit methods
        $tracked = $vp->auditTrackedAttributes();
        $this->assertIsArray($tracked);
        $this->assertContains('amount', $tracked);
        $this->assertContains('voucher_number', $tracked);

        $this->assertEquals('VP-000088', $vp->auditSubjectLabel());

        $vpWithoutNumber = new VoucherPayment(['id' => 999, 'voucher_number' => null]);
        $vpWithoutNumber->id = 999;
        $this->assertEquals('Voucher payment #999', $vpWithoutNumber->auditSubjectLabel());

        $this->assertEquals('Voucher payment', $vp->auditSubjectTypeName());
    }
}
