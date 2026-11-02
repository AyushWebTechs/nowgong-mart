<?php

namespace App\Services;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Schema;

class StoreSettings
{
    public const DEFAULTS = [
        'store_name' => 'Nowgong Mart',
        'store_tagline' => 'Kirana store + mini ice cream, delivered at home',
        'store_phone' => '',
        'whatsapp_number' => '',
        'store_email' => '',
        'store_address' => 'Nowgong, Chhatarpur, Madhya Pradesh',
        'opening_hours' => '8:00 AM - 9:00 PM, all days',
        'accepting_orders' => '1',
        'service_pincodes' => '471201',
        'service_area_note' => 'We deliver within Nowgong town.',
        'min_order_value' => '100',
        'delivery_fee' => '20',
        'free_delivery_above' => '300',
        'customer_cancel_allowed' => '1',
        'upi_id' => '',
        'upi_payee_name' => '',
        'upi_qr_path' => '',
        'ice_cream_note' => 'Ice cream is packed in an insulated bag and delivered first on the route.',
    ];

    /** @var array<string, string>|null */
    private ?array $values = null;

    public function all(): array
    {
        if ($this->values === null) {
            $stored = Schema::hasTable('store_settings')
                ? StoreSetting::query()->pluck('value', 'key')->map(fn ($v) => (string) $v)->all()
                : [];
            $this->values = array_merge(self::DEFAULTS, $stored);
        }

        return $this->values;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return (bool) (int) $this->get($key, '0');
    }

    public function money(string $key): string
    {
        return bcadd($this->get($key) ?: '0', '0', 2);
    }

    /** @return array<int, string> */
    public function servicePincodes(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $this->get('service_pincodes')))));
    }

    public function deliveryFeeFor(string $subtotal): string
    {
        $freeAbove = $this->money('free_delivery_above');

        if (bccomp($freeAbove, '0', 2) > 0 && bccomp($subtotal, $freeAbove, 2) >= 0) {
            return '0.00';
        }

        return $this->money('delivery_fee');
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                StoreSetting::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            }
        }

        $this->values = null;
    }
}
