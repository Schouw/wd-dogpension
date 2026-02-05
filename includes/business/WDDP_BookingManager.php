<?php

class WDDP_BookingManager
{


    public static function create(array $data, array $opts = []): int {
        global $wpdb;

        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        $dog_names = array_column($data['dogs'], 'name');

        $wpdb->insert($table, [
            'order_id'      => null, // oprettes manuelt – ingen Woo ordre
            'status'        => WDDP_StatusHelper::APPROVED,
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'],
            'address'       => $data['address'],
            'postal_code'   => $data['postal_code'],
            'city'          => $data['city'],
            'dropoff_date'  => $data['dropoff_date'],
            'pickup_date'   => $data['pickup_date'],
            'dropoff_time'  => $data['dropoff_time'],
            'pickup_time'   => $data['pickup_time'],
            'dog_names'     => maybe_serialize($dog_names),
            'dog_data'      => maybe_serialize($data['dogs']),
            'price'         => floatval($data['override_price'] ? $data['price'] : WDDP_BookingManager::calculatePrice($data['dropoff_date'], $data['pickup_date'], count($data['dogs']))),
            'notes'         => $data['notes'],
        ]);

        $booking_id = $wpdb->insert_id;

        // Valgfrit: send godkendelsesmail
        if (!empty($opts['send_approved_mail'])) {
            $booking = new WDDP_Booking($booking_id);

            $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder(new WC_Order(), [
                'from_date'      => $data['dropoff_date'],
                'to_date'        => $data['pickup_date'],
                'arrival_time'   => $data['dropoff_time'],
                'departure_time' => $data['pickup_time'],
                'dog_names'      => $dog_names,
                'notes'          => $data['notes'],
            ]);

            $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_APPROVED, $placeholders);
            $mail->send($data['email']);
        }

        return $booking_id;
    }

    /** Slet booking */
    public static function delete(int $id): bool {
        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
        return (bool) $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
    }

    /** Godkend booking + sideeffekter */
    public static function approve(int $id): void {
        $booking = new WDDP_Booking($id);
        $current = $booking->getStatus();

        WDDP_StatusHelper::assertTransitionAllowed($current, WDDP_StatusHelper::APPROVED);

        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        // 1) Opdater status
        $wpdb->update($table, ['status' => WDDP_StatusHelper::APPROVED], ['id' => $id], ['%s'], ['%d']);

        // 2) Opdater Woo order status til on-hold
        $order = wc_get_order($booking->getOrderId());
        if ($order) {
            remove_action('woocommerce_order_status_on_hold_notification', 'woocommerce_order_on_hold_notification');
            $order->update_status('on-hold'); // ingen standard mails, vi sender selv
        }
        $order->add_order_note('Booking godkendt i admin – ordre sat til on-hold. Betaling ikke hævet endnu.');


        // 3) Send godkendelsesmail
        $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder($order, [
            'from_date' => $booking->getBookingDateFrom(),
            'to_date' => $booking->getBookingDateTo(),
            'arrival_time' => $booking->getBookingDeliveryTime(),
            'departure_time' => $booking->getBookingPickUpTime(),
            'dog_names' => maybe_unserialize($booking->getDogData()),
            'notes' => '', // godkendelsesnote findes ikke her
        ]);

        $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_APPROVED, $placeholders);

        // Vedhæft Woo faktura som PDF? (valgfrit — ses nedenfor)
        $to = $booking->getEmail();
        $mail->send($to);
    }

    public static function reject(int $id, string $note = ''): void {
        $booking = new WDDP_Booking($id);
        $current = $booking->getStatus();

        WDDP_StatusHelper::assertTransitionAllowed($current, WDDP_StatusHelper::REJECTED);

        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        // Opdater status
        $wpdb->update($table, [
            'status' => WDDP_StatusHelper::REJECTED,
            'rejection_reason' => $note,
        ], ['id' => $id], ['%s','%s'], ['%d']);

        // Annuler Woo order (ingen Woo mails)
        $order = wc_get_order($booking->getOrderId());
        if ($order) {
            remove_action('woocommerce_order_status_cancelled_notification', 'woocommerce_order_cancelled_notification');
            $order->update_status('cancelled');
        }
        $order->add_order_note('Booking afvist i admin – ordre sat til cancelled.');

        // Send afvisningsmail
        $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder($order, [
            'from_date' => $booking->getBookingDateFrom(),
            'to_date' => $booking->getBookingDateTo(),
            'arrival_time' => $booking->getBookingDeliveryTime(),
            'departure_time' => $booking->getBookingPickUpTime(),
            'dog_names' => maybe_unserialize($booking->getDogData()),
            'notes' => $note,
        ]);

        $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_REJECTED, $placeholders);
        $mail->send($booking->getEmail());
    }


    public static function getBookingsWithStatuses(array $statuses): array {
        global $wpdb;

        if (empty($statuses)) {
            return [];
        }

        // Forbered pladsholdere (%s, %s, ...)
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        $sql = "
        SELECT id, first_name, last_name, dog_data, dropoff_date, pickup_date 
        FROM {$table} 
        WHERE status IN ($placeholders)
    ";

        $result = $wpdb->get_results($wpdb->prepare($sql, ...$statuses), ARRAY_A);

        $bookings = [];
        foreach ($result as $row) {
            $bookings[] = new WDDP_Booking($row['id']);
        }

        return $bookings;
    }

    public static function calculatePrice(string $from, string $to, int $dog_count): float {
        // Simpel placeholders – udbyg senere med specialperioder
        $prices = WDDP_Options::get( WDDP_Options::OPTION_PRICES, WDDP_Options::defaults_prices() );
        $d1 = (float) ($prices['dog1'] ?? 0);
        $d2 = (float) ($prices['dog2'] ?? 0);

        $days = 0;
        if ($from && $to) {
            $days = (int) max( 1, floor( ( strtotime($to) - strtotime($from) ) / DAY_IN_SECONDS ) + 1 );
        }

        if ($dog_count <= 0) return 0.0;
        if ($dog_count === 1) return $days * $d1;
        return $days * ($d1 + $d2);
    }




}