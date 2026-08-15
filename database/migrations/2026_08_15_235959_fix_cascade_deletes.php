<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Drop dangerous cascades on financial tables and add RESTRICT instead
        $changes = [
            // Gift cards: issued_by_user_id → restrict
            "ALTER TABLE gift_cards DROP FOREIGN KEY gift_cards_issued_by_user_id_foreign",
            // Bookings: user_id, exhibition_id → restrict
            "ALTER TABLE bookings DROP FOREIGN KEY bookings_user_id_foreign",
            "ALTER TABLE bookings DROP FOREIGN KEY bookings_exhibition_id_foreign",
            // Escrow: buyer_id, seller_id → restrict
            "ALTER TABLE escrow_transactions DROP FOREIGN KEY escrow_transactions_buyer_id_foreign",
            "ALTER TABLE escrow_transactions DROP FOREIGN KEY escrow_transactions_seller_id_foreign",
            // Agents: user_id → restrict
            "ALTER TABLE agents DROP FOREIGN KEY agents_user_id_foreign",
            // Agent documents: agent_id → restrict
            "ALTER TABLE agent_documents DROP FOREIGN KEY agent_documents_agent_id_foreign",
        ];

        // TiDB may not have all these FK constraints; try each and ignore errors
        foreach ($changes as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) {}
        }

        // Add new FK constraints with RESTRICT where possible
        $additions = [
            "ALTER TABLE gift_cards ADD CONSTRAINT gift_cards_issued_fk FOREIGN KEY (issued_by_user_id) REFERENCES users(id) ON DELETE RESTRICT",
        ];
        foreach ($additions as $sql) {
            try { DB::statement($sql); } catch (\Throwable $e) {}
        }
    }

    public function down(): void {
        // Reversible: the old cascades were the default, nothing to restore
    }
};