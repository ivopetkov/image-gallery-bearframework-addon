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
$context = $app->contexts->get(__DIR__);

$hasLightbox = false;
$hasResponsiveAttributes = false;
$hasElementID = false;
$internalOptionRenderContainer = $component->getAttribute('internal-option-render-container') !== 'false';
$internalOptionRenderImageContainer = $component->getAttribute('internal-option-render-image-container') !== 'false';

$files = [];
$domDocument = new HTML5DOMDocument();
$domDocument->loadHTML($component->innerHTML, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
$fileElements = $domDocument->querySelectorAll('file');
foreach ($fileElements as $index => $fileElement) {
    $filename = (string) $fileElement->getAttribute('filename');
    $fileWidth = (string) $fileElement->getAttribute('filewidth');
    $fileHeight = (string) $fileElement->getAttribute('fileheight');
    if ($fileWidth === '' || $fileHeight === '') {
        $details = $app->assets->getDetails($filename, ['width', 'height']);
        $fileWidth = $details['width'] !== null ? $details['width'] : null;
        $fileHeight = $details['height'] !== null ? $details['height'] : null;
    }
    $fileWidth = (int)$fileWidth;
    $fileHeight = (int)$fileHeight;
    $files[] = [
        'filename' => $filename,
        'width' => $fileWidth > 0 ? $fileWidth : 1,
        'height' => $fileHeight > 0 ? $fileHeight : 1,
        'element' => $fileElement
    ];
}

$type = (string) $component->type;
if (array_search($type, ['columns', 'grid', 'firstBig']) === false) {
    $type = 'columns';
}

$onClick = (string) $component->onClick;
if (array_search($onClick, ['fullscreen', 'url', 'custom', 'none']) === false) {
    $onClick = 'fullscreen';
}

$imageLoadingBackground = (string) $component->imageLoadingBackground;
if ($imageLoadingBackground === '') {
    $imageLoadingBackground = null;
}

$spacing = (string) $component->spacing;
if ($spacing === '') {
    $spacing = '0px';
}

$lazyLoadImages = $component->lazyLoadImages === 'true';

$galleryID = 'imggallery' . uniqid();
$containerAttributes = '';

if ($onClick === 'fullscreen') {
    $hasLightbox = true;
    $serverData = ['imagegallery', []];
    foreach ($files as $file) {
        $serverData[1][] = [$file['filename'], $file['width'], $file['height']];
    }
    $serverData = json_encode($serverData);
    $jsData = md5($serverData) . base64_encode($app->encryption->encrypt(gzcompress($serverData)));
}

$imageAspectRatio = null;

$containerStyle = '';
if ($type === 'columns') {

    $columnsCount = (string) $component->columnsCount;
    if (is_numeric($columnsCount) && ((int)$columnsCount >= 1 && (int)$columnsCount <= 20)) {
        $columnsCount = (int)$columnsCount;
    } else {
        $columnsCount = 'auto';
    }

    $imageSize = (string) $component->imageSize;
    if (array_search($imageSize, ['tiny', 'small', 'medium', 'large', 'huge']) === false) {
        $imageSize = 'medium';
    }

    $imageAspectRatio = (string) $component->imageAspectRatio;
    if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $imageAspectRatio) !== 1) {
        $imageAspectRatio = null;
    }

    $getColumnsStyle = function ($columnsCount, $attributeSelector = '') use ($galleryID, $spacing) {
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

    $maxHeights = [
        'tiny' => 90,
        'small' => 150,
        'medium' => 220,
        'large' => 300,
        'huge' => 400
    ];
    $imageSize = (string) $component->imageSize;
    if (!isset($maxHeights[$imageSize])) {
        $imageSize = 'medium';
    }
    $maxHeight = $maxHeights[$imageSize];

    $hasResponsiveAttributes = true;
    $hasElementID = true;

    $addFilesToRow = function ($attributeSelector, $filesOnRow, $isLastRow) use ($galleryID, &$containerStyle, $spacing) {
        $totalWidth = 0;
        foreach ($filesOnRow as $index => $fileData) {
            $totalWidth += $fileData[0];
        }
        $counter = 0;
        $filesOnRowCount = sizeof($filesOnRow);
        foreach ($filesOnRow as $index => $fileData) {
            list($width, $height, $maxWidth) = $fileData;
            $counter++;
            $widthFormula = '(100% - ' . $spacing . '*' . ($filesOnRowCount - 1) . ')*' . (number_format($totalWidth === 0 ? 0 : $width / $totalWidth, 6, '.', ''));
            $style = 'vertical-align:top;display:inline-block;width:calc(' . $widthFormula . ');';
            if ($counter < $filesOnRowCount) {
                $style .= 'margin-right:' . $spacing . ';';
            }
            if ($maxWidth !== null) {
                $style .= 'max-width:' . $maxWidth . ';';
            }
            if (!$isLastRow) {
                $style .= 'margin-bottom:' . $spacing . ';';
            }
            $containerStyle .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . ($index + 1) . '){' . $style . '}';
        }
        return $widthFormula . '/' . $width;
    };

    $minGridImageWidth = 200;
    $maxGridImageWidth = 2000;
    $gridImageWidthStep = 200;

    $lastRenderedHeightFormula = null;
    $responsiveAttributes = [];
    for ($maxWidth = $minGridImageWidth; $maxWidth <= $maxGridImageWidth; $maxWidth += $gridImageWidthStep) {
        $totalRowImagesWidth = 0;
        $selector = '[data-grid="' . $maxWidth . '"]';
        $filesOnRow = [];
        $showOnePerRow = ($imageSize === 'large' || $imageSize === 'huge') && $maxWidth < 500; // better for mobile
        $counter = 0;
        foreach ($files as $index => $file) {
            $counter++;
            $fileWidth = $file['width'];
            $fileHeight = $file['height'];
            $maxFileWidth = floor($fileWidth === null || $fileHeight === null ? 0 : $maxHeight / $fileHeight * $fileWidth);
            if ($showOnePerRow || $totalRowImagesWidth + $maxFileWidth > $maxWidth) {
                if (!empty($filesOnRow)) {
                    $lastRenderedHeightFormula = $addFilesToRow($selector, $filesOnRow, false);
                }
                $filesOnRow = [];
                $totalRowImagesWidth = 0;
            }
            $filesOnRow[$index] = [$maxFileWidth, $maxHeight, null];
            $totalRowImagesWidth += $maxFileWidth;
        }
        if (!empty($filesOnRow)) {
            if (sizeof($filesOnRow) === 1 && !$showOnePerRow) { // make the last one the same height as the previous ones
                if ($lastRenderedHeightFormula !== null) {
                    foreach ($filesOnRow as $index => $fileData) {
                        $fileData[2] = 'calc(' . $lastRenderedHeightFormula . '*' . $fileData[0] . ')';
                        $filesOnRow[$index] = $fileData;
                    }
                } else {
                    foreach ($filesOnRow as $index => $fileData) {
                        $fileData[2] = $fileData[0] . 'px';
                        $filesOnRow[$index] = $fileData;
                    }
                }
            }
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
} elseif ($type === 'firstBig') {
    $hasElementID = true;
    $hasResponsiveAttributes = true;
    $containerStyle .= '#' . $galleryID . '>div:first-child{display:block;width:100%;}';

    $responsiveAttributes = [];
    $responsiveAttributes[] = 'w<' . (100) . '=>data-columns=1';
    $responsiveAttributes[] = 'w>=' . (100) . '&&w<' . (100 * 2) . '=>data-columns=2';
    $responsiveAttributes[] = 'w>=' . (100 * 2) . '&&w<' . (100 * 3) . '=>data-columns=3';
    $responsiveAttributes[] = 'w>=' . (100 * 3) . '&&w<' . (100 * 4.3) . '=>data-columns=4';
    $responsiveAttributes[] = 'w>=' . (100 * 4.3) . '&&w<' . (100 * 6) . '=>data-columns=5';
    $responsiveAttributes[] = 'w>=' . (100 * 6) . '=>data-columns=6';
    $containerAttributes .= ' data-responsive-attributes="' . implode(',', $responsiveAttributes) . '"';

    for ($columnsCount = 1; $columnsCount <= 6; $columnsCount++) {
        $attributeSelector = '[data-columns="' . $columnsCount . '"]';
        $containerStyle .= '#' . $galleryID . $attributeSelector . '>div:not(:first-child){display:inline-block;width:calc((100% - ' . $spacing . '/2*' . ($columnsCount - 1) . ')/' . $columnsCount . ');margin-right:calc(' . $spacing . '/2);margin-top:calc(' . $spacing . '/2);}';
        $containerStyle .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . $columnsCount . 'n + 1){margin-right:0;}';
        for ($i = 1; $i <= $columnsCount; $i++) {
            $containerStyle .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . $i . ' + 1){margin-top:0;}';
        }
    }
}

if ($hasElementID) {
    $containerAttributes .= ' id="' . htmlentities($galleryID) . '"';
}

$class = (string) $component->class;
if (isset($class[0])) {
    $containerAttributes .= ' class="' . htmlentities($class) . '"';
}
echo '<html>';

echo '<head>';
if ($hasLightbox) {
    echo '<link rel="client-packages-embed" name="lightbox">';
}
if ($hasResponsiveAttributes) {
    echo '<link rel="client-packages-embed" name="responsiveAttributes">';
}
if (isset($containerStyle[0])) {
    echo '<style>' . $containerStyle . '</style>';
}
echo '</head>';

echo '<body>';
if ($hasLightbox) {
    echo '<script>';
    echo 'window.' . $galleryID . '=' . json_encode($jsData) . ';';
    echo '</script>';
}
if ($internalOptionRenderContainer) {
    echo '<div' . $containerAttributes . '>';
}
foreach ($files as $index => $file) {
    $filename = $file['filename'];
    $fileElement = $file['element'];
    $class = (string) $fileElement->getAttribute('class');
    $classAttribute = isset($class[0]) ? ' class="' . htmlentities($class) . '"' : '';
    $alt = (string) $fileElement->getAttribute('alt');
    $altAttribute = isset($alt[0]) ? ' alt="' . htmlentities($alt) . '"' : '';
    $title = (string) $fileElement->getAttribute('title');
    $titleAttribute = isset($title[0]) ? ' title="' . htmlentities($title) . '"' : '';
    $quality = (string)$fileElement->getAttribute('quality');
    $quality = isset($quality[0]) ? (int)$quality : null;
    if ($internalOptionRenderImageContainer) {
        echo '<div>';
    }
    if ($onClick === 'fullscreen') {
        $imageOnClick = 'clientPackages.get(\'lightbox\').then(function(lightbox){var context=lightbox.make({showCloseButton:false});' .
            'clientPackages.get(\'-ivopetkov-image-gallery-lightbox\').then(function(imageGalleryLightbox){' .
            'imageGalleryLightbox.open(context,window.' . $galleryID . ',' . $index . ');' .
            '})' .
            '});';
        echo '<a' . $titleAttribute . ' onclick="' . htmlentities($imageOnClick) . '" style="cursor:pointer;">';
    } elseif ($onClick === 'url') {
        $url = (string) $fileElement->getAttribute('url');
        echo '<a' . $titleAttribute . ' href="' . (isset($url[0]) ? htmlentities($url) : '#') . '">';
    } elseif ($onClick === 'custom') {
        $onClick = (string) $fileElement->getAttribute('onClick');
        echo '<a' . $titleAttribute . ' onclick="' . htmlentities(isset($onClick[0])) . '" style="cursor:pointer;">';
    }
    $currentImageAspectRatio = $imageAspectRatio;
    if ($type === 'firstBig' && $index > 0) {
        $currentImageAspectRatio = '1:1';
    }
    if ($lazyLoadImages) {
        $imageAttributes = '';
        if ($currentImageAspectRatio !== null) {
            $imageAttributes .= ' aspectRatio="' . $currentImageAspectRatio . '"';
        }
        if ($imageLoadingBackground !== null) {
            $imageAttributes .= ' loadingBackground="' . $imageLoadingBackground . '"';
        }
        if ($quality !== null) {
            $imageAttributes .= ' quality="' . $quality . '"';
        }
        $imageAttributes .= ' minImageWidth="' . $fileElement->getAttribute('minimagewidth') . '"';
        $imageAttributes .= ' minImageHeight="' . $fileElement->getAttribute('minimageheight') . '"';
        $imageAttributes .= ' maxImageWidth="' . $fileElement->getAttribute('maximagewidth') . '"';
        $imageAttributes .= ' maxImageHeight="' . $fileElement->getAttribute('maximageheight') . '"';
        $imageAttributes .= ' fileWidth="' . $file['width'] . '"';
        $imageAttributes .= ' fileHeight="' . $file['height'] . '"';
        echo '<component src="lazy-image"' . $classAttribute . $altAttribute . $titleAttribute . ' filename="' . htmlentities($filename) . '"' . $imageAttributes . '/>';
    } else {
        $assetOptions = [];
        $assetOptions['cacheMaxAge'] = 999999999;
        if ($currentImageAspectRatio !== null) {
            $currentImageAspectRatioParts = explode(':', $currentImageAspectRatio);
            $imageWidth = $file['width'];
            $imageHeight = $file['height'];
            $newImageHeight = $imageWidth * $currentImageAspectRatioParts[1] / $currentImageAspectRatioParts[0];
            if ($imageWidth !== null && $imageHeight !== null) {
                if ($newImageHeight > $imageHeight) {
                    $assetOptions['width'] = (int) ($imageHeight * $currentImageAspectRatioParts[0] / $currentImageAspectRatioParts[1]);
                    $assetOptions['height'] = $imageHeight;
                } else {
                    $assetOptions['width'] = $imageWidth;
                    $assetOptions['height'] = $newImageHeight;
                }
            }
        }
        if ($quality !== null) {
            $assetOptions['quality'] = $quality;
        }
        $imageURL = $app->assets->getURL($filename, $assetOptions);
        echo '<img' . $classAttribute . $altAttribute . $titleAttribute . ' style="max-width:100%;" src="' . $imageURL . '"/>';
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
    echo '<script>clientPackages.get(\'responsiveAttributes\').then(function(r){r.run();})</script>';
}
echo '</body>';

echo '</html>';
