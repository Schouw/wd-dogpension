<?php

class WDDP_DateHelper
{

    //TODO: REFACT AND DOC

    // Konverterer enhver af vores forventede inputs til ISO (Y-m-d). Returnerer '' hvis ugyldig.
    public static function to_iso( $val ): string {
        $val = trim( (string) $val );
        if ( $val === '' ) return '';

        // Allerede ISO?
        if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ) return $val;

        // dd/mm - yyyy (med eller uden mellemrum omkring bindestreg)
        if ( preg_match('/^(\d{2})\/(\d{2})\s*-\s*(\d{4})$/', $val, $m) ) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        // dd/mm/yyyy
        if ( preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $val, $m) ) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        // Fald tilbage til strtotime
        $ts = strtotime( $val );
        return $ts ? date('Y-m-d', $ts) : '';
    }

    // Format til visning i admin/lister: dd/mm - yyyy
    public static function to_display( $iso ): string {
        if ( ! $iso ) return '—';
        $ts = strtotime( $iso );
        if ( ! $ts ) return '—';
        return date_i18n( 'd/m - Y', $ts );
    }

}