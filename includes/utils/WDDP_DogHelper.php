<?php

class WDDP_DogHelper
{
    public static function encode( array $dogs ): string {
        $dogs = array_values( array_slice( array_map( [__CLASS__, 'sanitize_dog'], $dogs ), 0, 10 ) );
        return wp_json_encode( $dogs );
    }

    public static function decode( ?string $data ): array {
        if ( empty( $data ) ) return [];

        // Først: prøv unserialize (WordPress-safe)
        $unserialized = maybe_unserialize( $data );
        if ( is_array( $unserialized ) ) {
            return $unserialized;
        }

        // Fallback: prøv JSON
        $json = json_decode( $data, true );
        if ( is_array( $json ) ) {
            return $json;
        }

        return [];
    }



    public static function renderAdminCell($dogData): string
    {
        if (empty($dogData)) {
            return '—';
        }

        // hvis data er serialiseret
        if (is_string($dogData)) {
            $dogData = maybe_unserialize($dogData);
        }

        if (!is_array($dogData)) {
            return '—';
        }

        $out = '<ul class="wddp-dog-list">';

        foreach ($dogData as $dog) {

            // fallback hvis gammel string stadig findes
            if (is_string($dog)) {
                $out .= '<li>' . esc_html($dog) . '</li>';
                continue;
            }

            if (!is_array($dog)) {
                continue;
            }

            $name   = $dog['name']   ?? '—';
            $breed  = $dog['breed']  ?? '—';
            $age    = $dog['age']    ?? '—';
            $weight = $dog['weight'] ?? '—';

            $line = sprintf(
                '%s (%s), %s, %s kg',
                esc_html($name),
                esc_html($breed),
                esc_html($age),
                esc_html($weight)
            );

            $out .= '<li>' . $line . '</li>';

            if (!empty($dog['notes'])) {
                $out .= '<li class="wddp-dog-notes"><em>'
                    . esc_html($dog['notes'])
                    . '</em></li>';
            }
        }

        $out .= '</ul>';

        return $out;
    }
}