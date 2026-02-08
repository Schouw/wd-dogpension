<?php

class WDDP_DebugMailSetup
{
    //TODO: REFACT AND DOC


    public static function setupDebugEmail() {
        // SÆT EN AFSENDER (DKIM/DMARC bliver nemmere)
        add_filter('wp_mail_from', function($from){ return 'sabina@winther.nu'; });
        add_filter('wp_mail_from_name', function($name){ return 'Hundepension'; });

        // Log fejl fra wp_mail (hvis de opstår)
        add_action('wp_mail_failed', function($wp_error){
            error_log('wp_mail_failed: '. print_r($wp_error->get_error_messages(), true));
        });

        // Brug SMTP via PHPMailer (Mailtrap eksempel)
        add_action('phpmailer_init', function($phpmailer){
            $phpmailer->isSMTP();
            $phpmailer->Host       = 'send.one.com';
            $phpmailer->SMTPAuth   = true;

            $phpmailer->Timeout    = 60;
            $phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $phpmailer->SMTPAutoTLS = true;

            $phpmailer->Port       = 587;
            $phpmailer->Username   = 'sabina@winther.nu';
            $phpmailer->Password   = 'my7WSWzv7hF2';
        });
    }
}