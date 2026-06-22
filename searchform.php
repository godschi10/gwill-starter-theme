<?php defined( 'ABSPATH' ) || exit; ?>

<form
	role="search"
	aria-label="<?php esc_attr_e( 'Site search', 'gwill-starter' ); ?>"
	method="get"
	class="search-form"
	action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'gwill-starter' ); ?></span>
		<input
			type="search"
			class="search-field"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			name="s"
			placeholder="<?php esc_attr_e( 'Search…', 'gwill-starter' ); ?>"
		>
	</label>
	<button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'gwill-starter' ); ?></button>
</form>
