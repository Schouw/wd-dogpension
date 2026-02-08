<?php

class WDDP_BookingManager
{

    /**
     * Create booking and send mail if setting asks for it.
     */
    public static function create(array $data, array $opts = [], $initstatus = WDDP_StatusHelper::PENDING_REVIEW): int {

        // Save the booking in the db
        $booking_id = WDDP_BookingPersistence::createBooking($data, $initstatus);

        // Send approved mail
        //TODO: Extract in mail system
        if (!empty($opts['send_approved_mail'])) {
            $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder(new WC_Order(), [
                'from_date'      => $data['dropoff_date'],
                'to_date'        => $data['pickup_date'],
                'arrival_time'   => $data['dropoff_time'],
                'departure_time' => $data['pickup_time'],
                'dog_names'      => WDDP_DogHelper::extractDogNames($data['dogs']),
                'notes'          => $data['notes'],
            ]);

            $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_APPROVED, $placeholders);
            $mail->send($data['email']);
        }

        return $booking_id;
    }

    /**
     * Deletes the booking with the given id
     * @return bool true if deleted, otherwise false
     */
    public static function delete(int $id): bool {
        return WDDP_BookingPersistence::deleteBooking($id);
    }

    /**
     * Approve the booking by
     * - Setting status on booking to approved
     * - Change woocommerce to on hold
     * - Send approved mail
     */
    public static function approve(int $id): void {
        //TODO: Check if booking id exists

        // Update status in db
        WDDP_BookingPersistence::updateBookingStatus($id, WDDP_StatusHelper::APPROVED);

        //TODO: Move to woocommerce manager
        $booking = new WDDP_Booking($id);
        $order = wc_get_order($booking->getOrderId());
        if ($order) {
            remove_action('woocommerce_order_status_on_hold_notification', 'woocommerce_order_on_hold_notification');
            $order->update_status('on-hold'); // ingen standard mails, vi sender selv
        }
        $order->add_order_note('Booking godkendt i admin – ordre sat til on-hold. Betaling ikke hævet endnu.');


        // TODO: Send godkendelsesmail to mail manager
        $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder($order, [
            'from_date' => $booking->getBookingDateFrom(),
            'to_date' => $booking->getBookingDateTo(),
            'arrival_time' => $booking->getBookingDeliveryTime(),
            'departure_time' => $booking->getBookingPickUpTime(),
            'dog_names' => maybe_unserialize($booking->getDogData()),
            'notes' => '', // godkendelsesnote findes ikke her
        ]);

        $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_APPROVED, $placeholders);

        $to = $booking->getEmail();
        $mail->send($to);
    }


    /**
     * Reject the booking by
     * - Setting status on booking to rejected
     * - Change woocommerce to cancelled
     * - Send reject mail
     */
    public static function reject(int $id, string $note = ''): void {
        //TODO: Check if booking id exists

        // Update status in db and add reject note
        WDDP_BookingPersistence::updateBookingStatus($id, WDDP_StatusHelper::REJECTED);
        WDDP_BookingPersistence::insertRejectionNote($id, $note);

        //TODO: Move to woocommerce manager
        $booking = new WDDP_Booking($id);
        $order = wc_get_order($booking->getOrderId());
        if ($order) {
            remove_action('woocommerce_order_status_cancelled_notification', 'woocommerce_order_cancelled_notification');
            $order->update_status('cancelled');
        }
        $order->add_order_note('Booking afvist i admin – ordre sat til cancelled.');

        // TODO: Send rejectionsmail to mail manager
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


    /**
     * Gets all bookings with the given statusses
     * @return array array of all bookings with the given statusses
     */
    public static function getBookingsWithStatuses(array $statuses): array {

        $result = WDDP_BookingPersistence::getAllBookingsByStatus($statuses);

        $bookings = [];
        foreach ($result as $row) {
            $bookings[] = new WDDP_Booking($row['id']);
        }

        return $bookings;
    }

    /**
     * Calculate price for a booking on the given period for
     * the given amount of dogs
     * @return float calculated price
     */
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