<?php
/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

use \BearFramework\App;

$app = App::get();
$context = $app->getContext(__FILE__);

$hasLightbox = false;
$hasResponsiveAttributes = false;
$hasElementID = false;

$internalOptionRenderContainer = $component->getAttribute('internal-option-render-container') !== 'false';
$internalOptionRenderImageContainer = $component->getAttribute('internal-option-render-image-container') !== 'false';

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

$imageLoadingBackground = null;
$temp = (string) $component->imageLoadingBackground;
if ($temp !== '') {
    $imageLoadingBackground = $temp;
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

$galleryID = 'imggallery' . uniqid();
$containerAttributes = '';

if ($onClick === 'fullscreen') {
    $hasLightbox = true;
    $jsData = [
        'galleryID' => $galleryID,
        'lightboxData' => [
            'images' => []
        ],
        'images' => []
    ];
    $index = 0;
    foreach ($files as $file) {
        $filename = $file->getAttribute('filename');
        $imageContainerID = $galleryID . 'img' . $index;
        $html = $app->components->process('<div id="' . $imageContainerID . '"><component style="background-color:#000;" src="lazy-image" filename="' . $filename . '"/></div>');
        $imageDomDocument = new IvoPetkov\HTML5DOMDocument();
        $imageDomDocument->loadHTML($html);
        $imageHTMLBody = $imageDomDocument->querySelector('body');
        $imageHTML = $imageHTMLBody->innerHTML;
        $imageHTMLBody->parentNode->removeChild($imageHTMLBody);
        $jsData['lightboxData']['images'][] = [
            'html' => $imageHTML,
            'onBeforeShow' => 'window.' . $galleryID . 'ig.onBeforeShow(' . $index . ');',
            'onShow' => 'window.' . $galleryID . 'ig.onShow(' . $index . ');',
        ];
        list($imageWidth, $imageHeight) = $app->images->getSize($filename);
        $jsData['images'][] = [$imageWidth, $imageHeight, $imageDomDocument->saveHTML()];
        $index++;
    }
}

$getColumnsStyle = function($columnsCount, $attributeSelector = '') use ($galleryID, $spacing) {
    $result = '#' . $galleryID . $attributeSelector . '>div{vertical-align:top;display:inline-block;width:calc((100% - ' . $spacing . '*' . ($columnsCount - 1) . ')/' . $columnsCount . ');margin-right:' . $spacing . ';margin-top:' . $spacing . ';}';
    $result .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . $columnsCount . 'n){margin-right:0;}';
    for ($i = 1; $i <= $columnsCount; $i++) {
        $result .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . $i . '){margin-top:0;}';
    }
    return $result;
};

$containerStyle = '';
if (is_numeric($columnsCount)) { // Fixed columns count
    $containerStyle .= $getColumnsStyle($columnsCount);
    if ($columnsCount > 1) {
        $hasElementID = true;
    }
} else { // Auto columns count
    $hasResponsiveAttributes = true;
    $hasElementID = true;
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

if ($imageLoadingBackground !== null) {
    $imageAttributes .= ' loadingBackground="' . $imageLoadingBackground . '"';
}

if ($hasElementID) {
    $containerAttributes .= ' id="' . htmlentities($galleryID) . '"';
}

$class = (string) $component->class;
if (isset($class{0})) {
    $containerAttributes .= ' class="' . htmlentities($class) . '"';
}
?><html>
    <head><?php
        if ($hasLightbox) {
            echo '<script id="image-gallery-bearframework-addon-script-1" src="' . htmlentities($context->assets->getUrl('assets/HTML5DOMDocument.min.js')) . '"></script>';
            echo '<script id="image-gallery-bearframework-addon-script-2" src="' . htmlentities($context->assets->getUrl('assets/imageGallery.min.js')) . '"></script>';
        }
        if ($hasResponsiveAttributes) {
            echo '<script id="image-gallery-bearframework-addon-script-3" src="' . htmlentities($context->assets->getUrl('assets/responsiveAttributes.min.js')) . '"></script>';
        }
        if (isset($containerStyle{0})) {
            echo '<style>' . $containerStyle . '</style>';
        }
        ?></head>
    <body>
        <?php
        if ($hasLightbox) {
            ?><component src="js-lightbox" onload="<?= htmlentities('window.' . $galleryID . 'ig = new ivoPetkov.bearFramework.addons.imageGallery(' . json_encode($jsData) . ');') ?>"/><?php
        }
        if ($internalOptionRenderContainer) {
            echo '<div' . $containerAttributes . '>';
        }
        foreach ($files as $index => $file) {
            $class = (string) $file->getAttribute('class');
            $classAttribute = isset($class{0}) ? ' class="' . htmlentities($class) . '"' : '';
            $alt = (string) $file->getAttribute('alt');
            $altAttribute = isset($alt{0}) ? ' alt="' . htmlentities($alt) . '"' : '';
            $title = (string) $file->getAttribute('title');
            $titleAttribute = isset($title{0}) ? ' title="' . htmlentities($title) . '"' : '';
            if ($internalOptionRenderImageContainer) {
                echo '<div>';
            }
            if ($onClick === 'fullscreen') {
                echo '<a' . $titleAttribute . ' onclick="window.' . $galleryID . 'ig.open(' . $index . ');" style="cursor:pointer;">';
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
            if ($internalOptionRenderImageContainer) {
                echo '</div>';
            }
        }
        if ($internalOptionRenderContainer) {
            echo '</div>';
        }
        if ($hasResponsiveAttributes) {
            ?><script>responsiveAttributes.run();</script><?php
    }
    ?>
</body>
</html>