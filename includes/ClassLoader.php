<?php

require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_DatabaseSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_SettingsSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_BlockSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_RestSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_WooCommerceSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_DebugMailSetup.php';
require_once WT_DOG_PENSION_PATH . 'includes/setup/WDDP_EnqueueSetup.php';

require_once WT_DOG_PENSION_PATH . 'includes/utils/WDDP_Options.php';
require_once WT_DOG_PENSION_PATH . 'includes/utils/WDDP_StatusHelper.php';
require_once WT_DOG_PENSION_PATH . 'includes/utils/WDDP_DogHelper.php';
require_once WT_DOG_PENSION_PATH . 'includes/utils/WDDP_DateHelper.php';

require_once WT_DOG_PENSION_PATH . 'includes/business/WDDP_BookingManager.php';
require_once WT_DOG_PENSION_PATH . 'includes/business/WDDP_WooCommerceManager.php';
require_once WT_DOG_PENSION_PATH . 'includes/business/WDDP_MailManager.php';

require_once WT_DOG_PENSION_PATH . 'includes/model/WDDP_Booking.php';
require_once WT_DOG_PENSION_PATH . 'includes/model/WDDP_Mail.php';

require_once WT_DOG_PENSION_PATH . 'includes/admin/WDDP_AdminMenu.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminBookingPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminEditBookingPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminCreateBookingPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminSettingsPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminMailTextPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/WDDP_AdminCalendarPage.php';
require_once WT_DOG_PENSION_PATH . 'includes/admin/pages/tables/WDDP_AdminBookingsTable.php';
