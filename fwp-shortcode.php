<?php

/**
 * Plugin Name: FORTE-WP Shortcode
 * Plugin URI: https://www.forte.nl
 * Description: Defines some demo's of shortcodes
 * Version: 1.0.0
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

function fwp_hello() {
	return 'Hello guys!';
}

add_shortcode( 'hello', 'fwp_hello' );

function fwp_facebook() {
	$link  = get_permalink();
	$text  = '<a href="https://www.facebook.com/sharer/sharer.php?u=';
	$text .= esc_url( $link ) . '" target="_blank">';
	$text .= 'Share on facebook';
	$text .= '</a>';
	return $text;
}

add_shortcode( 'fb', 'fwp_facebook' );

function fwp_show_post( $atts ) {

	$atts = shortcode_atts(
		array(
			'id'         => 0,
			'color'      => 'black',
			'background' => 'lightgray',
		),
		$atts
	);

	ob_start();

	$show_post = get_post( absint( $atts['id'] ) );
	if ( $show_post ) {
		$title   = get_the_title( $show_post );
		$perma   = get_permalink( $show_post );
		$excerpt = wp_trim_excerpt( '', $show_post );

		$link  = '<a style="color:' . esc_attr( $atts['color'] );
		$link .= '" href="' . esc_url( $perma ) . ';">';

		echo '<div style="padding:0 10px 10px 10px;';
		echo 'color:' . esc_html( $atts['color'] ) . ';';
		echo 'background-color:' . esc_html( $atts['background'] ) . ';">';
		echo '<h4>' . $link . esc_html( $title ) . '</a></h4>';
		echo '<div>' . esc_html( $excerpt ) . ' ... ' . $link . 'Lees verder</a></div>';
		echo '</div>';
	}

	return ob_get_clean();
}

add_shortcode( 'show-post', 'fwp_show_post' );

function val_exchange( $atts ) {

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
		$rates = json_decode( wp_remote_retrieve_body( $remote ) );
		// print_r($rates);
		$rate_eur = floatval( $rates->$val->inverseRate );
		$rate_eur = number_format_i18n( $rate_eur, 3 );
		$date     = wp_date( 'd M Y, H:i', strtotime( $rates->$val->date ) );

		echo esc_html( "€ $rate_eur ($date) " );
	}
	return ob_get_clean();
}

add_shortcode( 'exchange', 'val_exchange' );

function fwp_form_generator() {

	ob_start();

	// delete_transient( 'fwp_database' );

	if ( current_user_can( 'administrator' ) ) {

		if ( isset( $_POST['_wpnonce'] ) &&
				isset( $_POST['voornaam'] ) &&
				isset( $_POST['achternaam'] ) &&
				isset( $_POST['woonplaats'] ) &&
				wp_verify_nonce( $_POST['_wpnonce'], 'fwp_add_member' ) ) {

			$row = array(
				'voornaam'   => sanitize_text_field( wp_unslash( $_POST['voornaam'] ) ),
				'achternaam' => sanitize_text_field( wp_unslash( $_POST['achternaam'] ) ),
				'woonplaats' => sanitize_text_field( wp_unslash( $_POST['woonplaats'] ) ),
			);

			fwp_add_to_database( $row );
		}

		echo '<div><form method="POST" style="display:flex; flex-direction:row; gap:10px;">';
		wp_nonce_field( 'fwp_add_member' );
		echo '<div><input type="text" name="voornaam" placeholder="Voornaam" /></div>';
		echo '<div><input type="text" name="achternaam" placeholder="Achternaam" /></div>';
		echo '<div><input type="text" name="woonplaats" placeholder="Woonplaats" /></div>';
		echo '<div><input type="submit" value="Toevoegen" /></div>';
		echo '</form></div>';
	}

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

function fwp_get_database() {
	$database = ( get_transient( 'fwp_database' ) ) ? get_transient( 'fwp_database' ) : array();
	return $database;
}

function fwp_add_to_database( $row ) {
	$database   = fwp_get_database();
	$database[] = $row;
	$columns    = array_column( $database, 'voornaam' );
	array_multisort( $columns, SORT_ASC, $database );
	set_transient( 'fwp_database', $database, 0 );
}
