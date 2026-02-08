<?php

class WDDP_AdminMailTextPage extends WDDP_AdminPage
{
    //TODO: REFACT AND DOC


    public function renderPage()
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'prices';

        $tabs = [
            'mail_received_customer'    => 'Bookningen modtaget (kunde)',
            'mail_received_admin'       => 'Bookningen modtaget (admin)',
            'mail_approved'             => 'Bookning er godkendt',
            'mail_rejected'             => 'Bookning er afvist',
            'mail_changed'              => 'Ændringer i booking',
            'mail_reminder'             => 'Påmindelse om din kommende bookning',
        ];

        if ( ! isset( $tabs[ $tab ] ) ) {
            $tab = 'mail_received_customer';
        }

        echo '<div class="wrap"><h1>Indstillinger</h1><h2 class="nav-tab-wrapper">';
        foreach ( $tabs as $slug => $label ) {
            $class = $slug === $tab ? ' nav-tab nav-tab-active' : ' nav-tab';
            $url   = admin_url( 'admin.php?page=wddp_menu-mail-settings&tab=' . $slug );
            echo '<a class="'.esc_attr($class).'" href="'.esc_url($url).'">'.esc_html($label).'</a>';
        }
        echo '</h2>';

        echo '<form method="post" action="options.php">';

        if ( $tab === 'mail_received_customer' ) $this->sectionReceivedCustomer();
        if ( $tab === 'mail_received_admin' ) $this->sectionReceivedAdmin();
        if ( $tab === 'mail_approved' )     $this->sectionReceivedApproved();
        if ( $tab === 'mail_rejected' ) $this->sectionReceivedRejected();
        if ( $tab === 'mail_changed' )  $this->sectionReceivedChanged();
        if ( $tab === 'mail_reminder' )  $this->sectionReceivedReminder();


        submit_button();
        echo '</form></div>';
    }

    private function sectionReceivedCustomer() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_PENDING_CUSTOMER, 'Bookning modtaget – Kunde');
    }

    private function sectionReceivedAdmin() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_PENDING_ADMIN, 'Bookning modtaget – Admin');
    }

    private function sectionReceivedApproved() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_APPROVED, 'Bookning godkendt');
    }

    private function sectionReceivedRejected() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_REJECTED, 'Bookning afvist');
    }

    private function sectionReceivedReminder() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_REMINDER, 'Påmindelse om kommende booking');
    }
    private function sectionReceivedChanged() {
        $this->renderEmailTemplateFields(WDDP_Mail::MAIL_CHANGED, 'Ændringer i booking');
    }



    private function renderEmailTemplateFields($key, $label) {
        settings_fields('wddp_settings_emails');
        $emails = WDDP_Options::get(WDDP_Options::OPTION_EMAILS, WDDP_Options::defaults_emails());
        $email  = $emails[$key] ?? [];

        ?>
        <h3><?php echo esc_html($label); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="email_subject_<?php echo esc_attr($key); ?>">Emne</label></th>
                <td>
                    <input type="text"
                           name="<?php echo esc_attr(WDDP_Options::OPTION_EMAILS); ?>[<?php echo esc_attr($key); ?>][subject]"
                           id="email_subject_<?php echo esc_attr($key); ?>"
                           class="regular-text"
                           value="<?php echo esc_attr($email['subject'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="email_body_<?php echo esc_attr($key); ?>">Indhold</label></th>
                <td>
                <textarea name="<?php echo esc_attr(WDDP_Options::OPTION_EMAILS); ?>[<?php echo esc_attr($key); ?>][body]"
                          id="email_body_<?php echo esc_attr($key); ?>"
                          rows="10"
                          class="large-text"><?php echo esc_textarea($email['body'] ?? ''); ?></textarea>
                    <p class="description">
                        Tilgængelige koder: <code>{first_name}</code>, <code>{last_name}</code>,
                        <code>{dropoff_date}</code>, <code>{pickup_date}</code>,
                        <code>{booking_id}</code>, <code>{site_name}</code>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }



    public function getTitle(){
        return "E-mail Indstillinger";
    }

    public function getSlug(){
        return "wddp_menu-mail-settings";
    }

    public function getParentSlug(){
       return"wddp_menu";
    }

    public function getIcon(){
    }

    public function getMenuOrder(){
    }
}