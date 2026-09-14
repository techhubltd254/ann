<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIfMissing('ad_campaigns', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 200), $t->text('description')->nullable(),
            $t->date('start_date'), $t->date('end_date')->nullable(),
            $t->decimal('budget', 12, 2), $t->string('currency', 3)->default('KES'),
            $t->string('status', 20)->default('draft'), $t->softDeletes(), $t->timestamps(),
        ]);
        $this->createIfMissing('ad_groups', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete(),
            $t->string('name', 200), $t->string('status', 20)->default('active'),
            $t->decimal('bid_amount', 10, 2)->default(0), $t->timestamps(),
        ]);
        $this->createIfMissing('ad_creatives', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('ad_group_id')->nullable()->constrained('ad_groups')->nullOnDelete(),
            $t->foreignId('campaign_id')->constrained('ad_campaigns')->cascadeOnDelete(),
            $t->string('title', 200), $t->text('body')->nullable(),
            $t->string('image_url', 500)->nullable(), $t->string('video_url', 500)->nullable(),
            $t->string('target_url', 500), $t->string('type', 50)->default('banner'),
            $t->string('status', 20)->default('pending'), $t->softDeletes(), $t->timestamps(),
        ]);
        $this->createIfMissing('ad_placements', fn (Blueprint $t) => [
            $t->id(), $t->string('slug', 100)->unique(), $t->string('name', 200),
            $t->text('description')->nullable(), $t->string('location', 100),
            $t->string('dimensions', 50)->nullable(), $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('advertisers', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->string('company_name', 200), $t->string('contact_email'),
            $t->string('contact_phone', 20)->nullable(),
            $t->decimal('wallet_balance', 12, 2)->default(0),
            $t->string('status', 20)->default('active'), $t->softDeletes(), $t->timestamps(),
        ]);
        $this->createIfMissing('ad_impressions', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('creative_id')->constrained('ad_creatives')->cascadeOnDelete(),
            $t->foreignId('placement_id')->constrained('ad_placements')->cascadeOnDelete(),
            $t->string('ip', 45)->nullable(), $t->string('user_agent', 500)->nullable(),
            $t->timestamp('viewed_at')->useCurrent(),
        ]);
        $this->createIfMissing('ad_clicks', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('creative_id')->constrained('ad_creatives')->cascadeOnDelete(),
            $t->foreignId('placement_id')->constrained('ad_placements')->cascadeOnDelete(),
            $t->string('ip', 45)->nullable(), $t->string('user_agent', 500)->nullable(),
            $t->timestamp('clicked_at')->useCurrent(),
        ]);
        $this->createIfMissing('advertisements', fn (Blueprint $t) => [
            $t->id(), $t->morphs('advertisable'), $t->string('title', 200),
            $t->text('content')->nullable(), $t->string('image_url', 500)->nullable(),
            $t->string('link_url', 500)->nullable(), $t->string('position', 50)->default('sidebar'),
            $t->date('start_date'), $t->date('end_date')->nullable(),
            $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('audit_logs', fn (Blueprint $t) => [
            $t->id(), $t->nullableMorphs('auditable'),
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(),
            $t->string('event', 50), $t->json('old_values')->nullable(),
            $t->json('new_values')->nullable(), $t->text('description')->nullable(),
            $t->string('ip', 45)->nullable(), $t->string('user_agent', 500)->nullable(),
            $t->timestamps(),
        ]);
        $this->createIfMissing('notification_logs', fn (Blueprint $t) => [
            $t->id(), $t->nullableMorphs('notifiable'), $t->string('channel', 50),
            $t->string('type', 100), $t->text('content'), $t->string('status', 20)->default('sent'),
            $t->text('response')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('broadcast_schedules', fn (Blueprint $t) => [
            $t->id(), $t->morphs('schedulable'), $t->timestamp('scheduled_at'),
            $t->timestamp('broadcast_at')->nullable(), $t->string('status', 20)->default('pending'),
            $t->text('notes')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('invoices', fn (Blueprint $t) => [
            $t->id(), $t->string('invoice_number', 50)->unique(),
            $t->morphs('billable'), $t->decimal('subtotal', 12, 2),
            $t->decimal('tax', 12, 2)->default(0), $t->decimal('total', 12, 2),
            $t->string('currency', 3)->default('KES'), $t->string('status', 20)->default('pending'),
            $t->timestamp('due_date')->nullable(), $t->timestamp('paid_at')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('invoice_items', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('invoice_id')->constrained()->cascadeOnDelete(),
            $t->morphs('itemizable'), $t->string('description'), $t->integer('quantity')->default(1),
            $t->decimal('unit_price', 12, 2), $t->decimal('total', 12, 2), $t->timestamps(),
        ]);
        $this->createIfMissing('billing_cycles', fn (Blueprint $t) => [
            $t->id(), $t->morphs('subscriber'), $t->string('period', 20),
            $t->timestamp('start_date'), $t->timestamp('end_date')->nullable(),
            $t->decimal('amount', 12, 2), $t->string('status', 20)->default('active'), $t->timestamps(),
        ]);
        $this->createIfMissing('content_pages', fn (Blueprint $t) => [
            $t->id(), $t->string('title', 200), $t->string('slug', 200)->unique(),
            $t->text('content')->nullable(), $t->json('meta')->nullable(),
            $t->string('template', 100)->nullable(), $t->boolean('is_published')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('seo_metadata', fn (Blueprint $t) => [
            $t->id(), $t->morphs('seoable'), $t->string('title', 200)->nullable(),
            $t->text('description')->nullable(), $t->text('keywords')->nullable(),
            $t->string('og_image', 500)->nullable(), $t->string('canonical_url', 500)->nullable(),
            $t->json('custom')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('drone_sequences', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->nullable()->constrained()->nullOnDelete(),
            $t->string('name', 200), $t->json('waypoints'), $t->decimal('altitude', 8, 2)->nullable(),
            $t->decimal('speed', 8, 2)->nullable(), $t->string('status', 20)->default('draft'), $t->timestamps(),
        ]);
        $this->createIfMissing('floor_plans', fn (Blueprint $t) => [
            $t->id(), $t->morphs('mappable'), $t->string('name', 200),
            $t->string('image_url', 500), $t->json('data')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('housing_projects', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->string('name', 200), $t->text('description')->nullable(),
            $t->decimal('latitude', 10, 7)->nullable(), $t->decimal('longitude', 10, 7)->nullable(),
            $t->string('developer', 200)->nullable(), $t->integer('units')->nullable(),
            $t->decimal('price_from', 12, 2)->nullable(), $t->string('status', 20)->default('planned'), $t->timestamps(),
        ]);
        $this->createIfMissing('presidential_audios', fn (Blueprint $t) => [
            $t->id(), $t->string('title', 200), $t->text('description')->nullable(),
            $t->string('audio_url', 500), $t->string('transcript', 5000)->nullable(),
            $t->string('language', 10)->default('en'), $t->date('recorded_at'),
            $t->boolean('is_published')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('landmarks', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 200), $t->text('description')->nullable(),
            $t->decimal('latitude', 10, 7), $t->decimal('longitude', 10, 7),
            $t->string('type', 50)->nullable(), $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('county_subscribers', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete(),
            $t->timestamp('subscribed_at')->useCurrent(), $t->timestamp('expires_at')->nullable(),
            $t->string('status', 20)->default('active'), $t->timestamps(),
        ]);
        $this->createIfMissing('county_subscription_plans', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->string('name', 200), $t->text('description')->nullable(),
            $t->decimal('price', 12, 2), $t->string('billing_period', 20)->default('monthly'),
            $t->integer('max_entities')->nullable(), $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('county_bulk_slot_allocations', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->integer('slots_allocated'), $t->integer('slots_used')->default(0),
            $t->timestamp('allocated_at')->useCurrent(), $t->timestamp('expires_at')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('county_financial_config', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->decimal('commission_rate', 5, 2)->default(0),
            $t->decimal('transaction_fee', 10, 2)->default(0),
            $t->string('payment_account', 200)->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('wallet_transactions', fn (Blueprint $t) => [
            $t->id(), $t->morphs('walletable'), $t->decimal('amount', 12, 2),
            $t->string('type', 50), $t->string('description')->nullable(),
            $t->decimal('balance_before', 12, 2), $t->decimal('balance_after', 12, 2),
            $t->string('reference', 100)->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('payment_gateways', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 100), $t->string('slug', 100)->unique(),
            $t->string('driver', 100), $t->json('config')->nullable(),
            $t->boolean('is_active')->default(false), $t->boolean('is_default')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('pipeline_jobs', fn (Blueprint $t) => [
            $t->id(), $t->morphs('pipelineable'), $t->string('engine', 100),
            $t->string('status', 30)->default('queued'), $t->json('config')->nullable(),
            $t->json('result')->nullable(), $t->text('error')->nullable(),
            $t->integer('progress')->default(0), $t->timestamp('started_at')->nullable(),
            $t->timestamp('completed_at')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('product_reviews', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('product_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->integer('rating')->default(5), $t->text('review')->nullable(),
            $t->boolean('is_verified')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('recommendations', fn (Blueprint $t) => [
            $t->id(), $t->morphs('recommendable'), $t->morphs('recommended'),
            $t->decimal('score', 8, 4)->default(0), $t->string('reason')->nullable(),
            $t->timestamps(),
        ]);
        $this->createIfMissing('pulse_entries', fn (Blueprint $t) => [
            $t->id(), $t->timestamp('timestamp'), $t->string('type', 100),
            $t->morphs('pulseable'), $t->json('data')->nullable(),
            $t->index(['timestamp', 'type']),
        ]);
        $this->createIfMissing('pulse_aggregates', fn (Blueprint $t) => [
            $t->id(), $t->string('bucket', 100), $t->string('period', 20),
            $t->string('metric', 100), $t->decimal('value', 20, 4),
            $t->timestamp('aggregated_at'), $t->unique(['bucket', 'period', 'metric', 'aggregated_at']),
        ]);
        $this->createIfMissing('pulse_values', fn (Blueprint $t) => [
            $t->id(), $t->string('key', 200), $t->text('value'),
            $t->timestamp('timestamp'), $t->unique('key'),
        ]);
        $this->createIfMissing('shipping_zones', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 200), $t->string('country', 100),
            $t->json('regions')->nullable(), $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('shipping_rates', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete(),
            $t->string('name', 200), $t->decimal('rate', 10, 2),
            $t->string('type', 50)->default('flat'), $t->decimal('min_weight', 8, 2)->nullable(),
            $t->decimal('max_weight', 8, 2)->nullable(), $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('courier_partners', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 200), $t->string('api_endpoint', 500)->nullable(),
            $t->string('api_key', 255)->nullable(), $t->json('config')->nullable(),
            $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('usage_logs', fn (Blueprint $t) => [
            $t->id(), $t->morphs('usager'), $t->string('metric', 100),
            $t->decimal('value', 12, 4), $t->timestamp('recorded_at')->useCurrent(),
            $t->index(['usager_type', 'usager_id', 'metric']),
        ]);
        $this->createIfMissing('voice_notes', fn (Blueprint $t) => [
            $t->id(), $t->morphs('ownable'), $t->string('audio_url', 500),
            $t->string('transcript', 5000)->nullable(), $t->string('language', 10)->default('en'),
            $t->integer('duration_seconds')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('speech_segments', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('voice_note_id')->constrained('voice_notes')->cascadeOnDelete(),
            $t->integer('start_ms'), $t->integer('end_ms'), $t->text('text'),
            $t->decimal('confidence', 5, 4)->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('screen_playlist_items', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('screen_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete(),
            $t->string('content_type', 50), $t->string('content_url', 500)->nullable(),
            $t->integer('duration_seconds')->default(15), $t->integer('order')->default(0),
            $t->boolean('is_active')->default(true), $t->timestamps(),
        ]);
        $this->createIfMissing('trader_spotlights', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('county_id')->constrained()->cascadeOnDelete(),
            $t->string('name', 200), $t->text('description')->nullable(),
            $t->string('product', 200)->nullable(), $t->string('contact', 200)->nullable(),
            $t->string('image_url', 500)->nullable(), $t->boolean('is_featured')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('oauth_clients', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(),
            $t->string('name', 200), $t->string('secret', 100), $t->text('redirect'),
            $t->boolean('personal_access_client')->default(false),
            $t->boolean('password_client')->default(false),
            $t->boolean('revoked')->default(false), $t->timestamps(),
        ]);
        $this->createIfMissing('oauth_tokens', fn (Blueprint $t) => [
            $t->id(), $t->foreignId('client_id')->constrained('oauth_clients')->cascadeOnDelete(),
            $t->nullableMorphs('tokenable'), $t->string('name')->nullable(),
            $t->text('scopes')->nullable(), $t->boolean('revoked')->default(false),
            $t->timestamp('expires_at')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('analytics_events', fn (Blueprint $t) => [
            $t->id(), $t->string('event', 100), $t->morphs('subject'),
            $t->json('payload')->nullable(), $t->string('ip', 45)->nullable(),
            $t->string('user_agent', 500)->nullable(),
            $t->timestamp('occurred_at')->useCurrent(),
            $t->index(['event', 'occurred_at']),
        ]);
        $this->createIfMissing('page_views', fn (Blueprint $t) => [
            $t->id(), $t->string('url', 500), $t->string('title', 255)->nullable(),
            $t->string('referrer', 500)->nullable(), $t->string('ip', 45)->nullable(),
            $t->string('user_agent', 500)->nullable(),
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(),
            $t->timestamp('viewed_at')->useCurrent(), $t->index(['url', 'viewed_at']),
        ]);
        $this->createIfMissing('embeddings', fn (Blueprint $t) => [
            $t->id(), $t->morphs('embeddable'), $t->text('content'), $t->binary('vector'),
            $t->string('model', 100)->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('payment_intents', fn (Blueprint $t) => [
            $t->id(), $t->string('stripe_pi_id', 255)->unique(), $t->morphs('payable'),
            $t->decimal('amount', 12, 2), $t->string('currency', 3)->default('KES'),
            $t->string('status', 30)->default('pending'),
            $t->string('payment_method', 50)->nullable(),
            $t->json('metadata')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('transaction_logs', fn (Blueprint $t) => [
            $t->id(), $t->string('reference', 50)->unique(), $t->morphs('loggable'),
            $t->decimal('amount', 12, 2), $t->string('currency', 3)->default('KES'),
            $t->string('type', 50), $t->string('status', 30)->default('pending'),
            $t->json('metadata')->nullable(), $t->string('gateway_response', 500)->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('flight_bookings', fn (Blueprint $t) => [
            $t->id(), $t->string('booking_reference', 20)->unique(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('flight_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('flight_inventory_id')->constrained('flight_inventory')->cascadeOnDelete(),
            $t->string('fare_class', 20)->default('economy'),
            $t->integer('passenger_count')->default(1),
            $t->decimal('subtotal', 10, 2), $t->decimal('tax', 10, 2)->default(0),
            $t->decimal('total', 10, 2), $t->string('currency', 3)->default('KES'),
            $t->string('status', 30)->default('pending'),
            $t->string('pnr_code', 10)->nullable()->unique(),
            $t->timestamp('booked_at')->useCurrent(), $t->timestamps(),
        ]);
        $this->createIfMissing('hotel_bookings', fn (Blueprint $t) => [
            $t->id(), $t->string('booking_reference', 20)->unique(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('hotel_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('hotel_room_id')->nullable()->constrained('hotel_rooms')->nullOnDelete(),
            $t->date('check_in'), $t->date('check_out'),
            $t->integer('guest_count')->default(1),
            $t->decimal('subtotal', 10, 2), $t->decimal('tax', 10, 2)->default(0),
            $t->decimal('total', 10, 2), $t->string('currency', 3)->default('KES'),
            $t->string('status', 30)->default('pending'), $t->timestamps(),
        ]);
        $this->createIfMissing('transfer_bookings', fn (Blueprint $t) => [
            $t->id(), $t->string('booking_reference', 20)->unique(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('transfer_id')->constrained('airport_transfers')->cascadeOnDelete(),
            $t->foreignId('flight_booking_id')->nullable()->constrained('flight_bookings')->nullOnDelete(),
            $t->string('pickup_location'), $t->string('dropoff_location'),
            $t->timestamp('pickup_datetime'), $t->string('flight_number', 10)->nullable(),
            $t->integer('passenger_count')->default(1), $t->decimal('total', 10, 2),
            $t->string('currency', 3)->default('KES'), $t->string('status', 30)->default('allocated'), $t->timestamps(),
        ]);
        $this->createIfMissing('attraction_bookings', fn (Blueprint $t) => [
            $t->id(), $t->string('booking_reference', 20)->unique(),
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(),
            $t->foreignId('attraction_id')->constrained('attractions')->cascadeOnDelete(),
            $t->date('visit_date'), $t->integer('visitors')->default(1),
            $t->decimal('total', 10, 2), $t->string('currency', 3)->default('KES'),
            $t->string('status', 30)->default('pending'), $t->text('notes')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('livestream_channels', fn (Blueprint $t) => [
            $t->id(), $t->string('name', 100), $t->string('platform', 50),
            $t->string('channel_id', 255), $t->string('stream_url', 500)->nullable(),
            $t->string('status', 20)->default('active'), $t->boolean('is_live')->default(false),
            $t->timestamp('last_live_at')->nullable(), $t->timestamps(),
        ]);
        $this->createIfMissing('consent_forms', fn (Blueprint $t) => [
            $t->id(), $t->string('title', 200), $t->string('slug', 200)->unique(),
            $t->string('language', 10)->default('en'), $t->text('content_en')->nullable(),
            $t->text('content_sw')->nullable(), $t->nullableMorphs('entity'),
            $t->boolean('is_active')->default(true), $t->integer('signed_count')->default(0), $t->timestamps(),
        ]);
    }

    public function down(): void {}

    private function createIfMissing(string $table, callable $schema): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, fn (Blueprint $t) => $schema($t));
        }
    }
};