<?php
namespace MeTransfers\Newsletter;

use MeTransfers\Admin\AuditLog;
use MeTransfers\Admin\Capabilities;
use MeTransfers\Admin\XlsxWorkbook;

/**
 * wp-admin → Newsletter: subscribers, campaigns, import and settings.
 * Every change goes through admin-post.php with a nonce and the
 * mt_manage_newsletter capability.
 */
final class Admin {
	const SLUG          = 'mt-newsletter';
	const CAMPAIGNS     = 'mt-newsletter-campaigns';
	const IMPORT        = 'mt-newsletter-import';
	const SETTINGS_PAGE = 'mt-newsletter-settings';
	const PER_PAGE      = 50;

	const ACTIONS = array(
		'mt_nl_bulk'          => 'handleBulk',
		'mt_nl_add'           => 'handleAdd',
		'mt_nl_paste'         => 'handlePaste',
		'mt_nl_queue_cancel'  => 'handleQueueCancel',
		'mt_nl_export'        => 'handleExport',
		'mt_nl_save_campaign' => 'handleCampaign',
		'mt_nl_campaign'      => 'handleCampaignAction',
		'mt_nl_preview'       => 'handlePreview',
		'mt_nl_process'       => 'handleProcess',
		'mt_nl_import'        => 'handleImport',
		'mt_nl_settings'      => 'handleSettings',
	);

	const NOTICES = array(
		'saved'       => 'Cambios guardados.',
		'added'       => 'Suscriptor añadido.',
		'updated'     => 'Suscriptor actualizado.',
		'blocked'     => 'Esa dirección se dio de baja o está marcada como rebotada. No se ha reactivado.',
		'invalid'     => 'El correo no es válido.',
		'bulk'        => 'Acción aplicada a %d suscriptores.',
		'resent'      => 'Correo de confirmación enviado a %d suscriptores pendientes.',
		'deleted'     => '%d suscriptores eliminados, con su historial de envíos.',
		'manual'      => '%s',
		'draft'       => 'Borrador guardado.',
		'locked'      => 'Esta campaña ya se está enviando o se envió: no se puede modificar. Duplícala para crear otra.',
		'test_sent'   => 'Correo de prueba enviado a %s.',
		'test_failed' => 'No se pudo enviar la prueba: %s',
		'queued'      => 'Campaña en cola: %d destinatarios. Se envía poco a poco respetando el límite por hora.',
		'empty'       => 'La campaña necesita asunto y contenido.',
		'no_audience' => 'No hay suscriptores en esa audiencia.',
		'cancelled'   => 'Campaña cancelada. Los correos que faltaban no se enviarán.',
		'duplicated'  => 'Campaña duplicada como borrador.',
		'removed'     => 'Borrador eliminado.',
		'processed'   => 'Procesado: %d correos enviados en esta tanda.',
		'import'      => 'Importación terminada.',
		'consent'     => 'Para importar como «Suscrito» tienes que confirmar que esos contactos dieron su consentimiento.',
		'error'       => '%s',
	);

	public function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		foreach ( self::ACTIONS as $action => $method ) {
			add_action( 'admin_post_' . $action, array( __CLASS__, $method ) );
		}
	}

	public static function menu() {
		$cap = Capabilities::MANAGE_NEWSLETTER;
		add_menu_page( 'Newsletter', 'Newsletter', $cap, self::SLUG, array( __CLASS__, 'renderSubscribers' ), 'dashicons-email-alt', 28 );
		add_submenu_page( self::SLUG, 'Suscriptores', 'Suscriptores', $cap, self::SLUG, array( __CLASS__, 'renderSubscribers' ) );
		add_submenu_page( self::SLUG, 'Campañas', 'Campañas', $cap, self::CAMPAIGNS, array( __CLASS__, 'renderCampaigns' ) );
		add_submenu_page( self::SLUG, 'Importar contactos', 'Importar', $cap, self::IMPORT, array( __CLASS__, 'renderImport' ) );
		add_submenu_page( self::SLUG, 'Ajustes del newsletter', 'Ajustes', $cap, self::SETTINGS_PAGE, array( __CLASS__, 'renderSettings' ) );
	}

	// ── Suscriptores ──────────────────────────────────────────────────────

	public static function renderSubscribers() {
		self::guard();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters.
		if ( isset( $_GET['subscriber'] ) ) {
			self::renderSubscriber( absint( $_GET['subscriber'] ) );
			return;
		}
		$args = array(
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'language' => isset( $_GET['language'] ) ? sanitize_key( wp_unslash( $_GET['language'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'page'     => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			'per_page' => self::PER_PAGE,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$counts = Subscribers::counts();
		$result = Subscribers::search( $args );
		$base   = admin_url( 'admin.php?page=' . self::SLUG );

		echo '<div class="wrap mt-nl-admin"><h1 class="wp-heading-inline">Newsletter · Suscriptores</h1> ';
		echo '<a href="#mt-nl-add" class="page-title-action" onclick="document.getElementById(\'mt-nl-add\').hidden=false">Añadir suscriptor</a> ';
		echo '<a href="' . esc_url( self::exportUrl( $args ) ) . '" class="page-title-action">Exportar Excel</a><hr class="wp-header-end">';
		self::notice();
		self::styles();

		echo '<ul class="subsubsub">';
		$tabs = array_merge( array( '' => 'Todos' ), Subscribers::STATUSES );
		$i    = 0;
		foreach ( $tabs as $status => $label ) {
			$count = '' === $status ? $counts['all'] : $counts[ $status ];
			echo '<li>' . ( $i++ ? ' | ' : '' ) . '<a href="' . esc_url( add_query_arg( 'status', $status, $base ) ) . '"' . ( $args['status'] === $status ? ' class="current"' : '' ) . '>' . esc_html( $label ) . ' <span class="count">(' . (int) $count . ')</span></a></li>';
		}
		echo '</ul>';

		echo '<form method="get" class="mt-nl-filters"><input type="hidden" name="page" value="' . esc_attr( self::SLUG ) . '"><input type="hidden" name="status" value="' . esc_attr( $args['status'] ) . '">';
		echo '<label class="screen-reader-text" for="mt-nl-search">Buscar</label><input type="search" id="mt-nl-search" name="s" value="' . esc_attr( $args['search'] ) . '" placeholder="Correo o nombre"> ';
		echo '<select name="language" aria-label="Idioma"><option value="">Todos los idiomas</option>' . self::options(
			array(
				'es' => 'Español',
				'en' => 'Inglés',
			),
			$args['language']
		) . '</select> ';
		echo '<button class="button">Filtrar</button></form>';

		self::renderAddForm();
		self::renderManualQueue();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return this.elements.namedItem(\'bulk\').value!==\'delete\'||confirm(\'¿Eliminar los suscriptores seleccionados y su historial? No se puede deshacer.\')">';
		echo '<input type="hidden" name="action" value="mt_nl_bulk">';
		wp_nonce_field( 'mt_nl_bulk' );
		echo '<div class="tablenav top"><select name="bulk" aria-label="Acción en lote"><option value="">Acciones en lote</option>';
		foreach ( Subscribers::STATUSES as $status => $label ) {
			echo '<option value="status:' . esc_attr( $status ) . '">Marcar como: ' . esc_html( $label ) . '</option>';
		}
		echo '<option value="resend">Reenviar email de confirmación (pendientes)</option><option value="delete">Eliminar (con su historial)</option></select> <button class="button">Aplicar</button>';
		self::pagination( $result['total'], $args['page'] );
		echo '</div>';

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><td class="check-column"><input type="checkbox" onclick="document.querySelectorAll(\'.mt-nl-check\').forEach(function(c){c.checked=this.checked}.bind(this))" aria-label="Seleccionar todos"></td>';
		foreach ( array( 'Correo', 'Nombre', 'Estado', 'Idioma', 'Origen', 'Página de registro', 'Páginas vistas', 'Alta', 'Envíos' ) as $column ) {
			echo '<th scope="col">' . esc_html( $column ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		if ( ! $result['rows'] ) {
			echo '<tr><td colspan="10">No hay suscriptores con estos filtros.</td></tr>';
		}
		foreach ( $result['rows'] as $row ) {
			$link = add_query_arg( 'subscriber', (int) $row['id'], $base );
			echo '<tr><th scope="row" class="check-column"><input type="checkbox" class="mt-nl-check" name="ids[]" value="' . (int) $row['id'] . '" aria-label="Seleccionar ' . esc_attr( $row['email'] ) . '"></th>';
			echo '<td><a href="' . esc_url( $link ) . '"><strong>' . esc_html( $row['email'] ) . '</strong></a><div class="row-actions"><button type="submit" class="button-link-delete" name="delete_id" value="' . (int) $row['id'] . '" onclick="return confirm(\'¿Eliminar este suscriptor y su historial?\')">Eliminar</button></div></td>';
			echo '<td>' . esc_html( $row['name'] ) . '</td>';
			echo '<td>' . self::badge( $row['status'] ) . '</td>';
			echo '<td>' . esc_html( strtoupper( $row['language'] ) ) . '</td>';
			echo '<td>' . esc_html( self::sourceLabel( $row['source'] ) ) . '</td>';
			echo '<td>' . esc_html( $row['signup_page'] ) . '</td>';
			echo '<td><a href="' . esc_url( $link ) . '">' . count( Subscribers::pages( $row ) ) . '</a></td>';
			echo '<td>' . esc_html( self::date( $row['created_at'] ) ) . '</td>';
			echo '<td>' . (int) $row['sends'] . '</td></tr>';
		}
		echo '</tbody></table></form></div>';
	}

	private static function renderManualQueue() {
		$count = ManualQueue::count();
		$next  = (int) get_option( ManualQueue::NEXT, 0 );
		echo '<details class="mt-nl-card"><summary style="cursor:pointer;font-size:14px;font-weight:600;padding:8px 0">Añadir por lotes · cola manual</summary><p><strong>' . (int) $count . ' contactos en espera.</strong> Se incorporan en orden, en tandas aleatorias de 12 a 98 cada 6 horas. Puedes seguir cargando listas sin límite total de cola. Las altas del popup y del pie entran directamente y no esperan estas tandas.</p>';
		if ( $count && $next ) {
			echo '<p>Próxima tanda: <strong>' . esc_html( wp_date( 'd/m/Y H:i', $next ) ) . '</strong> (hora del sitio). WP-Cron la ejecutará cuando el sitio reciba tráfico; un cron real permite ejecutarla puntualmente.</p>';
		}
		$last = get_option( ManualQueue::LAST, array() );
		if ( is_array( $last ) && ! empty( $last['at'] ) ) {
			echo '<p>Última tanda: ' . esc_html( wp_date( 'd/m/Y H:i', $last['at'] ) ) . ' · ' . (int) $last['added'] . ' incorporados · ' . (int) $last['failed'] . ' con error y conservados en espera.</p>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="mt_nl_paste">';
		wp_nonce_field( 'mt_nl_paste' );
		echo '<p><label for="mt-nl-emails">Pega los correos separados por comas, punto y coma o saltos de línea</label><br><textarea id="mt-nl-emails" name="emails" rows="5" class="large-text" required placeholder="persona@ejemplo.com, otra@ejemplo.com"></textarea></p>';
		echo '<p><label>Idioma <select name="language">' . self::options(
			array(
				'es' => 'Español',
				'en' => 'Inglés',
			),
			'es'
		) . '</select></label></p>';
		echo '<p><label><input type="radio" name="status" value="pending" checked> Solicitar confirmación cuando llegue su tanda</label><br><label><input type="radio" name="status" value="subscribed"> Incorporar como suscritos</label></p>';
		echo '<p><label><input type="checkbox" name="consent" value="1"> Confirmo que estos contactos aceptaron recibir nuestras comunicaciones (obligatorio para incorporarlos como suscritos).</label></p>';
		echo '<button type="submit" class="button button-primary">Añadir a la cola manual</button></form>';
		if ( $count ) {
			echo '<details><summary>Ver y cancelar contactos en espera (primeros 50)</summary><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="mt_nl_queue_cancel">';
			wp_nonce_field( 'mt_nl_queue_cancel' );
			echo '<ul>';
			foreach ( ManualQueue::preview() as $row ) {
				echo '<li>' . esc_html( $row['email'] ) . ' <button type="submit" name="queue_id" value="' . (int) $row['id'] . '" class="button-link-delete">Quitar de la cola</button></li>';
			}
			echo '</ul><button type="submit" name="clear_all" value="1" class="button" onclick="return confirm(\'¿Vaciar todos los contactos en espera? Los suscriptores ya incorporados no cambian.\')">Vaciar cola manual</button></form></details>';
		}
		echo '</details>';
	}

	public static function handlePaste() {
		self::guard( 'mt_nl_paste' );
		$back   = admin_url( 'admin.php?page=' . self::SLUG );
		$status = isset( $_POST['status'] ) && 'subscribed' === $_POST['status'] ? 'subscribed' : 'pending';
		if ( 'subscribed' === $status && empty( $_POST['consent'] ) ) {
			self::redirect( $back, 'consent' );
		}
		$text = isset( $_POST['emails'] ) && is_string( $_POST['emails'] ) ? wp_unslash( $_POST['emails'] ) : '';
		if ( '' === trim( $text ) || strlen( $text ) > Importer::MAX_BYTES ) {
			self::redirect( $back, 'error', 'Pega una lista de correos de hasta 10 MB por carga. Puedes realizar tantas cargas como necesites.' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Large manual lists, like file imports.
		}
		$report = ManualQueue::paste( $text, isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'es', $status, 'Carga manual por ' . wp_get_current_user()->user_login . ( 'subscribed' === $status ? ', que confirma su consentimiento' : ', pendiente de confirmación' ) );
		AuditLog::record( 'newsletter.manual_queued', 'newsletter', 0, $report );
		self::redirect( $back, $report['error'] ? 'error' : 'manual', sprintf( '%d en cola, %d duplicados, %d bloqueados por baja o rebote, %d inválidos y %d errores de guardado.', $report['queued'], $report['duplicate'], $report['blocked'], $report['invalid'], $report['error'] ) );
	}

	public static function handleQueueCancel() {
		self::guard( 'mt_nl_queue_cancel' );
		$id = isset( $_POST['queue_id'] ) ? absint( $_POST['queue_id'] ) : 0;
		if ( ! $id && empty( $_POST['clear_all'] ) ) {
			self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), 'error', 'Selecciona un contacto en espera.' );
		}
		$removed = ManualQueue::cancel( $id );
		self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), false === $removed ? 'error' : 'manual', false === $removed ? 'La cola está ocupada o no se pudo actualizar. Inténtalo de nuevo.' : sprintf( '%d contactos retirados de la cola.', $removed ) );
	}

	private static function renderAddForm() {
		echo '<div id="mt-nl-add" class="mt-nl-card" hidden><h2>Añadir suscriptor</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mt_nl_add">';
		wp_nonce_field( 'mt_nl_add' );
		echo '<p><label>Correo <input type="email" name="email" required class="regular-text"></label> <label>Nombre <input type="text" name="name" maxlength="120"></label> ';
		echo '<label>Idioma <select name="language">' . self::options(
			array(
				'es' => 'Español',
				'en' => 'Inglés',
			),
			'es'
		) . '</select></label></p>';
		echo '<p><label><input type="radio" name="status" value="pending" checked> Pendiente: le enviamos un correo para que confirme</label><br>';
		echo '<label><input type="radio" name="status" value="subscribed"> Suscrito: me ha dado su consentimiento (por escrito, en persona…)</label></p>';
		echo '<p>Las altas manuales esperan una tanda de 12 a 98 contactos cada 6 horas.</p><p><button class="button button-primary">Añadir a la cola manual</button></p></form></div>';
	}

	private static function renderSubscriber( $id ) {
		global $wpdb;
		$subscriber = Subscribers::find( $id );
		echo '<div class="wrap mt-nl-admin"><h1>Suscriptor</h1>';
		self::styles();
		self::notice();
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ) . '">← Volver a suscriptores</a></p>';
		if ( ! $subscriber ) {
			echo '<p>Este suscriptor no existe.</p></div>';
			return;
		}

		$fields = array(
			'Correo'                     => $subscriber['email'],
			'Nombre'                     => $subscriber['name'],
			'Estado'                     => Subscribers::STATUSES[ $subscriber['status'] ] ?? $subscriber['status'],
			'Idioma'                     => 'en' === $subscriber['language'] ? 'Inglés' : 'Español',
			'Origen'                     => self::sourceLabel( $subscriber['source'] ),
			'Página de registro'         => $subscriber['signup_page'],
			'Llegó desde'                => $subscriber['referrer'],
			'Consentimiento'             => $subscriber['consent_text'],
			'Fecha del consentimiento'   => self::date( $subscriber['consent_at'] ),
			'Confirmó su correo'         => self::date( $subscriber['confirmed_at'] ),
			'Último correo de confirmar' => self::date( $subscriber['confirmation_sent_at'] ),
			'Se dio de baja'             => self::date( $subscriber['unsubscribed_at'] ),
			'Alta'                       => self::date( $subscriber['created_at'] ),
			'Campañas recibidas'         => (string) (int) $subscriber['sends'],
			'Último envío'               => self::date( $subscriber['last_sent_at'] ),
		);
		echo '<div class="mt-nl-grid"><div class="mt-nl-card"><h2>' . esc_html( $subscriber['email'] ) . ' ' . self::badge( $subscriber['status'] ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $fields as $label => $value ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== (string) $value ? (string) $value : '—' ) . '</td></tr>';
		}
		echo '</table>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="mt-nl-inline" onsubmit="return this.elements.namedItem(\'bulk\').value!==\'delete\'||confirm(\'¿Eliminar este suscriptor y su historial? No se puede deshacer.\')">';
		echo '<input type="hidden" name="action" value="mt_nl_bulk"><input type="hidden" name="ids[]" value="' . (int) $subscriber['id'] . '"><input type="hidden" name="return" value="' . (int) $subscriber['id'] . '">';
		wp_nonce_field( 'mt_nl_bulk' );
		echo '<select name="bulk" aria-label="Acción">';
		foreach ( Subscribers::STATUSES as $status => $label ) {
			echo '<option value="status:' . esc_attr( $status ) . '"' . selected( $subscriber['status'], $status, false ) . '>Estado: ' . esc_html( $label ) . '</option>';
		}
		echo '<option value="resend">Reenviar email de confirmación</option><option value="delete">Eliminar (con su historial)</option></select> <button class="button">Aplicar</button> <button type="submit" class="button-link-delete" name="delete_id" value="' . (int) $id . '" onclick="return confirm(\'¿Eliminar este suscriptor y su historial?\')">Eliminar suscriptor</button></form></div>';

		$pages = Subscribers::pages( $subscriber );
		echo '<div class="mt-nl-card"><h2>Páginas que visitó antes de suscribirse</h2>';
		if ( ! $pages ) {
			echo '<p>Sin datos: la visita no aceptó las cookies analíticas o el contacto se añadió a mano o por importación.</p>';
		} else {
			echo '<ol class="mt-nl-pages">';
			foreach ( $pages as $page ) {
				echo '<li><a href="' . esc_url( home_url( $page['u'] ) ) . '" target="_blank" rel="noopener">' . esc_html( '' !== $page['t'] ? $page['t'] : $page['u'] ) . '</a> <code>' . esc_html( $page['u'] ) . '</code> <span class="description">' . esc_html( self::date( $page['at'] ) ) . '</span></li>';
			}
			echo '</ol>';
		}
		echo '</div>';

		$history = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT c.subject, d.status, d.sent_at, d.last_error FROM %i d LEFT JOIN %i c ON c.id = d.campaign_id WHERE d.subscriber_id = %d ORDER BY d.id DESC LIMIT 100', Campaigns::deliveriesTable(), Campaigns::table(), (int) $subscriber['id'] ), ARRAY_A );
		echo '<div class="mt-nl-card"><h2>Campañas</h2>';
		if ( ! $history ) {
			echo '<p>Todavía no ha recibido ninguna campaña.</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>Campaña</th><th>Estado</th><th>Enviado</th></tr></thead><tbody>';
			foreach ( $history as $row ) {
				echo '<tr><td>' . esc_html( (string) $row['subject'] ) . '</td><td>' . esc_html( self::deliveryLabel( $row['status'] ) ) . ( '' !== (string) $row['last_error'] && 'sent' !== $row['status'] ? ' <span class="description">(' . esc_html( $row['last_error'] ) . ')</span>' : '' ) . '</td><td>' . esc_html( self::date( $row['sent_at'] ) ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		echo '</div></div></div>';
	}

	public static function handleBulk() {
		self::guard( 'mt_nl_bulk' );
		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$action = isset( $_POST['bulk'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk'] ) ) : '';
		$return = isset( $_POST['return'] ) ? absint( $_POST['return'] ) : 0;
		$target = $return ? admin_url( 'admin.php?page=' . self::SLUG . '&subscriber=' . $return ) : admin_url( 'admin.php?page=' . self::SLUG );
		$ids    = array_values( array_filter( $ids ) );
		if ( ! empty( $_POST['delete_id'] ) ) {
			$ids    = array( absint( $_POST['delete_id'] ) );
			$action = 'delete';
		}
		if ( ! $ids || '' === $action ) {
			self::redirect( $target, 'error', 'Selecciona al menos un suscriptor y una acción.' );
		}

		if ( 'delete' === $action ) {
			$deleted = Subscribers::delete( $ids );
			if ( false === $deleted ) {
				self::redirect( $target, 'error', 'No se pudo eliminar. No se ha confirmado el borrado; vuelve a intentarlo.' );
			}
			AuditLog::record( 'newsletter.subscribers_deleted', 'newsletter', 0, array( 'count' => $deleted ) );
			self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), 'deleted', $deleted );
		}
		if ( 'resend' === $action ) {
			$sent = 0;
			foreach ( array_slice( $ids, 0, self::PER_PAGE ) as $id ) {
				$subscriber = Subscribers::find( $id );
				if ( $subscriber && 'pending' === $subscriber['status'] && PublicController::sendConfirmation( $subscriber ) ) {
					++$sent;
				}
			}
			self::redirect( $target, 'resent', $sent );
		}
		if ( 0 === strpos( $action, 'status:' ) ) {
			$status  = substr( $action, 7 );
			$changed = Subscribers::setStatus( $ids, $status );
			AuditLog::record(
				'newsletter.status_changed',
				'newsletter',
				0,
				array(
					'status' => $status,
					'count'  => count( $ids ),
				)
			);
			self::redirect( $target, 'bulk', $changed );
		}
		self::redirect( $target );
	}

	public static function handleAdd() {
		self::guard( 'mt_nl_add' );
		$status = isset( $_POST['status'] ) && 'subscribed' === $_POST['status'] ? 'subscribed' : 'pending';
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$result = ManualQueue::enqueue(
			$email,
			isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'es',
			$status,
			'admin',
			'Añadido a mano por ' . wp_get_current_user()->user_login . ( 'subscribed' === $status ? ', que confirma tener su consentimiento' : '' )
		);
		if ( 'queued' === $result ) {
			self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), 'manual', 'Contacto en cola: se incorporará en una tanda de 12 a 98 cada 6 horas.' );
		}
		if ( 'duplicate' === $result || 'error' === $result ) {
			self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), 'error' === $result ? 'error' : 'manual', 'error' === $result ? 'No se pudo guardar el contacto. La cola puede estar ocupada; vuelve a intentarlo.' : 'El contacto ya existe o está en cola. No se ha duplicado ni modificado.' );
		}
		$codes = array(
			'duplicate' => 'updated',
			'error'     => 'error',
			'added'     => 'added',
			'updated'   => 'updated',
			'unchanged' => 'updated',
			'blocked'   => 'blocked',
		);
		self::redirect( admin_url( 'admin.php?page=' . self::SLUG ), $codes[ $result ] ?? 'invalid' );
	}

	public static function handleExport() {
		self::guard( 'mt_nl_export' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified by guard().
		$args = array(
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
			'language' => isset( $_GET['language'] ) ? sanitize_key( wp_unslash( $_GET['language'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'per_page' => 500,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$book  = new XlsxWorkbook();
		$sheet = $book->addSheet( 'Suscriptores', array( 34, 24, 20, 8, 14, 36, 36, 18, 18, 18, 10, 90 ) );
		$book->addRow( $sheet, array_map( static fn( $title ) => array( $title, XlsxWorkbook::HEADER ), array( 'Correo', 'Nombre', 'Estado', 'Idioma', 'Origen', 'Página de registro', 'Llegó desde', 'Alta', 'Confirmado', 'Baja', 'Envíos', 'Páginas visitadas' ) ) );
		$book->freezeRows( $sheet, 1 );
		for ( $page = 1; ; $page++ ) {
			$result = Subscribers::search( $args + array( 'page' => $page ) );
			foreach ( $result['rows'] as $row ) {
				$book->addRow(
					$sheet,
					array(
						$row['email'],
						$row['name'],
						Subscribers::STATUSES[ $row['status'] ] ?? $row['status'],
						strtoupper( $row['language'] ),
						self::sourceLabel( $row['source'] ),
						$row['signup_page'],
						$row['referrer'],
						array( $row['created_at'], XlsxWorkbook::DATETIME ),
						array( (string) $row['confirmed_at'], XlsxWorkbook::DATETIME ),
						array( (string) $row['unsubscribed_at'], XlsxWorkbook::DATETIME ),
						(int) $row['sends'],
						implode( ' → ', array_column( Subscribers::pages( $row ), 'u' ) ),
					)
				);
			}
			if ( count( $result['rows'] ) < 500 ) {
				break;
			}
		}
		$book->autoFilter( $sheet, 1, $book->rowCount( $sheet ), 12 );

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$path = wp_tempnam( 'newsletter.xlsx' );
		$book->save( $path );
		AuditLog::record( 'export.newsletter_subscribers', 'newsletter', 0, array( 'rows' => $book->rowCount( $sheet ) - 1 ) );
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="newsletter-suscriptores-' . wp_date( 'Y-m-d' ) . '.xlsx"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming a server-generated temporary file.
		wp_delete_file( $path );
		exit;
	}

	// ── Campañas ──────────────────────────────────────────────────────────

	public static function renderCampaigns() {
		self::guard();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only navigation.
		if ( isset( $_GET['edit'] ) || isset( $_GET['new'] ) ) {
			self::renderEditor( isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0, isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : '' );
			return;
		}
		if ( isset( $_GET['view'] ) ) {
			self::renderCampaign( absint( $_GET['view'] ) );
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$base = admin_url( 'admin.php?page=' . self::CAMPAIGNS );
		echo '<div class="wrap mt-nl-admin"><h1 class="wp-heading-inline">Newsletter · Campañas</h1> <a href="' . esc_url( add_query_arg( 'new', 1, $base ) ) . '" class="page-title-action">Nueva campaña</a><hr class="wp-header-end">';
		self::notice();
		self::styles();
		self::renderQueueStatus();

		echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Asunto</th><th>Audiencia</th><th>Estado</th><th>Progreso</th><th>Creada</th><th>Terminada</th><th>Acciones</th></tr></thead><tbody>';
		$campaigns = Campaigns::latest();
		if ( ! $campaigns ) {
			echo '<tr><td colspan="7">Todavía no hay campañas. Crea la primera con «Nueva campaña».</td></tr>';
		}
		foreach ( $campaigns as $campaign ) {
			$id     = (int) $campaign['id'];
			$view   = add_query_arg( 'draft' === $campaign['status'] ? 'edit' : 'view', $id, $base );
			$action = static function ( $command, $label, $confirm = '' ) use ( $id ) {
				$url = wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_campaign&do=' . $command . '&id=' . $id ), 'mt_nl_campaign' );
				return '<a href="' . esc_url( $url ) . '"' . ( '' !== $confirm ? ' onclick="return confirm(\'' . esc_js( $confirm ) . '\')"' : '' ) . '>' . esc_html( $label ) . '</a>';
			};
			$links  = array( '<a href="' . esc_url( $view ) . '">' . ( 'draft' === $campaign['status'] ? 'Editar' : 'Ver' ) . '</a>', $action( 'duplicate', 'Duplicar' ) );
			if ( in_array( $campaign['status'], array( 'draft', 'sending' ), true ) ) {
				$links[] = 'draft' === $campaign['status'] ? $action( 'delete', 'Eliminar', '¿Eliminar este borrador?' ) : $action( 'cancel', 'Cancelar envío', '¿Cancelar el envío? Los correos que faltan no se enviarán.' );
			}
			echo '<tr><td><a href="' . esc_url( $view ) . '"><strong>' . esc_html( '' !== $campaign['subject'] ? $campaign['subject'] : '(sin asunto)' ) . '</strong></a></td>';
			echo '<td>' . esc_html( self::audienceLabel( $campaign ) ) . '</td>';
			echo '<td>' . esc_html( Campaigns::STATUSES[ $campaign['status'] ] ?? $campaign['status'] ) . '</td>';
			echo '<td>' . ( 'draft' === $campaign['status'] ? '—' : (int) $campaign['sent'] . ' / ' . (int) $campaign['total'] . ( (int) $campaign['failed'] ? ' · <span class="mt-nl-bad">' . (int) $campaign['failed'] . ' fallidos</span>' : '' ) ) . '</td>';
			echo '<td>' . esc_html( self::date( $campaign['created_at'] ) ) . '</td><td>' . esc_html( self::date( $campaign['finished_at'] ) ) . '</td>';
			echo '<td>' . implode( ' · ', $links ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links are escaped above.
		}
		echo '</tbody></table></div>';
	}

	private static function renderQueueStatus() {
		$pending = Campaigns::pendingCount();
		$next    = wp_next_scheduled( Campaigns::CRON_HOOK );
		echo '<div class="mt-nl-card mt-nl-queue"><p><strong>Cola de envío:</strong> ' . (int) $pending . ' correos pendientes · límite ' . (int) Settings::get( 'rate_per_hour' ) . ' por hora';
		echo $next ? ' · próxima tanda ' . esc_html( wp_date( 'H:i', $next ) ) : '';
		echo '</p>';
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			echo '<p class="description">WP-Cron está desactivado en wp-config.php: asegúrate de que el hosting ejecuta <code>wp-cron.php</code> cada pocos minutos, o pulsa «Procesar ahora».</p>';
		}
		if ( $pending ) {
			echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_process' ), 'mt_nl_process' ) ) . '">Procesar ahora una tanda</a></p>';
		}
		echo '</div>';
	}

	private static function renderEditor( $id, $template ) {
		$campaign = $id ? Campaigns::find( $id ) : null;
		if ( $id && ( ! $campaign || 'draft' !== $campaign['status'] ) ) {
			self::renderCampaign( $id );
			return;
		}
		$campaign = $campaign ? $campaign : array_merge( Campaigns::clean( array() ), self::template( $template ) );
		$counts   = array();
		foreach ( array_keys( Campaigns::AUDIENCES ) as $audience ) {
			foreach ( array( '', 'es', 'en' ) as $language ) {
				$counts[ $audience . '|' . $language ] = Campaigns::audienceCount( $audience, $language );
			}
		}

		echo '<div class="wrap mt-nl-admin"><h1>' . ( $id ? 'Editar campaña' : 'Nueva campaña' ) . '</h1>';
		self::notice();
		self::styles();
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::CAMPAIGNS ) ) . '">← Volver a campañas</a>';
		if ( ! $id ) {
			echo ' · Empezar con plantilla: <a href="' . esc_url( admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&new=1&template=offer' ) ) . '">Oferta</a> · <a href="' . esc_url( admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&new=1&template=confirm' ) ) . '">Pedir confirmación a pendientes</a>';
		}
		echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="mt-nl-campaign">';
		echo '<input type="hidden" name="action" value="mt_nl_save_campaign"><input type="hidden" name="id" value="' . (int) $id . '">';
		wp_nonce_field( 'mt_nl_save_campaign' );
		echo '<div class="mt-nl-grid mt-nl-editor"><div>';
		echo '<p><label for="mt-nl-subject"><strong>Asunto</strong></label><br><input type="text" id="mt-nl-subject" name="subject" value="' . esc_attr( $campaign['subject'] ) . '" maxlength="200" class="large-text" required></p>';
		echo '<p><label for="mt-nl-preheader"><strong>Texto de vista previa</strong> <span class="description">(opcional: lo que se ve junto al asunto en la bandeja de entrada)</span></label><br><input type="text" id="mt-nl-preheader" name="preheader" value="' . esc_attr( $campaign['preheader'] ) . '" maxlength="200" class="large-text"></p>';
		wp_editor(
			$campaign['body'],
			'mt_nl_body',
			array(
				'textarea_name' => 'body',
				'textarea_rows' => 20,
				'media_buttons' => true,
			)
		);
		echo '</div><div><div class="mt-nl-card"><h2>Destinatarios</h2>';
		echo '<p><label for="mt-nl-audience">Estado</label><br><select id="mt-nl-audience" name="audience_status">' . self::options( Campaigns::AUDIENCES, $campaign['audience_status'] ) . '</select></p>';
		echo '<p><label for="mt-nl-language">Idioma</label><br><select id="mt-nl-language" name="audience_language">' . self::options(
			array(
				''   => 'Todos',
				'es' => 'Español',
				'en' => 'Inglés',
			),
			$campaign['audience_language']
		) . '</select></p>';
		echo '<p><strong>Se enviará a <span id="mt-nl-count">0</span> personas.</strong></p><p class="description">Nunca se envía a direcciones dadas de baja o rebotadas.</p></div>';

		echo '<div class="mt-nl-card"><h2>Personalizar</h2><p class="description">Escribe estas etiquetas en el asunto o el texto:</p><ul>';
		foreach ( Mailer::TAGS as $tag => $description ) {
			echo '<li><code>' . esc_html( $tag ) . '</code> ' . esc_html( $description ) . '</li>';
		}
		echo '</ul><p class="description">El pie con el enlace de baja se añade siempre automáticamente.</p></div>';

		echo '<div class="mt-nl-card"><h2>Enviar</h2><p><button class="button" name="do" value="save">Guardar borrador</button></p>';
		echo '<p><label for="mt-nl-test">Enviar prueba a</label><br><input type="email" id="mt-nl-test" name="test_email" value="' . esc_attr( wp_get_current_user()->user_email ) . '" class="regular-text"> <button class="button" name="do" value="test">Enviar prueba</button></p>';
		if ( $id ) {
			echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_preview&id=' . $id ), 'mt_nl_preview' ) ) . '">Vista previa del borrador guardado</a></p>';
		}
		echo '<p><button class="button button-primary button-hero" name="do" value="send" id="mt-nl-send">Enviar campaña ahora</button></p></div>';
		echo '</div></div></form>';
		echo '<script>(function(){var counts=' . wp_json_encode( $counts ) . ';var a=document.getElementById("mt-nl-audience"),l=document.getElementById("mt-nl-language"),c=document.getElementById("mt-nl-count");function n(){return counts[a.value+"|"+l.value]||0}function u(){c.textContent=n()}a.addEventListener("change",u);l.addEventListener("change",u);u();document.getElementById("mt-nl-campaign").addEventListener("submit",function(e){var b=e.submitter;if(b&&b.value==="send"&&!confirm("¿Enviar la campaña a "+n()+" personas? Una vez enviada no se puede deshacer.")){e.preventDefault()}})})();</script>';
		echo '</div>';
	}

	private static function renderCampaign( $id ) {
		$campaign = Campaigns::find( $id );
		echo '<div class="wrap mt-nl-admin"><h1>Campaña</h1>';
		self::notice();
		self::styles();
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::CAMPAIGNS ) ) . '">← Volver a campañas</a></p>';
		if ( ! $campaign ) {
			echo '<p>Esta campaña no existe.</p></div>';
			return;
		}
		$counts = Campaigns::deliveryCounts( $id );
		echo '<div class="mt-nl-card"><h2>' . esc_html( $campaign['subject'] ) . '</h2><p>' . esc_html( Campaigns::STATUSES[ $campaign['status'] ] ?? $campaign['status'] ) . ' · ' . esc_html( self::audienceLabel( $campaign ) ) . '</p>';
		echo '<p><strong>' . (int) $counts['sent'] . '</strong> enviados · <strong>' . (int) ( $counts['pending'] + $counts['sending'] ) . '</strong> en cola · <strong>' . (int) $counts['failed'] . '</strong> fallidos · <strong>' . (int) ( $counts['skipped'] + $counts['cancelled'] ) . '</strong> omitidos (bajas o cancelados) de ' . (int) $campaign['total'] . '.</p>';
		echo '<p>En cola desde ' . esc_html( self::date( $campaign['queued_at'] ) ) . ( $campaign['finished_at'] ? ' · terminada ' . esc_html( self::date( $campaign['finished_at'] ) ) : '' ) . '</p>';
		echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_preview&id=' . $id ), 'mt_nl_preview' ) ) . '">Ver el correo</a> ';
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_campaign&do=duplicate&id=' . $id ), 'mt_nl_campaign' ) ) . '">Duplicar</a>';
		if ( 'sending' === $campaign['status'] ) {
			echo ' <a class="button" onclick="return confirm(\'¿Cancelar el envío? Los correos que faltan no se enviarán.\')" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mt_nl_campaign&do=cancel&id=' . $id ), 'mt_nl_campaign' ) ) . '">Cancelar envío</a>';
		}
		echo '</p></div>';
		self::renderQueueStatus();

		$failures = Campaigns::failures( $id );
		if ( $failures ) {
			echo '<div class="mt-nl-card"><h2>Fallidos</h2><table class="widefat striped"><thead><tr><th>Correo</th><th>Intentos</th><th>Motivo</th></tr></thead><tbody>';
			foreach ( $failures as $failure ) {
				echo '<tr><td>' . esc_html( $failure['email'] ) . '</td><td>' . (int) $failure['attempts'] . '</td><td>' . esc_html( $failure['last_error'] ) . '</td></tr>';
			}
			echo '</tbody></table><p class="description">Si una dirección no existe, márcala como «Rebotado» en Suscriptores para no volver a enviarle.</p></div>';
		}
		echo '</div>';
	}

	public static function handleCampaign() {
		self::guard( 'mt_nl_save_campaign' );
		$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$do    = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : 'save';
		$data  = array(
			'subject'           => isset( $_POST['subject'] ) ? wp_unslash( $_POST['subject'] ) : '',
			'preheader'         => isset( $_POST['preheader'] ) ? wp_unslash( $_POST['preheader'] ) : '',
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Campaigns::clean() runs wp_kses_post().
			'body'              => isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '',
			'audience_status'   => isset( $_POST['audience_status'] ) ? sanitize_text_field( wp_unslash( $_POST['audience_status'] ) ) : 'subscribed',
			'audience_language' => isset( $_POST['audience_language'] ) ? sanitize_key( wp_unslash( $_POST['audience_language'] ) ) : '',
		);
		$saved = Campaigns::save( $data, $id );
		if ( ! $saved ) {
			self::redirect( admin_url( 'admin.php?page=' . self::CAMPAIGNS . ( $id ? '&view=' . $id : '' ) ), 'locked' );
		}
		$edit = admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&edit=' . $saved );

		if ( 'test' === $do ) {
			$email = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
			if ( ! is_email( $email ) ) {
				self::redirect( $edit, 'invalid' );
			}
			$campaign = Campaigns::find( $saved );
			if ( Campaigns::sendTest( $campaign, $email, wp_get_current_user()->display_name ) ) {
				self::redirect( $edit, 'test_sent', $email );
			}
			self::redirect( $edit, 'test_failed', Mailer::lastError() );
		}

		if ( 'send' === $do ) {
			$campaign = Campaigns::find( $saved );
			if ( '' === trim( (string) $campaign['subject'] ) || '' === trim( wp_strip_all_tags( (string) $campaign['body'] ) ) ) {
				self::redirect( $edit, 'empty' );
			}
			if ( 0 === Campaigns::audienceCount( $campaign['audience_status'], $campaign['audience_language'] ) ) {
				self::redirect( $edit, 'no_audience' );
			}
			$queued = Campaigns::queue( $saved );
			if ( false === $queued ) {
				self::redirect( admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&view=' . $saved ), 'locked' );
			}
			AuditLog::record( 'newsletter.campaign_queued', 'newsletter_campaign', $saved, array( 'recipients' => $queued ) );
			self::redirect( admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&view=' . $saved ), 'queued', $queued );
		}
		self::redirect( $edit, 'draft' );
	}

	public static function handleCampaignAction() {
		global $wpdb;
		self::guard( 'mt_nl_campaign' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified by guard().
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$list = admin_url( 'admin.php?page=' . self::CAMPAIGNS );
		if ( 'duplicate' === $do ) {
			$copy = Campaigns::duplicate( $id );
			self::redirect( $copy ? admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&edit=' . $copy ) : $list, 'duplicated' );
		}
		if ( 'cancel' === $do ) {
			Campaigns::cancel( $id );
			AuditLog::record( 'newsletter.campaign_cancelled', 'newsletter_campaign', $id );
			self::redirect( admin_url( 'admin.php?page=' . self::CAMPAIGNS . '&view=' . $id ), 'cancelled' );
		}
		if ( 'delete' === $do ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE id = %d AND status = 'draft'", Campaigns::table(), $id ) );
			self::redirect( $list, 'removed' );
		}
		self::redirect( $list );
	}

	public static function handlePreview() {
		self::guard( 'mt_nl_preview' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified by guard().
		$campaign = Campaigns::find( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
		if ( ! $campaign ) {
			wp_die( 'Esta campaña no existe.' );
		}
		$user     = wp_get_current_user();
		$rendered = Mailer::renderCampaign(
			$campaign,
			array(
				'id'       => 0,
				'email'    => $user->user_email,
				'name'     => $user->display_name,
				'language' => '' !== (string) $campaign['audience_language'] ? $campaign['audience_language'] : 'es',
			)
		);
		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo $rendered['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Email HTML built from kses-filtered content and escaped values.
		exit;
	}

	public static function handleProcess() {
		self::guard( 'mt_nl_process' );
		$sent = Campaigns::dispatch();
		self::redirect( admin_url( 'admin.php?page=' . self::CAMPAIGNS ), 'processed', $sent );
	}

	// ── Importar ──────────────────────────────────────────────────────────

	public static function renderImport() {
		self::guard();
		echo '<div class="wrap mt-nl-admin"><h1>Newsletter · Importar contactos</h1>';
		self::notice();
		self::styles();
		$report = get_transient( 'mt_nl_import_' . get_current_user_id() );
		if ( is_array( $report ) ) {
			delete_transient( 'mt_nl_import_' . get_current_user_id() );
			echo '<div class="mt-nl-card"><h2>Resultado de la importación</h2><ul>';
			$lines = array(
				'error'      => 'con error de guardado; vuelve a cargar esos contactos',
				'queued'     => 'añadidos a la cola manual (12 a 98 cada 6 horas)',
				'added'      => 'añadidos',
				'updated'    => 'actualizados',
				'unchanged'  => 'ya estaban y no cambian',
				'blocked'    => 'no se tocan porque se dieron de baja o rebotaron',
				'duplicates' => 'repetidos dentro del archivo',
				'invalid'    => 'con correo no válido',
			);
			foreach ( $lines as $key => $label ) {
				echo '<li><strong>' . (int) ( $report[ $key ] ?? 0 ) . '</strong> ' . esc_html( $label ) . '</li>';
			}
			echo '</ul>';
			if ( $report['errors'] ) {
				echo '<details><summary>Detalle (' . count( $report['errors'] ) . ')</summary><ul>';
				foreach ( $report['errors'] as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul></details>';
			}
			echo '</div>';
		}

		echo '<div class="mt-nl-card"><form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="mt_nl_import">';
		wp_nonce_field( 'mt_nl_import' );
		echo '<p><label for="mt-nl-file"><strong>Archivo CSV o Excel (.xlsx)</strong></label><br><input type="file" id="mt-nl-file" name="file" accept=".csv,.txt,.xlsx" required></p>';
		echo '<p class="description">Una fila por contacto. Columnas reconocidas por su título: <code>email</code> o <code>correo</code> (obligatoria), <code>nombre</code> o <code>name</code>, <code>idioma</code> o <code>language</code> (es/en). Sin títulos, la primera columna con @ es el correo y la siguiente el nombre. Hasta ' . (int) Importer::MAX_ROWS . ' filas y 10 MB.</p>';
		echo '<fieldset><legend><strong>Estado de los contactos nuevos</strong></legend>';
		echo '<p><label><input type="radio" name="status" value="pending" checked> Pendiente de confirmar: se solicitará la confirmación automáticamente al incorporarlo en su tanda.</label></p>';
		echo '<p><label><input type="radio" name="status" value="subscribed"> Suscrito</label><br><label class="mt-nl-indent"><input type="checkbox" name="consent" value="1"> Confirmo que estos contactos aceptaron recibir comunicaciones comerciales de MeTransfers (RGPD y LSSI).</label></p></fieldset>';
		echo '<p><label>Idioma si el archivo no lo indica <select name="language">' . self::options(
			array(
				'es' => 'Español',
				'en' => 'Inglés',
			),
			'es'
		) . '</select></label></p>';
		echo '<p>Los contactos existentes se omiten sin cambiar su nombre, estado ni consentimiento.</p>';
		echo '<p class="description">Los contactos que se dieron de baja o rebotaron nunca se vuelven a suscribir con una importación.</p>';
		echo '<p>Los contactos del archivo se guardan en la cola manual y se incorporan en tandas aleatorias de 12 a 98 cada 6 horas. Los duplicados no modifican contactos existentes.</p><p><button class="button button-primary">Importar a la cola manual</button></p></form></div></div>';
	}

	public static function handleImport() {
		self::guard( 'mt_nl_import' );
		$status = isset( $_POST['status'] ) && 'subscribed' === $_POST['status'] ? 'subscribed' : 'pending';
		$back   = admin_url( 'admin.php?page=' . self::IMPORT );
		if ( 'subscribed' === $status && empty( $_POST['consent'] ) ) {
			self::redirect( $back, 'consent' );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Large imports.
		}
		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Upload array, validated by Importer.
			$rows = Importer::rowsFromUpload( isset( $_FILES['file'] ) ? (array) $_FILES['file'] : array() );
		} catch ( \RuntimeException $error ) {
			self::redirect( $back, 'error', $error->getMessage() );
		}
		$user   = wp_get_current_user();
		$report = Importer::import(
			$rows,
			$status,
			isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'es',
			! empty( $_POST['update_names'] ),
			'Importado por ' . $user->user_login . ' el ' . wp_date( 'Y-m-d' ) . ( 'subscribed' === $status ? ', que confirma su consentimiento' : '' ),
			true
		);
		set_transient( 'mt_nl_import_' . get_current_user_id(), $report, HOUR_IN_SECONDS );
		AuditLog::record( 'newsletter.imported', 'newsletter', 0, array_diff_key( $report, array( 'errors' => true ) ) + array( 'status' => $status ) );
		self::redirect( $back, 'import' );
	}

	// ── Ajustes ───────────────────────────────────────────────────────────

	public static function renderSettings() {
		self::guard();
		$settings = Settings::all();
		echo '<div class="wrap mt-nl-admin"><h1>Newsletter · Ajustes</h1>';
		self::notice();
		self::styles();

		$errors = Mailer::configurationErrors();
		echo '<div class="mt-nl-card"><p><strong>Servidor de correo:</strong> ' . ( ! $errors ? 'Configuración local SMTP completa, con cifrado obligatorio. Falta verificar la autenticación en un correo recibido.' : '<span class="mt-nl-bad">Envío del newsletter bloqueado.</span> Revisa las credenciales, el remitente, el puerto y el cifrado SMTP en Reservas → Ajustes Generales.' ) . '</p>';
		foreach ( $errors as $error ) {
			echo '<p class="mt-nl-bad">' . esc_html( $error ) . '</p>';
		}
		$sender = Mailer::sender();
		echo '<p><strong>Remitente:</strong> ' . esc_html( $sender['name'] . ' <' . $sender['email'] . '>' ) . '</p>';
		$sender_parts  = explode( '@', $sender['email'] );
		$sender_domain = strtolower( (string) ( $sender_parts[1] ?? '' ) );
		$site_domain   = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		if ( '' !== $sender_domain && preg_replace( '/^www\./', '', $site_domain ) !== $sender_domain ) {
			echo '<p class="mt-nl-bad">El dominio del remitente (' . esc_html( $sender_domain ) . ') difiere del dominio de la web. Verifica SPF, DKIM y DMARC en el dominio del remitente y la autorización del proveedor SMTP.</p>';
		}
		echo '<p>Usa un buzón autorizado por el proveedor SMTP, preferiblemente del dominio de la web. El dominio del remitente debe autenticar mediante SPF y DKIM y alinear SPF o DKIM con DMARC. Cambiar solo la dirección del remitente no autentica el dominio.</p>';
		echo '<p>Comprueba en Gmail → Mostrar original: SPF, DKIM y DMARC con resultado PASS. El proveedor debe firmar también List-Unsubscribe y List-Unsubscribe-Post para la baja en un clic. Estos resultados no se pueden confirmar desde los ajustes locales.</p></div>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="mt_nl_settings">';
		wp_nonce_field( 'mt_nl_settings' );
		echo '<div class="mt-nl-card"><h2>Popup y formulario del pie</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">Mostrar el popup</th><td><label><input type="checkbox" name="enabled" value="1"' . checked( $settings['enabled'], true, false ) . '> Activado</label><p class="description">No aparece nunca durante la reserva ni el pago, ni en el Portal de Hoteles.</p></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-delay">Aparece a los</label></th><td><input type="number" id="mt-nl-delay" name="delay" min="3" max="120" value="' . (int) $settings['delay'] . '" class="small-text"> segundos</td></tr>';
		echo '<tr><th scope="row">Formulario en el pie</th><td><label><input type="checkbox" name="footer_enabled" value="1"' . checked( $settings['footer_enabled'], true, false ) . '> Mostrar el bloque de suscripción en el pie de todas las páginas</label><p class="description">Siempre visible y sin molestar: también aparece en las páginas donde el popup no sale. Quien se suscribe desde aquí ya no ve el popup.</p></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-frequency">Mostrar como máximo una vez cada</label></th><td><input type="number" id="mt-nl-frequency" name="frequency_hours" min="1" max="720" value="' . (int) $settings['frequency_hours'] . '" class="small-text"> horas<p class="description">Por visitante: una vez por sesión y, como mucho, una vez en ese plazo por IP y por navegador. El navegador solo consulta al servidor cuando le toca mostrarlo.</p></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-dismiss">Si lo cierran, no volver a mostrarlo en</label></th><td><input type="number" id="mt-nl-dismiss" name="dismiss_days" min="1" max="365" value="' . (int) $settings['dismiss_days'] . '" class="small-text"> días</td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-excluded">No mostrar en estas rutas</label></th><td><textarea id="mt-nl-excluded" name="excluded_paths" rows="4" class="large-text code" placeholder="/contacto/">' . esc_textarea( $settings['excluded_paths'] ) . '</textarea><p class="description">Una por línea. Vale también para su versión en inglés (/en/…).</p></td></tr>';
		echo '</table></div>';

		echo '<div class="mt-nl-card"><h2>Suscripción y envío</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">Doble confirmación</th><td><input type="hidden" name="double_opt_in" value="1"><label><input type="checkbox" checked disabled> Obligatoria en las altas públicas: verificamos la dirección antes de enviar promociones.</label></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-rate">Máximo de correos por hora</label></th><td><input type="number" id="mt-nl-rate" name="rate_per_hour" min="10" max="5000" value="' . (int) $settings['rate_per_hour'] . '" class="small-text"><p class="description">Ajústalo al límite de tu proveedor de correo (en SiteGround depende del plan). Las campañas grandes se reparten en varias horas.</p></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-from-name">Nombre del remitente</label></th><td><input type="text" id="mt-nl-from-name" name="from_name" value="' . esc_attr( $settings['from_name'] ) . '" class="regular-text" placeholder="El de Ajustes Generales"></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-from-email">Correo del remitente</label></th><td><input type="email" id="mt-nl-from-email" name="from_email" value="' . esc_attr( $settings['from_email'] ) . '" class="regular-text" placeholder="El de Ajustes Generales"><p class="description">Déjalo vacío para usar el del SMTP. Si pones otro, tu servidor SMTP debe permitir enviar con él.</p></td></tr>';
		echo '<tr><th scope="row"><label for="mt-nl-footer">Pie de los correos</label></th><td><textarea id="mt-nl-footer" name="footer" rows="3" class="large-text">' . esc_textarea( $settings['footer'] ) . '</textarea><p class="description">Identifica a la empresa (nombre y dirección o web), como pide la LSSI. El enlace de baja se añade siempre.</p></td></tr>';
		echo '</table></div>';

		foreach ( Settings::TEXTS as $language => $defaults ) {
			echo '<div class="mt-nl-card"><h2>Textos en ' . ( 'es' === $language ? 'español' : 'inglés' ) . '</h2><table class="form-table" role="presentation">';
			$labels = array(
				'eyebrow'            => 'Etiqueta superior (en ámbar, como en la web)',
				'title'              => 'Título del popup',
				'text'               => 'Texto del popup',
				'button'             => 'Botón',
				'consent'            => 'Casilla de consentimiento',
				'success_pending'    => 'Mensaje tras suscribirse (con doble confirmación)',
				'success_subscribed' => 'Mensaje tras suscribirse (sin doble confirmación)',
				'footer_title'       => 'Título del bloque del pie',
				'footer_text'        => 'Texto del bloque del pie',
				'confirm_subject'    => 'Asunto del correo de confirmación',
				'confirm_body'       => 'Texto del correo de confirmación',
			);
			foreach ( $labels as $key => $label ) {
				$value = $settings['texts'][ $language ][ $key ] ?? '';
				$name  = 'texts[' . $language . '][' . $key . ']';
				$id    = 'mt-nl-' . $language . '-' . $key;
				$field = in_array( $key, array( 'text', 'confirm_body', 'footer_text' ), true )
					? '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3" class="large-text" placeholder="' . esc_attr( $defaults[ $key ] ) . '">' . esc_textarea( $value ) . '</textarea>'
					: '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="large-text" placeholder="' . esc_attr( $defaults[ $key ] ) . '">';
				echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>' . $field . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Field markup escaped above.
			}
			echo '</table><p class="description">Vacío = texto por defecto (el que se ve en gris).</p></div>';
		}
		submit_button( 'Guardar ajustes' );
		echo '</form></div>';
	}

	public static function handleSettings() {
		self::guard( 'mt_nl_settings' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings::sanitize() cleans every field.
		update_option( Settings::OPTION, Settings::sanitize( wp_unslash( $_POST ) ), false );
		AuditLog::record( 'newsletter.settings_saved', 'newsletter', 0 );
		self::redirect( admin_url( 'admin.php?page=' . self::SETTINGS_PAGE ), 'saved' );
	}

	// ── Helpers ───────────────────────────────────────────────────────────

	/**
	 * Capability always; the nonce for every change (links and forms alike).
	 */
	private static function guard( $nonce = '' ) {
		if ( ! current_user_can( Capabilities::MANAGE_NEWSLETTER ) ) {
			wp_die( 'No tienes permisos para gestionar el newsletter.', '', array( 'response' => 403 ) );
		}
		if ( '' !== $nonce ) {
			check_admin_referer( $nonce );
		}
	}

	/**
	 * @return never
	 */
	private static function redirect( $url, $notice = '', $value = '' ) {
		if ( '' !== $notice ) {
			$url = add_query_arg(
				array(
					'mt_nl_notice' => $notice,
					'mt_nl_value'  => rawurlencode( (string) $value ),
				),
				$url
			);
		}
		wp_safe_redirect( $url );
		exit;
	}

	private static function notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display only.
		$code = isset( $_GET['mt_nl_notice'] ) ? sanitize_key( wp_unslash( $_GET['mt_nl_notice'] ) ) : '';
		if ( ! isset( self::NOTICES[ $code ] ) ) {
			return;
		}
		$value = isset( $_GET['mt_nl_value'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['mt_nl_value'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$bad = in_array( $code, array( 'blocked', 'invalid', 'locked', 'test_failed', 'empty', 'no_audience', 'consent', 'error' ), true );
		echo '<div class="notice notice-' . ( $bad ? 'error' : 'success' ) . ' is-dismissible"><p>' . esc_html( sprintf( self::NOTICES[ $code ], $value ) ) . '</p></div>';
	}

	private static function pagination( $total, $page ) {
		$pages = (int) ceil( $total / self::PER_PAGE );
		echo '<div class="tablenav-pages"><span class="displaying-num">' . (int) $total . ' suscriptores</span>';
		if ( $pages > 1 ) {
			echo ' ' . wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $page,
						'total'   => $pages,
					)
				)
			);
		}
		echo '</div>';
	}

	private static function exportUrl( array $args ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'mt_nl_export',
					'status'   => $args['status'],
					'language' => $args['language'],
					's'        => $args['search'],
				),
				admin_url( 'admin-post.php' )
			),
			'mt_nl_export'
		);
	}

	private static function options( array $options, $selected ) {
		$html = '';
		foreach ( $options as $value => $label ) {
			$html .= '<option value="' . esc_attr( (string) $value ) . '"' . selected( (string) $selected, (string) $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html;
	}

	private static function badge( $status ) {
		return '<span class="mt-nl-badge mt-nl-badge--' . esc_attr( $status ) . '">' . esc_html( Subscribers::STATUSES[ $status ] ?? $status ) . '</span>';
	}

	private static function sourceLabel( $source ) {
		$labels = array(
			'popup'  => 'Popup de la web',
			'footer' => 'Formulario del pie',
			'import' => 'Importación',
			'admin'  => 'Añadido a mano',
		);
		return $labels[ $source ] ?? (string) $source;
	}

	private static function deliveryLabel( $status ) {
		$labels = array(
			'pending'   => 'En cola',
			'sending'   => 'Enviando',
			'sent'      => 'Enviado',
			'failed'    => 'Fallido',
			'skipped'   => 'Omitido',
			'cancelled' => 'Cancelado',
		);
		return $labels[ $status ] ?? (string) $status;
	}

	private static function audienceLabel( array $campaign ) {
		$language = array(
			''   => 'todos los idiomas',
			'es' => 'español',
			'en' => 'inglés',
		);
		return ( Campaigns::AUDIENCES[ $campaign['audience_status'] ] ?? $campaign['audience_status'] ) . ' · ' . ( $language[ (string) $campaign['audience_language'] ] ?? '' );
	}

	private static function date( $value ) {
		$value = (string) $value;
		if ( '' === $value || 0 === strpos( $value, '0000' ) ) {
			return '';
		}
		$time = strtotime( $value . ' UTC' );
		return $time ? wp_date( 'd/m/Y H:i', $time ) : $value;
	}

	/**
	 * @return array{subject?: string, body?: string, audience_status?: string}
	 */
	private static function template( $template ) {
		// The site's navy button, as on «Buscar vehículos».
		$button = static function ( $url, $label ) {
			return '<p style="text-align:center"><a href="' . $url . '" style="' . esc_attr( Brand::buttonStyle() ) . '">' . $label . '</a></p>';
		};
		if ( 'confirm' === $template ) {
			return array(
				'subject'         => 'Confirma tu suscripción a MeTransfers',
				'audience_status' => 'pending',
				'body'            => "<h1>Confirma tu suscripción</h1>\n<p>Hola {{nombre}},</p>\n<p>Para seguir recibiendo nuestras ofertas y novedades necesitamos que confirmes tu suscripción. Solo tienes que pulsar este botón:</p>\n"
					. $button( '{{confirmar}}', 'Confirmar suscripción' )
					. "\n<p>Si no quieres recibir nuestros correos, ignora este mensaje y no te volveremos a escribir.</p>",
			);
		}
		if ( 'offer' === $template ) {
			return array(
				'subject' => '{{nombre}}, tu próximo traslado con descuento',
				'body'    => "<h1>Oferta para suscriptores</h1>\n<p>Hola {{nombre}},</p>\n<p>Reserva tu traslado privado en Barcelona y Cataluña con un descuento especial para suscriptores: chófer profesional, precio cerrado y recogida en el aeropuerto, el puerto o tu hotel.</p>\n"
					. $button( '{{web}}', 'Reservar mi traslado' )
					. "\n<p>¡Buen viaje!<br>El equipo de MeTransfers</p>",
			);
		}
		return array();
	}

	private static function styles() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		echo '<style>.mt-nl-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 20px;margin:16px 0}.mt-nl-grid{display:grid;gap:16px}@media(min-width:1100px){.mt-nl-grid{grid-template-columns:minmax(0,1.6fr) minmax(300px,1fr)}.mt-nl-editor{grid-template-columns:minmax(0,1fr) 340px}}.mt-nl-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600;background:#f0f0f1}.mt-nl-badge--subscribed{background:#d1f2dc;color:#0a5c2a}.mt-nl-badge--pending{background:#fff1c2;color:#7a5200}.mt-nl-badge--unsubscribed{background:#e5e5e5;color:#3c434a}.mt-nl-badge--bounced{background:#fde0de;color:#8a1f11}.mt-nl-bad{color:#b32d2e;font-weight:600}.mt-nl-filters{margin:8px 0;clear:both}.mt-nl-pages li{margin-bottom:6px}.mt-nl-indent{margin-left:24px}.mt-nl-inline select{max-width:100%}</style>';
	}
}
