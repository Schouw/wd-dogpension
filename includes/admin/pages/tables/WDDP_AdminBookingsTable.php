<?php

// Sørg for at klassen findes
if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WDDP_AdminBookingsTable extends \WP_List_Table
{

    protected $table_name;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'booking',
            'plural' => 'bookings',
            'ajax' => false,
        ]);

        global $wpdb;
        $this->table_name = $wpdb->prefix . WDDP_DatabaseSetup::WDDP_DATABASE_NAME;
    }

    public function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'id' => 'ID',
            'customer' => 'Kunde',
            'dates' => 'Datoer',
            'price' => 'Pris',
            'dogs' => 'Hund(e)',
            'status' => 'Status',
            'changes' => 'Ændringer',
            'order' => 'Ordre',
            'created_at' => 'Oprettet',
            'actions' => 'Handlinger'
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'id' => ['id', true],
            'price' => ['price', false],
            'status' => ['status', false],
            'order' => ['order_id', false],
            'created_at' => ['created_at', true],
        ];
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="booking_ids[]" value="%d" />', intval($item->getId()));
    }

    public function column_id($item)
    {
        return intval($item->getId());
    }

    public function column_customer($item)
    {
        $name = trim($item->getCustomerName());
        $email = trim($item->getEmail());
        $phone = trim($item->getPhone());
        $note  = trim($item->getNotes());


        $out = '';


        if (!empty($note)) {
            $escaped_note = esc_attr($note);
            $out .= ' <a href="#" class="wddp-show-note" title="Vis note" data-note="' . $escaped_note . '">
                    <span class="dashicons dashicons-media-text" style="vertical-align:middle;"></span>
                 </a>';
        }

        $out .= '<strong>' . esc_html($name ?: '—') . '</strong>';

        if ($email !== '') {
            $out .= '<div class="sub">E-mail: <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>';
        }
        if ($phone !== '') {
            // Lille tel-link; fjern mellemrum til href
            $href = preg_replace('/\s+/', '', $phone);
            $out .= '<div class="sub">Telefon: <a href="tel:' . esc_attr($href) . '">' . esc_html($phone) . '</a></div>';
        }



        return $out;
    }

    private function format_change_value($val) {
        if (is_array($val)) {
            $out = '<ul>';
            foreach ($val as $k => $v) {
                if (is_array($v)) {
                    $out .= '<li>' . esc_html($k) . ': ' . $this->format_change_value($v) . '</li>';
                } else {
                    $out .= '<li>' . esc_html($k) . ': ' . esc_html($v) . '</li>';
                }
            }
            $out .= '</ul>';
            return $out;
        }
        return esc_html((string)$val);
    }


    public function column_dates($item)
    {
        $from = $this->fmt_date($item->getBookingDateFrom() ?? '');
        $to = $this->fmt_date($item->getBookingDateTo() ?? '');

        return sprintf(
            '<div class="wddp-dates"><span class="from">%s</span><br><span class="to">%s</span></div>',
            esc_html($from),
            esc_html($to)
        );
    }

    public function column_price($item)
    {
        $price = number_format((float)$item->getPrice(), 2, ',', '.') . ' kr.';
        return esc_html($price);
    }

    public function column_changes($item)
    {
        $log = maybe_unserialize($item->getChangeLog());
        if (!is_array($log) || empty($log)) {
            return '—';
        }

        $id = $item->getId();

        // Byg indhold som HTML string
        $content = '';
        foreach ($log as $entry) {
            $content .= '<div style="margin-bottom:1em;">';
            $content .= '<strong>' . esc_html($entry['changed_at']) . '</strong> – ' . esc_html($entry['user']) . '<br>';
            $content .= '<ul style="margin:0; padding-left:20px;">';
            foreach ($entry['changes'] as $field => $change) {
                if ($field === 'dog_data') {
                    $from = $this->formatDogChange($change['from']);
                    $to   = $this->formatDogChange($change['to']);
                } else {
                    $from = esc_html((string)$change['from']);
                    $to   = esc_html((string)$change['to']);
                }
                $content .= '<li><strong>' . esc_html($field) . '</strong>: <em>' . esc_html($from) . '</em> → <strong>' . esc_html($to) . '</strong></li>';
            }
            $content .= '</ul></div>';
        }

        // Escape for JS
        $escaped_content = esc_js($content);

        return sprintf(
            '<a href="#" class="wddp-show-history" data-content="%s" title="Se ændringshistorik"><span class="dashicons dashicons-update"></span></a>',
            $escaped_content
        );
    }

    private function formatDogChange(array $dogs): string
    {
        $out = '';

        foreach ($dogs as $i => $dog) {
            $out .= '<div style="margin-bottom:10px;">';
            $out .= '<strong>Hund ' . ($i + 1) . '</strong>';
            $out .= '<ul style="margin:5px 0 0 15px;">';

            foreach ($dog as $key => $value) {
                if ($value === '') continue;

                $label = ucfirst($key);
                $out .= '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
            }

            $out .= '</ul></div>';
        }

        return $out ?: '—';
    }



    public function column_dogs($item)
    {
        return WDDP_DogHelper::renderAdminCell($item->getDogData());
    }


    public function column_status($item)
    {
        $status = $item->getStatus();
        return WDDP_StatusHelper::renderBadge($status);
    }

    public function column_order($item)
    {
        $order_id = $item->getOrderId();
        if (!$order_id) {
            return '—';
        }

        // Link til admin-ordren (shop_order er et CPT)
        $url = admin_url('post.php?post=' . $order_id . '&action=edit');
        return sprintf(
            '<a href="%s">#%d</a>',
            esc_url($url),
            $order_id
        );
    }

    public function column_created_at($item)
    {
        return esc_html($this->fmt_date($item->getCreatedAt()));
    }

    public function column_actions($item)
    {
        $id = (int)$item->getId();
        $status = $item->getStatus();

        $btn = function ($label, $class, $dataAction = null) use ($id) {
            if ($dataAction) {
                return sprintf(
                    '<a href="#" class="button button-small wddp-booking-action %s" data-action="%s" data-id="%d">%s</a><br/>',
                    esc_attr($class),
                    esc_attr($dataAction),
                    $id,
                    esc_html($label)
                );
            }
            // “Edit” er et normalt link (ingen JS-post)
            $edit_url = admin_url('admin.php?page=wddp_menu-edit-booking&edit=' . $id);
            return sprintf(
                '<a href="%s" class="button button-small %s">%s</a><br/>',
                esc_url($edit_url),
                esc_attr($class),
                esc_html($label)
            );
        };

        $out = '<div class="wddp-actions">';

        switch ($status) {
            case WDDP_StatusHelper::PENDING_REVIEW:
                // Godkend, Afvis, Slet
                $out .= $btn(__('Godkend', 'wd-dog-pension'), 'button-primary', 'approve') . ' ';
                $out .= $btn(__('Afvis', 'wd-dog-pension'), 'button', 'reject') . ' ';
                $out .= $btn(__('Slet', 'wd-dog-pension'), 'button-link-delete', 'delete');
                break;

            case WDDP_StatusHelper::APPROVED:
                // Ændre (redigér), Afvis, Slet
                $out .= $btn(__('Ændr', 'wd-dog-pension'), 'button') . ' ';
                $out .= $btn(__('Afvis', 'wd-dog-pension'), 'button', 'reject') . ' ';
                $out .= $btn(__('Slet', 'wd-dog-pension'), 'button-link-delete', 'delete');
                break;

            case WDDP_StatusHelper::REJECTED:
                // kun Slet
                $out .= $btn(__('Slet', 'wd-dog-pension'), 'button-link-delete', 'delete');
                break;

            default:
                // fallback: kun Slet
                $out .= $btn(__('Slet', 'wd-dog-pension'), 'button-link-delete', 'delete');
                break;
        }

        $out .= '</div>';
        return $out;
    }


    public function column_default($item, $column_name)
    {
        return $item->getId();
    }

    protected function get_orderby_sql()
    {
        $allowed = ['id', 'order_id', 'price', 'status', 'created_at'];
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
        if (!in_array($orderby, $allowed, true)) {
            $orderby = 'created_at';
        }
        $order = (isset($_GET['order']) && 'asc' === strtolower($_GET['order'])) ? 'ASC' : 'DESC';
        return [$orderby, $order];
    }


    public function get_bulk_actions()
    {
        // Til senere (godkend/afvis/slet)
        return [];
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Søgning (på navn/email)
        $search = isset($_REQUEST['s']) ? trim(wp_unslash($_REQUEST['s'])) : '';
        $where = 'WHERE 1=1';
        $params = [];

        $status_filter = isset($_GET['wddp_status']) ? sanitize_key($_GET['wddp_status']) : '';
        if ($status_filter && WDDP_StatusHelper::isValid($status_filter)) {
            $where .= " AND status = %s";
            $params[] = $status_filter;
        }


        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= " AND ( first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR dog_names LIKE %s )";
            array_push($params, $like, $like, $like, $like);
        }

        //filter date
        $date_from = isset($_GET['wddp_date_from']) ? sanitize_text_field($_GET['wddp_date_from']) : '';
        $date_to   = isset($_GET['wddp_date_to']) ? sanitize_text_field($_GET['wddp_date_to']) : '';

        if ($date_from && $date_to) {
            $where .= " AND NOT (pickup_date < %s OR dropoff_date > %s)";
            $params[] = $date_from;
            $params[] = $date_to;
        }



        // Sortering
        list($orderby, $order) = $this->get_orderby_sql();

        // Total
        $sql_count = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
        $total_items = $params
            ? (int)$wpdb->get_var($wpdb->prepare($sql_count, $params))
            : (int)$wpdb->get_var($sql_count);

        // Data
        $sql_items = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params_items = $params;
        $params_items[] = $per_page;
        $params_items[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql_items, $params_items), ARRAY_A);

        $items_models = [];
        foreach ($items as $item) {
            $items_models[] = new WDDP_Booking($item['id']);
        }

        $this->items = $items_models;

        // Kolonne-headere SKAL sættes for at rækker vises
        $this->_column_headers = [
            $this->get_columns(),            // kolonner
            [],                              // skjulte kolonner
            $this->get_sortable_columns(),   // sortable
            'id'                             // primary column (valgfrit, men rart)
        ];

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    private function fmt_date($value): string
    {
        if (empty($value)) return '—';
        $ts = strtotime($value);
        if (!$ts) return '—';
        // Respekter WP’s tidszone/locale
        return date_i18n('d/m - Y', $ts);
    }

}
