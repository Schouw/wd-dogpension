<?php

abstract class WDDP_Mail {

    const MAIL_PENDING_CUSTOMER = 'mail_pending_customer';
    const MAIL_PENDING_ADMIN = 'mail_pending_admin';
    const MAIL_APPROVED = 'mail_approved';
    const MAIL_REJECTED = 'mail_rejected';
    const MAIL_REMINDER = 'mail_reminder';
    const MAIL_CHANGED = 'mail_changed';

    protected $placeholders = [];

    public function __construct($placeholders = []) {
        $this->placeholders = $placeholders;
    }

    abstract protected function getKey();

    protected function getOptions(){
        return WDDP_Options::get(WDDP_Options::OPTION_EMAILS);
    }

    protected function get_subject() {
        $subject = $this->getOptions()[$this->getKey()]['subject'];
        return is_string($subject) ? $subject : '';
    }

    protected function get_content() {
        $content = $this->getOptions()[$this->getKey()]['body'];
        return is_string($content) ? $content : '';
    }

    public function send($to) {
        $subjectRaw = is_string($this->get_subject()) ? $this->get_subject() : '';
        $subject = $this->replacePlaceholders($subjectRaw);
        $contentRaw = is_string($this->get_content()) ? $this->get_content() : '';
        $content = $this->replacePlaceholders($contentRaw);

        if (!$subject || !$content) return false;

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $body = $this->buildTemplate($subject, $content);

        return \wp_mail($to, $subject, $body, $headers);
    }

    protected function replacePlaceholders($content) {
        foreach ($this->placeholders as $key => $value) {

            // Placeholders der må indeholde HTML
            if (in_array($key, ['changes'], true)) {
                $replacement = (string) $value;
            } else {
                $replacement = esc_html((string) $value);
            }

            $content = str_replace('{' . $key . '}', $replacement, $content);
        }

        return nl2br($content);
    }

    protected function buildTemplate($subject, $content) {
        return '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .email-container { max-width: 600px; background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); margin: auto; }
                .email-header { background: #E27730; color: white; text-align: center; padding: 15px; border-radius: 8px 8px 0 0; font-size: 20px; }
                .email-content { padding: 20px; color: #333; font-size: 16px; line-height: 1.5; }
                .email-footer { text-align: center; padding: 15px; font-size: 14px; color: #777; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">' . esc_html($subject) . '</div>
                <div class="email-content">' . $content . '</div>
                <div class="email-footer">
                    Munkholm Hundecenter • kontakt@munkholmhundecenter.dk
                </div>
            </div>
        </body>
        </html>';
    }
}

class EmailBookingReceived extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_PENDING_CUSTOMER;
    }
}

class EmailBookingReceivedAdmin extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_PENDING_ADMIN;
    }
}

class EmailBookingApproved extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_APPROVED;
    }
}

class EmailBookingRejected extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_REJECTED;
    }
}

class EmailBookingChanged extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_CHANGED;
    }
}

class EmailBookingReminder extends WDDP_Mail{

    protected function getKey(){
        return self::MAIL_REMINDER;
    }
}