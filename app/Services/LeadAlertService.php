<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Notifies a store's admin by WhatsApp when a new Lead is created. This is a
 * business-initiated message outside any customer session, so it must go
 * out as a Meta-approved template — same requirement as any other outbound
 * WhatsApp message sent cold. Fails open: a missing phone, missing
 * template, or Meta API error never blocks lead creation.
 */
class LeadAlertService
{
    public static function notify(Lead $lead): void
    {
        $store = $lead->store;

        if (!$store || empty($store->alert_phone)) {
            return;
        }

        $template = WhatsAppTemplate::where('store_id', $store->id)
            ->where('is_lead_alert', true)
            ->first();

        if (!$template) {
            Log::warning('LeadAlertService: no lead-alert template configured for store', [
                'store_id' => $store->id,
            ]);
            return;
        }

        $data = [
            'store_name'           => $store->name,
            'customer_name'        => $lead->customer_name,
            'customer_phone'       => $lead->customer_phone,
            'product_service_name' => $lead->product_service_name,
            'sale_value'           => $lead->sale_value !== null
                ? number_format((float) $lead->sale_value, 0)
                : 'N/A',
        ];

        $resolvedValues = [];
        foreach ($template->parameters_map ?? [] as $position => $fieldKey) {
            $resolvedValues[(int) $position - 1] = $data[$fieldKey] ?? '';
        }
        ksort($resolvedValues);

        $wamid = WhatsAppService::sendTemplateMessage(
            to:           $store->alert_phone,
            templateName: $template->name,
            languageCode: $template->language,
            variables:    array_values($resolvedValues),
            store:        $store,
        );

        if (!$wamid) {
            Log::warning('LeadAlertService: failed to send admin alert', [
                'store_id' => $store->id,
                'lead_id'  => $lead->id,
            ]);
        }
    }
}
