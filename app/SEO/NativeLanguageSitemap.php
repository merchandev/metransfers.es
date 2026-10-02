<?php
namespace MeTransfers\SEO;

final class NativeLanguageSitemap extends \WP_Sitemaps_Provider {
	public function __construct() {
		$this->name        = 'mtlanguages';
		$this->object_type = 'post';
	}

	public function get_url_list( $page_num, $object_subtype = '' ) {
		return array_slice( LanguageSitemap::urls(), ( max( 1, (int) $page_num ) - 1 ) * 2000, 2000 );
	}

	public function get_max_num_pages( $object_subtype = '' ) {
		return (int) ceil( count( LanguageSitemap::urls() ) / 2000 );
	}
}
