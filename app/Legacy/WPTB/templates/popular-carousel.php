<?php
// Popular Destinations Carousel Template
// Uses 'wptb_destination' CPT with fallback to legacy array if empty.
$wptb_i18n = \MeTransfers\Booking\I18n::strings();

$args = array(
    'post_type'      => 'wptb_destination',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
);

$query = new WP_Query($args);
$slides = [];

// 1. Try to get from CPT
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $slides[] = [
            'title' => get_the_title(),
            'image' => get_the_post_thumbnail_url(get_the_ID(), 'large'), // High resolution image
            'dest'  => get_the_title(), // Value for input
        ];
    }
    wp_reset_postdata();
}

// 2. Fallback if no CPTs found (Legacy / First Run)
if (empty($slides)) {
    $destinations = [
       'Barcelona', 'Pineda de Mar', 'PortAventura', 'Barcelona Reus', 'Madrid Barcelona', 
       'Sevilla', 'Vigo Barcelona', 'Benidorm', 'Bilbao Barcelona', 'San Sebastian', 
       'Malgrat', 'Andorra', 'Granada Barcelona', 'Valencia', 'Lloret de Mar'
    ];
    foreach($destinations as $dest) {
        $slides[] = [
            'title' => $dest,
            'image' => '', // Placeholder or empty
            'dest'  => $dest
        ];
    }
}

// Duplicate for infinite scroll smoothness (x2 or x3 depending on count)
// For smoother infinite scroll with CSS, we need enough items to fill width.
$slides_display = array_merge($slides, $slides); 
if(count($slides) < 10) {
    $slides_display = array_merge($slides_display, $slides); // Triple if few items
}
?>

<!-- WPTB Booking System Wrapper for Encapsulation -->
<div class="wptb-booking-system">
    <!-- Direction Toggle -->
    <div class="mtfs-direction-toggle">
        <button type="button" class="mtfs-direction-btn active" data-direction="from-barcelona">
            <?php echo esc_html( $wptb_i18n['from_barcelona'] ); ?>
        </button>
        <button type="button" class="mtfs-direction-btn" data-direction="to-barcelona">
            <?php echo esc_html( $wptb_i18n['to_barcelona'] ); ?>
        </button>
    </div>

    <div class="mtfs-slider-wrapper">
        <div class="mtfs-slider">
            <div class="mtfs-slide-track">
                <?php foreach ($slides_display as $slide): ?>
                    <div class="mtfs-slide" data-destination="<?php echo esc_attr($slide['dest']); ?>" data-direction="from-barcelona">
                        <?php if ($slide['image']): ?>
                            <div class="mtfs-image-wrapper">
                                <img src="<?php echo esc_url($slide['image']); ?>" alt="<?php echo esc_attr($slide['title']); ?>" loading="eager">
                            </div>
                        <?php else: ?>
                            <!-- Fallback Placeholder if no image -->
                            <div class="mtfs-image-wrapper mtfs-image-wrapper--placeholder">
                                <span class="dashicons dashicons-location mtfs-placeholder-icon"></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mtfs-slide-content">
                            <h3><?php echo esc_html($slide['title']); ?></h3>
                            <div class="mtfs-slide-direction"><?php echo esc_html( $wptb_i18n['from_barcelona'] ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
