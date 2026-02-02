<?php

class WDDP_WooCommerceManager
{


    public static function validateBookingProduct(\WC_Product $product): array {
        $errors = [];

        if (!$product->is_type('simple')) {
            $errors[] = 'Produktet er ikke et simpelt produkt.';
        }

        if ($product->get_status() !== 'publish') {
            $errors[] = 'Produktet er ikke udgivet.';
        }

        if ($product->get_price() <= 0) {
            $errors[] = 'Produktet mangler pris.';
        }

        if ($product->is_on_sale()) {
            $errors[] = 'Produktet må ikke være på tilbud.';
        }

        if (!$product->is_in_stock()) {
            $errors[] = 'Produktet er ikke på lager.';
        }

        if (!$product->get_name()) {
            $errors[] = 'Produktet mangler navn.';
        }

        return $errors;
    }


    public static function deleteOrder($order_id){
        if ($order_id && get_post($order_id)) {
            add_filter('woocommerce_allow_delete_order', '__return_true');

            $order = wc_get_order($order_id);
            $order->update_status('cancelled', 'Slettet af admin');
            $order->delete( true ); // true = force delete (ingen trash)
        }

    }


    public static function orderIsBookingOnly($order): bool {
        $booking_product_id = intval(WDDP_Options::get(WDDP_Options::OPTION_WC)['product_id'] ?? 0);
        if ($booking_product_id <= 0) return false;

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() != $booking_product_id) {
                return false; // indeholder andet end booking
            }
        }

        return true; // kun booking-produkt
    }

    public static function createBookingInDatabase($order){
        global $wpdb;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // Kun booking-produktet
            $wc_settings = WDDP_Options::get(WDDP_Options::OPTION_WC);
            if ($product_id != intval($wc_settings['product_id'] ?? 0)) continue;

            // Udtræk bookingdata
            $raw_booking = $item->get_meta('_wddp_booking_data', true);

            if (!is_array($raw_booking)) continue;

            $booking_data = [
                'from_date'      => $raw_booking['from_date'] ?? '',
                'to_date'        => $raw_booking['to_date'] ?? '',
                'arrival_time'   => $raw_booking['arrival_time'] ?? '',
                'departure_time' => $raw_booking['departure_time'] ?? '',
                'dogs'           => $raw_booking['dogs'] ?? [],
                'dog_names'      => array_column($raw_booking['dogs'] ?? [], 'name'),
                'notes'          => '', // ← evt. senere udvide booking_data med samlet "notes"
            ];

            // Kundeinfo
            $first_name = $order->get_billing_first_name();
            $last_name  = $order->get_billing_last_name();
            $email      = $order->get_billing_email();
            $phone      = $order->get_billing_phone();
            $address    = $order->get_billing_address_1();
            $postal     = $order->get_billing_postcode();
            $city       = $order->get_billing_city();

            $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

            $existing = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE order_id = %d", $order->get_id())
            );

            if ($existing > 0) {
                // Allerede en booking med denne ordre – stop
                return;
            }

            $wpdb->insert($table, [
                'order_id'      => $order->get_id(),
                'status'        => WDDP_StatusHelper::PENDING_REVIEW,
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'email'         => $email,
                'phone'         => $phone,
                'address'       => $address,
                'postal_code'   => $postal,
                'city'          => $city,
                'dropoff_date'  => $booking_data['from_date'],
                'pickup_date'   => $booking_data['to_date'],
                'dropoff_time'  => $booking_data['arrival_time'],
                'pickup_time'   => $booking_data['departure_time'],
                'dog_names'     => maybe_serialize($booking_data['dog_names']),
                'dog_data'      => maybe_serialize($booking_data['dogs']),
                'price'         => $order->get_total(),
                'notes'         => trim($booking_data['notes']),
            ]);

        }

        $placeholders = static::buildPlaceholdersFromOrder($order, $booking_data);
        $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_PENDING_CUSTOMER, $placeholders);
        $mail->send($email);

        $settings = WDDP_Options::get(WDDP_Options::OPTION_WC);
        if (!empty($settings['notify_admin_on_create'])) {
            $admin_email = get_option('admin_email');
            $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_PENDING_ADMIN, $placeholders);
            $mail->send($admin_email);
        }
    }

    public static function buildPlaceholdersFromOrder(\WC_Order $order, array $booking_data): array {
        // Parse datoer
        $dates = explode('→', $booking_data['from_date']);
        $dropoff_date = isset($dates[0]) ? trim($dates[0]) : '';
        $pickup_date  = isset($dates[1]) ? trim($dates[1]) : '';

        return [
            'first_name'    => $order->get_billing_first_name(),
            'last_name'     => $order->get_billing_last_name(),
            'email'         => $order->get_billing_email(),
            'phone'         => $order->get_billing_phone(),
            'address'       => $order->get_billing_address_1(),
            'postal_code'   => $order->get_billing_postcode(),
            'city'          => $order->get_billing_city(),
            'dropoff_date'  => $dropoff_date,
            'pickup_date'   => $pickup_date,
            'dropoff_time'  => $booking_data['arrival_time'],
            'pickup_time'   => $booking_data['departure_time'],
            'dog_names'     => implode(', ', $booking_data['dog_names'] ?? []),
            'booking_id'    => $order->get_id(),
            'site_name'     => get_bloginfo('name'),
            'notes'         => trim($booking_data['notes']),
            'changes'       => $booking_data['changes'],
        ];
    }

    public static function getBookingDisplayMeta(array $booking): array {
        $item_data = [];

        if (empty($booking)) return $item_data;

        $item_data[] = [
            'name' => 'Datoer',
            'value' => $booking['from_date'] . ' → ' . $booking['to_date'],
        ];

        $item_data[] = [
            'name' => 'Tider',
            'value' => 'Aflevering: ' . $booking['arrival_time'] . ' / Afhentning: ' . $booking['departure_time'],
        ];

        foreach ($booking['dogs'] as $i => $dog) {
            $item_data[] = [
                'name' => 'Hund ' . ($i + 1),
                'value' => sprintf(
                    '%s (%s), %s år, %s kg',
                    $dog['name'] ?? '-', $dog['breed'] ?? '-', $dog['age'] ?? '-', $dog['weight'] ?? '-'
                ),
            ];
            if (!empty($dog['notes'])) {
                $item_data[] = [
                    'name' => 'Noter – ' . $dog['name'],
                    'value' => $dog['notes'],
                ];
            }
        }

        return $item_data;
    }


}