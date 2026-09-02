<?php
$updated_date = get_the_modified_date();
			?>
			<header class="page-banner">
				<div class="container banner-content gs-reveal-up">
					<h1 class="page-banner__title">
						<?php the_title(); ?>
					</h1>
					<p class="page-banner__meta">
						METRANSFERS GESTION SL &middot; Última actualización: <?php echo esc_html( $updated_date ); ?>
					</p>
				</div>
			</header>

			<section class="page-content-wrapper legal-page__content section">
				<div class="container page-content-shell">
					<div class="entry-content legal-prose">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
			<?php