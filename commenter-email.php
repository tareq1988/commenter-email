<?php
/**
 * Plugin Name: Commenter Email
 * Plugin URI: http://tareq.weDevs.com
 * Description: Send email to post commenters
 * Author: Tareq Hasan
 * Author URI: http://tareq.weDevs.com
 * Version: 0.1
 */

/**
 * Commenter Email class
 */
class WeDevs_Commenter_Email {

    function __construct() {
        add_action( 'wp_ajax_import-commenter', array($this, 'admin_ajax') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_menu() {
        add_options_page( __( 'Commenter Email', 'wedevs_ce' ), __( 'Commenter Email', 'wedevs_ce' ), 'manage_options', 'wedevs_ce', array($this, 'plugin_page') );
    }

    function get_post_dropdown( $post_id = 0 ) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'order' => 'DESC',
            'orderby' => 'comment_count',
        );
        $query = new WP_Query();
        $posts = $query->query( $args );

        $select = '<select name="post_id">';
        $select .= '<option value="-1">All Post</option>';
        foreach ($posts as $post) {
            if ( $post->comment_count != 0 ) {
                $select .= sprintf( '<option value="%d"%s>%s (%d)</option>', $post->ID, selected( $post_id, $post->ID, false ), esc_attr( $post->post_title ), $post->comment_count );
            }
        }
        $select .= '<select>';

        return $select;
    }

    function plugin_page() {
        global $wpdb;

        $users = array();
        $format = '';
        $glue = "\r\n";
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $type = isset( $_POST['type'] ) ? $_POST['type'] : '';
        $where = '';

        if ( isset( $_POST['submit_next'] ) ) {

            if ( $post_id != '-1' ) {
                $where = 'WHERE comment_post_ID = %d';
            }

            $sql = "SELECT distinct(`comment_author_email`) as email, comment_author as name
                    FROM $wpdb->comments $where";

            $result = $wpdb->get_results( $wpdb->prepare( $sql, $post_id ) );
            //var_dump( $result );
            //var_dump( $_POST );

            if ( $result ) {
                switch ($type) {
                    case 'sendpress':
                        $format = '%1$s, %2$s';
                        break;

                    case 'wysija':
                        $format = '%1$s; %2$s';
                        break;

                    default:
                        $format = '%2$s <%1$s>';
                        $glue = ",\r\n";
                        break;
                }

                foreach ($result as $user) {
                    if ( !empty( $user->email ) ) {
                        $users[] = sprintf( $format, $user->email, $user->name );
                    }
                }
            }
        }

        if ( isset( $_POST['send_email'] ) ) {
            $this->send_mail();
            echo '<div class="updated settings-error">';
            echo '<p><strong>' . __( 'E-Mail Sent.', 'wedevs_ce' ) . '</strong></p>';
            echo '</div>';
        }

        //var_dump( $type, $format, $glue );
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br></div>
            <h2><?php _e( 'Email Commenters', 'wedevs_ce' ) ?></h2>

            <?php if ( !isset( $_POST['submit_next'] ) ) { ?>
                <form name="" method="post">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Post', 'wedevs_ce' ); ?></th>
                            <td>
                                <?php echo $this->get_post_dropdown( $post_id ); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">&nbsp;</th>
                            <td>
                                <input type="submit" class="button-primary" name="submit_next" value="<?php echo esc_attr_e( '', 'wedevs_ce' ); ?>Next &raquo;" />
                            </td>
                        </tr>
                    </table>
                </form>
            <?php } else { ?>

                <form name="" method="post">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e( 'To', 'wedevs_ce' ); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="email_to" value="<?php echo esc_attr( array_pop( $users ) ); ?>" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Bcc', 'wedevs_ce' ); ?></th>
                            <td>
                                <textarea name="email_bcc" rows="5" cols="55" placeholder="Enter comma separated email address"><?php echo implode( $glue, $users ); ?></textarea>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'From Name', 'wedevs_ce' ); ?></th>
                            <td>
                                <input class="regular-text" type="text" placeholder="Enter from name. e.g: John Doe" name="email_from_name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'From Email', 'wedevs_ce' ); ?></th>
                            <td>
                                <input class="regular-text" type="text" placeholder="Enter from email. e.g: john@doe.com" name="email_from_email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Email Subject', 'wedevs_ce' ); ?></th>
                            <td>
                                <input class="regular-text" type="text" value="Email Subject" name="email_subject" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Email Message', 'wedevs_ce' ); ?></th>
                            <td>
                                <?php wp_editor( 'Add email body...', 'email_message', array('textarea_rows' => 15) ); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">&nbsp;</th>
                            <td>
                                <a class="button" href="<?php echo admin_url( 'options-general.php?page=wedevs_ce' ); ?>">&laquo; Back</a>
                                <input type="submit" class="button-primary" name="send_email" value="Send Email &raquo;" />
                            </td>
                        </tr>
                    </table>
                </form>
            <?php } ?>
        </div>
        <?php
    }

    function send_mail() {
        global $phpmailer;

        // (Re)create it, if it's gone missing
        if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
            require_once ABSPATH . WPINC . '/class-phpmailer.php';
            require_once ABSPATH . WPINC . '/class-smtp.php';
            $phpmailer = new PHPMailer( true );
        }

        $to = trim( $_POST['email_to'] );
        $bcc = trim( $_POST['email_bcc'] );
        $from_name = trim( stripslashes( $_POST['email_from_name'] ) );
        $from_email = trim( $_POST['email_from_email'] );
        $subject = trim( $_POST['email_subject'] );
        $message = stripslashes( $_POST['email_message'] );
        $headers = array();
        $attachments = array();

        // Compact the input, apply the filters, and extract them back out
        extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );

        // Empty out the values that may be set
        $phpmailer->ClearAddresses();
        $phpmailer->ClearAllRecipients();
        $phpmailer->ClearAttachments();
        $phpmailer->ClearBCCs();
        $phpmailer->ClearCCs();
        $phpmailer->ClearCustomHeaders();
        $phpmailer->ClearReplyTos();

        if ( empty( $from_email ) ) {
            // Get the site domain and get rid of www.
            $sitename = strtolower( $_SERVER['SERVER_NAME'] );
            if ( substr( $sitename, 0, 4 ) == 'www.' ) {
                $sitename = substr( $sitename, 4 );
            }

            $from_email = 'wordpress@' . $sitename;
        }

        $phpmailer->From = apply_filters( 'wp_mail_from', $from_email );
        $phpmailer->FromName = apply_filters( 'wp_mail_from_name', $from_name );

        // Set destination addresses
        if ( !is_array( $to ) ) {
            $to = explode( ',', $to );
        }

        foreach ((array) $to as $recipient) {
            try {
                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                $recipient_name = '';
                if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
                    if ( count( $matches ) == 3 ) {
                        $recipient_name = $matches[1];
                        $recipient = $matches[2];
                    }
                }
                $phpmailer->AddAddress( $recipient, $recipient_name );
            } catch (phpmailerException $e) {
                continue;
            }
        }


        if ( !empty( $bcc ) ) {
            if ( !is_array( $bcc ) ) {
                $bcc = explode( ',', $bcc );
            }

            foreach ((array) $bcc as $recipient) {
                try {
                    // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                    $recipient_name = '';
                    if ( preg_match( '/(.*)<(.+)>/', $recipient, $matches ) ) {
                        if ( count( $matches ) == 3 ) {
                            $recipient_name = $matches[1];
                            $recipient = $matches[2];
                        }
                    }

                    $phpmailer->AddBcc( $recipient, $recipient_name );
                } catch (phpmailerException $e) {
                    continue;
                }
            }
        }

        // Set mail's subject and body
        $phpmailer->Subject = $subject;
        $phpmailer->Body = $message;

        // Set to use PHP's mail()
        $phpmailer->IsMail();

        // Set Content-Type and charset
        // If we don't have a content-type from the input headers
        if ( !isset( $content_type ) )
            $content_type = 'text/html';

        $content_type = apply_filters( 'wp_mail_content_type', $content_type );

        $phpmailer->ContentType = $content_type;

        // Set whether it's plaintext, depending on $content_type
        if ( 'text/html' == $content_type )
            $phpmailer->IsHTML( true );

        // If we don't have a charset from the input headers
        if ( !isset( $charset ) )
            $charset = get_bloginfo( 'charset' );

        // Set the content-type and charset
        $phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

        do_action_ref_array( 'phpmailer_init', array(&$phpmailer) );

        // Send!
        try {
            $phpmailer->Send();
        } catch (phpmailerException $e) {
            return false;
        }
    }

}

$email_commenter = new WeDevs_Commenter_Email();