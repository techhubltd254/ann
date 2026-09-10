<?php

namespace App\Services;

use App\Models\Booth;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\TraderSpotlight;
use App\Models\Marketplace\Product;

/**
 * ContactCardRenderer — generates interactive trade/contact cards
 * for any booth, institution, trader, or county.
 */
class ContactCardRenderer
{
    public function forBooth(Booth $booth): array
    {
        return [
            'name' => $booth->name,
            'booth_number' => $booth->booth_number,
            'contacts' => [
                'name' => $booth->contact_name,
                'mobile' => $booth->contact_mobile,
                'whatsapp' => $booth->contact_whatsapp,
                'email' => $booth->contact_email,
                'department_lead' => $booth->department_lead,
            ],
            'is_trader' => !empty($booth->trader_type),
            'trader_type' => $booth->trader_type,
            'is_verified' => $booth->is_verified_trader,
            'whatsapp_link' => $booth->contact_whatsapp
                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $booth->contact_whatsapp)
                : null,
            'mailto_link' => $booth->contact_email
                ? 'mailto:' . $booth->contact_email
                : null,
        ];
    }

    public function forInstitution(CountyInstitution $inst): array
    {
        return [
            'name' => $inst->name,
            'type' => $inst->type,
            'contacts' => [
                'phone' => $inst->phone,
                'whatsapp' => $inst->whatsapp ?? $inst->phone,
                'email' => $inst->email,
                'website' => $inst->website,
                'location' => $inst->location,
                'department_leads' => $inst->department_leads,
            ],
            'is_trader' => $inst->is_verified_trader,
            'trader_type' => $inst->trader_type,
            'whatsapp_link' => ($inst->whatsapp ?? $inst->phone)
                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', ($inst->whatsapp ?? $inst->phone))
                : null,
            'mailto_link' => $inst->email ? 'mailto:' . $inst->email : null,
        ];
    }

    public function forTrader(TraderSpotlight $trader): array
    {
        return [
            'name' => $trader->name,
            'type' => $trader->trader_type,
            'is_verified' => $trader->is_verified,
            'contacts' => [
                'name' => $trader->contact_name,
                'mobile' => $trader->contact_mobile,
                'whatsapp' => $trader->contact_whatsapp,
                'email' => $trader->contact_email,
                'department_lead' => $trader->department_lead,
            ],
            'description' => $trader->description,
            'trade_info' => $trader->trade_info,
            'video_url' => $trader->spotlightVideo?->mp4Url() ?? $trader->spotlightVideo?->url(),
            'duration' => $trader->duration_seconds,
            'whatsapp_link' => $trader->contact_whatsapp
                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $trader->contact_whatsapp)
                : null,
        ];
    }

    public function forCounty(County $county): array
    {
        return [
            'name' => $county->name,
            'capital' => $county->capital,
            'trade' => [
                'volume_ksh' => $county->trade_volume_ksh,
                'top_exports' => $county->top_export_products,
                'opportunities' => $county->investment_opportunities,
            ],
            'contacts' => [
                'governor_office' => $county->contact_governor_phone,
                'commissioner' => ['name' => $county->contact_commissioner_name, 'phone' => $county->contact_commissioner_phone],
                'investment_desk' => $county->contact_investment_desk_email,
                'whatsapp_business' => $county->whatsapp_business,
            ],
            'whatsapp_link' => $county->whatsapp_business
                ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $county->whatsapp_business)
                : null,
        ];
    }

    public function forProduct(Product $product): array
    {
        return [
            'name' => $product->name,
            'county' => $product->county?->name,
            'is_spotlight' => $product->is_spotlight_product,
            'trade' => [
                'fob_price' => $product->fob_price,
                'moq' => $product->moq,
                'incoterm' => $product->incoterm,
                'hs_code' => $product->hs_code,
                'export_ready' => $product->export_readiness,
                'certifications' => $product->certifications,
                'enquiry_email' => $product->trade_enquiry_email,
            ],
            'video_url' => $product->video_url,
            'image_url' => $product->image_url,
            'institution' => $product->seller?->name,
        ];
    }
}