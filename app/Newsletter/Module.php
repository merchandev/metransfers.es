<?php
namespace MeTransfers\Newsletter;

/**
 * Wires the newsletter: sending worker, public popup and links, privacy
 * tools and, in wp-admin, the Newsletter menu.
 */
final class Module {
	public function register() {
		( new ManualQueue() )->register();
		( new Campaigns() )->register();
		( new PublicController() )->register();
		( new Privacy() )->register();
		if ( is_admin() ) {
			( new Admin() )->register();
		}
	}
}
