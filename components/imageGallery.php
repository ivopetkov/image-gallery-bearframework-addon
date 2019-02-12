<?php
/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\HTML5DOMDocument;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$hasLightbox = false;
$hasResponsiveAttributes = false;
$hasElementID = false;
$internalOptionRenderContainer = $component->getAttribute('internal-option-render-container') !== 'false';
$internalOptionRenderImageContainer = $component->getAttribute('internal-option-render-image-container') !== 'false';

$domDocument = new HTML5DOMDocument();
$domDocument->loadHTML($component->innerHTML, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
$files = $domDocument->querySelectorAll('file');

$type = 'columns';
$temp = (string) $component->type;
if ($temp !== '') {
    if (array_search($temp, ['columns', 'grid']) !== false) {
        $type = $temp;
    }
}

$onClick = 'fullscreen';
$temp = (string) $component->onClick;
if ($temp !== '') {
    if (array_search($temp, ['fullscreen', 'url', 'custom', 'none']) !== false) {
        $onClick = $temp;
    }
}

$imageLoadingBackground = null;
$temp = (string) $component->imageLoadingBackground;
if ($temp !== '') {
    $imageLoadingBackground = $temp;
}

$spacing = '0px';
$temp = (string) $component->spacing;
if ($temp !== '') {
    $spacing = $temp;
}

$lazyLoadImages = false;
if ($component->lazyLoadImages === 'true') {
    $lazyLoadImages = true;
}

$galleryID = 'imggallery' . uniqid();
$containerAttributes = '';

$localCache = [];
$getImageSize = function($filename) use ($app, &$localCache) {
    $cacheKey = 'image-gallery-image-size-' . $filename;
    if (isset($localCache[$cacheKey])) {
        return $localCache[$cacheKey];
    }
    $cachedData = $app->cache->getValue($cacheKey);
    if ($cachedData !== null) {
        $size = json_decode($cachedData, true);
        $localCache[$cacheKey] = $size;
        return $size;
    }
    try {
        $details = $app->assets->getDetails($filename, ['width', 'height']);
        $size = [$details['width'], $details['height']];
    } catch (\Exception $e) {
        $size = [1, 1];
    }
    $localCache[$cacheKey] = $size;
    $app->cache->set($app->cache->make($cacheKey, json_encode($size)));
    return $size;
};

if ($onClick === 'fullscreen') {
    $hasLightbox = true;
    $serverData = ['imagegallery', []];
    foreach ($files as $file) {
        $serverData[1][] = $file->getAttribute('filename');
    }
    $serverData = json_encode($serverData);
    $jsData = [
        'galleryID' => $galleryID,
        'serverData' => md5($serverData) . base64_encode($app->encryption->encrypt(gzcompress($serverData))),
        'imagesCount' => $files->length
    ];
}

$imageAspectRatio = null;

$containerStyle = '';
if ($type === 'columns') {

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

    $temp = (string) $component->imageAspectRatio;
    if ($temp !== '') {
        if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $temp) === 1) {
            $imageAspectRatio = $temp;
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
        $responsiveAttributes[] = 'w<' . ($imageWidthMultiplier * 100) . '=>data-columns=1';
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
} elseif ($type === 'grid') {

    $containerStyle .= '#' . $galleryID . '{opacity:0;}';
    $containerStyle .= '#' . $galleryID . '[data-grid]{opacity:1;}';

    $imageSize = 'medium';
    $maxHeights = [
        'tiny' => 90,
        'small' => 150,
        'medium' => 220,
        'large' => 300,
        'huge' => 400
    ];
    $temp = (string) $component->imageSize;
    if ($temp !== '') {
        if (isset($maxHeights[$temp])) {
            $imageSize = $temp;
        }
    }

    $hasResponsiveAttributes = true;
    $hasElementID = true;
    $maxHeight = $maxHeights[$imageSize];
    $filesSizes = [];
    foreach ($files as $index => $file) {
        $filename = (string) $file->getAttribute('filename');
        try {
            $filesSizes[$index] = $getImageSize($filename);
        } catch (\Exception $e) {
            if ($app->config->displayErrors) {
                throw $e;
            }
            $filesSizes[$index] = [0, 0];
        }
    }

    $addFilesToRow = function($attributeSelector, $filesOnRow, $isLastRow) use ($galleryID, &$containerStyle, $spacing) {
        $totalWidth = array_sum($filesOnRow);
        $counter = 0;
        $filesOnRowCount = sizeof($filesOnRow);
        foreach ($filesOnRow as $index => $width) {
            $counter++;
            $style = 'vertical-align:top;display:inline-block;width:calc((100% - ' . $spacing . '*' . ($filesOnRowCount - 1) . ')*' . (number_format($width / $totalWidth, 6, '.', '')) . ');';
            if ($counter < $filesOnRowCount) {
                $style .= 'margin-right:' . $spacing . ';';
            }
            //if ($filesOnRowCount === 1) {
            $style .= 'max-width:' . ($width * 1.1) . 'px;'; //let them be bigger but only by 10%
            //}
            if (!$isLastRow) {
                $style .= 'margin-bottom:' . $spacing . ';';
            }
            $containerStyle .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . ($index + 1) . '){' . $style . '}';
        }
    };

    $minGridImageWidth = 200;
    $maxGridImageWidth = 2000;
    $gridImageWidthStep = 200;

    $responsiveAttributes = [];
    for ($maxWidth = $minGridImageWidth; $maxWidth <= $maxGridImageWidth; $maxWidth += $gridImageWidthStep) {
        $totalRowImagesWidth = 0;
        $selector = '[data-grid="' . $maxWidth . '"]';
        $filesOnRow = [];
        $counter = 0;
        foreach ($filesSizes as $index => $fileSize) {
            $counter++;
            list($fileWidth, $fileHeight) = $fileSize;
            $maxFileWidth = $maxHeight / $fileHeight * $fileWidth;
            if ($totalRowImagesWidth + $maxFileWidth > $maxWidth) {
                if (!empty($filesOnRow)) {
                    $addFilesToRow($selector, $filesOnRow, false);
                }
                $filesOnRow = [];
                $totalRowImagesWidth = 0;
            }
            $filesOnRow[$index] = $maxFileWidth;
            $totalRowImagesWidth += $maxFileWidth;
        }
        if (!empty($filesOnRow)) {
            $addFilesToRow($selector, $filesOnRow, true);
        }
        if ($maxWidth - $gridImageWidthStep <= $minGridImageWidth) {
            $responsiveAttributes['min'] = 'w<' . $maxWidth . '=>data-grid=' . $maxWidth;
        } else {
            $responsiveAttributes[] = 'w>=' . ($maxWidth - $gridImageWidthStep) . '&&w<' . ($maxWidth) . '=>data-grid=' . $maxWidth;
        }
    }
    $responsiveAttributes[] = 'w>=' . $maxGridImageWidth . '=>data-grid=' . $maxGridImageWidth;
    $containerAttributes .= ' data-responsive-attributes="' . implode(',', $responsiveAttributes) . '"';
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
            echo '<script id="image-gallery-bearframework-addon-script-1" src="' . htmlentities($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1])) . '" async></script>';
            echo '<script id="image-gallery-bearframework-addon-script-2" src="' . htmlentities($context->assets->getURL('assets/imageGallery.min.js', ['cacheMaxAge' => 999999999, 'version' => 2])) . '" async></script>';
        }
        if ($hasResponsiveAttributes) {
            echo '<script id="image-gallery-bearframework-addon-script-3" src="' . htmlentities($context->assets->getURL('assets/responsiveAttributes.min.js', ['cacheMaxAge' => 999999999, 'version' => 1])) . '" async></script>';
        }
        if (isset($containerStyle{0})) {
            echo '<style>' . $containerStyle . '</style>';
        }
        ?></head>
    <body>
        <?php
        if ($hasLightbox) {
            echo '<component src="js-lightbox" />';
            echo '<script>'
            . 'var checkAndExecute=function(b,c){if(b())c();else{var a=function(){b()?(window.clearTimeout(a),c()):window.setTimeout(a,16)};window.setTimeout(a,16)}};'
            . 'checkAndExecute(function(){return typeof html5DOMDocument!=="undefined" && typeof ivoPetkov!=="undefined" && typeof ivoPetkov.bearFrameworkAddons!=="undefined" && typeof ivoPetkov.bearFrameworkAddons.imageGallery!=="undefined" && typeof ivoPetkov.bearFrameworkAddons.jsLightbox!=="undefined"},function(){window.' . $galleryID . 'ig = new ivoPetkov.bearFrameworkAddons.imageGallery(' . json_encode($jsData) . ')});';
            echo '</script>';
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
            if ($lazyLoadImages) {
                echo '<component src="lazy-image"' . $classAttribute . $altAttribute . $titleAttribute . ' filename="' . htmlentities($filename) . '"' . $imageAttributes . '/>';
            } else {
                $options = [];
                $options['cacheMaxAge'] = 999999999;
                $options['version'] = 1;
                if ($imageAspectRatio !== null) {
                    $imageAspectRatioParts = explode(':', $imageAspectRatio);
                    try {
                        list($imageWidth, $imageHeight) = $getImageSize($filename);
                    } catch (\Exception $e) {
                        if ($app->config->displayErrors) {
                            throw $e;
                        }
                        $imageWidth = 0;
                        $imageHeight = 0;
                    }
                    $newImageHeight = $imageWidth * $imageAspectRatioParts[1] / $imageAspectRatioParts[0];
                    if ($newImageHeight > $imageHeight) {
                        $options['width'] = $imageHeight * $imageAspectRatioParts[0] / $imageAspectRatioParts[1];
                        $options['height'] = $imageHeight;
                    } else {
                        $options['width'] = $imageWidth;
                        $options['height'] = $newImageHeight;
                    }
                    $imageUrl = $app->assets->getURL($filename, $options);
                } else {
                    $imageUrl = $app->assets->getURL($filename);
                }
                echo '<img' . $classAttribute . $altAttribute . $titleAttribute . ' style="max-width:100%;" src="' . $imageUrl . '"/>';
            }

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
            echo '<script>'
            . 'var checkAndExecute=function(b,c){if(b())c();else{var a=function(){b()?(window.clearTimeout(a),c()):window.setTimeout(a,16)};window.setTimeout(a,16)}};'
            . 'checkAndExecute(function(){return typeof responsiveAttributes!=="undefined"},function(){responsiveAttributes.run();});';
            echo '</script>';
        }
        ?>
    </body>
</html>