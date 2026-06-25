<?php
/**
 * WooCommerce email footer override — Molk Film branded.
 * Place: {theme}/woocommerce/emails/email-footer.php
 */

defined( 'ABSPATH' ) || exit;

$site_name = get_option( 'molkfilm_site_name',    'ملك فيلم' );
$email     = get_option( 'molkfilm_contact_email', '' );
$facebook  = get_option( 'molkfilm_facebook',      '' );
$instagram = get_option( 'molkfilm_instagram',     '' );
$youtube   = get_option( 'molkfilm_youtube',       '' );
?>
  </div><!-- /.email-body -->

  <div class="email-footer">
    <p style="margin:0 0 8px;">
      &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?> — <?php esc_html_e( 'جميع الحقوق محفوظة', 'molkfilm' ); ?>
    </p>
    <?php if ( $email ) : ?>
      <p style="margin:0 0 8px;">
        <a href="mailto:<?php echo esc_attr( $email ); ?>" style="color:#FFD844;"><?php echo esc_html( $email ); ?></a>
      </p>
    <?php endif; ?>
    <p style="margin:0;font-size:.75rem;">
      <?php if ( $facebook )  : ?><a href="<?php echo esc_url( $facebook );  ?>" style="color:rgba(255,255,255,.6);margin:0 6px;">Facebook</a><?php endif; ?>
      <?php if ( $instagram ) : ?><a href="<?php echo esc_url( $instagram ); ?>" style="color:rgba(255,255,255,.6);margin:0 6px;">Instagram</a><?php endif; ?>
      <?php if ( $youtube )   : ?><a href="<?php echo esc_url( $youtube );   ?>" style="color:rgba(255,255,255,.6);margin:0 6px;">YouTube</a><?php endif; ?>
    </p>
    <p style="margin:8px 0 0;font-size:.72rem;color:rgba(255,255,255,.4);">
      <?php esc_html_e( 'تلقّيت هذا البريد لأنك سجّلت في ملك فيلم. إذا كان هذا خطأً يرجى التواصل معنا.', 'molkfilm' ); ?>
    </p>
  </div>
</div><!-- /.email-wrap -->
</body>
</html>
