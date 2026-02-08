<?php

class WDDP_BookingValidator {

    //TODO: REFACT AND DOC


    /**
     * Basisvalidering (fælles for alle scenarier)
     * Tjekker datoer, tider, hunde.
     */
    public static function validateCore(array $data): array {
        $errors = [];

        // Tjek datoer
        if (empty($data['dropoff_date'])) {
            $errors[] = 'Fra-dato mangler.';
        }
        if (empty($data['pickup_date'])) {
            $errors[] = 'Til-dato mangler.';
        }

        // Tjek tider
        if (empty($data['dropoff_time'])) {
            $errors[] = 'Afleveringstidspunkt mangler.';
        }
        if (empty($data['pickup_time'])) {
            $errors[] = 'Afhentningstidspunkt mangler.';
        }

        // Tjek hunde
        $dogs = $data['dogs'] ?? [];
        if (!is_array($dogs) || count($dogs) === 0) {
            $errors[] = 'Du skal angive mindst én hund.';
        } else {
            foreach ($dogs as $i => $dog) {
                if (empty($dog['name']))   $errors[] = "Hund " . ($i + 1) . ": navn mangler.";
                if (empty($dog['breed']))  $errors[] = "Hund " . ($i + 1) . ": race mangler.";
                if (empty($dog['age']))    $errors[] = "Hund " . ($i + 1) . ": alder mangler.";
                if (!isset($dog['weight']) || $dog['weight'] === '') $errors[] = "Hund " . ($i + 1) . ": vægt mangler.";
            }
        }

        return $errors;
    }

    /**
     * Bruges i frontend – her ved vi IKKE hvem kunden er endnu
     */
    public static function validateForFrontend(array $data): array {
        return self::validateCore($data);
    }

    /**
     * Bruges når vi har WooCommerce-ordre og skal gemme booking i systemet
     */
    public static function validateWithCustomer(array $data): array {
        $errors = self::validateCore($data);

        // Kundenavn og kontaktoplysninger kræves her
        if (empty($data['first_name'])) {
            $errors[] = 'Fornavn mangler.';
        }
        if (empty($data['last_name'])) {
            $errors[] = 'Efternavn mangler.';
        }
        if (empty($data['email'])) {
            $errors[] = 'E-mail mangler.';
        }
        if (empty($data['phone'])) {
            $errors[] = 'Telefonnummer mangler.';
        }
        if (empty($data['address'])) {
            $errors[] = 'Adresse mangler.';
        }
        if (empty($data['postal_code'])) {
            $errors[] = 'Postnummer mangler.';
        }
        if (empty($data['city'])) {
            $errors[] = 'By mangler.';
        }

        return $errors;
    }
}
