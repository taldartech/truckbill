<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'LR_STATUS_PENDING',
                'name' => 'LR Status Pending',
                'subject' => '{{companyName}} | New LR Created: {{lrNumber}}',
                'body_html' => '<p>Hello {{name}},</p><p>A new Lorry Receipt has been created in the system for <strong>{{companyName}}</strong>.</p><p><strong>LR No:</strong> {{lrNumber}}<br><strong>Truck No:</strong> {{truckNumber}}<br><strong>LR Date:</strong> {{date}}<br><strong>Created At:</strong> {{lrCreateDate}}<br><strong>Status:</strong> {{status}}</p><p><strong>Last Updated:</strong> {{dateTime}}</p>',
                'allowed_variables' => ['name', 'companyName', 'lrNumber', 'truckNumber', 'date', 'lrCreateDate', 'status', 'dateTime'],
                'allowed_roles' => [],
                'send_to_transporter' => false,
                'send_to_customer' => false,
                'is_active' => true,
            ],
            [
                'key' => 'LR_STATUS_IN_PROGRESS',
                'name' => 'LR Status In Progress',
                'subject' => '{{companyName}} | LR In-Progress: {{lrNumber}}',
                'body_html' => '<p>Hello {{name}},</p><p>Your Lorry Receipt for <strong>{{companyName}}</strong> is now marked as <strong>In-Progress</strong>.</p><p><strong>LR No:</strong> {{lrNumber}}<br><strong>Truck No:</strong> {{truckNumber}}<br><strong>LR Date:</strong> {{date}}<br><strong>Updated At:</strong> {{dateTime}}</p><p>You can track the latest status in your TruckBill panel.</p>',
                'allowed_variables' => ['name', 'companyName', 'lrNumber', 'truckNumber', 'date', 'dateTime'],
                'allowed_roles' => [],
                'send_to_transporter' => true,
                'send_to_customer' => true,
                'is_active' => true,
            ],
            [
                'key' => 'LR_STATUS_DELIVERED',
                'name' => 'LR Status Delivered',
                'subject' => '{{companyName}} | LR Delivered: {{lrNumber}}',
                'body_html' => '<p>Hello {{name}},</p><p>Your Lorry Receipt for <strong>{{companyName}}</strong> has been marked as <strong>Delivered</strong>.</p><p><strong>LR No:</strong> {{lrNumber}}<br><strong>Truck No:</strong> {{truckNumber}}<br><strong>LR Date:</strong> {{date}}<br><strong>Delivered At:</strong> {{dateTime}}</p><p>Thank you for using TruckBill.</p>',
                'allowed_variables' => ['name', 'companyName', 'lrNumber', 'truckNumber', 'date', 'dateTime'],
                'allowed_roles' => [],
                'send_to_transporter' => true,
                'send_to_customer' => true,
                'is_active' => true,
            ],
            [
                'key' => 'FR_STATUS_CANCELLED',
                'name' => 'Freight Booking Cancelled',
                'subject' => '{{companyName}} | Good Hire Cancelled (LR {{lrNumber}})',
                'body_html' => '<p>Hello {{name}},</p><p>The Good Hire (Lorry Hire) for <strong>{{companyName}}</strong> has been <strong>cancelled</strong>.</p><p><strong>LR No:</strong> {{lrNumber}}<br><strong>Truck No:</strong> {{truckNumber}}<br><strong>Voucher No:</strong> {{voucherNumber}}<br><strong>LR Date:</strong> {{date}}<br><strong>Cancelled At:</strong> {{dateTime}}</p>',
                'allowed_variables' => ['name', 'companyName', 'lrNumber', 'truckNumber', 'voucherNumber', 'date', 'dateTime'],
                'allowed_roles' => ['admin', 'moderator', 'accountant'],
                'send_to_transporter' => true,
                'send_to_customer' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
