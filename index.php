<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use \BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__DIR__);

$context->assets
    ->addDir('assets');

$app->components
    ->addAlias('image-gallery', 'file:' . $context->dir . '/components/imageGallery.php');

$app->serverRequests
    ->add('-ivopetkov-image-gallery-get-images', function ($data) use ($app) {
        if (isset($data['serverData'])) {
            $serverData = $data['serverData'];
            $encryptedServerDataHash = substr($serverData, 0, 32);
            try {
                $encryptedServerData = gzuncompress($app->encryption->decrypt(base64_decode(substr($serverData, 32))));
            } catch (\Exception $e) {
                return;
            }
            if (md5($encryptedServerData) !== $encryptedServerDataHash) {
                return;
            }
            $encryptedServerData = json_decode($encryptedServerData, true);
            if (is_array($encryptedServerData) && isset($encryptedServerData[0], $encryptedServerData[1]) && $encryptedServerData[0] === 'imagegallery') {
                $result = [];
                $files = $encryptedServerData[1];
                $maxImageSize = 4000;

                $sharedImageAttributes = '';
                $imageLoadingBackground = isset($encryptedServerData[2]) ? $encryptedServerData[2] : '';
                if ($imageLoadingBackground !== '') {
                    $sharedImageAttributes .= ' loading-background="' . htmlentities($imageLoadingBackground) . '"';
                }

                foreach ($files as $file) {
                    $filename = $file[0];
                    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $imageWidth = $file[1];
                    $imageHeight = $file[2];
                    $imageAttributes = $sharedImageAttributes;
                    if (isset($file[3])) {
                        $imageAttributes .= $file[3];
                    }
                    $html = '<component src="lazy-image" style="background-color:#000;" filename="' . htmlentities($filename) . '" file-width="' . $imageWidth . '" file-height="' . $imageHeight . '" max-asset-width="' . $maxImageSize . '" max-asset-height="' . $maxImageSize . '"' . $imageAttributes . '/>';
                    $html = $app->components->process($html);
                    $html = $app->clientPackages->process($html);
                    //$downloadURL = $app->assets->getURL($filename, ['download' => true]);
                    if ($extension === 'svg') {
                        if ($imageWidth > $imageHeight) {
                            $maxWidth = $maxImageSize;
                            $maxHeight = floor($imageHeight / $imageWidth * $maxImageSize);
                        } else {
                            $maxHeight = $maxImageSize;
                            $maxWidth = floor($imageWidth / $imageHeight * $maxImageSize);
                        }
                        $result[] = [$maxWidth, $maxHeight, $html];
                    } else {
                        $result[] = [$imageWidth, $imageHeight, $html];
                    }
                }

                return json_encode([
                    'status' => '1',
                    'result' => $result
                ]);
            }
        }
    });

$app->clientPackages
    ->add('-ivopetkov-image-gallery-lightbox', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        //$package->addJSCode(file_get_contents($context->dir . '/dev/imageGalleryLightbox.js'));
        $package->addJSFile($context->assets->getURL('assets/imageGalleryLightbox.min.js', ['cacheMaxAge' => 999999999, 'version' => 13]));
        $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryLightbox;';

        $nextButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M10 6l6 6-6 6"/></svg>';
        $previousButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M14 18l-6-6 6-6"/></svg>';
        $closeButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#999"><path d="M11.47 10l7.08-7.08c.4-.4.4-1.06 0-1.47-.4-.4-1.06-.4-1.47 0L10 8.53 2.92 1.45c-.4-.4-1.07-.4-1.47 0-.4.4-.4 1.06 0 1.47L8.53 10l-7.08 7.08c-.4.4-.4 1.07 0 1.47.2.2.47.3.74.3.23 0 .5-.1.7-.3l7.1-7.08 7.07 7.08c.2.2.47.3.73.3.3 0 .56-.1.76-.3.4-.4.4-1.06 0-1.47L11.46 10z"/></svg>';
        $zoomInButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#999" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M8.974509 15.025492l-7.00884 7.008839"/><circle cx="9.202146" cy="-14.566437" r="7.525748" transform="matrix(0 1 -1 0 -.057854 .289271)"/><path d="M17.783256 9.531725h-6.43264"/><path d="M14.566936 12.748046v-6.43264"/></svg>';
        $zoomOutButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#999" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M8.974509 15.025492l-7.00884 7.008839"/><circle cx="9.202146" cy="-14.566437" r="7.525748" transform="matrix(0 1 -1 0 -.057854 .289271)"/><path d="M17.783256 9.531725h-6.43264"/></svg>';
        $buttonStyle = 'border-radius:2px;width:42px;height:42px;position:fixed;cursor:pointer;background-repeat:no-repeat;background-position:center;background-color:rgba(0,0,0,0.6);';
        $css = '[data-image-gallery-button="next"]{display:none;top:calc((100% - 42px)/2);right:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($nextButtonIcon) . ');background-size:30px;' . $buttonStyle . '}';
        $css .= '[data-image-gallery-button="previous"]{display:none;top:calc((100% - 42px)/2);left:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($previousButtonIcon) . ');background-size:30px;' . $buttonStyle . '}';
        $css .= '[data-image-gallery-button="close"]{right:5px;top:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($closeButtonIcon) . ');background-size:16px;' . $buttonStyle . '}';
        $css .= '[data-image-gallery-button="zoomin"]{display:none;right:52px;top:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($zoomInButtonIcon) . ');background-size:20px;' . $buttonStyle . 'background-position:10px 12px;}';
        $css .= '[data-image-gallery-button="zoomout"]{display:none;right:52px;top:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($zoomOutButtonIcon) . ');background-size:20px;' . $buttonStyle . 'background-position:10px 12px;}';
        //$css .= '[data-image-gallery-button="download"]{display:none;right:5px;bottom:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($closeButtonIcon) . ');background-size:16px;' . $buttonStyle . '}';

        $package->addCSSCode($css);
    })
    ->add('-ivopetkov-image-gallery-lightbox-requirements', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->embedPackage('html5DOMDocument');
        $package->embedPackage('touchEvents');
        //$package->addJSCode(file_get_contents($context->dir . '/dev/imageGalleryImageZoom.js'));
        $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryImageZoom;';
        $package->addJSFile($context->assets->getURL('assets/imageGalleryImageZoom.min.js', ['cacheMaxAge' => 999999999, 'version' => 6]));
    });
