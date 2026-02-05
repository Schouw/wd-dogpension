<?php

class WDDP_AdminBookingPage extends WDDP_AdminPage {

    public function __construct(){
        parent::__construct();
    }

    public static function handleBookingDelete() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Adgang nægtet', '', 403);
        }

        check_admin_referer('wddp_booking_action', 'wddp_nonce');

        $id = intval($_POST['booking_id'] ?? 0);
        if (!$id) {
            wp_redirect(add_query_arg(['wddp_notice' => 'error'], wp_get_referer()));
            exit;
        }

        $booking = new WDDP_Booking($id);
        $order_id = $booking->getOrderId();

        // Slet Woo order hvis findes
        WDDP_WooCommerceManager::deleteOrder($order_id);

        // Slet booking
        WDDP_BookingManager::delete($id);

        wp_redirect(add_query_arg(['wddp_notice' => 'deleted'], wp_get_referer()));
        exit;
    }

    public static function handleBookingUpdateStatus() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Adgang nægtet', '', 403);
        }

        check_admin_referer('wddp_booking_action', 'wddp_nonce');

        $id        = intval($_POST['booking_id'] ?? 0);
        $action    = sanitize_key($_POST['do'] ?? '');
        $notes     = sanitize_textarea_field($_POST['notes'] ?? '');

        if (!$id || !in_array($action, ['approve','reject'], true)) {
            wp_redirect(add_query_arg(['wddp_notice' => 'error'], wp_get_referer()));
            exit;
        }

        try {
            if ($action === 'approve') {
                WDDP_BookingManager::approve($id);
            } elseif ($action === 'reject') {
                WDDP_BookingManager::reject($id, $notes);
            }
            wp_redirect(add_query_arg(['wddp_notice' => 'updated'], wp_get_referer()));
        } catch (\Exception $e) {
            wp_redirect(add_query_arg(['wddp_notice' => 'invalid'], wp_get_referer()));
        }

        exit;
    }


    public function renderPage(){
        if ( ! class_exists('WDDP_AdminBookingsTable') ) {
            require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminBookingsTable.php';
        }

        $table = new WDDP_AdminBookingsTable();
        $table->prepare_items();

        // Nonce til row actions
        $nonce = wp_create_nonce( 'wddp_booking_action' );

        // Læs aktuel status (for preselect)
        $status_value = isset($_GET['wddp_status']) ? sanitize_key($_GET['wddp_status']) : '';
        $date_from = isset($_GET['wddp_date_from']) ? sanitize_text_field($_GET['wddp_date_from']) : '';
        $date_to   = isset($_GET['wddp_date_to']) ? sanitize_text_field($_GET['wddp_date_to']) : '';

        $reset_url = admin_url( 'admin.php?page=wddp_menu' );

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">Bookinger</h1>';

        if ( isset($_GET['wddp_notice']) ) {
            $type = sanitize_key($_GET['wddp_notice']);
            if ( $type === 'updated' ) {
                echo '<div class="notice notice-success is-dismissible"><p>Booking opdateret.</p></div>';
            } elseif ( $type === 'deleted' ) {
                echo '<div class="notice notice-success is-dismissible"><p>Booking slettet.</p></div>';
            } elseif ( $type === 'invalid' ) {
                echo '<div class="notice notice-warning is-dismissible"><p>Handling ikke tilladt for den nuværende status.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Der opstod en fejl.</p></div>';
            }
        }

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="wddp_menu" />';

        echo '<div class="tablenav top"><div class="alignleft actions">';

        // Status-filter
        echo '<select name="wddp_status">';
        echo '<option value="">' . esc_html__('Alle statusser', 'wddp-hundepension') . '</option>';
        foreach ( WDDP_StatusHelper::all() as $st ) {
            printf('<option value="%1$s"%2$s>%3$s</option>',
                esc_attr($st),
                selected($status_value, $st, false),
                esc_html(WDDP_StatusHelper::label($st))
            );
        }
        echo '</select>';

        echo '<input type="date" name="wddp_date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="date" name="wddp_date_to" value="' . esc_attr($date_to) . '" />';


        submit_button( __( 'Filtrér', 'wddp-hundepension' ), 'secondary', '', false );
        echo ' <a href="' . esc_url( $reset_url ) . '" class="button">' . esc_html__('Nulstil', 'wddp-hundepension') . '</a>';

        echo '</div>';

        // Søgning
        $table->search_box( 'Søg bookinger', 'wddp-booking' );

        echo '</div>'; // .tablenav.top

        $table->display();

        echo '</form>';

        // 🔻 Hidden form + endpoints til JS (vigtigt)
        $update_url = admin_url( 'admin-post.php?action=wddp_booking_update_status' );
        $delete_url = admin_url( 'admin-post.php?action=wddp_booking_delete' );

        echo '<form id="wddp-booking-action-form" method="post" action="' . esc_url( $update_url ) . '" style="display:none;">';
        echo '<input type="hidden" name="wddp_nonce" value="' . esc_attr($nonce) . '">';
        echo '<input type="hidden" name="booking_id" value="">';
        echo '<input type="hidden" name="do" value="">';
        echo '<input type="hidden" name="notes" value="">';
        echo '<input type="hidden" name="reason" value="">';
        echo '</form>';

        echo '<div id="wddp-action-endpoints"
            data-update="' . esc_url( $update_url ) . '"
            data-delete="' . esc_url( $delete_url ) . '"></div>';

        echo '</div>';


        echo '
<div id="wddp-change-modal" style="display:none; position: fixed; z-index: 9999; top: 10%; left: 50%; transform: translateX(-50%);
     background: #fff; border: 1px solid #ccc; padding: 20px; max-width: 600px; width: 90%; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
    <button id="wddp-change-modal-close" style="float:right; background:none; border:none; font-size:20px;">&times;</button>
    <div id="wddp-change-modal-content" style="max-height:400px; overflow-y:auto;"></div>
</div>
<div id="wddp-change-modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
     background: rgba(0,0,0,0.3); z-index: 9998;"></div>';


    }

    public function getTitle(){
        return "Hundepension";
    }

    public function getSlug(){
        return "wddp_menu";
    }

    public function getParentSlug(){
        return "";
    }

    public function getIcon(){
        return "dashicons-pets";
    }

    public function getMenuOrder(){
        return 26;
    }
}