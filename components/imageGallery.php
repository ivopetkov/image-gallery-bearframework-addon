<?php
/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */
$filenames = explode(',', $component->filenames);
$aspectRatio = null;
if (strlen($component->aspectRatio) > 0) {
    if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $component->aspectRatio) === 1) {
        $aspectRatio = $component->aspectRatio;
    }
}
$attributes = '';
if ($aspectRatio !== null) {
    $attributes .= ' aspectRatio="' . $aspectRatio . '"';
}

$images = [];
foreach ($filenames as $filename) {
    $size = $app->images->getSize($filename);
    $images[] = [
        'html' => $app->components->process('<component src="lazy-image" filename="' . $filename . '"/>'),
        'width' => $size[0],
        'height' => $size[1]
    ];
}
$filenames = array_values($filenames);
?><html>
    <body>
    <component src="js-lightbox" images="<?= htmlentities(json_encode($images)) ?>" />
    <div style="<?= $component->style ?>max-width:300px;">
        <?php
        foreach ($filenames as $index => $filename) {
            echo '<a onclick="window.lightbox.open(' . $index . ');">';
            echo '<component src="lazy-image" filename="' . $filename . '"' . $attributes . '/>';
            echo '</a>';
        }
        ?>
    </div>
    <script>
        window.lightbox = new jsLightbox(<?= json_encode($images); ?>);
    </script>
</body>
</html>