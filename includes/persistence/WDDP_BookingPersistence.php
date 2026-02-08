<?php


class WDDP_BookingPersistence
{
    /**
     * Gets the row of the booking with the given id
     * @return mixed if exits: array with the raw booking data - otherwise null
     */
    public static function getBookingRow($booking_id){
        global $wpdb;

        $table = self::getBookingTableName();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d LIMIT 1",
                $booking_id
            ),
            ARRAY_A
        );

        return $row;
    }

    /**
     * Saves the booking in the db.
     * @return int id of the newly created booking
     */
    public static function createBooking($data, $initstatus){
        global $wpdb;

        $table = self::getBookingTableName();

        $dog_names = WDDP_DogHelper::extractDogNames($data['dogs']);

        //TODO: Validate input
        $wpdb->insert($table, [
            'order_id'      => null, // oprettes manuelt – ingen Woo ordre
            'status'        => $initstatus,
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
            'dog_data'      => maybe_serialize($data['dogs']),
            'dog_names'     => maybe_serialize($dog_names),
            'price' => floatval(
                !empty($data['override_price'])
                    ? ($data['price'] ?? 0)
                    : WDDP_BookingManager::calculatePrice(
                    $data['dropoff_date'],
                    $data['pickup_date'],
                    count($data['dogs'] ?? [])
                )
            ),
            'notes'         => $data['notes'],
        ]);

        $booking_id = $wpdb->insert_id;

        return $booking_id;
    }

    /**
     * Updates the booking with the data. Data must be filled, or it will overwrite with null or empty values.
     * No validation is called before updating.
     * @return bool true if update was successful otherwise false
     */
    public static function updateBooking(int $booking_id, array $data){
        //TODO: Check if booking exists
        //TODO: validate data
        global $wpdb;
        $table = self::getBookingTableName();

        return (bool) $wpdb->update(
            $table,
            [
                'dropoff_date'  => $data['dropoff_date'],
                'pickup_date'   => $data['pickup_date'],
                'dropoff_time'  => $data['dropoff_time'],
                'pickup_time'   => $data['pickup_time'],
                'dog_names'     => maybe_serialize($data['dog_names']),
                'dog_data'      => maybe_serialize($data['dogs']),
                'price'         => $data['price'],
                'notes'         => $data['notes'],
            ],
            ['id' => $booking_id],
            ['%s','%s','%s','%s','%s','%s','%f','%s'],
            ['%d']
        );
    }

    /**
     * Deletes the booking with the given id in the db
     * @return bool true if deleted, otherwise false
     */
    public static function deleteBooking($booking_id){
        //TODO: Validate that it exists
        global $wpdb;
        $table = self::getBookingTableName();
        return (bool) $wpdb->delete( $table, [ 'id' => $booking_id ], [ '%d' ] );
    }

    /**
     * Updates the status of the booking with the given id to the given status.
     * Checks if the change is allowed
     */
    public static function updateBookingStatus($booking_id, $status){
        //TODO: Check if booking exists - error if not
        //TODO: Validate status given

        // get bookings current status
        $booking = new WDDP_Booking($booking_id);
        $current = $booking->getStatus();

        // make sure new status is allowed
        WDDP_StatusHelper::assertTransitionAllowed($current, $status);

        // update status
        global $wpdb;
        $table = self::getBookingTableName();
        $wpdb->update($table, ['status' => $status], ['id' => $booking_id], ['%s'], ['%d']);
    }

    /**
     * Addeds the given note as a rejection note to the booking for the given id
     */
    public static function insertRejectionNote($booking_id, $note){
        //TODO: Check if booking exists - error if not

        // update status
        global $wpdb;
        $table = self::getBookingTableName();
        $wpdb->update($table, ['rejection_reason' => $note], ['id' => $booking_id], ['%s'], ['%d']);

    }

    /**
     * Gets all the bookings in the db with the given statusses
     * @return array array of all the found bookings in raw rows
     */
    public static function getAllBookingsByStatus($statuses = []){
        global $wpdb;

        // Prepare placeholders
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        // Build select sql
        $table = self::getBookingTableName();
        $sql = "
        SELECT id, first_name, last_name, dog_data, dropoff_date, pickup_date 
        FROM {$table} 
        WHERE status IN ($placeholders)";

        // Fetch the bookings
        return $wpdb->get_results($wpdb->prepare($sql, ...$statuses), ARRAY_A);
    }

    /**
     * Update the order id with the given for the booknig with the given id
     */
    public static function addOrderIdToBooking($booking_id, $order_id){
        // update order id for booking
        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
        $wpdb->update($table, ['order_id' => $order_id], ['id' => $booking_id], ['%d'], ['%d']);
    }

    /**
     * Updates the booking with the given ids changelog. Adds the given to the existing.
     */
    public static function updateChangeLogForBooking($booking_id, $changes){
        //TODO: CHECK IF BOOKING EXISTS
        //TODO: Validate changes input


        // Get current changelog for the booking
        $booking = new WDDP_Booking($booking_id);
        $existing_log = maybe_unserialize($booking->getChangeLog());
        if (!is_array($existing_log)) {
            $existing_log = [];
        }

        // add new changes to the log
        $existing_log[] = [
            'changed_at' => current_time('mysql'),
            'user'       => wp_get_current_user()->user_email,
            'changes'    => $changes,
        ];

        // save it on the booking
        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
        $wpdb->update($table, [
            'change_log' => maybe_serialize($existing_log)
        ], ['id' => $booking->getId()]);
    }

    public static function getBookingTableName(){
        global $wpdb;
        return $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
    }



}