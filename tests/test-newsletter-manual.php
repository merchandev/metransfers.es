<?php
namespace MeTransfers\Core {
	class Outbox {
		public static $ok = true;
		public static $events = array();
		public static function enqueue( ...$args ) { self::$events[] = $args; return self::$ok; }
	}
}
namespace MeTransfers\Admin {
	class AuditLog { public static function record( ...$args ) {} }
}
namespace {
	ob_start();
	require __DIR__ . '/test-newsletter.php';
	ob_end_clean();
	require __DIR__ . '/../app/Newsletter/ManualQueue.php';
	require __DIR__ . '/../app/Newsletter/Admin.php';
	require __DIR__ . '/../app/Admin/Capabilities.php';
	require __DIR__ . '/../app/Newsletter/Privacy.php';
	function add_option( $key, $value, ...$rest ) { if ( array_key_exists( $key, $GLOBALS['mt_options'] ) ) { return false; } $GLOBALS['mt_options'][$key] = $value; return true; }
	function update_option( $key, $value, ...$rest ) { $GLOBALS['mt_options'][$key] = $value; return true; }
	function delete_option( $key ) { unset( $GLOBALS['mt_options'][$key] ); return true; }
	function wp_rand( $min, $max ) { return max( $min, min( $max, $GLOBALS['manual_size'] ?? 23 ) ); }
	function wp_next_scheduled( $hook ) { return $GLOBALS['manual_cron'][$hook] ?? false; }
	function wp_schedule_single_event( $at, $hook ) { $GLOBALS['manual_cron'][$hook] = $at; return true; }
	function wp_clear_scheduled_hook( $hook ) { unset( $GLOBALS['manual_cron'][$hook] ); }
	function current_user_can( $cap ) { return $GLOBALS['manual_can'] ?? true; }
	function check_admin_referer( $nonce ) { if ( ! ( $GLOBALS['manual_nonce'] ?? true ) ) { throw new \RuntimeException( 'nonce_rejected' ); } }
	function wp_die( ...$args ) { throw new \RuntimeException( 'access_denied' ); }
	function wp_safe_redirect( $url ) { throw new \RuntimeException( $url ); }
	function wp_get_current_user() { return (object) array( 'user_login' => 'test-admin' ); }
	class Manual_Db extends \Fake_Newsletter_Db {
		public $queue = array(), $deliveries = array(), $next_queue = 0, $snapshot = null, $fail_delete = false, $lock = true;
		public function get_var( $query ) {
			if ( str_contains( $query['sql'], 'GET_LOCK' ) ) { return $this->lock ? '1' : '0'; }
			if ( str_contains( $query['sql'], 'RELEASE_LOCK' ) ) { return '1'; }
			return count( $this->queue );
		}
		public function get_results( $query, $output ) { return array_slice( array_values( $this->queue ), 0, $query['args'][1] ?? 50 ); }
		public function get_row( $query, $output ) {
			if ( str_contains( $query['args'][0], 'manual_queue' ) ) {
				foreach ( $this->queue as $row ) { if ( $row['email'] === $query['args'][1] ) { return $row; } }
				return null;
			}
			return parent::get_row( $query, $output );
		}
		public function delete( $table, $where ) {
			if ( $this->fail_delete ) { return false; }
			$count = 0;
			foreach ( $this->queue as $id => $row ) {
				if ( ! array_diff_assoc( $where, $row ) ) { unset( $this->queue[$id] ); ++$count; }
			}
			return $count;
		}
		public function query( $query ) {
			if ( is_string( $query ) ) {
				if ( 'START TRANSACTION' === $query ) { $this->snapshot = array( $this->rows, $this->queue, $this->deliveries ); }
				if ( 'ROLLBACK' === $query && $this->snapshot ) { list( $this->rows, $this->queue, $this->deliveries ) = $this->snapshot; }
				return 1;
			}
			$args = $query['args'];
			if ( str_starts_with( $query['sql'], 'INSERT IGNORE' ) ) {
				foreach ( $this->queue as $row ) { if ( $row['email'] === $args[1] ) { return 0; } }
				$id = ++$this->next_queue;
				$this->queue[$id] = array_combine( array( 'email', 'name', 'language', 'subscriber_status', 'source', 'consent_text', 'created_at' ), array_slice( $args, 1 ) ) + array( 'id' => $id );
				return 1;
			}
			if ( str_starts_with( $query['sql'], 'DELETE q' ) ) {
				foreach ( array_slice( $args, 2 ) as $id ) {
					$email = $this->rows[$id]['email'] ?? '';
					foreach ( $this->queue as $qid => $row ) { if ( $email === $row['email'] ) { unset( $this->queue[$qid] ); } }
				}
				return 1;
			}
			if ( str_starts_with( $query['sql'], 'DELETE FROM' ) ) {
				if ( $this->fail_delete ) { return false; }
				if ( str_contains( $args[0], 'manual_queue' ) ) { $count = count( $this->queue ); $this->queue = array(); return $count; }
				$count = 0;
				foreach ( array_slice( $args, 1 ) as $id ) {
					if ( str_contains( $args[0], 'subscribers' ) && isset( $this->rows[$id] ) ) { unset( $this->rows[$id] ); ++$count; }
					if ( str_contains( $args[0], 'deliveries' ) ) { foreach ( $this->deliveries as $key => $delivery ) { if ( $id === $delivery['subscriber_id'] ) { unset( $this->deliveries[$key] ); ++$count; } } }
				}
				return $count;
			}
			return parent::query( $query );
		}
	}
	use MeTransfers\Newsletter\ManualQueue as Queue;
	use MeTransfers\Newsletter\Subscribers as Subs;
	use MeTransfers\Newsletter\Admin;
	$GLOBALS['wpdb'] = new Manual_Db();
	$db = $GLOBALS['wpdb'];
	$report = Queue::paste( 'alpha@example.test, beta@example.test; gamma@example.test' . "\n" . 'reservas@example.test, ALPHA@example.test, invalid...', 'es', 'subscribed', 'Confirmed consent' );
	assert_nl( 4 === $report['queued'] && 1 === $report['duplicate'] && 1 === $report['invalid'] && 4 === Queue::count() && ! $db->rows, 'Paste must parse comma, semicolon and newline lists; deduplicate and defer subscribers.' );
	$first_due = get_option( Queue::NEXT );
	assert_nl( $first_due >= time() + 6 * HOUR_IN_SECONDS - 1 && 0 === Queue::dispatch(), 'First manual batch must wait six hours.' );
	for ( $i = 0; $i < 110; ++$i ) { Queue::enqueue( "manual$i@example.com", 'Manual', 'es', 'subscribed', 'admin', 'Consent' ); }
	assert_nl( $first_due === get_option( Queue::NEXT ), 'Further uploads must not postpone the next batch.' );
	update_option( Queue::NEXT, time() - DAY_IN_SECONDS );
	assert_nl( 23 === Queue::dispatch() && 91 === Queue::count() && 23 === count( $db->rows ), 'A delayed cron releases only one bounded FIFO batch, without catch-up bursts.' );
	assert_nl( 'alpha@example.test' === reset( $db->rows )['email'] && 0 === Queue::dispatch(), 'FIFO order and six-hour spacing must hold across repeated worker invocations.' );
	$GLOBALS['manual_size'] = 98;
	update_option( Queue::NEXT, time() - 1 );
	assert_nl( 91 === Queue::dispatch() && 0 === Queue::count() && false === get_option( Queue::NEXT ), 'The final batch can be smaller and an empty queue has no next window.' );
	Queue::enqueue( 'organic@example.com', 'Manual name', 'en', 'subscribed', 'admin', 'Imported consent' );
	$organic = Subs::subscribe( array( 'email' => 'organic@example.com', 'name' => 'Popup name', 'source' => 'popup', 'consent_text' => 'Popup consent' ) );
	assert_nl( 'pending' === $organic['result'] && 1 === Queue::count(), 'Popup signup must be immediate even while its email is waiting manually.' );
	update_option( Queue::NEXT, time() - 1 );
	assert_nl( 0 === Queue::dispatch() && 'Popup name' === Subs::findByEmail( 'organic@example.com' )['name'] && 'pending' === Subs::findByEmail( 'organic@example.com' )['status'], 'Manual processing cannot overwrite or confirm a public signup.' );
	Queue::enqueue( 'pending@example.com', '', 'es', 'pending', 'admin', 'Request confirmation' );
	update_option( Queue::NEXT, time() - 1 );
	\MeTransfers\Core\Outbox::$ok = false;
	assert_nl( 0 === Queue::dispatch() && 1 === Queue::count() && null === Subs::findByEmail( 'pending@example.com' ), 'Outbox failure must roll back subscriber admission and retain the waiting row.' );
	\MeTransfers\Core\Outbox::$ok = true;
	update_option( Queue::NEXT, time() - 1 );
	assert_nl( 1 === Queue::dispatch() && 'pending' === Subs::findByEmail( 'pending@example.com' )['status'] && 0 === Queue::count(), 'Pending admission must durably enqueue confirmation before removing the waiting entry.' );
	Subs::setStatus( array( $organic['subscriber']['id'] ), 'unsubscribed' );
	assert_nl( 'blocked' === Queue::enqueue( 'organic@example.com', '', 'es', 'subscribed', 'admin', 'x' ), 'Manual enqueue cannot restore an unsubscribe.' );
	Queue::enqueue( 'cancel@example.com', '', 'es', 'subscribed', 'admin', 'x' );
	$queued = Queue::findByEmail( 'cancel@example.com' );
	assert_nl( 1 === Queue::cancel( $queued['id'] ) && 0 === Queue::count() && false === get_option( Queue::NEXT ), 'Cancellation must remove the contact and reset an empty schedule.' );
	$GLOBALS['manual_size'] = 12;
	for ( $i = 0; $i < 13; ++$i ) { Queue::enqueue( "small$i@example.test", '', 'es', 'subscribed', 'admin', 'Consent' ); }
	update_option( Queue::NEXT, time() - 1 );
	assert_nl( 12 === Queue::dispatch() && 1 === Queue::count(), 'The lower random batch bound must admit exactly twelve when at least twelve wait.' );
	update_option( Queue::NEXT, time() - 1 );
	assert_nl( 1 === Queue::dispatch() && 0 === Queue::count(), 'A final remainder below twelve must not be stranded.' );
	$subscriber = Subs::findByEmail( 'alpha@example.test' );
	$db->deliveries[] = array( 'subscriber_id' => (int) $subscriber['id'] );
	$db->fail_delete = true;
	assert_nl( false === Subs::delete( array( $subscriber['id'] ) ) && null !== Subs::find( $subscriber['id'] ) && count( $db->deliveries ), 'Database errors must not report a successful deletion or partially erase history.' );
	$db->fail_delete = false;
	$_POST = array( 'delete_id' => $subscriber['id'], 'ids' => array( $organic['subscriber']['id'] ), 'bulk' => '' );
	try { Admin::handleBulk(); } catch ( \RuntimeException $redirect ) { assert_nl( str_contains( $redirect->getMessage(), 'mt_nl_notice=deleted' ), 'Individual deletion should redirect with a real deletion count.' ); }
	assert_nl( null === Subs::find( $subscriber['id'] ) && null !== Subs::find( $organic['subscriber']['id'] ) && ! $db->deliveries, 'Individual delete must remove only the clicked subscriber and their history.' );
	$GLOBALS['manual_nonce'] = false;
	$_POST = array( 'delete_id' => $organic['subscriber']['id'] );
	try { Admin::handleBulk(); } catch ( \RuntimeException $denied ) { assert_nl( 'nonce_rejected' === $denied->getMessage(), 'Deletion must require a valid nonce.' ); }
	assert_nl( null !== Subs::find( $organic['subscriber']['id'] ), 'Rejected requests cannot delete subscribers.' );
	$GLOBALS['manual_nonce'] = true;
	$GLOBALS['manual_can'] = false;
	try { Admin::handleBulk(); } catch ( \RuntimeException $denied ) { assert_nl( 'access_denied' === $denied->getMessage(), 'Deletion must require newsletter capability.' ); }
	$GLOBALS['manual_can'] = true;
	$db->lock = false;
	assert_nl( 0 === Queue::dispatch() && false === Subs::delete( array( $organic['subscriber']['id'] ) ), 'A busy manual worker must prevent concurrent releases and conflicting deletion.' );
	$db->lock = true;
	$pending_delete = Subs::findByEmail( 'pending@example.com' );
	$_POST = array( 'ids' => array( $organic['subscriber']['id'], $pending_delete['id'] ), 'bulk' => 'delete' );
	try { Admin::handleBulk(); } catch ( \RuntimeException $redirect ) { assert_nl( str_contains( $redirect->getMessage(), 'mt_nl_notice=deleted' ), 'Bulk deletion must report its result.' ); }
	assert_nl( null === Subs::find( $organic['subscriber']['id'] ) && null === Subs::find( $pending_delete['id'] ), 'Bulk deletion must remove all selected subscribers.' );
	$_POST = array( 'emails' => 'consented@example.test, second@example.test', 'status' => 'subscribed' );
	try { Admin::handlePaste(); } catch ( \RuntimeException $redirect ) { assert_nl( str_contains( $redirect->getMessage(), 'mt_nl_notice=consent' ), 'Manual subscribed batches must require consent attestation.' ); }
	assert_nl( 0 === Queue::count(), 'Missing consent cannot queue subscribed contacts.' );
	$_POST['consent'] = '1';
	try { Admin::handlePaste(); } catch ( \RuntimeException $redirect ) { assert_nl( str_contains( $redirect->getMessage(), 'mt_nl_notice=manual' ), 'The paste controller must confirm its queued result.' ); }
	assert_nl( 2 === Queue::count() && null === Subs::findByEmail( 'consented@example.test' ), 'A valid administrative paste still defers admission.' );
	Queue::cancel();
	Queue::enqueue( 'privacy@example.com', '', 'es', 'pending', 'admin', 'Consent text' );
	assert_nl( count( \MeTransfers\Newsletter\Privacy::export( 'privacy@example.com' )['data'] ) && \MeTransfers\Newsletter\Privacy::erase( 'privacy@example.com' )['items_removed'], 'Waiting contacts must participate in privacy export and erasure.' );
	echo "Newsletter manual queue and deletion tests passed.\n";
}
