<?php

class WDDP_AdminCreateBookingPage extends WDDP_AdminPage{

    public static function handlePost(){
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('wddp_create_booking', 'wddp_nonce');

        $in = wp_unslash($_POST);

        // Simpel sanitize
        $data = [
            'first_name' => sanitize_text_field($in['first_name'] ?? ''),
            'last_name' => sanitize_text_field($in['last_name'] ?? ''),
            'email' => sanitize_email($in['email'] ?? ''),
            'phone' => sanitize_text_field($in['phone'] ?? ''),
            'address' => sanitize_text_field($in['address'] ?? ''),
            'postal_code' => sanitize_text_field($in['postal_code'] ?? ''),
            'city' => sanitize_text_field($in['city'] ?? ''),
            'dropoff_date' => WDDP_DateHelper::to_iso($in['dropoff_date'] ?? ''),
            'pickup_date' => WDDP_DateHelper::to_iso($in['pickup_date'] ?? ''),
            'dropoff_time' => sanitize_text_field($in['dropoff_time'] ?? ''),
            'pickup_time' => sanitize_text_field($in['pickup_time'] ?? ''),
            'dogs' => is_array($in['dogs'] ?? null) ? array_values($in['dogs']) : [],
            'notes' => wp_kses_post($in['notes'] ?? ''),
            'override_price' => !empty($in['override_price']),
            'price' => isset($in['price']) ? (float)$in['price'] : null,
        ];

        $opts = [
            'send_approved_mail' => !empty($in['send_approved_mail']),
        ];

        try {
            $booking_id = WDDP_BookingManager::create($data, $opts);
            wp_redirect(admin_url('admin.php?page=wddp_menu&wddp_notice=updated'));
        } catch (\Throwable $e) {
            // Du kan også sende fejlbeskeden i en transient hvis du vil vise den i UI
            error_log('Create booking error: ' . $e->getMessage());
            wp_redirect(admin_url('admin.php?page=wddp_menu&wddp_notice=error'));
        }
        exit;

    }

    public function renderPage(){
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $nonce = wp_create_nonce('wddp_create_booking');

        ?>
        <div class="wrap">
            <h1>Opret booking</h1>
            <form method="post" action="<?=  esc_url(admin_url('admin-post.php')) ?>">
                <input type="hidden" name="wddp_nonce" value="<?=esc_attr($nonce)?>">
                <input type="hidden" name="action" value="wddp_booking_create">
                <div style="display: flex; gap: 40px;">
                    <!-- Kolonne 1 -->
                    <div style="flex: 1;">
                        <?php $this->sectionFormCustomer(); ?>
                        <?php $this->sectionFormDetails(); ?>
                    </div>

                    <!-- Kolonne 2 -->
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

    public function sectionFormDogs(){
        $max_dogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, WDDP_Options::default_max_no_of_dogs());

        self::h2("Hunde oplysninger");
        echo '<p>Du kan tilføje op til ' . esc_html($max_dogs) . ' hund(e) baseret på indstillingerne.</p>';
        for ($i = 0; $i < $max_dogs; $i++) {
            echo '<tr><th><h4>Hund ' . ($i + 1) . '</h4></th><td></td></tr>';
            self::input("Navn", "dogs[$i][name]");
            self::input("Alder", "dogs[$i][age]");
            self::input("Race", "dogs[$i][breed]");
            self::input("Vægt", "dogs[$i][weight]");
            self::textarea("Noter", "dogs[$i][notes]");
        }

        self::startTable();
        self::endTable();
    }

    public function sectionFormCustomer(){
        self::h2("Kunde oplysninger");
        self::startTable();
        self::input('Fornavn', 'first_name');
        self::input('Efternavn', 'last_name');
        self::input('E-mail', 'email', 'email');
        self::input('Telefon', 'phone', 'text');
        self::input('Adresse', 'address');
        self::input('Postnr', 'postal_code', 'text');
        self::input('By', 'city');
        self::endTable();
    }

    public function sectionFormTime(){
        $slots = WDDP_Options::get(WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots());

        self::h2("Tidspunkt");
        self::startTable();
        self::input('Aflevering (dato)', 'dropoff_date', 'date');
        self::select('Afleveringstid', 'dropoff_time', $slots);
        self::input('Afhentning (dato)', 'pickup_date', 'date');
        self::select('Afhentningstid', 'pickup_time', $slots);
        self::endTable();
    }


    public function sectionFormDetails(){
        self::h2("Pris, mail og noter");
        self::startTable();
        echo '<tr><th>Pris</th><td>';
        echo '<label><input type="checkbox" name="override_price" value="1"> Angiv pris manuelt</label><br>';
        echo '<input type="number" step="0.01" name="price" value="" class="small-text">';
        echo '</td></tr>';

        self::textarea('Noter', 'notes');

        echo '<tr class="if-approved"><th>Mail</th><td>';
        echo '<label><input type="checkbox" name="send_approved_mail" value="1"> Send godkendelsesmail nu (hvis status = godkendt)</label>';
        echo '</td></tr>';

        self::endTable();
    }



    /** Små helpers til inputs */
    private static function h2($title){
        printf('<h2>%s</h2>', $title);
    }

    private static function startTable(){
        printf('<table class="form-table">');
    }

    private static function endTable(){
        printf('</table>');
    }
    private static function input($label, $name, $type = 'text')
    {
        printf(
            '<tr><th><label>%s</label></th><td><input type="%s" name="%s" class="regular-text"></td></tr>',
            esc_html($label),
            esc_attr($type),
            esc_attr($name)
        );
    }

    private static function textarea($label, $name)
    {
        printf(
            '<tr><th><label>%s</label></th><td><textarea name="%s" rows="4" class="large-text"></textarea></td></tr>',
            esc_html($label),
            esc_attr($name)
        );
    }

    private static function select($label, $name, $options)
    {
        echo '<tr><th><label>' . esc_html($label) . '</label></th><td>';
        echo '<select name="' . esc_attr($name) . '">';
        echo '<option value="">—</option>';
        foreach ($options as $opt) {
            printf('<option value="%s">%s</option>', esc_attr($opt), esc_html($opt));
        }
        echo '</select></td></tr>';
    }

    public function getTitle(){
        return "Opret bookning";
    }

    public function getSlug(){
        return "wddp_menu-create-booking";
    }

    public function getParentSlug(){
        return"wddp_menu";
    }

    public function getIcon(){
    }

    public function getMenuOrder(){
    }
}