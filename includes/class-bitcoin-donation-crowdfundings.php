<?php
if (!defined('ABSPATH')) {
    exit;
}

class Bitcoin_Donation_Crowdfundings_Metabox
{
    public function __construct()
    {
        add_action('init', [$this, 'register_crowdfundings_post_type']);
        add_action('init', [$this, 'register_custom_meta_fields']);
        add_action('add_meta_boxes', [$this, 'add_crowdfundings_metaboxes']);
        add_action('save_post', [$this, 'save_crowdfundings_meta'], 10, 2);
        add_filter('manage_bitcoin-cfs_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_bitcoin-cfs_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
    }

    public function register_crowdfundings_post_type()
    {
        register_post_type('bitcoin-cfs', [
            'labels' => [
                'name'               => 'Crowdfundings',
                'singular_name'      => 'Crowdfunding',
                'menu_name'          => 'Crowdfundings',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Crowdfunding',
                'edit_item'          => 'Edit Crowdfunding',
                'new_item'           => 'New Crowdfunding',
                'view_item'          => 'View Crowdfunding',
                'search_items'       => 'Search Crowdfundings',
                'not_found'          => 'No crowdfundings found',
                'not_found_in_trash' => 'No crowdfundings found in Trash',
            ],
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'bitcoin-cfs'],
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => ['title'],
            'show_in_rest'       => true
        ]);
    }

    public function register_custom_meta_fields()
    {
        register_meta('post', '_bitcoin_donation_crowdfundings_description', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_option_1', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_meta('post', '_bitcoin_donation_crowdfundings_option_2', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_meta('post', '_bitcoin_donation_crowdfundings_option_3', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);
        register_meta('post', '_bitcoin_donation_crowdfundings_option_4', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_amount', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
            'description' => 'Amount in satoshis',
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_starting_date', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_ending_date', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_thank_you_message', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_active', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
        ]);

        register_meta('post', '_bitcoin_donation_crowdfundings_one_vote', [
            'object_subtype' => 'bitcoin-cfs',
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
        ]);
    }

    public function add_crowdfundings_metaboxes()
    {
        add_meta_box(
            'bitcoin_donation_crowdfundings_details',
            'Crowdfundings Details',
            [$this, 'render_crowdfundings_metabox'],
            'bitcoin-cfs',
            'normal',
            'high'
        );
    }

    public function render_crowdfundings_metabox($post)
    {
        wp_nonce_field('bitcoin_donation_crowdfundings_nonce', 'bitcoin_donation_crowdfundings_nonce');

        $description = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_description', true);
        $option_1 = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_option_1', true);
        $option_2 = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_option_2', true);
        $option_3 = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_option_3', true);
        $option_4 = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_option_4', true);
        $amount = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_amount', true);
        $starting_date = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_starting_date', true);
        $ending_date = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_ending_date', true);
        $thank_you_message = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_thank_you_message', true);
        $active = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_active', true);
        if ($active === '') {
            $active = '1';
        }
        $one_vote = get_post_meta($post->ID, '_bitcoin_donation_crowdfundings_one_vote', true);
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}voting_payments WHERE status = 'completed' AND crowdfunding_id = %d",
            $post->ID
        );
        $results = $wpdb->get_results($query);
        $votes = [
            'option_1' => 0,
            'option_2' => 0,
            'option_3' => 0,
            'option_4' => 0,
        ];
        if (count($results) > 0) {
            foreach ($results as $result) {
                switch ($result->option_id) {
                    case 1:
                        $votes['option_1']++;
                        break;
                    case 2:
                        $votes['option_2']++;
                        break;
                    case 3:
                        $votes['option_3']++;
                        break;
                    case 4:
                        $votes['option_4']++;
                        break;
                }
            }
        }

?>
        <table class="form-table">
            <tr>
                <th scope="row">Active</th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="bitcoin_donation_crowdfundings_active"
                            value="1"
                            <?php checked($active, '1'); ?>>
                        Enable
                    </label>
                    <br>
                </td>
            </tr>
            <tr>
                <th scope="row">One Vote Per User</th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="bitcoin_donation_crowdfundings_one_vote"
                            value="1"
                            <?php checked($one_vote, '1'); ?>>
                        Enable
                    </label>
                    <br>
                </td>
            </tr>
            <tr>
            <tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_description"><?php echo esc_html_e('Description', 'bitcoin-donation-crowdfundings') ?></label>
                </th>
                <td>
                    <textarea
                        id="bitcoin_donation_crowdfundings_description"
                        name="bitcoin_donation_crowdfundings_description"
                        class="regular-text"
                        rows="2"
                        required
                        style="width: 350px"><?php echo esc_textarea($description); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_option_1"><?php echo esc_html_e('Option 1', 'bitcoin-donation-crowdfundings') ?></label>
                    <span style="font-weight: normal;">
                        (
                        <?php echo esc_attr($votes['option_1']); ?> votes
                        )
                    </span>
                </th>
                <td>
                    <input
                        type="text"
                        id="bitcoin_donation_crowdfundings_option_1"
                        name="bitcoin_donation_crowdfundings_option_1"
                        class="regular-text"
                        required
                        value="<?php echo esc_attr($option_1); ?>">

                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_option_2"><?php echo esc_html_e('Option 2', 'bitcoin-donation-crowdfundings') ?></label>
                    <span style="font-weight: normal;">
                        (
                        <?php echo esc_attr($votes['option_2']); ?> votes
                        )
                    </span>
                </th>
                <td>
                    <input
                        type="text"
                        id="bitcoin_donation_crowdfundings_option_2"
                        name="bitcoin_donation_crowdfundings_option_2"
                        class="regular-text"
                        required
                        value="<?php echo esc_attr($option_2); ?>">

                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_option_3"><?php echo esc_html_e('Option 3', 'bitcoin-donation-crowdfundings') ?></label>
                    <span style="font-weight: normal;">
                        (
                        <?php echo esc_attr($votes['option_3']); ?> votes
                        )
                    </span>
                </th>
                <td>
                    <input
                        type="text"
                        id="bitcoin_donation_crowdfundings_option_3"
                        name="bitcoin_donation_crowdfundings_option_3"
                        class="regular-text"
                        value="<?php echo esc_attr($option_3); ?>">

                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_option_4"><?php echo esc_html_e('Option 4', 'bitcoin-donation-crowdfundings') ?></label>
                    <span style="font-weight: normal;">
                        (
                        <?php echo esc_attr($votes['option_4']); ?> votes
                        )
                    </span>
                </th>
                <td>
                    <input
                        type="text"
                        id="bitcoin_donation_crowdfundings_option_4"
                        name="bitcoin_donation_crowdfundings_option_4"
                        class="regular-text"
                        value="<?php echo esc_attr($option_4); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_amount"><?php echo esc_html_e('Amount (in satoshis)', 'bitcoin-donation-crowdfundings') ?></label>
                </th>
                <td>
                    <input
                        type="number"
                        id="bitcoin_donation_crowdfundings_amount"
                        name="bitcoin_donation_crowdfundings_amount"
                        class="regular-text"
                        required
                        value="<?php echo esc_attr($amount); ?>"
                        min="0"
                        step="1">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_starting_date"><?php echo esc_html_e('Starting Date', 'bitcoin-donation-crowdfundings') ?></label>
                </th>
                <td>
                    <input
                        type="datetime-local"
                        id="bitcoin_donation_crowdfundings_starting_date"
                        name="bitcoin_donation_crowdfundings_starting_date"
                        class="regular-text"
                        required
                        value="<?php echo esc_attr($starting_date); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_ending_date"><?php echo esc_html_e('Ending Date', 'bitcoin-donation-crowdfundings') ?></label>
                </th>
                <td>
                    <input
                        type="datetime-local"
                        id="bitcoin_donation_crowdfundings_ending_date"
                        name="bitcoin_donation_crowdfundings_ending_date"
                        class="regular-text"
                        required
                        value="<?php echo esc_attr($ending_date); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bitcoin_donation_crowdfundings_thank_you_message"><?php echo esc_html_e('Thank You Message', 'bitcoin-donation-crowdfundings') ?></label>
                </th>
                <td>
                    <textarea
                        id="bitcoin_donation_crowdfundings_thank_you_message"
                        name="bitcoin_donation_crowdfundings_thank_you_message"
                        class="regular-text"
                        rows="2"
                        required
                        style="width: 350px"><?php echo esc_textarea($thank_you_message); ?></textarea>

                </td>
            </tr>
            <th scope="row">
                <label for="shortcode"><?php echo esc_html_e('Shortcode', 'bitcoin-donation-crowdfundings') ?></label>
            </th>
            <td>
                <input
                    type="text"
                    id="shortcode"
                    name="shortcode"
                    class="regular-text"
                    readonly
                    value='[bitcoin_voting id="<?php echo esc_html($post->ID); ?>"]'>
            </td>
            </tr>

        </table>
<?php
    }

    public function save_crowdfundings_meta($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            $expected_nonce = 'wp_rest';
            $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field($_SERVER['HTTP_X_WP_NONCE']) : '';
        } else {
            $expected_nonce = 'bitcoin_donation_crowdfundings_nonce';
            $nonce = filter_input(INPUT_POST, $expected_nonce, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if (empty($nonce) || !wp_verify_nonce($nonce, $expected_nonce)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ($post->post_type !== 'bitcoin-cfs') {
            return;
        }

        $fields = [
            'bitcoin_donation_crowdfundings_description' => 'text',
            'bitcoin_donation_crowdfundings_option_1'    => 'text',
            'bitcoin_donation_crowdfundings_option_2'    => 'text',
            'bitcoin_donation_crowdfundings_option_3'    => 'text',
            'bitcoin_donation_crowdfundings_option_4'    => 'text',
            'bitcoin_donation_crowdfundings_amount'      => 'number',
            'bitcoin_donation_crowdfundings_starting_date' => 'text',
            'bitcoin_donation_crowdfundings_ending_date'   => 'text',
            'bitcoin_donation_crowdfundings_thank_you_message' => 'text',
            'bitcoin_donation_crowdfundings_active'      => 'boolean',
            'bitcoin_donation_crowdfundings_one_vote'    => 'boolean',
        ];

        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            $required_fields = [
                'bitcoin_donation_crowdfundings_description',
                'bitcoin_donation_crowdfundings_option_1',
                'bitcoin_donation_crowdfundings_option_2',
                'bitcoin_donation_crowdfundings_amount',
                'bitcoin_donation_crowdfundings_starting_date',
                'bitcoin_donation_crowdfundings_ending_date'
            ];

            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    wp_die("Error: $field is required.");
                }
            }
        } else {
            $json_body = file_get_contents('php://input');
            $data = json_decode($json_body, true);

            if (isset($data['meta']) && is_array($data['meta'])) {
                $required_meta_fields = [
                    '_bitcoin_donation_crowdfundings_description',
                    '_bitcoin_donation_crowdfundings_option_1',
                    '_bitcoin_donation_crowdfundings_option_2',
                    '_bitcoin_donation_crowdfundings_amount',
                    '_bitcoin_donation_crowdfundings_starting_date',
                    '_bitcoin_donation_crowdfundings_ending_date'
                ];

                foreach ($required_meta_fields as $field) {
                    if (empty($data['meta'][$field])) {
                        return new WP_Error('missing_required_field', "Error: $field is required.", ['status' => 400]);
                    }
                }
            }
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            $json_body = file_get_contents('php://input');
            $data = json_decode($json_body, true);

            if (isset($data['meta']) && is_array($data['meta'])) {
                foreach ($fields as $field => $type) {
                    $json_key = '_' . $field;
                    if (isset($data['meta'][$json_key])) {
                        $value = $data['meta'][$json_key];
                        if ($type === 'boolean') {
                            $value = (bool)$value;
                        } elseif ($type === 'number') {
                            $value = floatval($value);
                        } else {
                            $value = sanitize_text_field($value);
                        }
                        update_post_meta($post_id, $json_key, $value);
                    }
                }
            }
            return;
        }

        foreach ($fields as $field => $type) {
            if ($type === 'boolean') {
                $value = isset($_POST[$field]) ? '1' : '';
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                if (isset($_POST[$field])) {
                    $value = $_POST[$field];
                    if ($type === 'number') {
                        $value = floatval($value);
                    } else {
                        $value = sanitize_text_field($value);
                    }
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
    }

    public function add_custom_columns($columns)
    {

        $new_columns = [
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'shortcode' => 'Shortcode',
            'amount' => 'Amount (satoshis)',
            'starting_date' => 'Starting Date',
            'ending_date' => 'Ending Date',
            'thank_you_message' => 'Thank You Message',
            'active' => 'Active',
            'one_vote' => 'One Vote',
        ];

        return $new_columns;
    }

    public function populate_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'description':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_description', true) ?: '');
                break;
            case 'option_1':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_option_1', true) ?: '');
                break;
            case 'option_2':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_option_2', true) ?: '');
                break;
            case 'option_3':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_option_3', true) ?: '');
                break;
            case 'option_4':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_option_4', true) ?: '');
                break;
            case 'amount':
                $amount = get_post_meta($post_id, '_bitcoin_donation_crowdfundings_amount', true);
                echo esc_html($amount ?: '0');
                break;
            case 'starting_date':
                $date = get_post_meta($post_id, '_bitcoin_donation_crowdfundings_starting_date', true);
                echo esc_html($date ?: '-');
                break;
            case 'ending_date':
                $date = get_post_meta($post_id, '_bitcoin_donation_crowdfundings_ending_date', true);
                echo esc_html($date ?: '-');
                break;
            case 'thank_you_message':
                echo esc_html(get_post_meta($post_id, '_bitcoin_donation_crowdfundings_thank_you_message', true) ?: '');
                break;
            case 'active':
                echo get_post_meta($post_id, '_bitcoin_donation_crowdfundings_active', true) ? '✓' : '✗';
                break;
            case 'one_vote':
                echo get_post_meta($post_id, '_bitcoin_donation_crowdfundings_one_vote', true) ? '✓' : '✗';
                break;
            case 'shortcode':
                echo '[bitcoin_voting id="' . esc_html($post_id) . '"]';
                break;
        }
    }
}

new Bitcoin_Donation_Crowdfundings_Metabox();
