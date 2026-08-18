<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slow-query tuning pass (2026-08-18): index the hot-path FK columns that
 * the DBA index audit flagged as missing. All additions are guarded by
 * hasTable/hasIndex so this is safe to run on both prod TiDB and local
 * SQLite regardless of schema provenance (commerce tables come from raw SQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Bookings: list by exhibition (admin dashboards) + status filters
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $t) {
                if (! $this->hasIndex('bookings', 'idx_bookings_exhibition')) {
                    $t->index(['exhibition_id', 'status'], 'idx_bookings_exhibition');
                }
            });
        }

        // Tickets: relationship loads (booking tickets), type lookups, holder
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $t) {
                if (! $this->hasIndex('tickets', 'idx_tickets_booking')) {
                    $t->index(['booking_id'], 'idx_tickets_booking');
                }
                if (! $this->hasIndex('tickets', 'idx_tickets_type_status')) {
                    $t->index(['ticket_type_id', 'status'], 'idx_tickets_type_status');
                }
                if (! $this->hasIndex('tickets', 'idx_tickets_user')) {
                    $t->index(['user_id'], 'idx_tickets_user');
                }
            });
        }

        // Ticket types: per-exhibition management views
        if (Schema::hasTable('ticket_types')) {
            Schema::table('ticket_types', function (Blueprint $t) {
                if (! $this->hasIndex('ticket_types', 'idx_ticket_types_exhibition')) {
                    $t->index(['exhibition_id'], 'idx_ticket_types_exhibition');
                }
            });
        }

        // Booking booths: nested booking items
        if (Schema::hasTable('booking_booths')) {
            Schema::table('booking_booths', function (Blueprint $t) {
                if (! $this->hasIndex('booking_booths', 'idx_booking_booths_booking')) {
                    $t->index(['booking_id'], 'idx_booking_booths_booking');
                }
            });
        }

        // Analytics events: user + type drill-downs
        if (Schema::hasTable('analytics_events')) {
            Schema::table('analytics_events', function (Blueprint $t) {
                if (! $this->hasIndex('analytics_events', 'idx_ae_user')) {
                    $t->index(['user_id'], 'idx_ae_user');
                }
            });
        }

        // Page views: reporting rollups by time
        if (Schema::hasTable('page_views')) {
            Schema::table('page_views', function (Blueprint $t) {
                if (! $this->hasIndex('page_views', 'idx_page_views_viewed_at')) {
                    $t->index(['viewed_at'], 'idx_page_views_viewed_at');
                }
            });
        }

        // County sector pivot: county drill-downs
        if (Schema::hasTable('county_sector')) {
            Schema::table('county_sector', function (Blueprint $t) {
                if (! $this->hasIndex('county_sector', 'idx_county_sector_sector')) {
                    $t->index(['sector_id'], 'idx_county_sector_sector');
                }
            });
        }

        // Users: county-based reporting/segmentation
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $t) {
                if (! $this->hasIndex('users', 'idx_users_county')) {
                    $t->index(['county_id'], 'idx_users_county');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'bookings' => 'idx_bookings_exhibition',
            'tickets' => 'idx_tickets_booking',
            'tickets' => 'idx_tickets_type_status',
            'booking_booths' => 'idx_booking_booths_booking',
            'analytics_events' => 'idx_ae_user',
            'page_views' => 'idx_page_views_viewed_at',
            'county_sector' => 'idx_county_sector_sector',
        ] as $table => $index) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    $t->dropIndex($index);
                });
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $index);
    }
};
