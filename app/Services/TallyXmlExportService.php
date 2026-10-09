<?php

namespace App\Services;

use App\Models\AccountingVoucher;
use App\Models\Company;
use App\Models\Ledger;
use Carbon\Carbon;
use SimpleXMLElement;

class TallyXmlExportService
{
    /**
     * Generate Tally-compatible XML for Ledger Masters.
     */
    public function exportMastersXml(Company $company): string
    {
        $ledgers = Ledger::with('accountGroup')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        $envelope = new SimpleXMLElement('<ENVELOPE/>');
        $header = $envelope->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        $body = $envelope->addChild('BODY');
        $importData = $body->addChild('IMPORTDATA');
        $reqDesc = $importData->addChild('REQUESTDESC');
        $reqDesc->addChild('REPORTNAME', 'All Masters');
        $staticVars = $reqDesc->addChild('STATICVARIABLES');
        $staticVars->addChild('SVCURRENTCOMPANY', htmlspecialchars($company->name));

        $reqData = $importData->addChild('REQUESTDATA');

        foreach ($ledgers as $ledger) {
            $msg = $reqData->addChild('TALLYMESSAGE');
            $tallyLedger = $msg->addChild('LEDGER');
            $tallyLedger->addAttribute('NAME', htmlspecialchars($ledger->name));
            $tallyLedger->addAttribute('ACTION', 'Create');

            $nameList = $tallyLedger->addChild('NAME.LIST');
            $nameList->addChild('NAME', htmlspecialchars($ledger->name));

            $parentGroup = $ledger->accountGroup?->name ?? 'Primary';
            $tallyLedger->addChild('PARENT', htmlspecialchars($parentGroup));

            if ($ledger->opening_balance > 0) {
                $multiplier = $ledger->opening_balance_type === 'debit' ? -1 : 1;
                $tallyLedger->addChild('OPENINGBALANCE', (string) ($ledger->opening_balance * $multiplier));
            }

            if ($ledger->gstin) {
                $tallyLedger->addChild('PARTYGSTIN', htmlspecialchars($ledger->gstin));
            }

            if ($ledger->pan_number) {
                $tallyLedger->addChild('PANNUMBER', htmlspecialchars($ledger->pan_number));
            }
        }

        return $envelope->asXML();
    }

    /**
     * Generate Tally-compatible XML for Accounting Vouchers between dates.
     */
    public function exportVouchersXml(Company $company, ?string $fromDate = null, ?string $toDate = null): string
    {
        $fromDate = $fromDate ?: now()->startOfMonth()->toDateString();
        $toDate = $toDate ?: now()->toDateString();

        $vouchers = AccountingVoucher::with(['items.ledger', 'items.costCentre'])
            ->where('company_id', $company->id)
            ->where('is_posted', true)
            ->whereBetween('voucher_date', [$fromDate, $toDate])
            ->orderBy('voucher_date', 'asc')
            ->get();

        $envelope = new SimpleXMLElement('<ENVELOPE/>');
        $header = $envelope->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        $body = $envelope->addChild('BODY');
        $importData = $body->addChild('IMPORTDATA');
        $reqDesc = $importData->addChild('REQUESTDESC');
        $reqDesc->addChild('REPORTNAME', 'Vouchers');
        $staticVars = $reqDesc->addChild('STATICVARIABLES');
        $staticVars->addChild('SVCURRENTCOMPANY', htmlspecialchars($company->name));

        $reqData = $importData->addChild('REQUESTDATA');

        foreach ($vouchers as $voucher) {
            $msg = $reqData->addChild('TALLYMESSAGE');
            $tallyVch = $msg->addChild('VOUCHER');
            $vchTypeName = ucfirst($voucher->voucher_type);
            $tallyVch->addAttribute('VCHTYPE', $vchTypeName);
            $tallyVch->addAttribute('ACTION', 'Create');

            $tallyDate = Carbon::parse($voucher->voucher_date)->format('Ymd');
            $tallyVch->addChild('DATE', $tallyDate);
            $tallyVch->addChild('VOUCHERTYPENAME', $vchTypeName);
            $tallyVch->addChild('VOUCHERNUMBER', htmlspecialchars($voucher->voucher_number));

            if ($voucher->reference_number) {
                $tallyVch->addChild('REFERENCE', htmlspecialchars($voucher->reference_number));
            }

            if ($voucher->narration) {
                $tallyVch->addChild('NARRATION', htmlspecialchars($voucher->narration));
            }

            foreach ($voucher->items as $item) {
                $ledgerEntry = $tallyVch->addChild('ALLLEDGERENTRIES.LIST');
                $ledgerName = $item->ledger?->name ?? 'Unknown Ledger';
                $ledgerEntry->addChild('LEDGERNAME', htmlspecialchars($ledgerName));

                $isDebit = $item->type === 'debit';
                $ledgerEntry->addChild('ISDEEMEDPOSITIVE', $isDebit ? 'Yes' : 'No');

                // Tally convention: debits are negative, credits are positive
                $tallyAmount = $isDebit ? -1 * (float) $item->amount : (float) $item->amount;
                $ledgerEntry->addChild('AMOUNT', (string) $tallyAmount);

                if ($item->costCentre) {
                    $catAlloc = $ledgerEntry->addChild('CATEGORYALLOCATIONS.LIST');
                    $catAlloc->addChild('CATEGORY', 'Primary Cost Category');
                    $costAlloc = $catAlloc->addChild('COSTCENTREALLOCATIONS.LIST');
                    $costAlloc->addChild('NAME', htmlspecialchars($item->costCentre->name));
                    $costAlloc->addChild('AMOUNT', (string) $tallyAmount);
                }
            }
        }

        return $envelope->asXML();
    }
}
