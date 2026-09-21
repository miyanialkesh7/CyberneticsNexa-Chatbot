<?php
/**
 * Settings: defaults, reading and sanitising the single `cnx_settings` option.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CNX_Settings {

	const MAX_REPLIES = 12;

	private static $settings = null;

	public static function positions() {
		return array( 'bottom-right', 'bottom-left', 'top-right', 'top-left' );
	}

	public static function animations() {
		return array(
			'pulse'  => __( 'Pulse ring (live)', 'cyberneticsnexa-chatbot' ),
			'bounce' => __( 'Bounce', 'cyberneticsnexa-chatbot' ),
			'wiggle' => __( 'Wiggle / ring', 'cyberneticsnexa-chatbot' ),
			'glow'   => __( 'Glow', 'cyberneticsnexa-chatbot' ),
			'float'  => __( 'Float', 'cyberneticsnexa-chatbot' ),
			'none'   => __( 'None', 'cyberneticsnexa-chatbot' ),
		);
	}

	public static function defaults() {
		return array(
			'enabled'           => 1,

			// Content.
			'logo_id'           => 0,
			'logo_url'          => '',
			'bot_title'         => 'CyberneticsNexa Assistant',
			'bot_slogan'        => 'Usually replies in a few seconds',
			'hello_message'     => 'Hello. I can help with pricing ranges, the roles we staff, how our governance works or booking a call. What would you like to start with?',
			'input_placeholder' => 'Type your question',
			'fallback_message'  => "Thanks for your message. I'm not sure I have the answer to that yet. Pick one of the options above, or talk to a live person.",

			// Quick replies (message title + message content).
			'replies'           => array(
				array(
					'title'    => 'Pricing',
					'type'     => 'reply',
					'content'  => 'Pricing depends on scope, team size and length of engagement. Share a few details and we will send you a tailored range.',
					'url'      => '',
					'keywords' => 'price, cost, rate, quote, budget',
				),
				array(
					'title'    => 'Roles',
					'type'     => 'reply',
					'content'  => 'We staff engineers, designers, project managers, QA and data specialists. Tell us what you need and we will match the right people.',
					'url'      => '',
					'keywords' => 'team, staff, hire, developer, engineer',
				),
				array(
					'title'    => 'Governance',
					'type'     => 'reply',
					'content'  => 'Every engagement has a named lead, weekly reporting and a clear escalation path, so you always know who is accountable.',
					'url'      => '',
					'keywords' => 'process, reporting, security, compliance',
				),
				array(
					'title'    => 'Book a call',
					'type'     => 'url',
					'content'  => '',
					'url'      => home_url( '/contact/' ),
					'keywords' => 'meeting, schedule, appointment, demo',
				),
				array(
					'title'    => 'Careers',
					'type'     => 'reply',
					'content'  => 'We are always looking for talented people. Send your CV to careers@example.com and we will be in touch.',
					'url'      => '',
					'keywords' => 'job, jobs, vacancy, work with us',
				),
			),

			// WhatsApp live person.
			'whatsapp_enabled'  => 1,
			'whatsapp_number'   => '',
			'whatsapp_label'    => 'Talk to a live person',
			'whatsapp_message'  => 'Hello, I would like to talk to someone.',
			'whatsapp_reply'    => "Connecting you to a live person on WhatsApp. If it doesn't open automatically, tap the button below.",
			'whatsapp_button'   => 'Open WhatsApp',

			// Floating launcher button.
			'launcher_text'     => 'Chat With Us',
			'launcher_style'    => 'pill', // pill | icon.
			'launcher_icon'     => 'dot',  // dot | chat | logo | custom.
			'launcher_icon_id'  => 0,
			'launcher_icon_url' => '',
			'animation'         => 'pulse',
			'show_online_dot'   => 1,

			// Placement & behaviour.
			'position'          => 'bottom-right',
			'offset_x'          => 24,
			'offset_y'          => 24,
			'popup_width'       => 370,
			'auto_open_delay'   => 0,
			'hide_on_mobile'    => 0,
			'z_index'           => 99999,
			'exclude_ids'       => '',

			// Colours.
			'color_primary'      => '#2563eb',
			'color_secondary'    => '#06b6d4',
			'color_header'       => '#0b1020',
			'color_launcher_bg'  => '#0b1020',
			'color_launcher_text' => '#ffffff',
			'color_send'         => '#0b1020',
		);
	}

	/**
	 * Saved settings merged over defaults.
	 */
	public static function get() {
		if ( null !== self::$settings ) {
			return self::$settings;
		}
		$saved = get_option( CNX_OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		self::$settings = array_merge( self::defaults(), $saved );
		return self::$settings;
	}

	public static function activate() {
		if ( false !== get_option( CNX_OPTION, false ) ) {
			return;
		}
		// Import settings from the earlier "InstinctX Chatbot" build, if it was installed.
		$old = get_option( 'ixc_settings', false );
		if ( is_array( $old ) ) {
			$old = array_merge( self::defaults(), $old );
			if ( isset( $old['bot_title'] ) && 'InstinctX Assistant' === $old['bot_title'] ) {
				$old['bot_title'] = 'CyberneticsNexa Assistant';
			}
			add_option( CNX_OPTION, self::sanitize( $old ) );
			return;
		}
		add_option( CNX_OPTION, self::defaults() );
	}

	/**
	 * Logo URL (uploaded image or bundled default SVG).
	 */
	public static function logo_url( $s = null ) {
		$s = $s ? $s : self::get();
		$url = self::image_url( $s['logo_id'], 'medium' );
		if ( $url ) {
			return $url;
		}
		if ( ! empty( $s['logo_url'] ) ) {
			return $s['logo_url'];
		}
		return CNX_URL . 'assets/default-logo.svg';
	}

	/**
	 * URL of a media-library image, or '' when missing. Falls back to the full-size file
	 * (needed for SVG / images without generated sizes).
	 */
	public static function image_url( $id, $size = 'medium' ) {
		$id = (int) $id;
		if ( ! $id ) {
			return '';
		}
		$url = wp_get_attachment_image_url( $id, $size );
		if ( ! $url ) {
			$url = wp_get_attachment_url( $id );
		}
		return $url ? $url : '';
	}

	/**
	 * https://wa.me link, or empty string when no number is configured.
	 */
	public static function whatsapp_url( $s = null ) {
		$s = $s ? $s : self::get();
		if ( empty( $s['whatsapp_enabled'] ) ) {
			return '';
		}
		$number = preg_replace( '/\D+/', '', (string) $s['whatsapp_number'] );
		if ( '' === $number ) {
			return '';
		}
		$url = 'https://wa.me/' . $number;
		if ( '' !== trim( (string) $s['whatsapp_message'] ) ) {
			$url .= '?text=' . rawurlencode( $s['whatsapp_message'] );
		}
		return $url;
	}

	/* ------------------------------------------------------------------
	 * Sanitising
	 * ---------------------------------------------------------------- */

	private static function text( $in, $key, $default ) {
		return isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : $default;
	}

	private static function area( $in, $key, $default ) {
		return isset( $in[ $key ] ) ? sanitize_textarea_field( $in[ $key ] ) : $default;
	}

	private static function enum( $in, $key, $allowed, $default ) {
		return ( isset( $in[ $key ] ) && in_array( $in[ $key ], $allowed, true ) ) ? $in[ $key ] : $default;
	}

	private static function num( $in, $key, $min, $max, $default ) {
		if ( ! isset( $in[ $key ] ) || '' === $in[ $key ] ) {
			return $default;
		}
		return max( $min, min( $max, (int) $in[ $key ] ) );
	}

	private static function color( $in, $key, $default ) {
		$c = isset( $in[ $key ] ) ? sanitize_hex_color( $in[ $key ] ) : '';
		return $c ? $c : $default;
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$in  = is_array( $input ) ? $input : array();
		$out = array();

		$out['enabled'] = empty( $in['enabled'] ) ? 0 : 1;

		// Content.
		$out['logo_id']           = isset( $in['logo_id'] ) ? absint( $in['logo_id'] ) : 0;
		$out['logo_url']          = isset( $in['logo_url'] ) ? esc_url_raw( trim( $in['logo_url'] ) ) : '';
		$out['bot_title']         = self::text( $in, 'bot_title', $d['bot_title'] );
		$out['bot_slogan']        = self::text( $in, 'bot_slogan', $d['bot_slogan'] );
		$out['hello_message']     = self::area( $in, 'hello_message', $d['hello_message'] );
		$out['input_placeholder'] = self::text( $in, 'input_placeholder', $d['input_placeholder'] );
		$out['fallback_message']  = self::area( $in, 'fallback_message', $d['fallback_message'] );

		// Quick replies.
		$replies = array();
		if ( ! empty( $in['replies'] ) && is_array( $in['replies'] ) ) {
			foreach ( $in['replies'] as $r ) {
				if ( ! is_array( $r ) ) {
					continue;
				}
				$title = isset( $r['title'] ) ? sanitize_text_field( $r['title'] ) : '';
				if ( '' === $title ) {
					continue;
				}
				$replies[] = array(
					'title'    => $title,
					'type'     => ( isset( $r['type'] ) && in_array( $r['type'], array( 'reply', 'url', 'whatsapp' ), true ) ) ? $r['type'] : 'reply',
					'content'  => isset( $r['content'] ) ? sanitize_textarea_field( $r['content'] ) : '',
					'url'      => isset( $r['url'] ) ? esc_url_raw( trim( $r['url'] ) ) : '',
					'keywords' => isset( $r['keywords'] ) ? sanitize_text_field( $r['keywords'] ) : '',
				);
				if ( count( $replies ) >= self::MAX_REPLIES ) {
					break;
				}
			}
		}
		$out['replies'] = $replies;

		// WhatsApp.
		$out['whatsapp_enabled'] = empty( $in['whatsapp_enabled'] ) ? 0 : 1;
		$out['whatsapp_number']  = isset( $in['whatsapp_number'] ) ? preg_replace( '/\D+/', '', $in['whatsapp_number'] ) : '';
		$out['whatsapp_label']   = self::text( $in, 'whatsapp_label', $d['whatsapp_label'] );
		$out['whatsapp_message'] = self::text( $in, 'whatsapp_message', $d['whatsapp_message'] );
		$out['whatsapp_reply']   = self::area( $in, 'whatsapp_reply', $d['whatsapp_reply'] );
		$out['whatsapp_button']  = self::text( $in, 'whatsapp_button', $d['whatsapp_button'] );

		// Launcher.
		$out['launcher_text']    = self::text( $in, 'launcher_text', $d['launcher_text'] );
		$out['launcher_style']   = self::enum( $in, 'launcher_style', array( 'pill', 'icon' ), $d['launcher_style'] );
		$out['launcher_icon']    = self::enum( $in, 'launcher_icon', array( 'dot', 'chat', 'logo', 'custom' ), $d['launcher_icon'] );
		$out['launcher_icon_id'] = isset( $in['launcher_icon_id'] ) ? absint( $in['launcher_icon_id'] ) : 0;
		$out['launcher_icon_url'] = isset( $in['launcher_icon_url'] ) ? esc_url_raw( trim( $in['launcher_icon_url'] ) ) : '';
		$out['animation']        = self::enum( $in, 'animation', array_keys( self::animations() ), $d['animation'] );
		$out['show_online_dot']  = empty( $in['show_online_dot'] ) ? 0 : 1;

		// Placement.
		$out['position']        = self::enum( $in, 'position', self::positions(), $d['position'] );
		$out['offset_x']        = self::num( $in, 'offset_x', 0, 400, $d['offset_x'] );
		$out['offset_y']        = self::num( $in, 'offset_y', 0, 400, $d['offset_y'] );
		$out['popup_width']     = self::num( $in, 'popup_width', 300, 520, $d['popup_width'] );
		$out['auto_open_delay'] = self::num( $in, 'auto_open_delay', 0, 300, $d['auto_open_delay'] );
		$out['hide_on_mobile']  = empty( $in['hide_on_mobile'] ) ? 0 : 1;
		$out['z_index']         = self::num( $in, 'z_index', 1, 2147483000, $d['z_index'] );

		$ids = isset( $in['exclude_ids'] ) ? explode( ',', (string) $in['exclude_ids'] ) : array();
		$ids = array_filter( array_map( 'absint', $ids ) );
		$out['exclude_ids'] = implode( ',', $ids );

		// Colours.
		foreach ( array( 'color_primary', 'color_secondary', 'color_header', 'color_launcher_bg', 'color_launcher_text', 'color_send' ) as $k ) {
			$out[ $k ] = self::color( $in, $k, $d[ $k ] );
		}

		return $out;
	}
}
