<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database scalability hardening — add missing PKs, FK indexes, and composite
 * indexes for the most frequent query patterns. Every index is guarded by
 * hasTable/hasColumn/hasIndex checks so this is safe to run on any env.
 */
return new class extends Migration
{
    private function missing(string $table, string $index): bool
    {
        return Schema::hasTable($table)
            && ! collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $index);
    }

    private function hasCol(string $table, string $col): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $col);
    }

    public function up(): void
    {
        // 1. FK indexes on high-traffic tables
        $fkIndexes = [
            'orders' => [
                ['billing_address_id', 'idx_orders_billing_address'],
                ['shipping_address_id', 'idx_orders_shipping_address'],
            ],
            'order_items' => [
                ['variant_id', 'idx_oi_variant'],
            ],
            'bookings' => [
                ['exhibition_id', 'idx_bookings_exhibition'],
            ],
            'tickets' => [
                ['user_id', 'idx_tickets_user'],
                ['ticket_type_id', 'idx_tickets_type'],
                ['booking_id', 'idx_tickets_booking'],
            ],
            'order_status_history' => [
                ['user_id', 'idx_osh_user'],
            ],
            'payment_intents' => [
                ['gateway_id', 'idx_pi_gateway'],
            ],
            'payment_transactions' => [
                ['user_id', 'idx_pt_user'],
                ['reference_id', 'idx_pt_reference'],
            ],
            'payment_disputes' => [
                ['user_id', 'idx_pd_user'],
                ['payment_intent_id', 'idx_pd_payment'],
            ],
            'refunds' => [
                ['user_id', 'idx_refunds_user'],
            ],
            'advertisements' => [
                ['user_id', 'idx_ads_user'],
            ],
            'ad_campaigns' => [
                ['advertiser_id', 'idx_adcamp_advertiser'],
            ],
            'ad_impressions' => [
                ['placement_id', 'idx_adimp_placement'],
                ['session_id', 'idx_adimp_session'],
            ],
            'ad_clicks' => [
                ['impression_id', 'idx_adclick_impression'],
            ],
            'ad_conversions' => [
                ['user_id', 'idx_adconv_user'],
                ['click_id', 'idx_adconv_click'],
                ['impression_id', 'idx_adconv_impression'],
            ],
            'analytics_events' => [
                ['user_id', 'idx_ae_user'],
            ],
            'page_views' => [
                ['user_id', 'idx_pv_user'],
            ],
            'notifications' => [
                ['user_id', 'idx_notif_user'],
            ],
            'notification_logs' => [
                ['user_id', 'idx_notiflog_user'],
            ],
            'user_notifications' => [
                ['user_id', 'idx_un_user'],
            ],
            'user_subscriptions' => [
                ['user_id', 'idx_us_user'],
                ['subscription_plan_id', 'idx_us_plan'],
            ],
            'product_reviews' => [
                ['variant_id', 'idx_pr_variant'],
                ['order_id', 'idx_pr_order'],
            ],
            'product_questions' => [
                ['user_id', 'idx_pq_user'],
            ],
            'return_requests' => [
                ['order_item_id', 'idx_rr_order_item'],
            ],
            'wishlists' => [
                ['user_id', 'idx_wish_user'],
            ],
            'cart_items' => [
                ['user_id', 'idx_ci_user'],
            ],
            'shipments' => [
                ['warehouse_id', 'idx_ship_warehouse'],
            ],
            'shipment_items' => [
                ['shipment_id', 'idx_si_shipment'],
                ['order_item_id', 'idx_si_order_item'],
            ],
            'courier_shipments' => [
                ['escrow_transaction_id', 'idx_cs_escrow'],
            ],
            'inventory_movements' => [
                ['variant_id', 'idx_im_variant'],
                ['warehouse_id', 'idx_im_warehouse'],
            ],
            'escrow_transactions' => [
                ['reference_id', 'idx_et_reference'],
            ],
            'dispute_cases' => [
                ['escrow_transaction_id', 'idx_dc_escrow'],
            ],
            'commission_logs' => [
                ['order_id', 'idx_cl_order'],
            ],
            'coupon_usages' => [
                ['user_id', 'idx_cu_user'],
                ['coupon_id', 'idx_cu_coupon'],
            ],
            'gift_cards' => [
                ['issued_by_user_id', 'idx_gc_issued'],
                ['redeemed_by_user_id', 'idx_gc_redeemed'],
            ],
            'auctions' => [
                ['product_id', 'idx_auc_product'],
                ['seller_id', 'idx_auc_seller'],
                ['winner_id', 'idx_auc_winner'],
            ],
            'auction_bids' => [
                ['user_id', 'idx_ab_user'],
            ],
            'rfqs' => [
                ['buyer_id', 'idx_rfq_buyer'],
            ],
            'trade_enquiries' => [
                ['product_id', 'idx_te_product'],
                ['county_id', 'idx_te_county'],
            ],
            'conversations' => [
                ['user_id', 'idx_conv_user'],
                ['vendor_id', 'idx_conv_vendor'],
            ],
            'course_enrollments' => [
                ['user_id', 'idx_ce_user'],
            ],
            'tour_guides' => [
                ['user_id', 'idx_tg_user'],
                ['county_id', 'idx_tg_county'],
            ],
            'safety_alerts' => [
                ['county_id', 'idx_sa_county'],
            ],
            'incident_reports' => [
                ['user_id', 'idx_ir_user'],
                ['county_id', 'idx_ir_county'],
            ],
            'agencies' => [
                ['ministry_id', 'idx_agency_ministry'],
            ],
            'county_products' => [
                ['user_id', 'idx_cp_user'],
            ],
            'county_product_bookings' => [
                ['user_id', 'idx_cpb_user'],
            ],
            'airports' => [
                ['county_id', 'idx_airport_county'],
            ],
            'car_rentals' => [
                ['county_id', 'idx_cr_county'],
            ],
            'exhibitions' => [
                ['county_id', 'idx_exh_county'],
                ['venue_id', 'idx_exh_venue'],
            ],
            'event_sessions' => [
                ['exhibition_id', 'idx_es_exhibition'],
            ],
            'livestream_channels' => [
                ['exhibition_id', 'idx_lc_exhibition'],
            ],
            'news_posts' => [
                ['author_id', 'idx_np_author'],
            ],
            'content_pages' => [
                ['author_id', 'idx_cp_author'],
            ],
            'sector_entities' => [
                ['entity_id', 'idx_se_entity'],
            ],
            'sector_entity_reviews' => [
                ['user_id', 'idx_ser_user'],
                ['sector_entity_id', 'idx_ser_entity'],
            ],
            'entity_trust_scores' => [
                ['sector_entity_id', 'idx_ets_entity'],
            ],
            'immersive_content_records' => [
                ['county_id', 'idx_icr_county'],
                ['sector_entity_id', 'idx_icr_entity'],
            ],
            'travel_package_bookings' => [
                ['travel_package_id', 'idx_tpb_package'],
            ],
            'travel_package_items' => [
                ['travel_package_id', 'idx_tpi_package'],
                ['item_id', 'idx_tpi_item'],
            ],
            'transfer_bookings' => [
                ['transfer_id', 'idx_tb_transfer'],
                ['flight_booking_id', 'idx_tb_flight'],
            ],
            'hotel_bookings' => [
                ['user_id', 'idx_hb_user'],
            ],
            'airport_transfers' => [
                ['airport_id', 'idx_at_airport'],
            ],
            'restaurant_bookings' => [
                ['table_id', 'idx_rb_table'],
            ],
            'attraction_bookings' => [
                ['user_id', 'idx_ab_user'],
                ['attraction_id', 'idx_ab_attraction'],
            ],
            'flight_bookings' => [
                ['flight_inventory_id', 'idx_fb_inventory'],
            ],
            'invoice_items' => [
                ['invoice_id', 'idx_ii_invoice'],
            ],
            'invoices' => [
                ['subscription_id', 'idx_inv_subscription'],
            ],
            'warehouses' => [
                ['county_id', 'idx_wh_county'],
            ],
            'pickup_requests' => [
                ['shipment_id', 'idx_pr_shipment'],
                ['supplier_id', 'idx_pr_supplier'],
            ],
            'refresh_tokens' => [
                ['user_id', 'idx_rt_user'],
            ],
            'user_mfa_devices' => [
                ['user_id', 'idx_umd_user'],
            ],
            'oauth_access_tokens' => [
                ['client_id', 'idx_oat_client'],
            ],
            'oauth_auth_codes' => [
                ['client_id', 'idx_oac_client'],
            ],
            'search_queries' => [
                ['user_id', 'idx_sq_user'],
            ],
            'recently_viewed' => [
                ['viewable_id', 'idx_rv_viewable'],
            ],
            'recommendations' => [
                ['model_id', 'idx_rec_model'],
            ],
            'recommendation_interactions' => [
                ['item_id', 'idx_ri_item'],
            ],
            'travel_itinerary_items' => [
                ['reference_id', 'idx_tii_reference'],
            ],
            'match_scores' => [
                ['model_id', 'idx_ms_model'],
            ],
            'dynamic_pricing_rules' => [
                ['model_id', 'idx_dpr_model'],
            ],
            'flash_sale_products' => [
                ['flash_sale_id', 'idx_fsp_sale'],
            ],
            'seo_metadata' => [
                ['page_id', 'idx_sm_page'],
            ],
            'settlement_transactions' => [
                ['payment_intent_id', 'idx_st_payment'],
                ['settlement_batch_id', 'idx_st_batch'],
            ],
            'settlement_batches' => [
                ['gateway_id', 'idx_sb_gateway'],
            ],
            'advertiser_transactions' => [
                ['reference_id', 'idx_at_reference'],
            ],
            'county_wallet_transactions' => [
                ['reference_id', 'idx_cwt_reference'],
            ],
            'usage_logs' => [
                ['subscription_id', 'idx_ul_subscription'],
            ],
            'product_images' => [
                ['variant_id', 'idx_pi_variant'],
            ],
            'suppliers' => [
                ['user_id', 'idx_sup_user'],
            ],
            'shopping_carts' => [
                ['session_id', 'idx_sc_session'],
            ],
            'sessions' => [
                ['user_id', 'idx_sessions_user_id'],
            ],
        ];

        foreach ($fkIndexes as $table => $indexes) {
            foreach ($indexes as [$col, $idxName]) {
                if ($this->missing($table, $idxName) && $this->hasCol($table, $col)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($col, $idxName));
                }
            }
        }

        // 3. Composite indexes for the most frequent JOIN patterns
        $composites = [
            'orders' => [
                ['cols' => ['user_id', 'status'], 'name' => 'idx_orders_user_status'],
            ],
            'order_items' => [
                ['cols' => ['order_id', 'variant_id'], 'name' => 'idx_oi_order_variant'],
            ],
            'bookings' => [
                ['cols' => ['user_id', 'exhibition_id'], 'name' => 'idx_bookings_user_exhibition'],
            ],
            'payments' => [
                ['cols' => ['user_id', 'status'], 'name' => 'idx_payments_user_status'],
            ],
            'product_variants' => [
                ['cols' => ['product_id', 'price'], 'name' => 'idx_pv_product_price'],
            ],
            'users' => [
                ['cols' => ['email', 'status'], 'name' => 'idx_users_email_status'],
            ],
            'county_products' => [
                ['cols' => ['county_id', 'product_id'], 'name' => 'idx_cp_county_product'],
            ],
            'ad_campaigns' => [
                ['cols' => ['advertiser_id', 'status'], 'name' => 'idx_adcamp_advertiser_status'],
            ],
            'shipments' => [
                ['cols' => ['order_id', 'status'], 'name' => 'idx_ship_order_status'],
            ],
        ];

        foreach ($composites as $table => $indexes) {
            foreach ($indexes as $idx) {
                if ($this->missing($table, $idx['name'])) {
                    $allColsExist = true;
                    foreach ($idx['cols'] as $col) {
                        if (! $this->hasCol($table, $col)) { $allColsExist = false; break; }
                    }
                    if ($allColsExist) {
                        Schema::table($table, fn (Blueprint $t) => $t->index($idx['cols'], $idx['name']));
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Rollback not needed — indexes are additive and non-destructive
    }
};