<?php

class WDDP_RestSetup
{

    public static $apiName = "wddp_api";

    public static function init() {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }


    public static function registerRoutes(){
        register_rest_route(self::$apiName, '/config', [
            'methods' => 'GET',
            'callback' => [self::class, 'getConfig'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::$apiName, '/calculatePrice', [
            'methods'             => 'POST', // <-- vigtig: POST
            'callback'            => [self::class, 'calculatePrice'],
            'permission_callback' => '__return_true',
            'args'                => [
                'fromDate'  => ['required' => true,  'type' => 'string'],
                'toDate'    => ['required' => true,  'type' => 'string'],
                // enten dogsCount ELLER dogs[] (frontend kan sende hvad der er lettest)
                'dogsCount' => ['required' => false, 'type' => 'integer'],
                'dogs'      => ['required' => false, 'type' => 'array'],
            ],
        ]);

        register_rest_route(self::$apiName, '/create_booking', [
            'methods' => 'POST',
            'callback' => [self::class, 'createBooking'],
            'permission_callback' => '__return_true',
        ]);

    }

    public static function createBooking(\WP_REST_Request $request) {
        // 1. Læs data fra request
        $data = $request->get_json_params();

        $from_date  = sanitize_text_field($data['from_date'] ?? '');
        $to_date    = sanitize_text_field($data['to_date'] ?? '');
        $arrival_time = sanitize_text_field($data['arrival_time'] ?? '');
        $departure_time = sanitize_text_field($data['departure_time'] ?? '');
        $dogs       = $data['dogs'] ?? [];
        $price = floatval($data['price'] ?? 0);

        // 2. Hent indstillinger
        $wc_opts = WDDP_Options::get(WDDP_Options::OPTION_WC, WDDP_Options::defaults_wc());
        $product_id = intval($wc_opts['product_id'] ?? 0);
        $redirect = $wc_opts['redirect'] ?? 'checkout'; // 'checkout' eller 'cart'

        if ($product_id <= 0 || !get_post($product_id)) {
            return new \WP_Error('missing_product', 'WooCommerce-produktet mangler.', ['status' => 400]);
        }

        // 3. Tilføj til kurv med custom data
        $cart_item_data['booking_data'] = [
            'from_date'         => $from_date,
            'to_date'           => $to_date,
            'arrival_time'      => $arrival_time,
            'departure_time'    => $departure_time,
            'dogs' => array_map(function($dog) {
                return [
                    'name'   => sanitize_text_field($dog['name'] ?? ''),
                    'breed'  => sanitize_text_field($dog['breed'] ?? ''),
                    'age'    => (int) ($dog['age'] ?? 0),
                    'weight' => (float) ($dog['weight'] ?? 0),
                    'notes'  => sanitize_textarea_field($dog['notes'] ?? ''),
                ];
            }, $dogs),
            'calculated_price'  => $price,
        ];

        $cart_item_data['unique_key'] = md5(microtime() . rand());
        // Gør WooCart klar hvis den ikke er det
        if (!WC()->cart) {
            wc_load_cart();
        }

        WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        // 4. Returnér redirect-URL til frontend
        $url = $redirect === 'cart' ? wc_get_cart_url() : wc_get_checkout_url();

        return new \WP_REST_Response([
            'success' => true,
            'redirect' => $url,
        ]);
    }

    public static function getConfig(\WP_REST_Request $request) {
        $slots = WDDP_Options::get(WDDP_Options::OPTION_SLOTS, WDDP_Options::defaults_slots());
        $closed  = WDDP_Options::get(WDDP_Options::OPTION_CLOSED, []); // array af ['from'=>'YYYY-MM-DD','to'=>'YYYY-MM-DD']
        $today   = wp_date('Y-m-d'); // WordPress’ tidszone
        $prices = WDDP_Options::get(WDDP_Options::OPTION_PRICES);
        $maxDogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, WDDP_Options::default_max_no_of_dogs());


        return new \WP_REST_Response([
            'slots' => $slots,
            'closedPeriods' => $closed,
            'today'         => $today,
            'prices'       => $prices,
            'maxDogs'       => $maxDogs,
        ]);
    }

    public static function calculatePrice(\WP_REST_Request $request ) {
        // --- Input ---
        $fromIso   = trim( (string) $request->get_param('fromDate') );
        $toIso     = trim( (string) $request->get_param('toDate') );
        $dogsCount = $request->get_param('dogsCount');
        $dogsArr   = $request->get_param('dogs');

        // antal hunde: understøt både tal og array (fallback = 1)
        $maxDogs = WDDP_Options::get(WDDP_Options::OPTION_MAX_NO_DOGS, 2);
        $nDogs = max(0, min($maxDogs, intval($dogsCount ?? 1)));


        // --- Validering af datoformat ---
        $from = self::dateFromISO($fromIso);
        $to   = self::dateFromISO($toIso);
        if (!$from || !$to) {
            return new \WP_Error('bad_dates', 'Ugyldigt datoformat. Brug YYYY-MM-DD.', ['status' => 400]);
        }
        if ($from > $to) {
            return new \WP_Error('bad_range', 'Fra-dato skal være før eller samme dag som Til-dato.', ['status' => 400]);
        }

        // --- "Tidligst i morgen" i WP-tidszone ---
        $tz       = wp_timezone(); // WordPress tidszone
        $now      = new \DateTime('now', $tz);
        $today    = new \DateTime($now->format('Y-m-d'), $tz);
        $earliest = clone $today; $earliest->modify('+1 day');

        if ($from < $earliest || $to < $earliest) {
            return new \WP_Error('too_early', 'Datoer skal være tidligst i morgen.', ['status' => 400]);
        }

        // --- Lukkede perioder ---
        $closed = WDDP_Options::get( WDDP_Options::OPTION_CLOSED, [] );
        foreach ($closed as $cp) {
            $cFrom = self::dateFromISO($cp['from'] ?? null, $tz);
            $cTo   = self::dateFromISO($cp['to']   ?? null, $tz);
            if ($cFrom && $cTo && self::overlaps($from, $to, $cFrom, $cTo)) {
                return new WP_Error('closed_overlap', 'De valgte datoer overlapper en lukket periode.', ['status' => 400]);
            }
        }

        // --- Priser ---
        $prices = WDDP_Options::get( WDDP_Options::OPTION_PRICES, WDDP_Options::defaults_prices() );
        $baseDog1 = floatval($prices['dog1'] ?? 0);
        $baseDog2 = floatval($prices['dog2'] ?? 0);
        $special  = is_array($prices['special'] ?? null) ? $prices['special'] : [];

        // guard: ingen hunde => total 0
        if ($nDogs <= 0) {
            return new \WP_REST_Response([
                'total'    => 0.0,
                'days'     => 0,
                'hasDog2'  => false,
                'currency' => 'DKK',
            ]);
        }

        // --- Beregn dag-for-dag ---
        $total = 0.0;
        $days  = 0;

        /** @var \DateTime $cursor */
        $cursor = clone $from;
        while ($cursor <= $to) {
            $dIso = $cursor->format('Y-m-d');

            // find evt. special-pris for denne dato
            [$d1, $d2] = self::ratesForDate($dIso, $baseDog1, $baseDog2, $special);

            // læg sammen for antal hunde (max 2)
            $dayPrice = 0.0;
            if ($nDogs >= 1) $dayPrice += $d1;
            if ($nDogs >= 2) $dayPrice += $d2;

            $total += $dayPrice;
            $days++;

            $cursor->modify('+1 day');
        }

        return new \WP_REST_Response([
            'total'    => round($total, 2),
            'days'     => $days,
            'hasDog2'  => $nDogs >= 2,
            'currency' => 'DKK',
        ]);
    }

    // ---------- Helpers ----------

    private static function dateFromISO(?string $iso, ?\DateTimeZone $tz = null): ?\DateTime {
        if (!$iso) return null;
        $tz = $tz ?: wp_timezone();
        $dt = \DateTime::createFromFormat('Y-m-d', $iso, $tz);
        if (!$dt) return null;
        // normaliser til midnat lokal tid
        return new \DateTime($dt->format('Y-m-d'), $tz);
    }

    private static function overlaps(\DateTime $aFrom, \DateTime $aTo, \DateTime $bFrom, \DateTime $bTo): bool {
        return $aFrom <= $bTo && $bFrom <= $aTo;
    }

    /**
     * Returnerer [prisHund1, prisHund2] for en given dato (tager højde for specialperioder).
     * $special er array af rækker med: ['from'=>'YYYY-MM-DD','to'=>'YYYY-MM-DD','dog1'=>float,'dog2'=>float]
     */
    private static function ratesForDate(string $iso, float $base1, float $base2, array $special): array {
        foreach ($special as $row) {
            $sFrom = $row['from'] ?? '';
            $sTo   = $row['to']   ?? '';
            if (!$sFrom || !$sTo) continue;

            if ($iso >= $sFrom && $iso <= $sTo) {
                $r1 = isset($row['dog1']) ? floatval($row['dog1']) : $base1;
                $r2 = isset($row['dog2']) ? floatval($row['dog2']) : $base2;
                return [$r1, $r2];
            }
        }
        return [$base1, $base2];
    }



}