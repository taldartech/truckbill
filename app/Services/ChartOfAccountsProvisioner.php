<?php

namespace App\Services;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Ledger;

class ChartOfAccountsProvisioner
{
    /**
     * Provision standard Indian transport Chart of Accounts and default Ledgers for a company.
     */
    public function provisionForCompany(Company $company): void
    {
        // 1. Primary Groups & Sub-Groups
        $groups = [
            // Assets
            'Current Assets' => [
                'type' => 'asset',
                'parent' => null,
                'children' => [
                    'Bank Accounts' => 'asset',
                    'Cash-in-Hand' => 'asset',
                    'Sundry Debtors' => 'asset',
                    'Loans & Advances' => 'asset',
                ],
            ],
            'Fixed Assets' => [
                'type' => 'asset',
                'parent' => null,
                'children' => [
                    'Vehicles & Fleets' => 'asset',
                    'Office Equipment' => 'asset',
                ],
            ],

            // Liabilities
            'Current Liabilities' => [
                'type' => 'liability',
                'parent' => null,
                'children' => [
                    'Sundry Creditors' => 'liability',
                    'Duties & Taxes' => 'liability',
                    'Provisions' => 'liability',
                ],
            ],
            'Capital Account' => [
                'type' => 'equity',
                'parent' => null,
                'children' => [],
            ],
            'Loans & Borrowings' => [
                'type' => 'liability',
                'parent' => null,
                'children' => [],
            ],

            // Incomes
            'Direct Incomes' => [
                'type' => 'income',
                'parent' => null,
                'children' => [
                    'Freight Revenue' => 'income',
                    'Detention & Demurrage Charges' => 'income',
                ],
            ],
            'Indirect Incomes' => [
                'type' => 'income',
                'parent' => null,
                'children' => [
                    'Shortage & Damage Recovery' => 'income',
                    'Miscellaneous Income' => 'income',
                ],
            ],

            // Expenses
            'Direct Expenses' => [
                'type' => 'expense',
                'parent' => null,
                'children' => [
                    'Transporter Freight Hiring Charges' => 'expense',
                    'Diesel & Fuel Expenses' => 'expense',
                    'Toll & Border Taxes' => 'expense',
                    'Driver Bhatta & Expenses' => 'expense',
                ],
            ],
            'Indirect Expenses' => [
                'type' => 'expense',
                'parent' => null,
                'children' => [
                    'Office Rent' => 'expense',
                    'Salaries & Wages' => 'expense',
                    'Printing & Stationery' => 'expense',
                    'Bank Charges' => 'expense',
                    'Round Off' => 'expense',
                ],
            ],
        ];

        $createdGroups = [];

        foreach ($groups as $groupName => $data) {
            $parentGroup = AccountGroup::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $groupName,
                ],
                [
                    'primary_type' => $data['type'],
                    'parent_id' => null,
                    'is_builtin' => true,
                ]
            );
            $createdGroups[$groupName] = $parentGroup;

            foreach ($data['children'] as $childName => $childType) {
                $childGroup = AccountGroup::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $childName,
                    ],
                    [
                        'primary_type' => $childType,
                        'parent_id' => $parentGroup->id,
                        'is_builtin' => true,
                    ]
                );
                $createdGroups[$childName] = $childGroup;
            }
        }

        // 2. Default System Ledgers
        $defaultLedgers = [
            [
                'name' => 'Cash A/c',
                'group' => 'Cash-in-Hand',
                'code' => 'CASH',
                'type' => 'debit',
            ],
            [
                'name' => 'Freight Income A/c',
                'group' => 'Freight Revenue',
                'code' => 'FR_INCOME',
                'type' => 'credit',
            ],
            [
                'name' => 'Transporter Freight Hiring A/c',
                'group' => 'Transporter Freight Hiring Charges',
                'code' => 'FR_EXPENSE',
                'type' => 'debit',
            ],
            [
                'name' => 'Diesel & Fuel Expenses A/c',
                'group' => 'Diesel & Fuel Expenses',
                'code' => 'FUEL_EXPENSE',
                'type' => 'debit',
            ],
            [
                'name' => 'Shortage & Damage Recovery A/c',
                'group' => 'Shortage & Damage Recovery',
                'code' => 'SHORTAGE_REC',
                'type' => 'credit',
            ],
            [
                'name' => 'TDS u/s 194C Payable',
                'group' => 'Duties & Taxes',
                'code' => 'TDS_194C',
                'tds_section' => '194C',
                'type' => 'credit',
            ],
            [
                'name' => 'CGST Output A/c',
                'group' => 'Duties & Taxes',
                'code' => 'CGST_OUT',
                'type' => 'credit',
            ],
            [
                'name' => 'SGST Output A/c',
                'group' => 'Duties & Taxes',
                'code' => 'SGST_OUT',
                'type' => 'credit',
            ],
            [
                'name' => 'IGST Output A/c',
                'group' => 'Duties & Taxes',
                'code' => 'IGST_OUT',
                'type' => 'credit',
            ],
            [
                'name' => 'Round Off A/c',
                'group' => 'Round Off',
                'code' => 'ROUND_OFF',
                'type' => 'debit',
            ],
        ];

        // Add company bank account if available
        if (! empty($company->bank) && ! empty($company->account_number)) {
            $bankLedgerName = trim($company->bank).' (A/c '.$company->account_number.')';
            $defaultLedgers[] = [
                'name' => $bankLedgerName,
                'group' => 'Bank Accounts',
                'code' => 'BANK_PRIMARY',
                'type' => 'debit',
            ];
        }

        foreach ($defaultLedgers as $item) {
            $groupId = $createdGroups[$item['group']]->id ?? null;
            if (! $groupId) {
                continue;
            }

            Ledger::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $item['name'],
                ],
                [
                    'account_group_id' => $groupId,
                    'code' => $item['code'] ?? null,
                    'opening_balance' => 0.00,
                    'opening_balance_type' => $item['type'] ?? 'debit',
                    'tds_section' => $item['tds_section'] ?? null,
                    'is_builtin' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
