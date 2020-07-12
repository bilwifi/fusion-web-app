<?php

defined('ABSPATH') || exit;

// Include Tables
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class send_push_notifications_list_all_class extends WP_List_Table
{

    public static $table;
    public $root_path = FUSION_WA_URL;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->set_table();
        parent::__construct([
            'singular' => 'Notification', //singular name of the listed records
            'plural'   => 'Notifications', //plural name of the listed records
            'ajax'     => false, //should this table support ajax?
        ]);
    }

    /**
     * Submenu
     */
    function get_views()
    {
        $views = array();
        $current = (!empty($_REQUEST['filtered']) ? $_REQUEST['filtered'] : 'all');

        // All link
        $class = ($current == 'all' ? ' class="current"' : '');
        $sent = remove_query_arg('filtered');
        $views['all'] = "<a href='{$sent}' {$class} >" . __('All', FUSION_WA_SLUG) . " (" . self::record_count() . ")</a>";

        // Draft link
        $draft = add_query_arg('filtered', 'draft');
        $class = ($current == 'draft' ? ' class="current"' : '');
        $views['draft'] = "<a href='{$draft}' {$class} >" . __('Draft', FUSION_WA_SLUG) . " (" . self::record_count('draft') . ")</a>";

        // Trash link
        $trashed = add_query_arg('filtered', 'trashed');
        $class = ($current == 'trashed' ? ' class="current"' : '');
        $views['trashed'] = "<a href='{$trashed}' {$class} >" . __('Trash', FUSION_WA_SLUG) . " (" . self::record_count('trashed') . ")</a>";

        return $views;
    }

    /**
     * Set table name
     */
    private function set_table()
    {
        global $wpdb;
        self::$table = $wpdb->prefix . FUSION_WA_T_ALL;
    }

    /**
     * Get items
     */
    public static function get_items($per_page = 20, $page_number = 1)
    {
        global $wpdb;
        $sql = "SELECT * FROM " . self::$table;
        if (!empty($_REQUEST['filtered'])) {
            $sql .= " WHERE status = '" . $_REQUEST['filtered'] . "' ";
        } else {
            $sql .= " WHERE status = 'sent' ";
        }
        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY updated_at desc';
        }
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
        return $wpdb->get_results($sql, 'ARRAY_A');
    }

    /**
     * Get item
     */
    public static function get_item($id)
    {
        global $wpdb;
        $sql = "SELECT * FROM " . self::$table . " where id = $id LIMIT 1";
        return $wpdb->get_row($sql);
    }

    /**
     * Delete record
     *
     * @param int $id record ID
     */
    public static function delete_record($id)
    {
        global $wpdb;
        $row = self::get_item($id);
        if ($row->status === 'trashed') {
            $wpdb->delete(
                self::$table,
                ['id' => $id],
                ['%d']
            );
        } else {
            $wpdb->update(
                self::$table,
                ['status' => 'trashed'],
                ['id' => $id]
            );
        }
    }

    /**
     * Restore record
     *
     * @param int $id record ID
     */
    public static function restore_record($id)
    {
        global $wpdb;
        $wpdb->update(
            self::$table,
            ['status' => 'sent'],
            ['id' => $id]
        );
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count($status = 'sent')
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM " . self::$table . " WHERE status = '$status'";
        return $wpdb->get_var($sql);
    }


    /** Text displayed when no items available */
    public function no_items()
    {
        echo esc_html__('No records to display.', FUSION_WA_SLUG);
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_title($item)
    {
        // create a nonce
        $actions = [];
        $title = '<strong>' . $item['title'] . '</strong>';
        $delete_nonce = wp_create_nonce('push_action_single_record');
        $action = (!empty($_REQUEST['filtered']) ? ($_REQUEST['filtered'] == 'trashed' ? __('Delete forever', FUSION_WA_SLUG) : __('Remove', FUSION_WA_SLUG)) : __('Remove', FUSION_WA_SLUG));
        $confirmation_nedded = $action !== __('Remove', FUSION_WA_SLUG) ? 'return confirm(\'Are you sure you want to remove this item?\');' : '';

        $draft_or_restore = (!empty($_REQUEST['filtered']) ? ($_REQUEST['filtered'] == 'draft' ? __('Edit', FUSION_WA_SLUG) : __('Restore', FUSION_WA_SLUG)) : __('Resend', FUSION_WA_SLUG));

        $actions['delete'] = sprintf(
            '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s" onclick="' . esc_html($confirmation_nedded) . '">' . $action . '</a>',
            esc_attr($_REQUEST['page']),
            'delete-single',
            absint($item['id']),
            $delete_nonce
        );

        if ($action === __('Remove', FUSION_WA_SLUG)) {
            $actions['create'] = '<a href="' . FUSION_WA_URL . '-send_new&action=edit&id=' . absint($item['id']) . '">' . $draft_or_restore . '</a>';
        } else {
            $actions['restore'] = sprintf(
                '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . __('Restore', FUSION_WA_SLUG) . '</a>',
                esc_attr($_REQUEST['page']),
                'restore-single',
                absint($item['id']),
                $delete_nonce
            );
        }
        return $title . $this->row_actions($actions);
    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-action[]" value="%s" />',
            $item['id']
        );
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        return [
            'cb'      => '<input type="checkbox" />',
            'title' => __('Title', FUSION_WA_SLUG),
            'subtitle' => __('Subtitle (iOS)', FUSION_WA_SLUG),
            'body' => __('Message', FUSION_WA_SLUG),
            'recipients' => __('Recipients', FUSION_WA_SLUG),
            'updated_at' => __('Sent on', FUSION_WA_SLUG)
        ];
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'updated_at' => array('updated_at', false)

        );
        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $action = !empty($_REQUEST['filtered']) ? $_REQUEST['filtered'] : 'all';

        switch ($action) {
            case 'trashed':
                $actions = [
                    'bulk-restore' => __('Restore', FUSION_WA_SLUG),
                    'bulk-delete' => __('Delete forever', FUSION_WA_SLUG)
                ];
                break;
            default:
                $actions = [
                    'bulk-delete' => __('Delete', FUSION_WA_SLUG)
                ];
                break;
        }

        return $actions;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();
        $this->process_action();

        $per_page     = $this->get_items_per_page('items_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
        $this->items = self::get_items(20, $current_page);
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {
        // Detect when a bulk action is being triggered...
        if ($this->current_action() === 'bulk-delete') {
            // security check!
            if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {
                $nonce  = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
                $action = 'bulk-' . $this->_args['plural'];
                if (!wp_verify_nonce($nonce, $action))
                    wp_die('Nope! Security check failed!');
            }
            // Go ahead
            $delete_ids = esc_sql($_POST['bulk-action']);
            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::delete_record($id);
            }
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Items successfully removed!', FUSION_WA_SLUG);
            wp_redirect($this->root_path);
            exit;
        }
        
        // Detect when a bulk action is being triggered...
        if ($this->current_action() === 'bulk-restore') {
            // security check!
            if (isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce'])) {
                $nonce  = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);
                $action = 'bulk-' . $this->_args['plural'];
                if (!wp_verify_nonce($nonce, $action))
                    wp_die('Nope! Security check failed!');
            }
            // Go ahead
            $restore_ids = esc_sql($_POST['bulk-action']);
            // loop over the array of record IDs and delete them
            foreach ($restore_ids as $id) {
                self::restore_record($id);
            }
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Items successfully restored!', FUSION_WA_SLUG);
            wp_redirect($this->root_path);
            exit;
        }
    }

    /**
     * Process other actions
     */
    public function process_action()
    {
        // Detect when single delete action is being triggered...
        if ($this->current_action() === 'delete-single') {
            // security check!
            $delete_id = esc_sql($_REQUEST['id']);
            $nonce  = filter_input(INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING);
            if (!wp_verify_nonce($nonce, 'push_action_single_record')) {
                die('Go get a life script kiddies');
            }
            // loop over the array of record IDs and delete them
            self::delete_record($delete_id);
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Item successfully removed!', FUSION_WA_SLUG);
            wp_redirect($this->root_path);
            exit;
        }

        // Detect when single restore action is being triggered...
        if ($this->current_action() === 'restore-single') {
            // security check!
            $restore_id = esc_sql($_REQUEST['id']);
            $nonce  = filter_input(INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING);
            if (!wp_verify_nonce($nonce, 'push_action_single_record')) {
                die('Go get a life script kiddies');
            }
            // loop over the array of record IDs and delete them
            self::restore_record($restore_id);
            $_SESSION[FUSION_WA_SLUG . '-message-type'] = 'success';
            $_SESSION[FUSION_WA_SLUG . '-message'] = __('Item successfully restored!', FUSION_WA_SLUG);
            wp_redirect($this->root_path);
            exit;
        }
    }
}
