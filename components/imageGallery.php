<?php
/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

$domDocument = new IvoPetkov\HTML5DOMDocument();
$domDocument->loadHTML($component->innerHTML);
$files = $domDocument->querySelectorAll('file');

$onClick = 'fullscreen';
if (strlen($component->onClick) > 0) {
    if (array_search($component->onClick, ['fullscreen', 'url', 'custom', 'none']) !== false) {
        $onClick = $component->onClick;
    }
}

$imageAspectRatio = null;
if (strlen($component->imageAspectRatio) > 0) {
    if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $component->imageAspectRatio) === 1) {
        $imageAspectRatio = $component->imageAspectRatio;
    }
}

$columnsCount = 'auto';
if (strlen($component->columnsCount) > 0) {
    if (is_numeric($component->columnsCount)) {
        $columnsCount = (int) $component->columnsCount;
        if ($columnsCount < 1 || $columnsCount > 20) {
            $columnsCount = 'auto';
        }
    }
}

$imageSize = 'medium';
if ($columnsCount === 'auto') {
    if (strlen($component->imageSize) > 0) {
        if (array_search($component->imageSize, ['tiny', 'small', 'medium', 'large', 'huge']) !== false) {
            $imageSize = $component->imageSize;
        }
    }
}

$spacing = '0px';
if (strlen($component->spacing) > 0) {
    $spacing = $component->spacing;
}

$containerID = 'imggallery' . uniqid();
$containerAttributes = '';

if ($onClick === 'fullscreen') {
    $lightboxImages = [];
    foreach ($files as $file) {
        $filename = $file->getAttribute('filename');
        $size = $app->images->getSize($filename);
        $html = $app->components->process('<component style="background-color:#000;" src="lazy-image" filename="' . $filename . '"/>');
        $imageDomDocument = new IvoPetkov\HTML5DOMDocument();
        $imageDomDocument->loadHTML($html);
        $imageHTMLBody = $imageDomDocument->querySelector('body');
        $imageHTML = $imageHTMLBody->innerHTML;
        $imageHTMLBody->parentNode->removeChild($imageHTMLBody);
        $lightboxImages[] = [
            'html' => $imageHTML,
            'width' => $size[0],
            'height' => $size[1],
            'onBeforeFocus' => 'htmlMagic.insert(' . json_encode($imageDomDocument->saveHTML()) . ');',
            'onShow' => 'responsivelyLazy.run();'
        ];
    }
}

$getColumnsStyle = function($columnsCount, $attributeSelector = '') use ($containerID, $spacing) {
    $result = '#' . $containerID . $attributeSelector . '>div{vertical-align:top;display:inline-block;width:calc((100% - ' . $spacing . '*' . ($columnsCount - 1) . ')/' . $columnsCount . ');margin-right:' . $spacing . ';margin-top:' . $spacing . ';}';
    $result .= '#' . $containerID . $attributeSelector . '>div:nth-child(' . $columnsCount . 'n){margin-right:0;}';
    for ($i = 1; $i <= $columnsCount; $i++) {
        $result .= '#' . $containerID . $attributeSelector . '>div:nth-child(' . $i . '){margin-top:0;}';
    }
    return $result;
};

$containerStyle = '';
if (is_numeric($columnsCount)) { // Fixed columns count
    $containerStyle .= $getColumnsStyle($columnsCount);
} else { // Auto columns count
    $imageWidthMultipliers = [
        'tiny' => 1,
        'small' => 2,
        'medium' => 3,
        'large' => 4,
        'huge' => 5
    ];
    $imageWidthMultiplier = $imageWidthMultipliers[$imageSize];
    $responsiveAttributes = [];
    $responsiveAttributes[] = 'w>=0&&w<' . ($imageWidthMultiplier * 100) . '=>data-columns=1';
    $responsiveAttributes[] = 'w>=' . ($imageWidthMultiplier * 100) . '&&w<' . ($imageWidthMultiplier * 100 * 2) . '=>data-columns=2';
    $responsiveAttributes[] = 'w>=' . ($imageWidthMultiplier * 100 * 2) . '&&w<' . ($imageWidthMultiplier * 100 * 3) . '=>data-columns=3';
    $responsiveAttributes[] = 'w>=' . ($imageWidthMultiplier * 100 * 3) . '&&w<' . ($imageWidthMultiplier * 100 * 4.3) . '=>data-columns=4';
    $responsiveAttributes[] = 'w>=' . ($imageWidthMultiplier * 100 * 4.3) . '&&w<' . ($imageWidthMultiplier * 100 * 6) . '=>data-columns=5';
    $responsiveAttributes[] = 'w>=' . ($imageWidthMultiplier * 100 * 6) . '=>data-columns=6';
    $containerAttributes .= ' data-responsive-attributes="' . implode(',', $responsiveAttributes) . '"';

    for ($i = 1; $i <= 6; $i++) {
        $containerStyle .= $getColumnsStyle($i, '[data-columns="' . $i . '"]');
    }
}

$imageAttributes = '';
if ($imageAspectRatio !== null) {
    $imageAttributes .= ' aspectRatio="' . $imageAspectRatio . '"';
}

$containerAttributes .= ' id="' . htmlentities($containerID) . '"';
$containerAttributes .= ' style="' . htmlentities($containerStyle) . '"';
?><html>
    <head>
        <script src="http://all.projects/html-magic-js/HTMLMagic.js?<?= time() ?>"></script>
        <style><?= $containerStyle ?></style>
    </head>
    <body>
        <script src="http://all.projects/responsive-attributes/responsiveAttributes.js?<?= time() ?>"></script>
        <?php
        if ($onClick === 'fullscreen') {
            ?><component src="js-lightbox" onload="<?= htmlentities('window.' . $containerID . 'lb = new ivoPetkov.bearFramework.addons.jsLightbox(' . json_encode($lightboxImages) . ');') ?>"/><?php
        }
        ?>
    <div<?= $containerAttributes ?>>
        <?php
        foreach ($files as $index => $file) {
            $title = (string) $file->getAttribute('title');
            $titleAttribute = isset($title{0}) ? ' title="' . htmlentities($title) . '"' : '';
            $class = (string) $file->getAttribute('class');
            $classAttribute = isset($class{0}) ? ' class="' . htmlentities($class) . '"' : '';
            echo '<div>';
            if ($onClick === 'fullscreen') {
                echo '<a' . $titleAttribute . ' onclick="window.' . $containerID . 'lb.open(' . $index . ');" style="cursor:pointer;">';
            } elseif ($onClick === 'url') {
                $url = (string) $file->getAttribute('url');
                echo '<a' . $titleAttribute . ' href="' . (isset($url{0}) ? htmlentities($url) : '#') . '">';
            } elseif ($onClick === 'custom') {
                $onClick = (string) $file->getAttribute('onClick');
                echo '<a' . $titleAttribute . ' onclick="' . htmlentities(isset($onClick{0})) . '" style="cursor:pointer;">';
            }
            $filename = (string) $file->getAttribute('filename');
            echo '<component src="lazy-image" responsively-lazy-overflown="true"' . $titleAttribute . $classAttribute . ' filename="' . htmlentities($filename) . '"' . $imageAttributes . '/>';
            if ($onClick === 'fullscreen' || $onClick === 'url' || $onClick === 'custom') {
                echo '</a>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <script>responsiveAttributes.run();</script>
</body>
</html>