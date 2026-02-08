<?php

class WDDP_MailManager
{

    //TODO: REFACT AND DOC



    public static function buildMail($mailType, $placerholders){

        $mail = null;

        switch($mailType){
            case WDDP_Mail::MAIL_PENDING_CUSTOMER:
                $mail = new EmailBookingReceived($placerholders);
                break;
            case WDDP_Mail::MAIL_PENDING_ADMIN:
                $mail = new EmailBookingReceivedAdmin($placerholders);
                break;
            case WDDP_Mail::MAIL_APPROVED:
                $mail = new EmailBookingApproved($placerholders);
                break;
            case WDDP_Mail::MAIL_REJECTED:
                $mail = new EmailBookingRejected($placerholders);
                break;
            case WDDP_Mail::MAIL_CHANGED:
                $mail = new EmailBookingChanged($placerholders);
                break;
            case WDDP_Mail::MAIL_REMINDER:
                $mail = new EmailBookingReminder($placerholders);
                break;
        }

        return $mail;
    }
}