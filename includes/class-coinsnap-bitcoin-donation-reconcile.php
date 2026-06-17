<?php
/**
 * Admin "Check status" reconcile action for the Transactions page.
 *
 * Inbound webhooks are the only thing that flips a transaction to "paid", but the
 * payment provider cannot reach local-dev hosts (e.g. *.ddev.site) and webhooks can be
 * lost or misconfigured in production. This adds a manual per-row re-check that asks the
 * provider for the live invoice status and syncs it locally via the webhook code path.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coinsnap_Bitcoin_Donation_Reconcile {

    const ACTION = 'coinsnap_bd_reconcile';

    public function __construct() {
        add_action( 'coinsnap_core_transaction_row_actions', array( $this, 'render_row_action' ), 10, 2 );
        add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
        add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
    }

    /**
     * Only act on this plugin's own transactions page (the hook is shared by core).
     */
    private function is_our_instance( $instance ): bool {
        $core = coinsnap_bitcoin_donation_get_core();
        return is_object( $instance )
            && method_exists( $instance, 'option_key' )
            && $instance->option_key() === $core->option_key();
    }

    /**
     * Print a "Check status" button for transactions that aren't settled yet.
     *
     * @param object $transaction Transaction row.
     * @param object $instance    Plugin instance.
     */
    public function render_row_action( $transaction, $instance ): void {
        if ( ! $this->is_our_instance( $instance ) ) {
            return;
        }
        if ( empty( $transaction->payment_invoice_id ) || 'paid' === $transaction->payment_status ) {
            return;
        }
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:4px;">
            <input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
            <input type="hidden" name="invoice_id" value="<?php echo esc_attr( $transaction->payment_invoice_id ); ?>" />
            <?php wp_nonce_field( self::ACTION . '_' . $transaction->payment_invoice_id ); ?>
            <button type="submit" class="button button-small"><?php esc_html_e( 'Check status', 'coinsnap-bitcoin-donation' ); ?></button>
        </form>
        <?php
    }

    /**
     * Handle the reconcile request: query the provider and sync the local record.
     */
    public function handle(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'coinsnap-bitcoin-donation' ), '', array( 'response' => 403 ) );
        }

        $invoice_id = isset( $_POST['invoice_id'] )
            ? sanitize_text_field( wp_unslash( $_POST['invoice_id'] ) )
            : '';

        check_admin_referer( self::ACTION . '_' . $invoice_id );

        $status = '' !== $invoice_id
            ? coinsnap_bitcoin_donation_Webhooks::reconcile_invoice( $invoice_id )
            : 'error';

        $back = wp_get_referer();
        if ( ! $back ) {
            $back = admin_url( 'edit.php?post_type=donation-form&page=coinsnap-bitcoin-donation-transactions' );
        }

        wp_safe_redirect( add_query_arg( 'cbd_reconciled', rawurlencode( $status ), $back ) );
        exit;
    }

    /**
     * Show the result of a reconcile as an admin notice.
     */
    public function maybe_notice(): void {
        $result = filter_input( INPUT_GET, 'cbd_reconciled', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        if ( null === $result || '' === $result ) {
            return;
        }

        if ( 'paid' === $result ) {
            $class   = 'notice-success';
            $message = __( 'Payment confirmed with the provider and marked as paid.', 'coinsnap-bitcoin-donation' );
        } elseif ( 'failed' === $result ) {
            $class   = 'notice-warning';
            $message = __( 'The invoice has expired or is invalid; marked as failed.', 'coinsnap-bitcoin-donation' );
        } elseif ( 'error' === $result ) {
            $class   = 'notice-error';
            $message = __( 'Could not check the payment status. Please try again.', 'coinsnap-bitcoin-donation' );
        } else {
            $class   = 'notice-info';
            /* translators: %s: current payment status. */
            $message = sprintf( __( 'Payment is not settled yet (status: %s).', 'coinsnap-bitcoin-donation' ), $result );
        }

        printf(
            '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr( $class ),
            esc_html( $message )
        );
    }
}

new Coinsnap_Bitcoin_Donation_Reconcile();
