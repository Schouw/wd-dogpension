<?php

class WDDP_Options
{

    const OPTION_PRICES = 'wddp_hp_prices';
    const OPTION_WC = 'wddp_hp_wc';
    const OPTION_CLOSED = 'wddp_hp_closed_periods';
    const OPTION_SLOTS = 'wddp_hp_slots';
    const OPTION_EMAILS = 'wddp_hp_email_templates';
    const OPTION_MAX_NO_DOGS = 'wddp_hp_max_no_dogs';

    public static function get(string $key, $default = [])
    {
        $val = get_option($key, null);
        return ($val === null) ? $default : $val;
    }

    public static function defaults_prices(): array
    {
        return [
            'dog1' => 250,
            'dog2' => 200,
            'dog3' => 200,
            'special' => [] // [ ['from'=>'dd/mm - yyyy','to'=>'dd/mm - yyyy','dog1'=>0,'dog2'=>0], ... ]
        ];
    }

    public static function defaults_wc(): array
    {
        return [
            'product_id' => 0,
            'redirect' => 'checkout', // checkout|cart
            'notify_admin_on_create' => 1,
            'checkout_notice' => 'Dette er en bookingforespørgsel. Du modtager først endelig bekræftelse og betaling trækkes først ved godkendelse.'
        ];
    }

    public static function defaults_closed(): array
    {
        return []; // [ ['from'=>'dd/mm - yyyy','to'=>'dd/mm - yyyy'], ... ]
    }

    public static function defaults_slots(): array
    {
        return ['09:00 – 09:30', '16:00 – 16:30'];
    }

    public static function default_max_no_of_dogs(): int
    {
        return 10;
    }

    public static function defaults_emails(): array
    {
        return [
            WDDP_Mail::MAIL_PENDING_CUSTOMER => [
                'subject' => 'Vi har modtaget din booking #{booking_id}',
                'body' => "Hej {first_name},\n\nTak for din booking fra {dropoff_date} til {pickup_date}.\nVi vender tilbage med godkendelse snarest.\n\nMvh {site_name}",
            ],
            WDDP_Mail::MAIL_PENDING_ADMIN => [
                'subject' => 'Ny booking #{booking_id}',
                'body' => "Ny booking modtaget.\nKunde: {first_name} {last_name}\nDatoer: {dropoff_date} → {pickup_date}",
            ],
            WDDP_Mail::MAIL_APPROVED => [
                'subject' => 'Din booking #{booking_id} er godkendt',
                'body' => "Hej {first_name},\n\nDin booking er godkendt. {notes}\nDu modtager separat WooCommerce-kvittering for betaling.\n\nMvh {site_name}",
            ],
            WDDP_Mail::MAIL_REJECTED => [
                'subject' => 'Din booking #{booking_id} er afvist',
                'body' => "Hej {first_name},\n\nVi må desværre afvise din booking.\nÅrsag: {notes}\n\nMvh {site_name}",
            ],
            WDDP_Mail::MAIL_REMINDER => [
                'subject' => 'Påmindelse vedr. booking #{booking_id}',
                'body' => "Hej {first_name},\n\nLille reminder om jeres booking {dropoff_date} → {pickup_date}.\n\nMvh {site_name}",
            ],
            WDDP_Mail::MAIL_CHANGED => [
                'subject' => 'Der er ændringer i din booking (#{booking_id})',
                'body' => "Hej {first_name},\n\nDer er foretaget ændringer i din booking:\n\n{changes}\n\nMvh {site_name}",
            ],
        ];
    }
}
