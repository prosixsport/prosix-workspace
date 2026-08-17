FACTORY BOARD FEATURE INSTALL

1) Copy the three migrations to database/migrations.
2) Copy:
   app/Models/FactoryBoardSetting.php
   app/Models/FactoryBoardCustomField.php
   app/Http/Controllers/Api/FactoryBoardController.php
   app/Observers/OrderObserver.php

3) Add routes/api-additions.php contents into routes/api.php.
4) Add AppServiceProvider-addition.php code to app/Providers/AppServiceProvider.php.
5) Add custom_fields cast to App\Models\Order.
6) Confirm Order model has members() relation using order_members pivot.
7) Replace your current AllOrdersView.vue with resources/js/views/AllOrdersView.vue
   (or copy it to the actual path of this page).
8) Run:
   php artisan migrate
   php artisan optimize:clear
   npm run dev

FEATURES:
- Super admin sees "Select Owner" persistent ON/OFF control.
- ON: all non-client owners (super_admin/admin/member) auto attach to every NEW order.
- OFF: no automatic owner assignment.
- Super admin Columns menu can hide/show existing columns such as Packing Detail.
- Super admin can create/edit/delete custom dropdown columns.
- Example custom column:
    Name: Priority
    Options:
      Urgent
      Normal
      Hold
- Admin/member/super_admin can select a custom dropdown value per order.
- Clients cannot edit custom values.

NOTE:
If your Order relation is called owners() instead of members(), update ONE line in
app/Observers/OrderObserver.php accordingly.
