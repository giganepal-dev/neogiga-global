<?php

namespace Database\Seeders\Marketing;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingAutomationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Electronics Components','Robotics','IoT Projects','Batteries and Power Storage','Solar and Renewable Energy','Tools and Equipment','EV Components','Industrial Automation','Smart Farming','Offers and Deals','New Arrivals','LMS Tutorials','Vendor Updates'] as $name) {
            DB::table('newsletter_categories')->updateOrInsert(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach (['Email OTP'=>'email_otp','Welcome / Account Created'=>'welcome','Order Confirmation'=>'order_confirmation','Payment Confirmation'=>'payment_confirmation','Order Status Update'=>'order_status_update','Shipment Update'=>'shipment_update','Delivered'=>'delivered','Abandoned Cart Reminder'=>'abandoned_cart','Newsletter'=>'newsletter','Vendor Approval'=>'vendor_approval','Back in Stock'=>'back_in_stock','Price Drop Alert'=>'price_drop_alert'] as $name => $type) {
            DB::table('email_templates')->updateOrInsert(['slug' => Str::slug($type)], ['name' => $name, 'type' => $type, 'subject' => 'NeoGiga: '.$name, 'text_body' => 'Hello {{customer_name}}, this is your '.$name.' message.', 'variables' => json_encode(['customer_name','email','unsubscribe_url','otp_code','order_number']), 'is_transactional' => !in_array($type, ['newsletter','abandoned_cart','back_in_stock','price_drop_alert'], true), 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach (['Nepal Customers','India Customers','Global Customers','Robotics Interested','Battery Interested','Solar Interested','IoT Interested','Abandoned Cart Users','High Value Customers','Inactive 30 Days','B2B Buyers','School and Lab Buyers','Newsletter Subscribers'] as $name) {
            DB::table('customer_segments')->updateOrInsert(['slug' => Str::slug($name)], ['name' => $name, 'rules' => json_encode([]), 'type' => 'dynamic', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach (['email_otp','order_confirmation','abandoned_cart','vendor_update'] as $name) {
            DB::table('whatsapp_templates')->updateOrInsert(['slug' => Str::slug($name)], ['name' => Str::headline($name), 'approval_status' => 'placeholder', 'body' => 'NeoGiga template placeholder: '.$name, 'updated_at' => now(), 'created_at' => now()]);
        }
        foreach (['email_provider'=>'log','whatsapp_provider'=>'manual_export','newsletter_double_opt_in'=>false,'abandoned_cart_first_reminder_minutes'=>60,'campaign_daily_limit'=>1000] as $key => $value) {
            DB::table('marketing_settings')->updateOrInsert(['key'=>$key], ['value'=>json_encode($value), 'group'=>'marketing', 'updated_at'=>now(), 'created_at'=>now()]);
        }
        DB::table('analytics_settings')->updateOrInsert(['key'=>'ga_measurement_id'], ['value'=>json_encode(null), 'group'=>'analytics', 'updated_at'=>now(), 'created_at'=>now()]);
    }
}
