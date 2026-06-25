<?php
/**
 * Author social profile fields.
 *
 * Adds a "Social Links" section to the WordPress user profile admin screen
 * and exposes the gwill_get_author_socials() template helper used by
 * template-parts/author-box.php and author.php.
 *
 * Adding a platform: add an entry to gwill_author_social_fields().
 * Removing a platform: remove the entry. Any saved meta is orphaned in the
 * database (harmless) but will no longer output on the front end.
 *
 * @package GWill_Starter
 */

defined( 'ABSPATH' ) || exit;

// ── Field definitions ─────────────────────────────────────────────────────────

/**
 * Returns the canonical social link field definitions.
 *
 * Each entry:
 *   key         string  user meta key (gwill_social_* prefix) or 'website' for built-in
 *   label       string  label in the admin profile screen
 *   placeholder string  example URL shown as input placeholder
 *   icon        string  inline SVG; always includes aria-hidden="true" focusable="false"
 *   aria        string  accessible label used on the front-end <a> tag
 *   builtin     bool    (optional) true = read from WP's native user_url, not custom meta
 *
 * @return array<int, array<string, mixed>>
 */
function gwill_author_social_fields(): array {
	return [
		[
			'key'         => 'website',
			'label'       => __( 'Website', 'gwill-starter' ),
			'placeholder' => 'https://example.com',
			/* Feather "globe" — stroke-based icon, uses currentColor via stroke attribute */
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
			'aria'        => __( 'Website', 'gwill-starter' ),
			'builtin'     => true, // reads user_url; WP already shows this field in "Contact Info"
		],
		[
			'key'         => 'gwill_social_twitter',
			'label'       => __( 'Twitter / X', 'gwill-starter' ),
			'placeholder' => 'https://x.com/username',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.74l7.73-8.835L1.254 2.25H8.08l4.259 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
			'aria'        => __( 'Twitter / X', 'gwill-starter' ),
		],
		[
			'key'         => 'gwill_social_linkedin',
			'label'       => __( 'LinkedIn', 'gwill-starter' ),
			'placeholder' => 'https://linkedin.com/in/username',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
			'aria'        => __( 'LinkedIn', 'gwill-starter' ),
		],
		[
			'key'         => 'gwill_social_github',
			'label'       => __( 'GitHub', 'gwill-starter' ),
			'placeholder' => 'https://github.com/username',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
			'aria'        => __( 'GitHub', 'gwill-starter' ),
		],
		[
			'key'         => 'gwill_social_instagram',
			'label'       => __( 'Instagram', 'gwill-starter' ),
			'placeholder' => 'https://instagram.com/username',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
			'aria'        => __( 'Instagram', 'gwill-starter' ),
		],
		[
			'key'         => 'gwill_social_facebook',
			'label'       => __( 'Facebook', 'gwill-starter' ),
			'placeholder' => 'https://facebook.com/username',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
			'aria'        => __( 'Facebook', 'gwill-starter' ),
		],
		[
			'key'         => 'gwill_social_youtube',
			'label'       => __( 'YouTube', 'gwill-starter' ),
			'placeholder' => 'https://youtube.com/@channel',
			'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
			'aria'        => __( 'YouTube', 'gwill-starter' ),
		],
	];
}

// ── Template helper ───────────────────────────────────────────────────────────

/**
 * Returns an array of active social links for a given author.
 *
 * Filters out any field with an empty URL so templates can safely iterate
 * without conditional checks per item.
 *
 * Example output:
 *   [
 *     [ 'url' => 'https://x.com/gwill', 'icon' => '<svg>…</svg>', 'aria' => 'Twitter / X' ],
 *     …
 *   ]
 *
 * @param int $user_id WordPress user ID.
 * @return array<int, array<string, string>>
 */
function gwill_get_author_socials( int $user_id ): array {
	$user  = get_userdata( $user_id );
	$links = [];

	if ( ! $user ) {
		return $links;
	}

	foreach ( gwill_author_social_fields() as $field ) {
		if ( ! empty( $field['builtin'] ) ) {
			$url = esc_url( $user->user_url );
		} else {
			$url = esc_url( (string) get_user_meta( $user_id, $field['key'], true ) );
		}

		if ( ! $url ) {
			continue;
		}

		$links[] = [
			'url'  => $url,
			'icon' => $field['icon'], // developer-supplied SVG — not user input
			'aria' => $field['aria'],
		];
	}

	return $links;
}

// ── Admin: render Social Links section on user profile screen ─────────────────

/**
 * Outputs the Social Links table on the user profile / edit-user admin screen.
 *
 * Hooks: show_user_profile (own profile) + edit_user_profile (other user's profile).
 *
 * The website / user_url field is intentionally excluded here because WordPress
 * already displays it in the "Contact Info" section above. Showing it twice
 * creates confusion about which field is canonical.
 *
 * @param WP_User $user The user whose profile is being edited.
 */
function gwill_render_social_profile_fields( WP_User $user ): void {
	wp_nonce_field( 'gwill_save_social_' . $user->ID, 'gwill_social_nonce' );
	?>
	<h2><?php esc_html_e( 'Social Links', 'gwill-starter' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
			<?php foreach ( gwill_author_social_fields() as $field ) :
				// Skip built-in website — WP already shows user_url in Contact Info.
				if ( ! empty( $field['builtin'] ) ) {
					continue;
				}
				$saved = (string) get_user_meta( $user->ID, $field['key'], true );
			?>
			<tr>
				<th>
					<label for="<?php echo esc_attr( $field['key'] ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
					</label>
				</th>
				<td>
					<input
						type="url"
						id="<?php echo esc_attr( $field['key'] ); ?>"
						name="<?php echo esc_attr( $field['key'] ); ?>"
						value="<?php echo esc_attr( $saved ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

add_action( 'show_user_profile', 'gwill_render_social_profile_fields' );
add_action( 'edit_user_profile', 'gwill_render_social_profile_fields' );

// ── Admin: save Social Links ──────────────────────────────────────────────────

/**
 * Saves social link meta when the user profile form is submitted.
 *
 * Security:
 *   1. capability check — current user must be able to edit the target user
 *   2. nonce verification — prevents CSRF
 *   3. esc_url_raw() sanitization — ensures only valid URLs are stored
 *
 * Empty values are deleted rather than stored as empty strings to keep
 * usermeta clean.
 *
 * @param int $user_id User ID of the profile being saved.
 */
function gwill_save_social_profile_fields( int $user_id ): void {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if (
		! isset( $_POST['gwill_social_nonce'] ) ||
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['gwill_social_nonce'] ) ),
			'gwill_save_social_' . $user_id
		)
	) {
		return;
	}

	foreach ( gwill_author_social_fields() as $field ) {
		if ( ! empty( $field['builtin'] ) ) {
			continue; // WP saves user_url itself via the Contact Info section.
		}

		$value = isset( $_POST[ $field['key'] ] )
			? esc_url_raw( wp_unslash( $_POST[ $field['key'] ] ) )
			: '';

		if ( $value ) {
			update_user_meta( $user_id, $field['key'], $value );
		} else {
			delete_user_meta( $user_id, $field['key'] );
		}
	}
}

add_action( 'personal_options_update',  'gwill_save_social_profile_fields' );
add_action( 'edit_user_profile_update', 'gwill_save_social_profile_fields' );
