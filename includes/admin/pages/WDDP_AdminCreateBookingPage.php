<?php

class WDDP_AdminCreateBookingPage extends WDDP_AdminPage
{
    //TODO: REFACT AND DOC

    private static $validation_errors = [];
    private array $form_data = [];

    public static function handlePost()
    {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('wddp_create_booking', 'wddp_nonce');

        $in = wp_unslash($_POST);

        $data = [
                'first_name'     => sanitize_text_field($in['first_name'] ?? ''),
                'last_name'      => sanitize_text_field($in['last_name'] ?? ''),
                'email'          => sanitize_email($in['email'] ?? ''),
                'phone'          => sanitize_text_field($in['phone'] ?? ''),
                'address'        => sanitize_text_field($in['address'] ?? ''),
                'postal_code'    => sanitize_text_field($in['postal_code'] ?? ''),
                'city'           => sanitize_text_field($in['city'] ?? ''),
                'dropoff_date'   => WDDP_DateHelper::to_iso($in['dropoff_date'] ?? ''),
                'pickup_date'    => WDDP_DateHelper::to_iso($in['pickup_date'] ?? ''),
                'dropoff_time'   => sanitize_text_field($in['dropoff_time'] ?? ''),
                'pickup_time'    => sanitize_text_field($in['pickup_time'] ?? ''),
                'dogs' => is_array($in['dogs'] ?? null)
                        ? array_values(array_filter($in['dogs'], fn($dog) => is_array($dog) && !empty($dog['name'])))
                        : [],
                'notes'          => wp_kses_post($in['notes'] ?? ''),
                'override_price' => !empty($in['override_price']),
                'price'          => isset($in['price']) ? (float)$in['price'] : null,
        ];
        $data['dog_names'] = WDDP_DogHelper::extractDogNames($data['dogs']);

        $opts = [
                'send_approved_mail' => !empty($in['send_approved_mail']),
                'create_woo_order'   => true // TODO: Gør Woo ordre valgfrit igen senere
        ];

        $validation_errors = WDDP_BookingValidator::validateWithCustomer($data);
        if (!empty($validation_errors)) {
            set_transient('wddp_create_validation_errors', $validation_errors, 60);
            set_transient('wddp_create_form_data', $data, 60);
            wp_redirect(admin_url('admin.php?page=wddp_menu-create-booking'));
            exit;
        }

        try {
            // TODO: Flyt til WDDP_BookingWorkflow::createFromAdmin() senere

            // 1. Opret booking
            $booking_id = WDDP_BookingManager::create($data, $opts, WDDP_StatusHelper::APPROVED);

            // 2. Hvis ønsket, opret Woo ordre
            if (!empty($opts['create_woo_order'])) {
                $order_id = WDDP_WooCommerceManager::createOrderFromBookingData($data);

                if ($order_id) {
                   WDDP_BookingPersistence::addOrderIdToBooking($booking_id, $order_id);
                } else {
                    error_log("Kunne ikke oprette WooCommerce ordre for booking #{$booking_id}");
                }
            }

            wp_redirect(admin_url('admin.php?page=wddp_menu&wddp_notice=updated'));
            exit;
        } catch (\Throwable $e) {
            error_log('Create booking error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=wddp_menu&wddp_notice=error'));
            exit;
        }
    }

    public function renderPage()
    {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $nonce = wp_create_nonce('wddp_create_booking');

        if (empty(self::$validation_errors)) {
            self::$validation_errors = get_transient('wddp_create_validation_errors') ?: [];
            delete_transient('wddp_create_validation_errors');
        }

        if (!empty(self::$validation_errors)) {
            echo '<div class="notice notice-error"><ul>';
            foreach (self::$validation_errors as $err) {
                echo '<li>' . esc_html($err) . '</li>';
            }
            echo '</ul></div>';
        }

        $this->form_data = get_transient('wddp_create_form_data') ?: [];
        delete_transient('wddp_create_form_data');

        ?>
        <div class="wrap">
            <h1>Opret booking</h1>
            <form method="post" id="wddp-edit-booking-form" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                <input type="hidden" name="wddp_nonce" value="<?= esc_attr($nonce) ?>">
                <input type="hidden" name="action" value="wddp_booking_create">
                <div style="display: flex; gap: 40px;">
                    <div style="flex: 1;">
                        <?php $this->sectionFormCustomer(); ?>
                        <?php $this->sectionFormDetails(); ?>
                    </div>
                    <div style="flex: 1;">
                        <?php $this->sectionFormTime(); ?>
                        <?php $this->sectionFormDogs(); ?>
                    </div>
                </div>
                <?php submit_button('Gem booking'); ?>
            </form>
        </div>
        <?php
    }

    public function sectionFormCustomer()
    {
        self::h2("Kundeoplysninger");
        self::startTable();
        self::input('Fornavn', 'first_name', 'text', null, $this->form_data['first_name'] ?? '');
        self::input('Efternavn', 'last_name', 'text', null, $this->form_data['last_name'] ?? '');
        self::input('E-mail', 'email', 'email', null, $this->form_data['email'] ?? '');
        self::input('Telefon', 'phone', 'text', null, $this->form_data['phone'] ?? '');
        self::input('Adresse', 'address', 'text', null, $this->form_data['address'] ?? '');
        self::input('Postnr', 'postal_code', 'text', null, $this->form_data['postal_code'] ?? '');
        self::input('By', 'city', 'text', null, $this->form_data['city'] ?? '');
        self::endTable();
    }

    public function sectionFormTime()
    {
        $slots = WDDP_Options::get(WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots());

        self::h2("Tidspunkt");
        self::startTable();
        self::input('Aflevering (dato)', 'dropoff_date', 'date', 'from_date', $this->form_data['dropoff_date'] ?? '');
        self::select('Afleveringstid', 'dropoff_time', $slots, 'arrival_time', $this->form_data['dropoff_time'] ?? '');
        self::input('Afhentning (dato)', 'pickup_date', 'date', 'to_date', $this->form_data['pickup_date'] ?? '');
        self::select('Afhentningstid', 'pickup_time', $slots, 'departure_time', $this->form_data['pickup_time'] ?? '');
        self::endTable();
    }

    public function sectionFormDogs()
    {
        $max_dogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, 2);

        self::h2("Hundeoplysninger");
        echo '<p>Du kan tilføje op til ' . esc_html($max_dogs) . ' hund(e).</p>';
        echo '<div id="wddp-dog-fields">';
        $dogs = $this->form_data['dogs'] ?? [[]];
        foreach ($dogs as $i => $dog) {
            echo $this->renderDogBlock($i, $dog);
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="add-dog" data-max="' . esc_attr($max_dogs) . '">Tilføj hund</button></p>';
    }

    private function renderDogBlock($index, $dog = [])
    {
        ob_start();
        ?>
        <div class="dog-block" data-index="<?= $index ?>" style="border:1px solid #ccc; padding:1em; margin-bottom:1em;">
            <strong>Hund <?= $index + 1 ?></strong>
            <p><label>Navn<br><input type="text" name="dogs[<?= $index ?>][name]" value="<?= esc_attr($dog['name'] ?? '') ?>" required></label></p>
            <p><label>Race<br><input type="text" name="dogs[<?= $index ?>][breed]" value="<?= esc_attr($dog['breed'] ?? '') ?>" required></label></p>
            <p><label>Alder<br><input type="text" name="dogs[<?= $index ?>][age]" value="<?= esc_attr($dog['age'] ?? '') ?>" required></label></p>
            <p><label>Vægt<br><input type="number" step="0.1" name="dogs[<?= $index ?>][weight]" value="<?= esc_attr($dog['weight'] ?? '') ?>" required></label></p>
            <p><label>Noter<br><textarea name="dogs[<?= $index ?>][notes]"><?= esc_textarea($dog['notes'] ?? '') ?></textarea></label></p>
            <p><button type="button" class="button remove-dog">Fjern hund</button></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function sectionFormDetails()
    {
        self::h2("Pris, mail og noter");
        self::startTable();
        echo '<tr><th>Pris</th><td>';
        echo '<div>Ny pris:
            <span id="price-loading" style="display:none;">
                <span class="spinner" style="vertical-align:middle; margin-right:5px;"></span>
                Beregner...
            </span>
            <strong id="calculated-price">—</strong>
        </div>';
        echo '<p><button type="button" class="button" id="recalculate-price">Udregn nu</button></p>';
        echo '<p><label><input type="checkbox" id="manual_price_enable" name="override_price" value="1"> Angiv pris manuelt</label></p>';
        echo '<p><input type="number" step="0.01" id="manual_price" name="price" value="' . esc_attr($this->form_data['price'] ?? '') . '" class="small-text" disabled> kr.</p>';
        echo '</td></tr>';

        self::textarea('Noter', 'notes', $this->form_data['notes'] ?? '');

        echo '<tr><th>Mail</th><td>';
        echo '<label><input type="checkbox" name="send_approved_mail" value="1"> Send godkendelsesmail nu</label>';
        echo '</td></tr>';

        // TODO: Gør Woo ordre valgfrit igen senere
        echo '<input type="hidden" name="create_woo_order" value="1">';

        self::endTable();
    }

    // ----- UI Helper -----

    private static function h2($title)
    {
        echo '<h2>' . esc_html($title) . '</h2>';
    }

    private static function startTable()
    {
        echo '<table class="form-table">';
    }

    private static function endTable()
    {
        echo '</table>';
    }

    private static function input($label, $name, $type = 'text', $id = null, $value = '')
    {
        printf(
                '<tr><th><label for="%s">%s</label></th><td><input type="%s" name="%s" id="%s" value="%s" class="regular-text"></td></tr>',
                esc_attr($id ?? $name),
                esc_html($label),
                esc_attr($type),
                esc_attr($name),
                esc_attr($id ?? $name),
                esc_attr($value)
        );
    }

    private static function textarea($label, $name, $value = '')
    {
        printf(
                '<tr><th><label>%s</label></th><td><textarea name="%s" rows="4" class="large-text">%s</textarea></td></tr>',
                esc_html($label),
                esc_attr($name),
                esc_textarea($value)
        );
    }

    private static function select($label, $name, $options, $id = null, $selected = '')
    {
        echo '<tr><th><label for="' . esc_attr($id ?? $name) . '">' . esc_html($label) . '</label></th><td>';
        printf('<select name="%s" id="%s">', esc_attr($name), esc_attr($id ?? $name));
        echo '<option value="">—</option>';
        foreach ($options as $opt) {
            $isSelected = selected($selected, $opt, false);
            printf('<option value="%s" %s>%s</option>', esc_attr($opt), $isSelected, esc_html($opt));
        }
        echo '</select></td></tr>';
    }

    public function getTitle() { return "Opret bookning"; }
    public function getSlug() { return "wddp_menu-create-booking"; }
    public function getParentSlug() { return "wddp_menu"; }
    public function getIcon() {}
    public function getMenuOrder() {}
}
