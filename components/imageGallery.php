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
    $fileWidth = (string) $fileElement->getAttribute('file-width');
    $fileHeight = (string) $fileElement->getAttribute('file-height');
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

$type = (string) $component->getAttribute('type');
if (array_search($type, ['columns', 'grid', 'firstBig']) === false) {
    $type = 'columns';
}

$onClick = (string) $component->getAttribute('onclick');
if (array_search($onClick, ['fullscreen', 'url', 'script', 'none']) === false) {
    $onClick = 'fullscreen';
}

$imageLoadingBackground = (string) $component->getAttribute('image-loading-background');
if ($imageLoadingBackground === '') {
    $imageLoadingBackground = null;
}

$spacing = (string) $component->getAttribute('spacing');
if ($spacing === '') {
    $spacing = '0px';
}

$lazyLoad = $component->getAttribute('lazy-load') === 'true';

$galleryID = 'imggallery' . uniqid();
$containerAttributes = '';

if ($onClick === 'fullscreen') {
    $hasLightbox = true;
}

$imageAspectRatio = null;

$containerStyle = '';
if ($type === 'columns') {

    $columnsCount = (string) $component->getAttribute('columns-count');
    if (is_numeric($columnsCount) && ((int)$columnsCount >= 1 && (int)$columnsCount <= 20)) {
        $columnsCount = (int)$columnsCount;
    } else if ($columnsCount === 'match') {
        $columnsCount = sizeof($files);
    } else {
        $columnsCount = 'auto';
    }

    $imageSize = (string) $component->getAttribute('image-size');
    if (array_search($imageSize, ['tiny', 'small', 'medium', 'large', 'huge']) === false) {
        $imageSize = 'medium';
    }

    $imageAspectRatio = (string) $component->getAttribute('image-aspect-ratio');
    if (preg_match('/^[0-9\.]+:[0-9\.]+$/', $imageAspectRatio) !== 1) {
        $imageAspectRatio = null;
    }

    $getColumnsStyle = function ($columnsCount, $attributeSelector = '') use ($galleryID, $spacing) {
        $result = '#' . $galleryID . $attributeSelector . '>div{vertical-align:top;display:inline-block;width:calc((100% - ' . $spacing . '*' . ($columnsCount - 1) . ')/' . $columnsCount . ');margin-right:' . $spacing . ';margin-top:' . $spacing . ';}';
        $result .= '#' . $galleryID . $attributeSelector . '>div:nth-child(' . $columnsCount . 'n){margin-right:0;width:calc((100% - ' . $spacing . '*' . ($columnsCount - 1) . ')/' . $columnsCount . ' - 0.99px);}'; // 0.99px is fix so there is no overflow
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
    $imageSize = (string) $component->getAttribute('image-size');
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
        $filesOnRowCount = sizeof($filesOnRow);
        $allFilesWidthFormulas = [];
        if ($filesOnRowCount > 1) { // Move the previous to last, last.
            $lastIndex = null;
            $previousToLastIndex = null;
            $temp = [];
            $counter = 0;
            foreach ($filesOnRow as $index => $fileData) {
                $counter++;
                if ($counter === $filesOnRowCount - 1) {
                    $previousToLast = [$index, $fileData];
                    $previousToLastIndex = $index;
                } else {
                    $temp[$index] = $fileData;
                }
                $lastIndex = $index;
            }
            $temp[$previousToLast[0]] = $previousToLast[1];
            $filesOnRow = $temp;
        }
        foreach ($filesOnRow as $index => $fileData) {
            list($width, $maxWidth) = $fileData;
            $widthFormula = '(100% - ' . $spacing . '*' . ($filesOnRowCount - 1) . ')*' . (number_format($totalWidth === 0 ? 0 : $width / $totalWidth, 6, '.', ''));
            $allFilesWidthFormulas[] = $widthFormula;
            $style = 'vertical-align:top;display:inline-block;width:calc(' . $widthFormula . ');';
            if ($filesOnRowCount > 1) {
                if ($lastIndex === $index) {
                } elseif ($previousToLastIndex !== null && $previousToLastIndex === $index) { // last one with margin right
                    $style .= 'margin-right:calc(100% - ' . $spacing . '*' . ($filesOnRowCount - 2) . ' - 0.999999px - ' . implode(' - ', $allFilesWidthFormulas) . ');';
                } else {
                    $style .= 'margin-right:' . $spacing . ';';
                }
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

    $minGridImageWidth = 250; // There is a special case for 450
    $maxGridImageWidth = 2050;
    $gridImageWidthStep = 200;

    $lastRenderedHeightFormula = null;
    $responsiveAttributes = [];
    for ($maxWidth = $minGridImageWidth; $maxWidth <= $maxGridImageWidth; $maxWidth += $gridImageWidthStep) {
        $totalRowImagesWidth = 0;
        $selector = '[data-grid="' . $maxWidth . '"]';
        $filesOnRow = [];
        $showOnePerRow = false; // better for mobile
        if (($imageSize === 'medium' || $imageSize === 'large' || $imageSize === 'huge') && $maxWidth <= 450) { // 450 is big phone's width
            $showOnePerRow = true;
        }
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
            $filesOnRow[$index] = [$maxFileWidth, null];
            $totalRowImagesWidth += $maxFileWidth;
        }
        if (!empty($filesOnRow)) {
            if (!$showOnePerRow) { // make the last one the same height as the previous ones
                if ($lastRenderedHeightFormula !== null) {
                    foreach ($filesOnRow as $index => $fileData) {
                        $fileData[1] = 'calc(' . $lastRenderedHeightFormula . '*' . $fileData[0] . ')';
                        $filesOnRow[$index] = $fileData;
                    }
                } else {
                    foreach ($filesOnRow as $index => $fileData) {
                        $fileData[1] = $fileData[0] . 'px';
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

$class = (string) $component->getAttribute('class');
if (isset($class[0])) {
    $containerAttributes .= ' class="' . htmlentities($class) . '"';
}

$supportedAssetOptionsAttributes = [
    'cacheMaxAge' => ['asset-cache-max-age', 'int'],
    'quality' => ['asset-quality', 'int'],
    'svgFill' => ['asset-svg-fill', 'string'],
    'svgStroke' => ['asset-svg-stroke', 'string']
];

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
if ($internalOptionRenderContainer) {
    echo '<div' . $containerAttributes . '>';
}

if ($hasLightbox) {
    $lightboxServerData = [
        'imagegallery', // verification key
        [], // files data
        (string) $component->getAttribute('preview-image-loading-background')
    ];
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
    if ($lazyLoad || $hasLightbox) {
        $assetOptionsAsAttributes = '';
    }
    if (!$lazyLoad) {
        $assetOptions = [];
    }
    foreach ($supportedAssetOptionsAttributes as $assetOptionName => $assetOptionAttributeData) {
        $assetOptionAttributeName = $assetOptionAttributeData[0];
        $assetOptionAttributeValue = (string)$fileElement->getAttribute($assetOptionAttributeName);
        if ($assetOptionAttributeValue !== '') {
            if ($lazyLoad) {
                $assetOptionsAsAttributes .= ' ' . $assetOptionAttributeName . '="' . htmlentities($assetOptionAttributeValue) . '"';
            } else {
                if ($assetOptionAttributeData[1] === 'int') {
                    $assetOptionAttributeValue = (int)$assetOptionAttributeValue;
                }
                $assetOptions[$assetOptionName] = $assetOptionAttributeValue;
            }
        }
    }
    if ($hasLightbox) {
        $lightboxServerData[1][] = [$filename, $file['width'], $file['height'], $assetOptionsAsAttributes];
    }
    if ($internalOptionRenderImageContainer) {
        echo '<div>';
    }
    if ($onClick === 'fullscreen') {
        $imageOnClick = 'window.' . $galleryID . 'c(' . $index . ');';
        echo '<a' . $titleAttribute . ' onclick="' . htmlentities($imageOnClick) . '" style="cursor:pointer;">';
    } elseif ($onClick === 'url') {
        $url = (string) $fileElement->getAttribute('url');
        echo '<a' . $titleAttribute . ' href="' . (isset($url[0]) ? htmlentities($url) : '#') . '">';
    } elseif ($onClick === 'script') {
        $onClickScript = (string) $fileElement->getAttribute('script');
        echo '<a' . $titleAttribute . ' onclick="' . htmlentities($onClickScript) . '" style="cursor:pointer;">';
    }
    $currentImageAspectRatio = $imageAspectRatio;
    if ($type === 'firstBig' && $index > 0) {
        $currentImageAspectRatio = '1:1';
    }
    if ($lazyLoad) {
        $imageAttributes = '';
        if ($currentImageAspectRatio !== null) {
            $imageAttributes .= ' aspect-ratio="' . htmlentities($currentImageAspectRatio) . '"';
        }
        if ($imageLoadingBackground !== null) {
            $imageAttributes .= ' loading-background="' . htmlentities($imageLoadingBackground) . '"';
        }
        $imageAttributes .= ' min-asset-width="' . $fileElement->getAttribute('min-asset-width') . '"';
        $imageAttributes .= ' min-asset-height="' . $fileElement->getAttribute('min-asset-height') . '"';
        $imageAttributes .= ' max-asset-width="' . $fileElement->getAttribute('max-asset-width') . '"';
        $imageAttributes .= ' max-asset-height="' . $fileElement->getAttribute('max-asset-height') . '"';
        $imageAttributes .= ' file-width="' . $file['width'] . '"';
        $imageAttributes .= ' file-height="' . $file['height'] . '"';
        echo '<component src="lazy-image"' . $classAttribute . $altAttribute . $titleAttribute . ' filename="' . htmlentities($filename) . '"' . $imageAttributes . $assetOptionsAsAttributes . '/>';
    } else {
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
        $imageURL = $app->assets->getURL($filename, $assetOptions);
        echo '<img' . $classAttribute . $altAttribute . $titleAttribute . ' style="max-width:100%;" src="' . $imageURL . '"/>';
    }

    if ($onClick === 'fullscreen' || $onClick === 'url' || $onClick === 'script') {
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
if ($hasLightbox) {
    echo '<script>';
    $lightboxServerData = json_encode($lightboxServerData);
    $lightboxJsData = md5($lightboxServerData) . base64_encode($app->encryption->encrypt(gzcompress($lightboxServerData)));
    echo 'window.' . $galleryID . 'c=function(i){clientPackages.get(\'lightbox\').then(function(lightbox){var c=lightbox.make({showCloseButton:false});clientPackages.get(\'-ivopetkov-image-gallery-lightbox\').then(function(l){l.open(c,' . json_encode($lightboxJsData) . ',i);})});};';
    echo '</script>';
}
echo '</body>';

echo '</html>';
