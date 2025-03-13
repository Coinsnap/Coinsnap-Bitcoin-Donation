<?php
if (!defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Public_Donors
{
    public function __construct()
    {
        add_action('init', [$this, 'register_public_donors_post_type']);
        add_action('init', [$this, 'register_custom_meta_fields']);
        add_action('add_meta_boxes', [$this, 'add_public_donors_metaboxes']);
        add_action('save_post', [$this, 'save_public_donors_meta'], 10, 2);
        add_filter('manage_bitcoin-pds_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_bitcoin-pds_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
    }

    public function register_public_donors_post_type()
    {
        register_post_type('bitcoin-pds', [
            'labels' => [
                'name'               => 'Public Donors',
                'singular_name'      => 'Public Donor',
                'menu_name'          => 'Public Donors',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Donor',
                'edit_item'          => 'Edit Donor',
                'new_item'           => 'New Donor',
                'view_item'          => 'View Donor',
                'search_items'       => 'Search Donors',
                'not_found'          => 'No donors found',
                'not_found_in_trash' => 'No donors found in Trash',
            ],
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'bitcoin-pds'],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => ['title'],
            'show_in_rest'       => true
        ]);
    }

    public function register_custom_meta_fields()
    {
        register_meta('post', '_bitcoin_donation_donor_name', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_amount', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_message', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_form_type', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_dont_show', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_hide', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_email', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_address', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_payment_id', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_custom_field', [
            'object_subtype' => 'bitcoin-pds',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
    }

    public function add_public_donors_metaboxes()
    {
        add_meta_box(
            'bitcoin_donation_public_donors_details',
            'Public Donor Details',
            [$this, 'render_public_donors_metabox'],
            'bitcoin-pds',
            'normal',
            'high'
        );
    }

    public function render_public_donors_metabox($post)
    {
        wp_nonce_field('bitcoin_donation_public_donors_nonce', 'bitcoin_donation_public_donors_nonce');

        $name = get_post_meta($post->ID, '_bitcoin_donation_donor_name', true);
        $amount = get_post_meta($post->ID, '_bitcoin_donation_amount', true);
        $message = get_post_meta($post->ID, '_bitcoin_donation_message', true);
        $form_type = get_post_meta($post->ID, '_bitcoin_donation_form_type', true);
        $dont_show = get_post_meta($post->ID, '_bitcoin_donation_dont_show', true);
        $hide = get_post_meta($post->ID, '_bitcoin_donation_hide', true);
        $email = get_post_meta($post->ID, '_bitcoin_donation_email', true);
        $address = get_post_meta($post->ID, '_bitcoin_donation_address', true);
        $payment_id = get_post_meta($post->ID, '_bitcoin_donation_payment_id', true);
        $custom_field = get_post_meta($post->ID, '_bitcoin_donation_custom_field', true);
?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_donor_name"><?php echo esc_html_e('Name', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_donor_name" name="bitcoin_donation_donor_name" class="regular-text" value="<?php echo esc_attr($name); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_amount"><?php echo esc_html_e('Amount', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_amount" name="bitcoin_donation_amount" class="regular-text" value="<?php echo esc_attr($amount); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_message"><?php echo esc_html_e('Message', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <textarea id="bitcoin_donation_message" name="bitcoin_donation_message" class="regular-text" rows="3" readonly><?php echo esc_textarea($message); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_form_type"><?php echo esc_html_e('Form Type', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_form_type" name="bitcoin_donation_form_type" class="regular-text" value="<?php echo esc_attr($form_type); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_email"><?php echo esc_html_e('Email', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="email" id="bitcoin_donation_email" name="bitcoin_donation_email" class="regular-text" value="<?php echo esc_attr($email); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_address"><?php echo esc_html_e('Address', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_address" name="bitcoin_donation_address" class="regular-text" value="<?php echo esc_attr($address); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_payment_id"><?php echo esc_html_e('Payment ID', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_payment_id" name="bitcoin_donation_payment_id" class="regular-text" value="<?php echo esc_attr($payment_id); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_custom_field"><?php echo esc_html_e('Custom Field', 'bitcoin-donation') ?></label>
                </th>
                <td>
                    <input type="text" id="bitcoin_donation_custom_field" name="bitcoin_donation_custom_field" class="regular-text" value="<?php echo esc_attr($custom_field); ?>" readonly>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html_e('Don\'t Show', 'bitcoin-donation') ?></th>
                <td>
                    <label>
                        <input type="checkbox" onclick="return false" name="bitcoin_donation_dont_show" value="1" <?php checked($dont_show, '1'); ?> readonly>
                        Enable
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html_e('Hide', 'bitcoin-donation') ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="bitcoin_donation_hide" value="1" <?php checked($hide, '1'); ?>>
                        Enable
                    </label>
                </td>
            </tr>

        </table>
<?php
    }

    public function save_public_donors_meta($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        if ($post->post_type !== 'bitcoin-pds') return;

        if (
            !isset($_POST['bitcoin_donation_public_donors_nonce']) ||
            !wp_verify_nonce($_POST['bitcoin_donation_public_donors_nonce'], 'bitcoin_donation_public_donors_nonce')
        ) {
            return;
        }

        $fields = [
            'bitcoin_donation_donor_name' => 'text',
            'bitcoin_donation_amount' => 'text',
            'bitcoin_donation_message' => 'text',
            'bitcoin_donation_form_type' => 'text',
            'bitcoin_donation_dont_show' => 'boolean',
            'bitcoin_donation_hide' => 'boolean',
            'bitcoin_donation_email' => 'text',
            'bitcoin_donation_address' => 'text',
            'bitcoin_donation_payment_id' => 'text',
            'bitcoin_donation_custom_field' => 'text',
        ];

        foreach ($fields as $field => $type) {
            if ($type === 'boolean') {
                $value = isset($_POST[$field]) ? '1' : '';
            } else {
                $value = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
            }
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    public function add_custom_columns($columns)
    {
        return [
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'name' => 'Name',
            'email' => 'Email',
            'amount' => 'Amount',
            'message' => 'Message',
            'address' => 'Address',
            'payment_id' => 'Payment ID',
            'form_type' => 'Form Type',
            'custom_field' => 'Custom Field',
            'dont_show' => 'Don\'t Show',
            'hide' => 'Hide'
        ];
    }

    public function populate_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'name':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_donor_name', true));
                break;
            case 'amount':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_amount', true));
                break;
            case 'message':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_message', true));
                break;
            case 'form_type':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_form_type', true));
                break;
            case 'dont_show':
                echo get_post_meta($post_id, '_bitcoin_donation_dont_show', true) ? '✓' : '✗';
                break;
            case 'hide':
                echo get_post_meta($post_id, '_bitcoin_donation_hide', true) ? '✓' : '✗';
                break;
            case 'email':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_email', true));
                break;
            case 'address':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_address', true));
                break;
            case 'payment_id':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_payment_id', true));
                break;
            case 'custom_field':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_custom_field', true));
                break;
        }
    }
}

new Bitcoin_Donation_Public_Donors();
