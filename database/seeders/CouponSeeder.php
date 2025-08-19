<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Global percentage discount coupon
        Coupon::create([
            'code' => 'WELCOME10',
            'discount_percentage' => 10,
            'discount_amount' => 0,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addMonth(),
            'usage_limit' => 100,
            'used_count' => 0,
            'status' => 'active',
        ]);

        // 2. Global fixed discount coupon
        Coupon::create([
            'code' => 'FLAT50',
            'discount_percentage' => 0,
            'discount_amount' => 50,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addMonth(),
            'usage_limit' => 30,
            'used_count' => 0,
            'status' => 'active',
        ]);

        // 3. Global percentage discount
        Coupon::create([
            'code' => 'DOC20',
            'discount_percentage' => 20,
            'discount_amount' => 0,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addWeeks(3),
            'usage_limit' => 50,
            'used_count' => 0,
            'status' => 'active',
        ]);

        // 4. Global combo discount
        Coupon::create([
            'code' => 'HEALTH30',
            'discount_percentage' => 15,
            'discount_amount' => 30,
            'valid_from' => Carbon::now()->subDay(),
            'valid_to' => Carbon::now()->addWeeks(4),
            'usage_limit' => 75,
            'used_count' => 0,
            'status' => 'active',
        ]);

        // 5. Limited-time fixed discount
        Coupon::create([
            'code' => 'SPECIAL100',
            'discount_percentage' => 0,
            'discount_amount' => 100,
            'valid_from' => Carbon::now(),
            'valid_to' => Carbon::now()->addDays(10),
            'usage_limit' => 10,
            'used_count' => 0,
            'status' => 'active',
        ]);
    }
}
