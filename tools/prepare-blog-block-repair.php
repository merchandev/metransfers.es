<?php
declare(strict_types=1);
/**
 * Proposes block repairs from an authenticated raw post_content backup.
 * Never writes to WordPress. Usage:
 * php tools/prepare-blog-block-repair.php backup.json proposal.json 1000,1012,...
 */
if (PHP_SAPI !== 'cli') { exit; }
if ($argc < 4 || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php prepare-blog-block-repair.php backup.json proposal.json comma-separated-ids\n");
    exit(1);
}
function htmlRoot(string $html): array {
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8"?><html><body><div id="audit-root">' . $html . '</div></body></html>', LIBXML_NONET);
    return [$doc, $doc->getElementById('audit-root')];
}
function references(DOMNode $root): array {
    $result = [];
    foreach (['a' => 'href', 'img' => 'src'] as $tag => $attribute) {
        foreach ($root->getElementsByTagName($tag) as $node) { $result[$tag . ':' . $node->getAttribute($attribute)] = true; }
    }
    ksort($result);
    return array_keys($result);
}
function wrapNode(DOMDocument $doc, DOMNode $node): string {
    $html = trim($doc->saveHTML($node));
    $name = $node->nodeName;
    if ($name === 'p') { $block = 'paragraph'; $attrs = ''; }
    elseif (preg_match('/^h([1-6])$/', $name, $matches)) {
        $block = 'heading'; $attrs = $matches[1] === '2' ? '' : ' ' . json_encode(['level' => (int) $matches[1]]);
    } elseif ($name === 'ul' || $name === 'ol') {
        $block = 'list'; $attrs = $name === 'ol' ? ' {"ordered":true}' : '';
        $html = preg_replace('/(<li(?:\s[^>]*)?>)/', '<!-- wp:list-item -->$1', $html);
        $html = str_replace('</li>', '</li><!-- /wp:list-item -->', $html);
    } elseif ($name === 'figure' && $node instanceof DOMElement && str_contains($node->getAttribute('class'), 'wp-block-image')) {
        $block = 'image'; $imageAttrs = [];
        if (preg_match('/(?:^|\s)size-([\w-]+)/', $node->getAttribute('class'), $size)) { $imageAttrs['sizeSlug'] = $size[1]; }
        if (preg_match('/(?:^|\s)align(left|right|center|wide|full)(?:\s|$)/', $node->getAttribute('class'), $align)) { $imageAttrs['align'] = $align[1]; }
        $image = $node->getElementsByTagName('img')->item(0);
        if ($image && preg_match('/\bwp-image-(\d+)\b/', $image->getAttribute('class'), $imageId)) { $imageAttrs['id'] = (int) $imageId[1]; }
        $attrs = $imageAttrs ? ' ' . json_encode($imageAttrs) : '';
    } elseif ($name === 'figure' && $node instanceof DOMElement && str_contains($node->getAttribute('class'), 'wp-block-table')) {
        $block = 'table'; $attrs = '';
    } else { throw new RuntimeException('Unsupported top-level node: ' . $name); }
    return '<!-- wp:' . $block . $attrs . " -->\n" . $html . "\n<!-- /wp:" . $block . ' -->';
}
libxml_use_internal_errors(true);
$snapshot = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$eligible = isset($argv[3]) ? array_map('intval', explode(',', $argv[3])) : [];
$repairs = []; $refused = [];
foreach ($snapshot['posts'] as $post) {
    $raw = $post['content'];
    if (!str_contains($raw, 'wp:post-content') && !in_array($post['id'], $eligible, true)) { continue; }
    try {
        preg_match_all('/<!--\s*\/?wp:([\w-]+(?:\/[\w-]+)?)/', $raw, $blocks);
        $unexpected = array_diff(array_unique($blocks[1]), ['paragraph', 'heading', 'list', 'list-item', 'image', 'table', 'post-content']);
        if ($unexpected) { throw new RuntimeException('Other blocks present: ' . implode(', ', $unexpected)); }
        if (preg_match('/<(?:form|input|button|iframe|script)\b|\[[a-zA-Z][\w-]*(?:\s|\])/', $raw)) { throw new RuntimeException('Interactive HTML or shortcode present'); }
        $html = preg_replace('/<!--\s*\/?wp:[\s\S]*?-->/', '', $raw);
        [$doc, $root] = htmlRoot($html);
        $unique = []; $sourceCount = 0;
        foreach ($root->childNodes as $index => $node) {
            if ($node instanceof DOMText && trim($node->textContent) === '') { continue; }
            $sourceCount++;
            $outer = trim($doc->saveHTML($node));
            $signature = hash('sha256', preg_replace('/>\s+</', '><', $outer));
            // Last occurrences retain the order of the complete final article,
            // after the malformed nested copies prepended fragments to it.
            $unique[$signature] = ['index' => $index, 'node' => $node];
        }
        uasort($unique, fn($a, $b) => $a['index'] <=> $b['index']);
        $content = implode("\n\n", array_map(fn($item) => wrapNode($doc, $item['node']), array_values($unique)));
        [$cleanDoc, $cleanRoot] = htmlRoot(preg_replace('/<!--\s*\/?wp:[\s\S]*?-->/', '', $content));
        if (references($root) !== references($cleanRoot)) { throw new RuntimeException('Link or image reference set changed'); }
        $beforeText = [];
        foreach ($unique as $item) { $beforeText[] = trim(preg_replace('/\s+/u', ' ', $item['node']->textContent)); }
        $afterText = [];
        foreach ($cleanRoot->childNodes as $node) {
            if ($node instanceof DOMText && trim($node->textContent) === '') { continue; }
            $afterText[] = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        }
        if ($beforeText !== $afterText) { throw new RuntimeException('Unique text changed'); }
        $repairs[] = ['id' => $post['id'], 'title' => $post['title'], 'expected_modified' => $post['modified'], 'expected_raw_sha256' => hash('sha256', $raw), 'old_characters' => mb_strlen($raw), 'new_characters' => mb_strlen($content), 'original_html_nodes' => $sourceCount, 'unique_html_nodes' => count($unique), 'references' => references($root), 'new_content' => $content];
    } catch (Throwable $error) { $refused[] = ['id' => $post['id'], 'error' => $error->getMessage()]; }
}
$result = ['source' => 'authenticated_raw_post_content', 'status' => 'prepared_not_applied', 'repairs' => $repairs, 'refused' => $refused];
file_put_contents($argv[2], json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode(['repairs' => count($repairs), 'refused' => $refused, 'metrics' => array_map(fn($r) => array_diff_key($r, ['new_content' => 1, 'references' => 1]), $repairs)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), "\n";
