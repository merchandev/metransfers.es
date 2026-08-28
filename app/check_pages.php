<?php
require_once get_template_directory() . '/includes/services.php';
$services = me_transfers_get_service_catalog();
$slugs = array_keys($services);
echo "<pre>Valid slugs from catalog: " . implode(', ', $slugs) . "</pre>";
