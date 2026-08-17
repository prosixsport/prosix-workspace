<?php

namespace App\Observers;

use App\Models\FactoryBoardSetting;
use App\Models\Order;
use App\Models\User;

class OrderObserver
{
    public function created(Order $order): void
    {
        $setting = FactoryBoardSetting::singleton();

        if (!$setting->auto_assign_all_owners) {
            return;
        }

        /*
         * Clients are intentionally excluded.
         * Every super_admin/admin/member becomes an owner/member
         * of each newly-created order while the switch is ON.
         */
        $ownerIds = User::query()
            ->whereIn('role', [
                'super_admin',
                'admin',
                'member',
            ])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!$ownerIds) {
            return;
        }

        /*
         * Your CRM already uses the order_members pivot and member
         * management endpoints. If the relation in your Order model
         * is named owners() instead of members(), change only the
         * next line from members() to owners().
         */
        $order->members()->syncWithoutDetaching($ownerIds);
    }
}
