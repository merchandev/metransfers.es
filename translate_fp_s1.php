<?php
$file = 'front-page.php';
$content = file_get_contents($file);

// Find exact strings from the content using safe regex that only match inside one line.
// We use \s*([^<]+)\s* to capture the text inside tags WITHOUT crossing lines.

$patterns = [
    '/(<span class="srv__num">01<\/span>\s*<div class="srv__ico">.*?<\/div>\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<a href=".*?">)(.*?)( <svg)/s',
    '/(<span class="srv__num">02<\/span>\s*<div class="srv__ico">.*?<\/div>\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<a href=".*?">)(.*?)( <svg)/s',
    '/(<span class="srv__num">03<\/span>\s*<div class="srv__ico">.*?<\/div>\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<a href=".*?">)(.*?)( <svg)/s',
    '/(<span class="srv__num">04<\/span>\s*<div class="srv__ico">.*?<\/div>\s*<h3>)(.*?)(<\/h3>\s*<p>)(.*?)(<\/p>\s*<a href=".*?">)(.*?)( <svg)/s',
];

foreach ($patterns as $pattern) {
    $content = preg_replace_callback($pattern, function($m) {
        $title = trim($m[2]);
        $desc = trim($m[4]);
        $link = trim($m[6]);
        return $m[1] . '<?php echo mt_translate("' . addslashes(html_entity_decode($title)) . '"); ?>' . $m[3] . '<?php echo mt_translate("' . addslashes(html_entity_decode($desc)) . '"); ?>' . $m[5] . '<?php echo mt_translate("' . addslashes(html_entity_decode($link)) . '"); ?>' . $m[7];
    }, $content);
}

file_put_contents($file, $content);
echo "Section 1 done\n";
