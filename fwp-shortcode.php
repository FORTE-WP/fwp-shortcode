<?php
/**
 * Plugin Name: FORTE-WP Shortcode
 * Plugin URI: https://www.forte.nl
 * Description: Defines some demo's of shortcodes
 * Version: 1.1.5
 * Author: FORTE web publishing
 * Author URI: https://www.forte.nl
 * Text Domain: fwp
 * Domain Path: /lang
 *
 * @package fwp-shortcode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple shortcode callback
 *
 * @return string
 */
function fwp_hello() {
	return 'Hello guys!';
}

add_shortcode( 'hello', 'fwp_hello' );

/**
 * Shortcode callback for share post on facebook without cookies
 *
 * @return string
 */
function fwp_facebook() {
    global $post;
	$link  = get_permalink( $post );
	$text  = '<a href="https://www.facebook.com/sharer/sharer.php?u=';
	$text .= esc_url( $link ) . '" target="_blank">';
	$text .= 'Share on facebook';
	$text .= '</a>';
	return $text;
}

add_shortcode( 'fb', 'fwp_facebook' );

/**
 * Shortcode callback for displaying selected post titles and excerpts
 *
 * @param array $atts Attributes for shortcode.
 * @return string
 */
function fwp_show_post( $atts ) {

	$atts = shortcode_atts( // allowed attributes.
		array(
			'id'         => 0,
			'color'      => 'black',
			'background' => 'lightgray',
		),
		$atts
	);

	// Start output buffering.
	ob_start();

	$show_post = get_post( absint( $atts['id'] ) );
	if ( $show_post ) {
		$title   = get_the_title( $show_post );
		$link   = get_permalink( $show_post );
		$excerpt = wp_trim_excerpt( '', $show_post );

		?>
        <style>
            #show-post-<?php echo esc_attr( $atts['id'] ) ?> { color: <?php echo esc_attr( $atts['color'] ) ?>; background: <?php echo esc_attr( $atts['background'] ) ?> ;padding: 0 10px 10px 10px; }
            #show-post-<?php echo esc_attr( $atts['id'] ) ?>:hover { background-color: gray }
            #show-post-<?php echo esc_attr( $atts['id'] ) ?> a {color: <?php echo esc_attr( $atts['color'] ) ?> }
            </style>
		<div id = "show-post-<?php echo $atts['id'] ?>">
		<h3><a href="<?php echo esc_url( $link ) ?>" ><?php echo esc_html( $title ); ?></a></h3>
		<div><?php echo esc_html( $excerpt ) ?>...<a href="<?php echo esc_url( $link ) ?>">Lees verder</a></div>
		</div>
		<?php
	}

	// Return buffered output.
	return ob_get_clean();
}

add_shortcode( 'show-post', 'fwp_show_post' );

/**
 * Shortcode callback for displaying most recent exchange rate for any valuta to
 * the Euro
 *
 * @param array $atts Attributes for shortcode.
 * @return string
 */
function fwp_exchange( $atts ) {

	$atts = shortcode_atts(
		array(
			'val' => 'EUR',
		),
		$atts
	);

	$val = $atts['val'];

	ob_start();
	$uri    = 'http://www.floatrates.com/daily/eur.json';
	$remote = wp_remote_get( $uri );
	if ( $remote ) {
		// print_r( $remote ); // for debugging. Disabled.
		$rates    = json_decode( wp_remote_retrieve_body( $remote ) );
		$rate_eur = floatval( $rates->$val->inverseRate );
		$rate_eur = number_format_i18n( $rate_eur, 3 );
		$date     = wp_date( 'd M Y, H:i', strtotime( $rates->$val->date ) );

		echo esc_html( "€ $rate_eur ($date) " );
	}
	return ob_get_clean();
}

add_shortcode( 'exchange', 'fwp_exchange' );

/**
 * Simple demontration for form handling in a ahortcode. This shortcode makes
 * use of aditional data handling functions
 *
 * @return string
 */
function fwp_form_generator() {

	ob_start();

	// delete_transient( 'fwp_database' ); // Used to reset database. Disabled.

	// check on user rights.
	if ( current_user_can( 'administrator' ) ) {

		// check if all form fields are provided and nonce is validated.
		if ( isset( $_POST['_wpnonce'] ) &&
				isset( $_POST['voornaam'] ) &&
				isset( $_POST['achternaam'] ) &&
				isset( $_POST['woonplaats'] ) &&
				wp_verify_nonce( $_POST['_wpnonce'], 'fwp_add_member' ) ) {

			// sanitize form fields and vreate row for addition to database.
			$row = array(
				'voornaam'   => sanitize_text_field( wp_unslash( $_POST['voornaam'] ) ),
				'achternaam' => sanitize_text_field( wp_unslash( $_POST['achternaam'] ) ),
				'woonplaats' => sanitize_text_field( wp_unslash( $_POST['woonplaats'] ) ),
			);

			// add row to database in separate function / data layer.
			fwp_add_to_database( $row );
		}

		// echo all empty form fields.
		echo '<div><form method="POST" style="display:flex; flex-direction:row; gap:10px;">';
		wp_nonce_field( 'fwp_add_member' );
		echo '<div><input type="text" name="voornaam" placeholder="Voornaam" /></div>';
		echo '<div><input type="text" name="achternaam" placeholder="Achternaam" /></div>';
		echo '<div><input type="text" name="woonplaats" placeholder="Woonplaats" /></div>';
		echo '<div><input type="submit" value="Toevoegen" /></div>';
		echo '</form></div>';

		// disable refresh with reposting values
		echo '<script>if( window.history.replaceState ) {window.history.replaceState( null, null, window.location.href );}</script>';
	}

	// echo all database rows if user is logged in.
	if ( is_user_logged_in() ) {
		$database = fwp_get_database();

		echo '<div style="display:flex; flex-direction:row; flex-wrap:wrap;">';
		foreach ( $database as $row ) {
			echo '<div style="flex:30%">' . esc_html( $row['voornaam'] ) . '</div>';
			echo '<div style="flex:30%">' . esc_html( $row['achternaam'] ) . '</div>';
			echo '<div style="flex:30%">' . esc_html( $row['woonplaats'] ) . '</div>';
			if ( current_user_can( 'administrator' ) ) {
				echo '<div style="flex:10%">&#10005;</div>';
			}
		}
		echo '</div>';
	} else {
			echo '<p>Je moet ingelogd zijn om deze pagina te bekijken</p>';
	}
	return ob_get_clean();
}

add_shortcode( 'form', 'fwp_form_generator' );

/**
 * Database simulation with transient
 *
 * @return array
 */
function fwp_get_database() {
	$database = ( get_transient( 'fwp_database' ) ) ? get_transient( 'fwp_database' ) : array();
	return $database;
}
/**
 * Adds row to simulated database and sorts on name
 *
 * @param array $row array of 'voornaam', 'achternaam' and 'woonplaats'.
 */
function fwp_add_to_database( $row ) {
	$database   = fwp_get_database();
	$database[] = $row;
	$columns    = array_column( $database, 'voornaam' );
	array_multisort( $columns, SORT_ASC, $database );
	set_transient( 'fwp_database', $database, 0 );
}
