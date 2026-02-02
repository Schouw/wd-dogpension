<?php

class WDDP_SettingsSetup {



    public static function init() {
        add_action( 'admin_init', [ self::class, 'register' ] );
    }

    public static function register() {
        register_setting( 'wddp_settings_dogs',     WDDP_Options::OPTION_MAX_NO_DOGS, [ 'sanitize_callback' => [ self::class, 'sanitize_dogs' ] ] );
        register_setting( 'wddp_settings_prices',   WDDP_Options::OPTION_PRICES, [ 'sanitize_callback' => [ self::class, 'sanitize_prices' ] ] );
        register_setting( 'wddp_settings_wc',       WDDP_Options::OPTION_WC,     [ 'sanitize_callback' => [ self::class, 'sanitize_wc' ] ] );
        register_setting( 'wddp_settings_closed',   WDDP_Options::OPTION_CLOSED, [ 'sanitize_callback' => [ self::class, 'sanitize_closed' ] ] );
        register_setting( 'wddp_settings_slots',    WDDP_Options::OPTION_SLOTS,  [ 'sanitize_callback' => [ self::class, 'sanitize_slots' ] ] );
        register_setting('wddp_settings_emails',    WDDP_Options::OPTION_EMAILS, [ 'sanitize_callback' => [ self::class, 'sanitize_emails' ] ]);

    }



    /* ===========================
       Sanitize callbacks (med guard)
       =========================== */
    public static function sanitize_emails($in) {
        if ($in === null || !is_array($in)) {
            return WDDP_Options::defaults_emails();
        }

        $out = WDDP_Options::defaults_emails();

        foreach ($out as $key => $template) {
            if (isset($in[$key]['subject'])) {
                $out[$key]['subject'] = sanitize_text_field($in[$key]['subject']);
            }
            if (isset($in[$key]['body'])) {
                $out[$key]['body'] = sanitize_textarea_field($in[$key]['body']);
            }
        }

        return $out;
    }


    public static function sanitize_prices( $in ) {
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

    public static function sanitize_wc( $in ) {
        if ( $in === null ) {
            return get_option( WDDP_Options::OPTION_WC, WDDP_Options::defaults_wc() );
        }

        $product_id = isset($in['product_id']) ? absint($in['product_id']) : 0;

        // Valider produkt, hvis muligt
        if ($product_id > 0) {
            $product = wc_get_product($product_id);
            if ($product) {
                $errors = WDDP_WooCommerceManager::validateBookingProduct($product);
                if (!empty($errors)) {
                    // Vis fejl i admin og behold eksisterende settings
                    add_settings_error(
                        'wddp_hp_wc',
                        'invalid_product',
                        'Valgt produkt er ugyldigt: ' . implode(', ', $errors)
                    );
                    return get_option(WDDP_Options::OPTION_WC); // behold eksisterende værdi
                }
            }
        }

        // Returnér det samlede array – med valid product_id
        return [
            'product_id' => $product_id,
            'redirect'   => in_array($in['redirect'] ?? 'checkout', ['checkout','cart'], true) ? $in['redirect'] : 'checkout',
            'notify_admin_on_create' => !empty($in['notify_admin_on_create']) ? 1 : 0,
            'checkout_notice' => isset($in['checkout_notice']) ? sanitize_textarea_field($in['checkout_notice']) : '',
        ];
    }


    public static function sanitize_dogs( $in ) {
        if ( $in === null ) {
            return WDDP_Options::default_max_no_of_dogs();
        }
        return max(1, min(10, absint($in)));
    }
    public static function sanitize_closed( $in ) {
        if ( $in === null ) {
            return get_option( WDDP_Options::OPTION_CLOSED, WDDP_Options::defaults_closed() );
        }
        $out = [];
        if ( is_array($in) ) {
            foreach ( $in as $r ) {
                $from = WDDP_DateHelper::to_iso( $r['from'] ?? '' );
                $to   = WDDP_DateHelper::to_iso( $r['to'] ?? '' );
                if ( $from === '' || $to === '' ) continue;
                if ( $from > $to ) { $tmp = $from; $from = $to; $to = $tmp; }
                $out[] = [ 'from' => $from, 'to' => $to ];
            }
        }
        return $out;
    }

    public static function sanitize_slots( $in ) {
        if ( $in === null ) {
            return get_option( WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots() );
        }
        $out = [];
        if ( is_array($in) ) {
            foreach ( $in as $v ) {
                $t = trim( (string) $v );
                if ( $t !== '' ) {
                    $out[] = $t;
                }
            }
        }
        return $out;
    }

}