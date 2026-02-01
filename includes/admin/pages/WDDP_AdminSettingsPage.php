    <?php

    class WDDP_AdminSettingsPage extends WDDP_AdminPage
    {

        public function renderPage()
        {
            $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'prices';

            $tabs = [
                'dogs'   => 'Hunde',
                'prices' => 'Prisindstillinger',
                'wc'     => 'WooCommerce',
                'closed' => 'Lukkede perioder',
                'slots'  => 'Aflevering/Afhentning',
            ];

            $group_by_tab = [
                'dogs' => 'wddp_settings_dogs',
                'prices' => 'wddp_settings_prices',
                'wc'     => 'wddp_settings_wc',
                'closed' => 'wddp_settings_closed',
                'slots'  => 'wddp_settings_slots',
            ];

            if ( ! isset( $tabs[ $tab ] ) ) {
                $tab = 'prices';
            }

            echo '<div class="wrap"><h1>Indstillinger</h1><h2 class="nav-tab-wrapper">';
            foreach ( $tabs as $slug => $label ) {
                $class = $slug === $tab ? ' nav-tab nav-tab-active' : ' nav-tab';
                $url   = admin_url( 'admin.php?page=wddp_menu-settings&tab=' . $slug );
                echo '<a class="'.esc_attr($class).'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
            }
            echo '</h2>';

            echo '<form method="post" action="options.php">';
            settings_fields( $group_by_tab[ $tab ] ); // 🔴 vigtigt: kun gruppen for aktiv fane

            if ( $tab === 'dogs' ) $this->sectionDogs();
            if ( $tab === 'prices' ) $this->sectionPrices();
            if ( $tab === 'wc' )     $this->sectionWordpress();
            if ( $tab === 'closed' ) $this->sectionClosed();
            if ( $tab === 'slots' )  $this->sectionSlots();

            submit_button();
            echo '</form></div>';
        }

        /**
         * FANE: Hunde
         */
        private function sectionDogs() {
            $max = (int) WDDP_Options::get(
                WDDP_Options::OPTION_MAX_NO_DOGS,
                WDDP_Options::default_max_no_of_dogs()
            );
            ?>
            <h3>Hunde</h3>
            <table class="form-table">
                <tr>
                    <th><label for="wddp_max_no_of_dogs">Max antal hunde i en booking</label></th>
                    <td>
                        <input type="number"
                               min="1"
                               max="20"
                               id="wddp_max_no_of_dogs"
                               name="<?php echo esc_attr(WDDP_Options::OPTION_MAX_NO_DOGS); ?>"
                               value="<?php echo esc_attr($max); ?>"
                               class="small-text">
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * FANE: Prisindstillinger
         */
        private function sectionPrices() {
            $opt = WDDP_Options::get( WDDP_Options::OPTION_PRICES, WDDP_Options::defaults_prices() );
            ?>
            <h3>Prisindstillinger</h3>
            <table class="form-table">
                <tr>
                    <th><label for="wddp_price_dog1">Pris pr. dag – Hund 1</label></th>
                    <td><input type="number" step="0.01" id="wddp_price_dog1" name="<?php echo esc_attr(WDDP_Options::OPTION_PRICES); ?>[dog1]" value="<?php echo esc_attr($opt['dog1']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="wddp_price_dog2">Pris pr. dag – Hund 2+</label></th>
                    <td><input type="number" step="0.01" id="wddp_price_dog2" name="<?php echo esc_attr(WDDP_Options::OPTION_PRICES); ?>[dog2]" value="<?php echo esc_attr($opt['dog2']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Særlige perioder</th>
                    <td>
                        <table class="widefat striped" id="wddp-special-periods">
                            <thead>
                            <tr>
                                <th>Fra (dato)</th>
                                <th>Til (dato)</th>
                                <th>Hund 1</th>
                                <th>Hund 2</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $rows = $opt['special'] ?? [];
                            if ( empty( $rows ) ) {
                                $rows = [ [ 'from' => '', 'to' => '', 'dog1' => '', 'dog2' => '' ] ];
                            }
                            foreach ( $rows as $i => $row ) {
                                $from_iso = WDDP_DateHelper::to_iso( $row['from'] ?? '' );
                                $to_iso   = WDDP_DateHelper::to_iso( $row['to'] ?? '' );

                                echo '<tr>';
                                printf(
                                    '<td><input type="date" name="%s[special][%d][from]" value="%s" class="regular-text"></td>',
                                    esc_attr(WDDP_Options::OPTION_PRICES), $i, esc_attr($from_iso)
                                );
                                printf(
                                    '<td><input type="date" name="%s[special][%d][to]" value="%s" class="regular-text"></td>',
                                    esc_attr(WDDP_Options::OPTION_PRICES), $i, esc_attr($to_iso)
                                );
                                printf(
                                    '<td><input type="number" step="0.01" name="%s[special][%d][dog1]" value="%s" class="small-text"></td>',
                                    esc_attr(WDDP_Options::OPTION_PRICES), $i, esc_attr($row['dog1'] ?? '')
                                );
                                printf(
                                    '<td><input type="number" step="0.01" name="%s[special][%d][dog2]" value="%s" class="small-text"></td>',
                                    esc_attr(WDDP_Options::OPTION_PRICES), $i, esc_attr($row['dog2'] ?? '')
                                );
                                echo '<td><a href="#" class="button wddp-row-del">–</a></td>';
                                echo '</tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                        <p><a href="#" class="button" id="wddp-special-add">+ Tilføj periode</a></p>
                        <p class="description">Datoer gemmes som ISO (yyyy-mm-dd) for korrekt beregning; visning kan være dd/mm - yyyy.</p>
                    </td>
                </tr>
            </table>
            <?php
        }

        private function sectionWordpress() {
            $opt = WDDP_Options::get( WDDP_Options::OPTION_WC, WDDP_Options::defaults_wc() );
            ?>
            <h3>WooCommerce</h3>
            <table class="form-table">
                <tr>
                    <th><label for="wddp_wc_product_id">Produkt til booking</label></th>
                    <td>
                        <input type="number" id="wddp_wc_product_id" name="<?php echo esc_attr(WDDP_Options::OPTION_WC); ?>[product_id]" value="<?php echo esc_attr($opt['product_id']); ?>" class="regular-text">
                        <p class="description">Angiv produkt-ID for bookingproduktet.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wddp_wc_redirect">Redirect efter formular</label></th>
                    <td>
                        <select id="wddp_wc_redirect" name="<?php echo esc_attr(WDDP_Options::OPTION_WC); ?>[redirect]">
                            <option value="checkout" <?php selected($opt['redirect'], 'checkout'); ?>>Kassen</option>
                            <option value="cart" <?php selected($opt['redirect'], 'cart'); ?>>Kurven</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Mail til admin ved oprettelse</th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(WDDP_Options::OPTION_WC); ?>[notify_admin_on_create]" value="1" <?php checked( ! empty($opt['notify_admin_on_create']) ); ?>>
                            Ja, send mail
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Tekst i kassen (checkout)</th>
                    <td>
                        <textarea name="<?php echo esc_attr(WDDP_Options::OPTION_WC); ?>[checkout_notice]" rows="4" cols="50"><?php echo esc_textarea($opt['checkout_notice'] ?? ''); ?></textarea>
                        <p class="description">Denne tekst vises i kassen og minder kunden om, at dette kun er en forespørgsel.</p>
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * FANE: Lukkede perioder
         */
        private function sectionClosed() {
            $opt = WDDP_Options::get( WDDP_Options::OPTION_CLOSED, WDDP_Options::defaults_closed() );
            if ( empty( $opt ) ) {
                $opt = [ [ 'from' => '', 'to' => '' ] ];
            }
            ?>
            <h3>Lukkede perioder</h3>
            <table class="widefat striped" id="wddp-closed-periods">
                <thead>
                <tr>
                    <th>Fra (dato)</th>
                    <th>Til (dato)</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $opt as $i => $row ):
                    $from_iso = WDDP_DateHelper::to_iso( $row['from'] ?? '' );
                    $to_iso   = WDDP_DateHelper::to_iso( $row['to'] ?? '' );
                    ?>
                    <tr>
                        <td><input type="date" name="<?php echo esc_attr(WDDP_Options::OPTION_CLOSED); ?>[<?php echo $i; ?>][from]" value="<?php echo esc_attr($from_iso); ?>" class="regular-text"></td>
                        <td><input type="date" name="<?php echo esc_attr(WDDP_Options::OPTION_CLOSED); ?>[<?php echo $i; ?>][to]"   value="<?php echo esc_attr($to_iso); ?>" class="regular-text"></td>
                        <td><a href="#" class="button wddp-row-del">–</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><a href="#" class="button" id="wddp-closed-add">+ Tilføj periode</a></p>
            <?php
        }

        /**
         * FANE: Aflevering/Afhentning – tidspunkter
         */
        private function sectionSlots() {
            $opt = WDDP_Options::get( WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots() );
            if ( empty( $opt ) ) {
                $opt = [ '09:00 – 09:30' ];
            }
            ?>
            <h3>Aflevering/Afhentning – tidspunkter</h3>
            <table class="widefat striped" id="wddp-slots">
                <thead>
                <tr>
                    <th>Tidspunkt (tekst)</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $opt as $val ): ?>
                    <tr>
                        <td><input type="text" name="<?php echo esc_attr(WDDP_Options::OPTION_SLOTS); ?>[]" value="<?php echo esc_attr($val); ?>" class="regular-text" placeholder="09:00 – 09:30"></td>
                        <td><a href="#" class="button wddp-row-del">–</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><a href="#" class="button" id="wddp-slots-add">+ Tilføj tidspunkt</a></p>
            <?php
        }

        /* ===========================
           Sanitize callbacks (med guard)
           =========================== */

        public static function sanitizePrices( $in ) {
            // Guard: hvis feltet ikke var i POST, returnér eksisterende værdi
            if ( $in === null ) {
                return get_option( WDDP_Options::OPTION_PRICES, WDDP_Options::defaults_prices() );
            }

            $out = WDDP_Options::defaults_prices();
            $out['dog1'] = isset($in['dog1']) ? floatval($in['dog1']) : 0;
            $out['dog2'] = isset($in['dog2']) ? floatval($in['dog2']) : 0;
            $out['dog3'] = isset($in['dog3']) ? floatval($in['dog3']) : 0;

            $out['special'] = [];
            if ( ! empty($in['special']) && is_array($in['special']) ) {
                foreach ( $in['special'] as $r ) {
                    $from = WDDP_DateHelper::to_iso( $r['from'] ?? '' );
                    $to   = WDDP_DateHelper::to_iso( $r['to'] ?? '' );
                    if ( $from === '' || $to === '' ) continue;

                    // Sørg for fra <= til
                    if ( $from > $to ) { $tmp = $from; $from = $to; $to = $tmp; }

                    $out['special'][] = [
                            'from' => $from,
                            'to'   => $to,
                            'dog1' => isset($r['dog1']) ? floatval($r['dog1']) : 0,
                            'dog2' => isset($r['dog2']) ? floatval($r['dog2']) : 0,
                            'dog3' => isset($r['dog3']) ? floatval($r['dog3']) : 0,
                    ];
                }
            }
            return $out;
        }


        public function getTitle(){
            return "Indstillinger";
        }

        public function getSlug(){
            return "wddp_menu-settings";
        }

        public function getParentSlug(){
           return"wddp_menu";
        }

        public function getIcon(){
        }

        public function getMenuOrder(){
        }
    }