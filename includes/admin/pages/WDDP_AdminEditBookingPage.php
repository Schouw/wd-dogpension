<?php

class WDDP_AdminEditBookingPage extends WDDP_AdminPage {

    public function __construct() {
        parent::__construct();
        add_action('admin_post_wddp_update_booking', [$this, 'wddp_handle_admin_booking_update']);
    }

    public static function calculateBookingChanges(WDDP_Booking $booking, array $new_data): array
    {
        $old_data = [
            'from_date'      => $booking->getBookingDateFrom(),
            'to_date'        => $booking->getBookingDateTo(),
            'arrival_time'   => $booking->getBookingDeliveryTime(),
            'departure_time' => $booking->getBookingPickUpTime(),
            'dog_data'       => maybe_unserialize($booking->getDogData()),
            'price'          => (float) $booking->getPrice(),
            'notes'          => (string) $booking->getNotes(),
        ];

        $changes = [];

        foreach ($new_data as $key => $new_val) {
            $old_val = $old_data[$key] ?? null;

            // 🐶 Specialhåndtering af hunde
            if ($key === 'dog_data') {
                $old_norm = self::normalizeDogs($old_val);
                $new_norm = self::normalizeDogs($new_val);

                if ($old_norm != $new_norm) {
                    $changes[$key] = [
                        'from' => $old_norm,
                        'to'   => $new_norm,
                    ];
                }
                continue;
            }

            // 📦 Arrays (andre end hunde)
            if (is_array($new_val) && is_array($old_val)) {
                if ($new_val != $old_val) {
                    $changes[$key] = [
                        'from' => $old_val,
                        'to'   => $new_val,
                    ];
                }
                continue;
            }

            // ✏️ Simple værdier
            if ((string) $new_val !== (string) $old_val) {
                $changes[$key] = [
                    'from' => $old_val,
                    'to'   => $new_val,
                ];
            }
        }

        return $changes;
    }

    private static function normalizeDogs($dogs): array
    {
        if (!is_array($dogs)) {
            return [];
        }

        $normalized = [];

        foreach ($dogs as $dog) {
            $normalized[] = [
                'name'   => trim((string)($dog['name'] ?? '')),
                'breed'  => trim((string)($dog['breed'] ?? '')),
                'age'    => trim((string)($dog['age'] ?? '')),
                'weight' => trim((string)($dog['weight'] ?? '')),
                'notes'  => trim((string)($dog['notes'] ?? '')),
            ];
        }

        return $normalized;
    }


    public static function recordBookingChanges(WDDP_Booking $booking, array $changes): void
    {
        if (empty($changes)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        $existing_log = maybe_unserialize($booking->getChangeLog());
        if (!is_array($existing_log)) {
            $existing_log = [];
        }

        $existing_log[] = [
            'changed_at' => current_time('mysql'),
            'user'       => wp_get_current_user()->user_email,
            'changes'    => $changes,
        ];

        $wpdb->update($table, [
            'change_log' => maybe_serialize($existing_log)
        ], ['id' => $booking->getId()]);
    }

    /**
     * @param $order
     * @param $from
     * @param $to
     * @param $arrival
     * @param $departure
     * @param array $dogs
     * @param array $dog_names
     * @param $notes
     * @param float $final_price
     * @param mixed $dog
     * @return void
     */
    public static function opdaterWooCommerceOrdre($order, $from, $to, $arrival, $departure, array $dogs, array $dog_names, $notes, float $final_price, mixed $dog): void
    {
// ---------- Opdater WooCommerce ordre ----------
        $settings = WDDP_Options::get(WDDP_Options::OPTION_WC);
        $booking_product_id = intval($settings['product_id'] ?? 0);

        foreach ($order->get_items() as $item_id => $item) {
            if ((int)$item->get_product_id() !== $booking_product_id) {
                continue; // spring over hvis det ikke er booking-produktet
            }

            $item->update_meta_data('_wddp_booking_data', [
                'from_date' => $from,
                'to_date' => $to,
                'arrival_time' => $arrival,
                'departure_time' => $departure,
                'dogs' => $dogs,
                'dog_names' => $dog_names,
                'notes' => $notes,
            ]);

            // 💰 Opdater pris for denne vare
            $item->set_total($final_price);
            $item->set_subtotal($final_price);

            //update display
            $display_meta = WDDP_WooCommerceManager::getBookingDisplayMeta([
                'from_date' => $from,
                'to_date' => $to,
                'arrival_time' => $arrival,
                'departure_time' => $departure,
                'dogs' => $dogs,
            ]);

            // Ryd gamle visninger først, så der ikke er duplikater
            $item->delete_meta_data('Datoer');
            $item->delete_meta_data('Tider');
            foreach ($dogs as $i => $dog) {
                $item->delete_meta_data('Hund ' . ($i + 1));
                if (!empty($dog['notes'])) {
                    $item->delete_meta_data('Noter – ' . $dog['name']);
                }
            }

            // Tilføj visningsdata igen
            foreach ($display_meta as $row) {
                $item->update_meta_data($row['name'], $row['value']);
            }


            $item->save();
        }

        // 💰 Opdater totals på hele ordren
        $order->calculate_totals();
        $order->save();

        $order->add_order_note('Booking ændret via admin.');
    }


    public function getSlug() {
        return 'wddp_menu-edit-booking';
    }

    public function getTitle() {
        return 'Redigér booking';
    }

    public function getParentSlug() {
        return 'wddp_menu';
    }

    public function renderPage() {
        $booking_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        if (! $booking_id) {
            echo '<div class="notice notice-error"><p>Ugyldigt booking-ID.</p></div>';
            return;
        }
        $max_dogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, WDDP_Options::default_max_no_of_dogs());


        $booking = new WDDP_Booking($booking_id);
        if (! $booking->getId()) {
            echo '<div class="notice notice-error"><p>Bookingen blev ikke fundet.</p></div>';
            return;
        }

        $errors = get_transient('wddp_edit_booking_errors_' . $booking_id);
        if ($errors) {
            delete_transient('wddp_edit_booking_errors_' . $booking_id);

            echo '<div class="notice notice-error"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }


        $status = $booking->getStatus();
        $order_id = $booking->getOrderId();

        // Udgangspunkt: kun pending/approved kan redigeres
        $can_edit = in_array($status, [
            WDDP_StatusHelper::PENDING_REVIEW,
            WDDP_StatusHelper::APPROVED,
        ], true);


        if ($can_edit && $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $orderstatus = $order->get_status(); // fx 'on-hold', 'processing', 'completed', 'pending'

                // Tillad kun redigering hvis ordren ikke er betalt/håndteret
                if (in_array($orderstatus, ['pending', 'on-hold'], true)) {
                    $can_edit = true;
                } else {
                    $can_edit = false;
                }
            }
        }


        if (! $can_edit) {
            echo '<div class="notice notice-warning"><p>Bookingens status er <strong>' . esc_html($status) . '</strong> og kan derfor ikke redigeres.</p></div>';
        }


        // Felter fra booking
        $from = esc_attr($booking->getBookingDateFrom());
        $to   = esc_attr($booking->getBookingDateTo());
        $arrival = esc_attr($booking->getBookingDeliveryTime());
        $departure = esc_attr($booking->getBookingPickUpTime());
        $dogs = maybe_unserialize($booking->getDogData());
        if (!is_array($dogs)) $dogs = [];
        $notes = esc_textarea($booking->getNotes());

        $manual_price = false;
        $current_price = floatval($booking->getPrice());

        // Tider fra options
        $slots = WDDP_Options::get(WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots());

        echo '<div class="wrap">';
        echo '<h1>Redigér booking #' . $booking_id . '</h1>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="wddp-edit-booking-form">';
        if (! $can_edit) echo '<fieldset disabled>';

        wp_nonce_field('wddp_edit_booking_' . $booking_id);

        echo '<input type="hidden" name="action" value="wddp_update_booking">';
        echo '<input type="hidden" name="booking_id" value="' . esc_attr($booking_id) . '">';

// Wrapper med flexbox
        echo '<div style="display: flex; gap: 40px; align-items: flex-start;">';

// ---------- Kolonne 1 ----------
        echo '<div style="flex: 2;">';

// Sektion: Dato og tidspunkter
        echo '<h2>Dato og tidspunkter</h2>';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th><label for="from_date">Fra dato</label></th><td><input type="date" id="from_date" name="from_date" value="' . $from . '" required></td></tr>';
        echo '<tr><th><label for="to_date">Til dato</label></th><td><input type="date" id="to_date" name="to_date" value="' . $to . '" required></td></tr>';
        echo '<tr><th><label for="arrival_time">Afleveringstid</label></th><td><select id="arrival_time" name="arrival_time">';
        foreach ($slots as $slot) {
            printf('<option value="%s"%s>%s</option>', esc_attr($slot), selected($arrival, $slot, false), esc_html($slot));
        }
        echo '</select></td></tr>';
        echo '<tr><th><label for="departure_time">Afhentningstid</label></th><td><select id="departure_time" name="departure_time">';
        foreach ($slots as $slot) {
            printf('<option value="%s"%s>%s</option>', esc_attr($slot), selected($departure, $slot, false), esc_html($slot));
        }
        echo '</select></td></tr>';
        echo '</tbody></table>';

        // Sektion: Kundeoplysninger
        echo '<h2>Kundeoplysninger</h2>';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th>Navn</th><td>' . esc_html($booking->getCustomerName()) . '</td></tr>';
        echo '<tr><th>Email</th><td><a href="mailto:' . esc_attr($booking->getEmail()) . '">' . esc_html($booking->getEmail()) . '</a></td></tr>';
        echo '<tr><th>Telefon</th><td>' . esc_html($booking->getPhone()) . '</td></tr>';
        echo '</tbody></table>';


        // 📝 Sektion: Noter (generelle noter til bookingen)
        echo '<h2>Noter</h2>';
        echo '<p><label for="notes">Evt. interne noter:</label><br>';
        echo '<textarea name="notes" id="notes" rows="5" style="width:100%;">' . esc_textarea($notes) . '</textarea></p>';

        // Sektion: Hunde
        echo '<h2>Hund(e)</h2>';
        echo '<div id="wddp-dog-fields">';
        foreach ($dogs as $i => $dog) {
            echo '<div class="dog-block" data-index="' . $i . '" style="border:1px solid #ccc; padding: 1em; margin-bottom: 1em;">';
            echo '<strong>Hund ' . ($i + 1) . '</strong>';
            printf('<p><label>Navn<br><input type="text" name="dogs[%d][name]" value="%s" required></label></p>', $i, esc_attr($dog['name'] ?? ''));
            printf('<p><label>Race<br><input type="text" name="dogs[%d][breed]" value="%s" required></label></p>', $i, esc_attr($dog['breed'] ?? ''));
            printf('<p><label>Alder<br><input type="text" name="dogs[%d][age]" value="%s" required></label></p>', $i, esc_attr($dog['age'] ?? ''));
            printf('<p><label>Vægt<br><input type="text" step="0.1" name="dogs[%d][weight]" value="%s" required></label></p>', $i, esc_attr($dog['weight'] ?? ''));
            printf('<p><label>Noter<br><textarea name="dogs[%d][notes]">%s</textarea></label></p>', $i, esc_textarea($dog['notes'] ?? ''));
            echo '<p><button type="button" class="button remove-dog">Fjern hund</button></p>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="add-dog" data-max="' . esc_attr($max_dogs) . '">Tilføj hund</button></p>';

        echo '</div>'; // end kolonne 1

// ---------- Kolonne 2 ----------
        echo '<div style="flex: 1; background: #f9f9f9; padding: 20px; border: 1px solid #ccc;">';
        echo '<h2>Pris</h2>';

        echo '<p>Oprindelig pris: <strong>' . number_format((float)$booking->getPrice(), 2, ',', '.') . ' kr.</strong></p>';

        echo '<p>Ny pris:
                <span id="price-loading" style="display:none;">
                    <span class="spinner" style="vertical-align:middle; margin-right:5px;"></span>
                    Beregner...
                </span>
                <strong id="calculated-price">—</strong>
                </p>
';

        echo '<p><label><input type="checkbox" id="manual_price_enable" name="manual_price_enable" value="1"> Angiv pris manuelt</label></p>';

        echo '<p><input type="number" step="0.01" id="manual_price" name="manual_price" value="' . esc_attr($current_price) . '" class="small-text" disabled> kr.</p>';

        echo '<hr>';
        echo '<p><input type="submit" class="button button-primary" value="Gem ændringer" onclick="return confirm(\'Er du sikker på at du vil gemme ændringerne?\');"></p>';

        echo '</div>'; // end kolonne 2

        echo '</div>'; // end flex wrapper

        if (! $can_edit) echo '</fieldset>';
        echo '</form>';
        echo '</div>'; // end wrap

    }

    public static function handleBookingUpdate() {
        $max_dogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, WDDP_Options::default_max_no_of_dogs());


        if (! current_user_can('manage_options')) {
            wp_die('Ikke tilladt.');
        }

        $booking_id = absint($_POST['booking_id'] ?? 0);
        check_admin_referer('wddp_edit_booking_' . $booking_id);

        $booking = new WDDP_Booking($booking_id);
        if (! $booking->getId()) {
            wp_redirect(add_query_arg('msg', 'invalid_booking', wp_get_referer()));
            exit;
        }

        $order = null;
        $status = $booking->getStatus();
        $order_id = $booking->getOrderId();
        $can_edit = in_array($status, [WDDP_StatusHelper::PENDING_REVIEW, WDDP_StatusHelper::APPROVED], true);

        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order && ! in_array($order->get_status(), ['pending', 'on-hold'], true)) {
                $can_edit = false;
            }
        }

        if (! $can_edit) {
            wp_redirect(add_query_arg('msg', 'not_editable', wp_get_referer()));
            exit;
        }

        // ---------- Hent og valider input ----------
        $from = sanitize_text_field($_POST['from_date'] ?? '');
        $to = sanitize_text_field($_POST['to_date'] ?? '');
        $arrival = sanitize_text_field($_POST['arrival_time'] ?? '');
        $departure = sanitize_text_field($_POST['departure_time'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $dogs_raw = $_POST['dogs'] ?? [];
        $dogs = [];

        foreach ($dogs_raw as $dog) {
            $dogs[] = [
                'name'   => sanitize_text_field($dog['name'] ?? ''),
                'breed'  => sanitize_text_field($dog['breed'] ?? ''),
                'age'    => sanitize_text_field($dog['age'] ?? 0),
                'weight' => sanitize_text_field($dog['weight'] ?? 0),
                'notes'  => sanitize_textarea_field($dog['notes'] ?? ''),
            ];
        }

        $dog_names = array_column($dogs, 'name');
        $dog_count = count($dogs);

        $validation_data = [
            // Kundeoplysninger (fra eksisterende booking)
            'first_name'  => $booking->getFirstName(),
            'last_name'   => $booking->getLastName(),
            'email'       => $booking->getEmail(),
            'phone'       => $booking->getPhone(),
            'address'     => $booking->getAddress(),
            'postal_code' => $booking->getPostalCode(),
            'city'        => $booking->getCity(),

            // Booking (fra formular)
            'dropoff_date' => $from,
            'pickup_date'  => $to,
            'dropoff_time' => $arrival,
            'pickup_time'  => $departure,

            // Hunde
            'dogs' => $dogs,

            // Noter (valgfri)
            'notes' => $notes,
        ];

        $errors = WDDP_BookingValidator::validateWithCustomer($validation_data);

        if (!empty($errors)) {
            // Gem fejl midlertidigt (samme mønster som create)
            set_transient('wddp_edit_booking_errors_' . $booking_id, $errors, 60);

            wp_redirect(add_query_arg('msg', 'validation_error', wp_get_referer()));
            exit;
        }


        // ---------- Beregn pris ----------
        $use_manual = !empty($_POST['manual_price_enable']);
        $manual_price = floatval($_POST['manual_price'] ?? 0);
        $calculated_price = WDDP_BookingManager::calculatePrice($from, $to, $dog_count);
        $final_price = $use_manual ? $manual_price : $calculated_price;

        // ---------- Opdater booking ----------
        if ($dog_count < 1 || $dog_count > $max_dogs) {
            wp_redirect(add_query_arg('msg', 'invalid_dog_count', wp_get_referer()));
            exit;
        }


        global $wpdb;
        $table = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;

        $wpdb->update($table, [
            'dropoff_date'  => $from,
            'pickup_date'   => $to,
            'dropoff_time'  => $arrival,
            'pickup_time'   => $departure,
            'dog_names'     => maybe_serialize($dog_names),
            'dog_data'      => maybe_serialize($dogs),
            'price'         => $final_price,
            'notes'         => $notes,
        ], ['id' => $booking_id], ['%s','%s','%s','%s','%s','%s','%f','%s'], ['%d']);

        $new_data = [
            'from_date'      => $from,
            'to_date'        => $to,
            'arrival_time'   => $arrival,
            'departure_time' => $departure,
            'dog_data'       => $dogs,
            'price'          => $final_price,
            'notes'          => $notes,
        ];

        $changes = self::calculateBookingChanges($booking, $new_data);

        self::recordBookingChanges($booking, $changes);

        if($order)
            self::opdaterWooCommerceOrdre($order, $from, $to, $arrival, $departure, $dogs, $dog_names, $notes, $final_price, $dog);

        // ---------- Send ændringsmail ----------
        $label_map = [
            'from_date' => 'Fra dato',
            'to_date' => 'Til dato',
            'arrival_time' => 'Afleveringstid',
            'departure_time' => 'Afhentningstid',
            'price' => 'Pris',
            'notes' => 'Noter',
            'dog_data' => 'Hund(e)',
        ];


        $changeLogHtml = '';
        if (!empty($changes)) {
            $changeLogHtml .= "<ul>";
            foreach ($changes as $key => $change) {
                $label = $label_map[$key] ?? ucfirst(str_replace('_', ' ', $key));

                if (in_array($key, ['from_date', 'to_date'])) {
                    $from = WDDP_DateHelper::to_display($change['from']);
                    $to   = WDDP_DateHelper::to_display($change['to']);
                } elseif ($key === 'dog_data') {
                    $from = self::formatDogChangeMail($change['from'], $change['to']);
                    $to = '';
                } else {
                    $from = $change['from'];
                    $to   = $change['to'];
                }

                $changeLogHtml .= "<li><strong>{$label}:</strong><br>{$from}</li>";
            }
            $changeLogHtml .= "</ul>";
        }



        $placeholders = WDDP_WooCommerceManager::buildPlaceholdersFromOrder($order, [
            'from_date'      => $from,
            'to_date'        => $to,
            'arrival_time'   => $arrival,
            'departure_time' => $departure,
            'dog_names'      => $dog_names,
            'notes'          => $notes,
            'changes'        => $changeLogHtml,
        ]);

        $mail = WDDP_MailManager::buildMail(WDDP_Mail::MAIL_CHANGED, $placeholders);
        $mail->send($booking->getEmail());

        // ---------- Redirect med succesbesked ----------
        wp_redirect(admin_url('admin.php?page=wddp_menu&wddp_notice=updated'));
        exit;

    }

    public static function formatDogChangeMail(array $from, array $to): string {
        $out = '';

        foreach ($from as $i => $dogBefore) {
            $dogAfter = $to[$i] ?? [];

            $changes = [];
            foreach ($dogBefore as $key => $oldValue) {
                $newValue = $dogAfter[$key] ?? '';
                if ((string)$oldValue !== (string)$newValue) {
                    $label = ucfirst($key); // Eller oversæt fx 'name' → 'Navn'
                    $changes[] = "<li><strong>{$label}:</strong> {$oldValue} → {$newValue}</li>";
                }
            }

            if (!empty($changes)) {
                $out .= '<p><strong>Hund ' . ($i + 1) . '</strong><ul>' . implode('', $changes) . '</ul></p>';
            }
        }

        return $out ?: '—';
    }



    public function getIcon(){
    }

    public function getMenuOrder(){
    }
}
