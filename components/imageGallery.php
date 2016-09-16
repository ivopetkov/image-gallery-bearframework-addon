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
$temp = (string) $component->onClick;
if ($temp !== '') {
    if (array_search($temp, ['fullscreen', 'url', 'custom', 'none']) !== false) {
        $onClick = $temp;
    }
}

$imageAspectRatio = null;
$temp = (string) $component->imageAspectRatio;
if ($temp !== '') {
    if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $temp) === 1) {
        $imageAspectRatio = $temp;
    }
}

$columnsCount = 'auto';
$temp = (string) $component->columnsCount;
if ($temp !== '') {
    if (is_numeric($temp)) {
        $temp = (int) $temp;
        if ($temp >= 1 && $temp <= 20) {
            $columnsCount = $temp;
        }
    }
}

$imageSize = 'medium';
$temp = (string) $component->imageSize;
if ($temp !== '') {
    if (array_search($temp, ['tiny', 'small', 'medium', 'large', 'huge']) !== false) {
        $imageSize = $temp;
    }
}

$spacing = '0px';
$temp = (string) $component->spacing;
if ($temp !== '') {
    $spacing = $temp;
}

$containerID = 'imggallery' . uniqid();
$containerAttributes = '';

if ($onClick === 'fullscreen') {
    $jsData = [
        'containerID' => $containerID,
        'lightboxData' => [
            'images' => []
        ],
        'images' => []
    ];
    $index = 0;
    foreach ($files as $file) {
        $filename = $file->getAttribute('filename');
        $imageContainerID = $containerID . 'img' . $index;
        $html = $app->components->process('<div id="' . $imageContainerID . '"><component style="background-color:#000;" src="lazy-image" filename="' . $filename . '"/></div>');
        $imageDomDocument = new IvoPetkov\HTML5DOMDocument();
        $imageDomDocument->loadHTML($html);
        $imageHTMLBody = $imageDomDocument->querySelector('body');
        $imageHTML = $imageHTMLBody->innerHTML;
        $imageHTMLBody->parentNode->removeChild($imageHTMLBody);
        $jsData['lightboxData']['images'][] = [
            'html' => $imageHTML,
            'onBeforeShow' => 'window.' . $containerID . 'ig.onBeforeShow(' . $index . ');',
            'onShow' => 'window.' . $containerID . 'ig.onShow(' . $index . ');',
        ];
        list($imageWidth, $imageHeight) = $app->images->getSize($filename);
        $jsData['images'][] = [$imageWidth, $imageHeight, $imageDomDocument->saveHTML()];
        $index++;
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

$class = (string) $component->class;
if (isset($class{0})) {
    $containerAttributes .= ' class="' . htmlentities($class) . '"';
}
?><html>
    <head>
        <script id="image-gallery-bearframework-addon-script-1" src="<?= htmlentities($context->assets->getUrl('assets/HTML5DOMDocument.min.js')) ?>"></script>
        <?php
        if ($onClick === 'fullscreen') {
            ?><script id="image-gallery-bearframework-addon-script-2" src="<?= htmlentities($context->assets->getUrl('assets/imageGallery.min.js')) ?>"></script><?php
        }
        ?>
        <style><?= $containerStyle ?></style>
    </head>
    <body>
        <script id="image-gallery-bearframework-addon-script-3" src="<?= htmlentities($context->assets->getUrl('assets/responsiveAttributes.min.js')) ?>"></script>
        <?php
        if ($onClick === 'fullscreen') {
            ?><component src="js-lightbox" onload="<?= htmlentities('window.' . $containerID . 'ig = new ivoPetkov.bearFramework.addons.imageGallery(' . json_encode($jsData) . ');') ?>"/><?php
        }
        ?>
    <div<?= $containerAttributes ?>>
        <?php
        foreach ($files as $index => $file) {
            $class = (string) $file->getAttribute('class');
            $classAttribute = isset($class{0}) ? ' class="' . htmlentities($class) . '"' : '';
            $alt = (string) $file->getAttribute('alt');
            $altAttribute = isset($alt{0}) ? ' alt="' . htmlentities($alt) . '"' : '';
            $title = (string) $file->getAttribute('title');
            $titleAttribute = isset($title{0}) ? ' title="' . htmlentities($title) . '"' : '';
            echo '<div>';
            if ($onClick === 'fullscreen') {
                echo '<a' . $titleAttribute . ' onclick="window.' . $containerID . 'ig.open(' . $index . ');" style="cursor:pointer;">';
            } elseif ($onClick === 'url') {
                $url = (string) $file->getAttribute('url');
                echo '<a' . $titleAttribute . ' href="' . (isset($url{0}) ? htmlentities($url) : '#') . '">';
            } elseif ($onClick === 'custom') {
                $onClick = (string) $file->getAttribute('onClick');
                echo '<a' . $titleAttribute . ' onclick="' . htmlentities(isset($onClick{0})) . '" style="cursor:pointer;">';
            }
            $filename = (string) $file->getAttribute('filename');
            echo '<component src="lazy-image"' . $classAttribute . $altAttribute . $titleAttribute . ' filename="' . htmlentities($filename) . '"' . $imageAttributes . '/>';
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