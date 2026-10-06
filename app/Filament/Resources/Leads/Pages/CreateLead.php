<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Conversation;
use App\Services\Inventory\ProductFinderService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * A lead created manually here still represents a real customer, and if
     * that customer originally reached out through a Click-to-WhatsApp ad,
     * their conversation already has the ctwa_clid — attach it so the
     * LeadObserver can still report this to Meta's Conversions API instead
     * of silently skipping it for lack of ad attribution.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $storeId = $data['store_id'] ?? Auth::user()?->store_id;

        // store_id isn't a field on the form (see LeadForm.php — it's only
        // shown as a select for super admins), so without this it never made
        // it into the insert at all and the create failed with a SQL error
        // ("Field 'store_id' doesn't have a default value").
        $data['store_id'] = $storeId;

        if ($storeId && !empty($data['customer_phone'])) {
            $ctwaClid = Conversation::where('store_id', $storeId)
                ->where('customer_phone', $data['customer_phone'])
                ->value('ctwa_clid');

            if ($ctwaClid) {
                $data['ctwa_clid'] = $ctwaClid;
            }
        }

        // Same resolution the bot already does when it creates a lead: match
        // the typed product name against the catalog to pull its price, so
        // the operator doesn't have to type a value by hand for a normal
        // catalog sale. The "Sale Value" field on the form still lets them
        // override or fill it in for cases this can't resolve (negotiated
        // price, multi-item order, service with no fixed price).
        if (empty($data['sale_value']) && $storeId && !empty($data['product_service_name'])) {
            $product = (new ProductFinderService())
                ->findProductMentionedInMessage($data['product_service_name'], $storeId);

            if ($product) {
                $data['sale_value'] = $product->price;
                $data['product_id'] = $product->id;
            }
        }

        return $data;
    }
}
