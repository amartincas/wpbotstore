<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Product;
use App\Services\Inventory\ProductFinderService;
use App\Services\MetaConversionsApiService;
use Illuminate\Console\Command;

/**
 * One-off backfill for leads that existed before the LeadObserver started
 * sending every new Lead to Meta's Conversions API automatically. Leads
 * created after the observer went live don't need this — they're already
 * marked via meta_capi_sent_at at creation time.
 *
 * A lead without a stored ctwa_clid can't be backfilled: that value is only
 * ever exposed by Meta on the first inbound message of a Click-to-WhatsApp
 * conversation, so if it wasn't captured then, it's gone for good.
 */
class BackfillMetaConversions extends Command
{
    protected $signature = 'meta:backfill-conversions {--dry-run : List what would be sent without actually sending it}';

    protected $description = 'Send existing leads that predate the CAPI integration to Meta\'s Conversions API';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $leads = Lead::whereNull('meta_capi_sent_at')
            ->where('customer_name', '!=', 'Unknown')
            ->with('store')
            ->get();

        $this->info("Leads pendientes de procesar: {$leads->count()}");

        $sent = 0;
        $skippedNoCtwaClid = 0;
        $skippedNoStore = 0;

        foreach ($leads as $lead) {
            if (!$lead->store) {
                $skippedNoStore++;
                continue;
            }

            // Older leads created before we started copying ctwa_clid onto the
            // lead itself may still have it on the conversation record.
            $ctwaClid = $lead->ctwa_clid ?? Conversation::where('store_id', $lead->store_id)
                ->where('customer_phone', $lead->customer_phone)
                ->value('ctwa_clid');

            if (!$ctwaClid) {
                $skippedNoCtwaClid++;
                $this->line("  [sin ctwa_clid] lead #{$lead->id} ({$lead->customer_phone}) — no se puede enviar");
                if (!$dryRun) {
                    $lead->update(['meta_capi_sent_at' => now()]);
                }
                continue;
            }

            // Same fallback chain used at live creation time: prefer the
            // linked product's price, else try to match product_service_name
            // against the catalog by name.
            $salePrice = $lead->sale_value;
            if ($salePrice === null && $lead->product_id) {
                $salePrice = Product::find($lead->product_id)?->price;
            }
            if ($salePrice === null && !empty($lead->product_service_name)) {
                $salePrice = (new ProductFinderService())
                    ->findProductMentionedInMessage($lead->product_service_name, $lead->store_id)
                    ?->price;
            }

            $this->line("  [enviando] lead #{$lead->id} ({$lead->customer_phone}), sale_value=" . ($salePrice ?? 'null'));

            if (!$dryRun) {
                MetaConversionsApiService::sendLeadEvent(
                    $lead->store,
                    $lead->customer_phone,
                    $ctwaClid,
                    $lead->created_at
                );

                if ($salePrice !== null) {
                    MetaConversionsApiService::sendPurchaseEvent(
                        $lead->store,
                        $lead->customer_phone,
                        (float) $salePrice,
                        $lead->store->meta_capi_currency,
                        $ctwaClid,
                        $lead->created_at
                    );
                }

                $lead->update(['meta_capi_sent_at' => now()]);
            }

            $sent++;
        }

        $this->newLine();
        $this->info("Enviados: {$sent} | Sin ctwa_clid (no se pueden enviar): {$skippedNoCtwaClid} | Sin tienda: {$skippedNoStore}");

        if ($dryRun) {
            $this->comment('Esto fue un dry-run — no se envió ni se marcó nada. Corre sin --dry-run para procesarlos de verdad.');
        }

        return self::SUCCESS;
    }
}
