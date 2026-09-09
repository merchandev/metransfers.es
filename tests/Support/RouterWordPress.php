<?php
namespace MeTransfers\I18n;

function is_post_publicly_viewable( $post ): bool {
	return $post->publicly_viewable ?? true;
}
