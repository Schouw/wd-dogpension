<?php

class WDDP_WooCommerceSetup
{
    //TODO: REFACT AND DOC


    public static function init(){
        add_filter('woocommerce_get_item_data', [static::class, 'getItemData'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [static::class, 'createOrderLine'], 10, 4);
        add_action('woocommerce_before_calculate_totals', [static::class, 'setCustomPrice'], 20, 1);
        add_action('woocommerce_checkout_before_customer_details', [static::class, 'displayBox']);
        add_action('woocommerce_order_status_changed', [static::class, 'handleOrderCompleted'], 10, 4);

        // Undgå Woo-mail til kunden for booking-ordrer
        add_filter('woocommerce_email_enabled_customer_processing_order', [static::class, 'maybeSuppressCustomerMail'], 10, 2);
        add_filter('woocommerce_email_enabled_customer_completed_order', [static::class, 'maybeSuppressCustomerMail'], 10, 2);
        add_filter('woocommerce_email_enabled_customer_on_hold_order', [static::class, 'maybeSuppressCustomerMail'], 10, 2);
        add_filter('woocommerce_email_enabled_customer_invoice', [static::class, 'maybeSuppressCustomerMail'], 10, 2);
        add_filter('woocommerce_email_enabled_customer_cancelled_order', [static::class, 'maybeSuppressCustomerMail'], 10, 2);

        // Undgå mail til admin
        add_filter('woocommerce_email_enabled_new_order', [static::class, 'maybeSuppressCustomerMail'], 10, 2);

        // box på kurv og kasse
        add_action('template_redirect', [static::class, 'maybeInjectBookingNoticeEarly']);

    }


    public static function maybeInjectBookingNoticeEarly() {
        if (is_cart() || is_checkout()) {
            wc_print_notice(
                '<strong>Bemærk ved booking:</strong> Din booking er endnu ikke garanteret – den skal først godkendes. Vi hæver ikke beløbet før godkendelse.
     <button type="button" class="wddp-close-notice" aria-label="Luk besked" style="float:right;">×</button>',
                'notice'
            );

        }
    }

    public static function maybeSuppressCustomerMail($enabled, $order) {
        if (! $order instanceof WC_Order) return $enabled;

        // Lad være med at overstyre hvis den allerede er slået fra
        if (! $enabled) return false;

        return WDDP_WooCommerceManager::orderIsBookingOnly($order) ? false : true;
    }

    public static function handleOrderCompleted($order_id, $from_status, $to_status, $order) {
        if (!in_array($to_status, ['processing', 'completed'], true)) return;

        WDDP_WooCommerceManager::createBookingInDatabase($order);
    }


    public static function displayBox() {

        $wc = WDDP_Options::get(WDDP_Options::OPTION_WC, WDDP_Options::defaults_wc());
        $notice = trim($wc['checkout_notice'] ?? '');

        if ($notice !== '') {
            echo '<div class="wddp-checkout-notice" style="border: 1px solid #ccc; padding: 1em; margin-bottom: 1.5em; background: #f9f9f9;">';
            echo wpautop(wp_kses_post($notice));
            echo '</div>';
        }
    }

    public static function setCustomPrice($cart) {
       if (is_admin() && !defined('DOING_AJAX')) return;

        // Gennemgå hvert cart item
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!isset($cart_item['booking_data'])) continue;

            $booking = $cart_item['booking_data'];

            // Beregnet pris – skal være inkl. moms
            $price = floatval($booking['calculated_price'] ?? 0);
            if ($price <= 0) continue;

            // Overskriv prisen
            $cart_item['data']->set_price($price);
        }
    }



    public static function createOrderLine($item, $cart_item_key, $values, $order)
    {
        if (empty($values['booking_data'])) return;


        $booking = $values['booking_data'];

        $item->add_meta_data('Booking info', 'Se nedenfor');
        $item->add_meta_data('Datoer', $booking['from_date'] . ' → ' . $booking['to_date']);
        $item->add_meta_data('Tider', 'Aflevering: ' . $booking['arrival_time'] . ' / Afhentning: ' . $booking['departure_time']);
        foreach ($booking['dogs'] as $i => $dog) {
            $item->add_meta_data('Hund ' . ($i + 1), sprintf(
                '%s (%s), %s, %s kg',
                $dog['name'] ?? '-', $dog['breed'] ?? '-', $dog['age'] ?? '-', $dog['weight'] ?? '-'
            ));

            if (!empty($dog['notes'])) {
                $item->add_meta_data('Noter – ' . $dog['name'], $dog['notes']);
            }
        }

        //add all data raw
        $item->add_meta_data('_wddp_booking_data', $booking, true);

    }

    public static function getItemData($item_data, $cart_item) {
        if (empty($cart_item['booking_data'])) return $item_data;

        $booking = $cart_item['booking_data'];
        $meta = WDDP_WooCommerceManager::getBookingDisplayMeta($booking);

        return array_merge($item_data, $meta);
    }




}