<?php

class WDDP_AdminCalendarPage extends WDDP_AdminPage {

    public function renderPage() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($this->getTitle()); ?></h1>

            <div class="wddp-calendar-legend">
                <span class="legend-item" style="background-color:#28a745"></span> Godkendt
                <span class="legend-item" style="background-color:#ffc107"></span> Afventer
                <span class="legend-item" style="background-color:#dc3545"></span> Afvist
            </div>


            <div id="wddp-calendar"></div>

            <div id="wddp-booking-modal" style="display:none;">
                <div class="modal-content"></div>
                <div class="modal-actions"></div>
                <button class="button">Luk</button>
            </div>


        </div>
        <?php
    }

    public function getTitle() {
        return 'Booking Kalender';
    }

    public function getSlug() {
        return 'wddp_menu-calendar';
    }

    public function getParentSlug() {
        return 'wddp_menu'; // top-level
    }

    public function getIcon() {
        return 'dashicons-calendar-alt';
    }

    public function getMenuOrder() {
        return 20;
    }
}
