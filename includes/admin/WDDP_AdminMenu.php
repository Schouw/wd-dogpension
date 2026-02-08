<?php

class WDDP_AdminMenu
{
    //TODO: REFACT AND DOC



    public static function init() {
        add_action( 'admin_menu', function (){
            new WDDP_AdminBookingPage();
            new WDDP_AdminEditBookingPage();
            new WDDP_AdminCreateBookingPage();
            new WDDP_AdminSettingsPage();
            new WDDP_AdminMailTextPage();
            new WDDP_AdminCalendarPage();
        } );

        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
        add_action('admin_post_wddp_booking_delete',  [WDDP_AdminBookingPage::class, 'handleBookingDelete']);
        add_action('admin_post_wddp_booking_update_status',  [WDDP_AdminBookingPage::class, 'handleBookingUpdateStatus']);
        add_action('admin_post_wddp_update_booking',  [WDDP_AdminEditBookingPage::class, 'handleBookingUpdate']);
        add_action('admin_post_wddp_booking_create', [WDDP_AdminCreateBookingPage::class, 'handlePost']);

    }


    public static function enqueue_assets( $hook_suffix ) {
        if ( strpos($hook_suffix, 'wddp_menu') === false ) {
            return; // kun på egne sider
        }

        if (str_ends_with($hook_suffix,'wddp_menu-create-booking')) {
            wp_enqueue_script(
                'wddp-admin-booking-create',
                WT_DOG_PENSION_URL . 'assets/js/admin-booking-create.js',
                [],
                WT_DOG_PENSION_VERSION,
                true
            );
            wp_localize_script('wddp-admin-booking-create', 'wddpPriceCalc', [
                'api_url' => rest_url('/wddp_api/calculatePrice'),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]);
        }

        if (str_ends_with($hook_suffix,'wddp_menu-edit-booking')) {
            wp_enqueue_script('wddp-admin-booking-edit', WT_DOG_PENSION_URL . 'assets/js/admin-booking-edit.js', [], WT_DOG_PENSION_VERSION, true);
            wp_localize_script('wddp-admin-booking-edit', 'wddpPriceCalc', [
                'api_url' => rest_url('/wddp_api/calculatePrice'),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]);
        }



        if (str_ends_with($hook_suffix,'wddp_menu')) {
            wp_enqueue_script('wddp-admin', WT_DOG_PENSION_URL . 'assets/js/admin-bookings.js', [], WT_DOG_PENSION_VERSION, true);
        }
        if (str_ends_with($hook_suffix,'wddp_menu-settings')) {
            wp_enqueue_script('wddp-admin-settings', WT_DOG_PENSION_URL . 'assets/js/admin-settings.js', [], WT_DOG_PENSION_VERSION, true);
        }

        if (str_ends_with($hook_suffix,'wddp_menu-calendar')) {
            wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css');
            wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js', [], null, true);

            wp_enqueue_script('wddp-calendar', WT_DOG_PENSION_URL . 'assets/js/admin-calendar.js', ['fullcalendar-js'], WT_DOG_PENSION_VERSION, true);

            // Her henter vi bookinger og lokaliserer dem til JS
            $bookings = WDDP_BookingManager::getBookingsWithStatuses([WDDP_StatusHelper::APPROVED, WDDP_StatusHelper::PENDING_REVIEW, WDDP_StatusHelper::REJECTED]); // denne laver vi næste
            $events = array_map(function($booking) {
                $dogs = $booking->getDogs();

                $summary = array_map(function($dog) {
                    return $dog->getSummary();
                }, $dogs);

                $title = $booking->getFirstName() . ' - ' . implode(', ', $summary);

                $color = match ($booking->getStatus()) {
                    'approved' => '#28a745',
                    'pending_review' => '#ffc107',
                    'rejected' => '#dc3545',
                    default => '#6c757d'
                };


                return [
                    'title' => esc_js($title),
                    'start' => $booking->getBookingDateFrom(),
                    'end'   => (new DateTime($booking->getBookingDateTo()))->modify('+1 day')->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'extendedProps' => [
                        'booking_id' => $booking->getId(),
                        'nonce' => wp_create_nonce('wddp_booking_action'),
                        'customer' => $booking->getCustomerName(),
                        'email' => $booking->getEmail(),
                        'phone' => $booking->getPhone(),
                        'drop_off_time' => $booking->getBookingDeliveryTime(),
                        'pick_up_time' => $booking->getBookingPickUpTime(),
                        'notes' => $booking->getNotes(),
                        'dogs' => array_map(fn($dog) => $dog->getFullDescription(), $booking->getDogs())
                    ]

                ];
            }, $bookings);



            wp_localize_script('wddp-calendar', 'wddpCalendarData', [
                'events' => $events,
            ]);
        }


        wp_enqueue_style('wddp-admin', WT_DOG_PENSION_URL . 'assets/css/admin.css', [], WT_DOG_PENSION_VERSION);

    }



}