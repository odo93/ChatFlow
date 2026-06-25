<?php
/**
 * MolkFilm_Payments
 *
 * Registers three WooCommerce payment gateways in code:
 *
 *  1. Paymob (Egypt)  — auth → create-order → payment-key → iFrame
 *  2. PayTabs (KSA/UAE) — hosted payment page + IPN callback
 *  3. PayPal            — standard WC PayPal redirect (built-in WC support)
 *
 * On successful payment (payment_complete):
 *  - Enrolls student in the linked Tutor LMS course
 *  - Increments seats_taken
 *  - Sends branded confirmation email
 *
 * ⚠️  API keys are NEVER hardcoded.  All credentials are read from
 *     get_option() which is set via the Settings page in wp-admin.
 *
 * TODO markers show exactly where to paste live credentials.
 */

defined( 'ABSPATH' ) || exit;

class MolkFilm_Payments {

    public static function init() {
        // Register gateways with WooCommerce
        add_filter( 'woocommerce_payment_gateways', [ __CLASS__, 'add_gateways' ] );

        // Post-payment enrolment + email
        add_action( 'woocommerce_payment_complete', [ __CLASS__, 'handle_successful_payment' ] );
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'handle_successful_payment' ] );

        // Paymob callback endpoint
        add_action( 'woocommerce_api_molkfilm_paymob', [ __CLASS__, 'paymob_callback' ] );

        // PayTabs IPN endpoint
        add_action( 'woocommerce_api_molkfilm_paytabs', [ __CLASS__, 'paytabs_ipn' ] );

        // Branded email template override (optional — adds header colour)
        add_filter( 'woocommerce_email_styles', [ __CLASS__, 'branded_email_styles' ] );
        add_action( 'woocommerce_email_header',  [ __CLASS__, 'email_header' ], 10, 2 );
    }

    // ── Gateway list ─────────────────────────────────────────────────────────

    public static function add_gateways( $gateways ) {
        $gateways[] = 'MolkFilm_Gateway_Paymob';
        $gateways[] = 'MolkFilm_Gateway_PayTabs';
        // PayPal is the standard WC gateway; we expose its settings via our
        // Settings page but don't replace the gateway class.
        return $gateways;
    }

    // ── Post-payment: enrol + seats + email ──────────────────────────────────

    public static function handle_successful_payment( $order_id ) {
        // Idempotency: only run once per order
        if ( get_post_meta( $order_id, '_molkfilm_enrolled', true ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = $order->get_user_id();
        if ( ! $user_id ) {
            // Guest checkout — try to find user by email
            $user = get_user_by( 'email', $order->get_billing_email() );
            $user_id = $user ? $user->ID : 0;
        }
        if ( ! $user_id ) return;

        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $course_id  = MolkFilm_Course_Fields::get_course_by_product( $product_id );
            if ( ! $course_id ) continue;

            // Tutor LMS enrolment
            if ( function_exists( 'tutor_utils' ) ) {
                $already = tutor_utils()->is_enrolled( $course_id, $user_id );
                if ( ! $already ) {
                    tutor_utils()->do_enrol( $course_id, $order_id, $user_id );
                }
            }
        }

        update_post_meta( $order_id, '_molkfilm_enrolled', 1 );
        self::send_enrollment_email( $order );
    }

    // ── Branded confirmation email ────────────────────────────────────────────

    private static function send_enrollment_email( $order ) {
        $user  = get_user_by( 'id', $order->get_user_id() );
        $email = $order->get_billing_email();
        $name  = $order->get_billing_first_name();

        // Build course list
        $course_lines = '';
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $course_id  = MolkFilm_Course_Fields::get_course_by_product( $product_id );
            if ( $course_id ) {
                $course_lines .= '<li><a href="' . esc_url( get_permalink( $course_id ) ) . '">' . esc_html( get_the_title( $course_id ) ) . '</a></li>';
            }
        }

        $subject = sprintf( esc_html__( 'تأكيد تسجيلك في ملك فيلم — الطلب #%d', 'molkfilm' ), $order->get_id() );
        $body    = '
<div dir="rtl" style="font-family:Tajawal,Arial,sans-serif;max-width:600px;margin:auto;background:#fff;border-radius:12px;overflow:hidden;">
  <div style="background:#155541;padding:28px 32px;text-align:center;">
    <h1 style="color:#FFD844;margin:0;font-size:1.6rem;">ملك فيلم</h1>
    <p style="color:rgba(255,255,255,.8);margin:8px 0 0;font-size:.9rem;">أكاديمية صناعة الأفلام</p>
  </div>
  <div style="padding:32px;">
    <h2 style="color:#155541;">مرحباً ' . esc_html( $name ) . '!</h2>
    <p>نُبشّرك بنجاح تسجيلك. إليك تفاصيل طلبك:</p>
    <ul>' . wp_kses_post( $course_lines ) . '</ul>
    <p>يمكنك الوصول إلى دوراتك من لوحة التحكم الخاصة بك:</p>
    <p><a href="' . esc_url( get_option( 'woocommerce_myaccount_page_id' ) ? get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) : home_url() ) . '"
          style="background:#FFD844;color:#184133;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
        الذهاب للوحة التحكم
    </a></p>
    <hr style="border:none;border-top:1px solid #e5e5e5;margin:24px 0;">
    <p style="font-size:.8rem;color:#8FB3A6;">ملك فيلم | ' . esc_html( get_option( 'molkfilm_contact_email', '' ) ) . '</p>
  </div>
</div>';

        wp_mail(
            $email,
            $subject,
            $body,
            [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_option( 'molkfilm_site_name', 'ملك فيلم' ) . ' <' . get_option( 'woocommerce_email_from_address', 'no-reply@molkfilm.com' ) . '>',
            ]
        );
    }

    // ── Email styling ────────────────────────────────────────────────────────

    public static function branded_email_styles( $css ) {
        $css .= '
        body { background-color: #F4F7F5 !important; }
        #wrapper { background-color: #F4F7F5 !important; }
        #template_header { background-color: #155541 !important; }
        #template_header h1 { color: #FFD844 !important; }
        #template_footer { background-color: #184133 !important; color: #fff !important; }
        a.button { background-color: #FFD844 !important; color: #184133 !important; }
        ';
        return $css;
    }

    public static function email_header( $email_heading, $email ) {
        // Custom header is handled inside WC email templates; here we only
        // ensure the from address is always the site option.
    }

    // ── Paymob callback handler ───────────────────────────────────────────────

    public static function paymob_callback() {
        // Paymob posts transaction result as JSON to this URL.
        // URL: https://yoursite.com/?wc-api=molkfilm_paymob
        $payload = file_get_contents( 'php://input' );
        $data    = json_decode( $payload, true );

        if ( empty( $data ) ) {
            wp_die( 'No data', 400 );
        }

        // Verify HMAC to ensure the call is genuinely from Paymob
        $hmac_secret = get_option( 'molkfilm_paymob_hmac_secret', '' );
        if ( $hmac_secret && isset( $data['hmac'] ) ) {
            // Paymob HMAC is computed over specific concatenated fields
            // See: https://docs.paymob.com/docs/transaction-webhook
            $fields = [
                $data['obj']['amount_cents']         ?? '',
                $data['obj']['created_at']           ?? '',
                $data['obj']['currency']             ?? '',
                $data['obj']['error_occured']        ?? '',
                $data['obj']['has_parent_transaction']?? '',
                $data['obj']['id']                   ?? '',
                $data['obj']['integration_id']       ?? '',
                $data['obj']['is_3d_secure']         ?? '',
                $data['obj']['is_auth']              ?? '',
                $data['obj']['is_capture']           ?? '',
                $data['obj']['is_refunded']          ?? '',
                $data['obj']['is_standalone_payment']?? '',
                $data['obj']['is_voided']            ?? '',
                $data['obj']['order']['id']          ?? '',
                $data['obj']['owner']                ?? '',
                $data['obj']['pending']              ?? '',
                $data['obj']['source_data']['pan']   ?? '',
                $data['obj']['source_data']['sub_type']?? '',
                $data['obj']['source_data']['type']  ?? '',
                $data['obj']['success']              ?? '',
            ];
            $concat    = implode( '', array_map( fn( $v ) => (string) $v, $fields ) );
            $calc_hmac = hash_hmac( 'sha512', $concat, $hmac_secret );
            if ( ! hash_equals( $calc_hmac, $data['hmac'] ) ) {
                wp_die( 'Invalid HMAC', 403 );
            }
        }

        // Find WC order by Paymob merchant_order_id (stored in order meta)
        if ( ! empty( $data['obj']['order']['merchant_order_id'] ) ) {
            $order_id = absint( $data['obj']['order']['merchant_order_id'] );
            $order    = wc_get_order( $order_id );
            if ( $order && ! empty( $data['obj']['success'] ) && $data['obj']['success'] === true ) {
                $order->payment_complete( $data['obj']['id'] ?? '' );
                $order->add_order_note( 'Paymob payment confirmed. TXN: ' . ( $data['obj']['id'] ?? '' ) );
            }
        }

        status_header( 200 );
        exit( 'OK' );
    }

    // ── PayTabs IPN handler ───────────────────────────────────────────────────

    public static function paytabs_ipn() {
        // PayTabs posts IPN data to: https://yoursite.com/?wc-api=molkfilm_paytabs
        $payload = file_get_contents( 'php://input' );
        $data    = json_decode( $payload, true );

        if ( empty( $data ) ) {
            wp_die( 'No data', 400 );
        }

        // Verify signature using server key
        $server_key = get_option( 'molkfilm_paytabs_server_key', '' );
        if ( $server_key && isset( $data['signature'] ) ) {
            // PayTabs signature = SHA256( server_key + "|" + tran_ref + "|" + cart_id + "|" + cart_amount + "|" + cart_currency )
            $calc_sig = hash( 'sha256', implode( '|', [
                $server_key,
                $data['tran_ref']      ?? '',
                $data['cart_id']       ?? '',
                $data['cart_amount']   ?? '',
                $data['cart_currency'] ?? '',
            ] ) );
            if ( ! hash_equals( $calc_sig, $data['signature'] ) ) {
                wp_die( 'Invalid signature', 403 );
            }
        }

        $order_id = absint( $data['cart_id'] ?? 0 );
        $order    = wc_get_order( $order_id );
        if ( $order && isset( $data['payment_result']['response_status'] ) &&
             $data['payment_result']['response_status'] === 'A' ) {
            $order->payment_complete( $data['tran_ref'] ?? '' );
            $order->add_order_note( 'PayTabs payment confirmed. Ref: ' . ( $data['tran_ref'] ?? '' ) );
        }

        status_header( 200 );
        exit( 'OK' );
    }
}

// =============================================================================
// GATEWAY 1: Paymob (مصر)
// =============================================================================

class MolkFilm_Gateway_Paymob extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'molkfilm_paymob';
        $this->method_title       = esc_html__( 'Paymob (مصر)', 'molkfilm' );
        $this->method_description = esc_html__( 'دفع بالبطاقة عبر بوابة Paymob المصرية', 'molkfilm' );
        $this->has_fields         = false;
        $this->icon               = MOLKFILM_PLUGIN_URI . 'assets/img/paymob-logo.png'; // add logo to assets/img/

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title', esc_html__( 'بطاقة ائتمان / Paymob', 'molkfilm' ) );
        $this->description = $this->get_option( 'description', esc_html__( 'ادفع بأمان عبر Paymob', 'molkfilm' ) );
        $this->enabled     = $this->get_option( 'enabled', 'yes' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled'     => [ 'title' => __( 'تفعيل', 'molkfilm' ),    'type' => 'checkbox', 'default' => 'yes' ],
            'title'       => [ 'title' => __( 'الاسم المعروض', 'molkfilm' ), 'type' => 'text', 'default' => 'بطاقة ائتمان / Paymob' ],
            'description' => [ 'title' => __( 'الوصف', 'molkfilm' ),    'type' => 'textarea', 'default' => 'ادفع بأمان عبر Paymob' ],
        ];
        // NOTE: API keys are read from molkfilm Settings page (wp_options) — not duplicated here.
    }

    public function process_payment( $order_id ) {
        $order       = wc_get_order( $order_id );
        $api_key     = get_option( 'molkfilm_paymob_api_key', '' );
        $integ_id    = get_option( 'molkfilm_paymob_integration_id', '' );
        $iframe_id   = get_option( 'molkfilm_paymob_iframe_id', '' );

        // TODO: Replace empty-string check with live key validation
        if ( ! $api_key || ! $integ_id || ! $iframe_id ) {
            wc_add_notice( esc_html__( 'بوابة Paymob غير مُهيّأة. تواصل مع الدعم.', 'molkfilm' ), 'error' );
            return [ 'result' => 'fail' ];
        }

        try {
            // Step 1: Authenticate → get auth_token
            $auth_response = wp_remote_post( 'https://accept.paymob.com/api/auth/tokens', [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( [ 'api_key' => $api_key ] ),
                'timeout' => 30,
            ] );
            if ( is_wp_error( $auth_response ) ) throw new \Exception( $auth_response->get_error_message() );
            $auth_data = json_decode( wp_remote_retrieve_body( $auth_response ), true );
            $auth_token = $auth_data['token'] ?? '';
            if ( ! $auth_token ) throw new \Exception( 'Paymob auth failed' );

            // Step 2: Register order
            $amount_cents = (int) round( $order->get_total() * 100 );
            $items        = [];
            foreach ( $order->get_items() as $item ) {
                $items[] = [
                    'name'        => $item->get_name(),
                    'amount_cents'=> (string) round( $item->get_total() * 100 ),
                    'description' => $item->get_name(),
                    'item_category' => 'course',
                    'quantity'    => (string) $item->get_quantity(),
                ];
            }
            $order_response = wp_remote_post( 'https://accept.paymob.com/api/ecommerce/orders', [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( [
                    'auth_token'         => $auth_token,
                    'delivery_needed'    => false,
                    'amount_cents'       => $amount_cents,
                    'currency'           => get_option( 'molkfilm_currency', 'EGP' ),
                    'merchant_order_id'  => $order_id,
                    'items'              => $items,
                ] ),
                'timeout' => 30,
            ] );
            if ( is_wp_error( $order_response ) ) throw new \Exception( $order_response->get_error_message() );
            $order_data    = json_decode( wp_remote_retrieve_body( $order_response ), true );
            $paymob_order_id = $order_data['id'] ?? '';
            if ( ! $paymob_order_id ) throw new \Exception( 'Paymob order creation failed' );

            // Step 3: Get payment key
            $billing         = $order->get_address( 'billing' );
            $pk_response     = wp_remote_post( 'https://accept.paymob.com/api/acceptance/payment_keys', [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( [
                    'auth_token'     => $auth_token,
                    'amount_cents'   => $amount_cents,
                    'expiration'     => 3600,
                    'order_id'       => $paymob_order_id,
                    'billing_data'   => [
                        'first_name'    => $billing['first_name'] ?: 'NA',
                        'last_name'     => $billing['last_name']  ?: 'NA',
                        'email'         => $order->get_billing_email(),
                        'phone_number'  => $billing['phone'] ?: '+201000000000',
                        'country'       => $billing['country'] ?: 'EG',
                        'city'          => $billing['city'] ?: 'Cairo',
                        'state'         => $billing['state'] ?: 'Cairo',
                        'street'        => $billing['address_1'] ?: 'NA',
                        'building'      => 'NA',
                        'floor'         => 'NA',
                        'apartment'     => 'NA',
                    ],
                    'currency'       => get_option( 'molkfilm_currency', 'EGP' ),
                    'integration_id' => (int) $integ_id,
                ] ),
                'timeout' => 30,
            ] );
            if ( is_wp_error( $pk_response ) ) throw new \Exception( $pk_response->get_error_message() );
            $pk_data    = json_decode( wp_remote_retrieve_body( $pk_response ), true );
            $payment_key = $pk_data['token'] ?? '';
            if ( ! $payment_key ) throw new \Exception( 'Paymob payment key failed' );

            // Store token so callback can match it
            $order->update_meta_data( '_paymob_payment_key', $payment_key );
            $order->save();

            // Step 4: Redirect to iFrame
            $iframe_url = "https://accept.paymob.com/api/acceptance/iframes/{$iframe_id}?payment_token={$payment_key}";
            $order->update_status( 'pending', esc_html__( 'في انتظار دفع Paymob', 'molkfilm' ) );

            return [
                'result'   => 'success',
                'redirect' => $iframe_url,
            ];

        } catch ( \Exception $e ) {
            wc_add_notice( esc_html__( 'خطأ في معالجة الدفع: ', 'molkfilm' ) . esc_html( $e->getMessage() ), 'error' );
            return [ 'result' => 'fail' ];
        }
    }
}

// =============================================================================
// GATEWAY 2: PayTabs (KSA / UAE)
// =============================================================================

class MolkFilm_Gateway_PayTabs extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'molkfilm_paytabs';
        $this->method_title       = esc_html__( 'PayTabs (السعودية / الإمارات)', 'molkfilm' );
        $this->method_description = esc_html__( 'دفع بالبطاقة عبر بوابة PayTabs', 'molkfilm' );
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title', esc_html__( 'بطاقة ائتمان / PayTabs', 'molkfilm' ) );
        $this->description = $this->get_option( 'description', esc_html__( 'ادفع بأمان عبر PayTabs', 'molkfilm' ) );
        $this->enabled     = $this->get_option( 'enabled', 'no' ); // off by default; enable once keys set

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled'     => [ 'title' => __( 'تفعيل', 'molkfilm' ),        'type' => 'checkbox', 'default' => 'no' ],
            'title'       => [ 'title' => __( 'الاسم المعروض', 'molkfilm' ), 'type' => 'text',     'default' => 'بطاقة ائتمان / PayTabs' ],
            'description' => [ 'title' => __( 'الوصف', 'molkfilm' ),        'type' => 'textarea', 'default' => 'ادفع بأمان عبر PayTabs' ],
        ];
    }

    public function process_payment( $order_id ) {
        $order       = wc_get_order( $order_id );
        $profile_id  = get_option( 'molkfilm_paytabs_profile_id', '' );
        $server_key  = get_option( 'molkfilm_paytabs_server_key', '' );

        // TODO: Replace empty-string check with live key validation
        if ( ! $profile_id || ! $server_key ) {
            wc_add_notice( esc_html__( 'بوابة PayTabs غير مُهيّأة. تواصل مع الدعم.', 'molkfilm' ), 'error' );
            return [ 'result' => 'fail' ];
        }

        try {
            $billing   = $order->get_address( 'billing' );
            $region    = 'SAU'; // PayTabs region: SAU, ARE, EGY — TODO: configure per merchant

            $response = wp_remote_post( "https://secure.paytabs.com/payment/request", [
                'headers' => [
                    'Content-Type'   => 'application/json',
                    'authorization'  => $server_key,
                ],
                'body' => wp_json_encode( [
                    'profile_id'     => $profile_id,
                    'tran_type'      => 'sale',
                    'tran_class'     => 'ecom',
                    'cart_id'        => (string) $order_id,
                    'cart_currency'  => $order->get_currency(),
                    'cart_amount'    => floatval( $order->get_total() ),
                    'cart_description' => esc_html__( 'دورة تدريبية — ملك فيلم', 'molkfilm' ),
                    'callback'       => add_query_arg( 'wc-api', 'molkfilm_paytabs', home_url( '/' ) ),
                    'return'         => $this->get_return_url( $order ),
                    'customer_details' => [
                        'name'    => $billing['first_name'] . ' ' . $billing['last_name'],
                        'email'   => $order->get_billing_email(),
                        'phone'   => $billing['phone'] ?: '+966500000000',
                        'street1' => $billing['address_1'] ?: 'NA',
                        'city'    => $billing['city'] ?: 'Riyadh',
                        'state'   => $billing['state'] ?: 'Riyadh',
                        'country' => $billing['country'] ?: 'SA',
                        'zip'     => $billing['postcode'] ?: '00000',
                        'ip'      => WC_Geolocation::get_ip_address(),
                    ],
                ] ),
                'timeout' => 30,
            ] );

            if ( is_wp_error( $response ) ) throw new \Exception( $response->get_error_message() );

            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! empty( $data['redirect_url'] ) ) {
                $order->update_status( 'pending', esc_html__( 'في انتظار دفع PayTabs', 'molkfilm' ) );
                return [
                    'result'   => 'success',
                    'redirect' => $data['redirect_url'],
                ];
            }

            throw new \Exception( $data['message'] ?? 'PayTabs request failed' );

        } catch ( \Exception $e ) {
            wc_add_notice( esc_html__( 'خطأ في معالجة الدفع: ', 'molkfilm' ) . esc_html( $e->getMessage() ), 'error' );
            return [ 'result' => 'fail' ];
        }
    }
}

/*
 * GATEWAY 3: PayPal
 * ─────────────────
 * WooCommerce ships with PayPal Standard (woocommerce-gateway-paypal-standard).
 * The official "WooCommerce PayPal Payments" plugin (free, by WooCommerce) is
 * recommended.  Our Settings page exposes the Client ID / Secret / Mode so the
 * merchant never needs to dig through WC settings.
 *
 * On plugin activation we pre-configure the PayPal gateway options:
 */
add_action( 'molkfilm_after_setup', function () {
    $client_id = get_option( 'molkfilm_paypal_client_id', '' );
    $secret    = get_option( 'molkfilm_paypal_secret', '' );
    $mode      = get_option( 'molkfilm_paypal_mode', 'sandbox' );

    if ( $client_id ) {
        // WooCommerce PayPal Payments plugin option keys
        update_option( 'woocommerce-ppcp-settings', array_merge(
            get_option( 'woocommerce-ppcp-settings', [] ),
            [
                'client_id'        => $client_id,
                'client_secret'    => $secret,
                'sandbox_on'       => ( $mode === 'sandbox' ) ? '1' : '0',
                'enabled'          => 'yes',
            ]
        ) );
    }
} );
