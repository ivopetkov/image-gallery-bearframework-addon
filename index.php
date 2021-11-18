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
                $filenames = $encryptedServerData[1];
                $maxImageSize = isset($encryptedServerData[2]) ? (int)$encryptedServerData[2] : null;
                if ($maxImageSize === 0) {
                    $maxImageSize = null;
                }
                $getImageSize = function ($filename) use ($app) {
                    try {
                        $details = $app->assets->getDetails($filename, ['width', 'height']);
                        $size = [$details['width'], $details['height']];
                    } catch (\Exception $e) {
                        $size = [1, 1];
                    }
                    return $size;
                };

                foreach ($filenames as $filename) {
                    $html = $app->components->process('<component style="background-color:#000;" src="lazy-image" filename="' . $filename . '" maxSize="' . $maxImageSize . '"/>');
                    list($imageWidth, $imageHeight) = $getImageSize($filename);
                    if ($maxImageSize !== null) {
                        if ($imageWidth > $maxImageSize) {
                            $imageHeight = floor($maxImageSize / $imageWidth * $imageHeight);
                            $imageWidth = $maxImageSize;
                        }
                        if ($imageHeight > $maxImageSize) {
                            $imageWidth = floor($maxImageSize / $imageHeight * $imageWidth);
                            $imageHeight = $maxImageSize;
                        }
                    }
                    $result[] = [$imageWidth, $imageHeight, $html];
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
        //$package->addJSCode(file_get_contents($context->dir . '/assets/imageGalleryLightbox.js'));
        $package->addJSFile($context->assets->getURL('assets/imageGalleryLightbox.min.js', ['cacheMaxAge' => 999999999, 'version' => 6]));
        $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryLightbox;';

        $nextButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M10 6l6 6-6 6"/></svg>';
        $previousButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"><path d="M14 18l-6-6 6-6"/></svg>';
        $closeButtonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#999"><path d="M11.47 10l7.08-7.08c.4-.4.4-1.06 0-1.47-.4-.4-1.06-.4-1.47 0L10 8.53 2.92 1.45c-.4-.4-1.07-.4-1.47 0-.4.4-.4 1.06 0 1.47L8.53 10l-7.08 7.08c-.4.4-.4 1.07 0 1.47.2.2.47.3.74.3.23 0 .5-.1.7-.3l7.1-7.08 7.07 7.08c.2.2.47.3.73.3.3 0 .56-.1.76-.3.4-.4.4-1.06 0-1.47L11.46 10z"/></svg>';
        $buttonStyle = 'border-radius:2px;width:42px;height:42px;position:fixed;cursor:pointer;background-repeat:no-repeat;background-position:center;background-color:rgba(0,0,0,0.6);';
        $css = '[data-image-gallery-button="next"]{display:none;top:calc((100% - 42px)/2);right:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($nextButtonIcon) . ');background-size:30px;' . $buttonStyle . '}';
        $css .= '[data-image-gallery-button="previous"]{display:none;top:calc((100% - 42px)/2);left:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($previousButtonIcon) . ');background-size:30px;' . $buttonStyle . '}';
        $css .= '[data-image-gallery-button="close"]{right:5px;top:5px;background-image:url(data:image/svg+xml;base64,' . base64_encode($closeButtonIcon) . ');background-size:16px;' . $buttonStyle . '}';
        $package->addCSSCode($css);
    })
    ->add('-ivopetkov-image-gallery-lightbox-requirements', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->addJSFile($context->assets->getURL('assets/swiper.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->addCSSFile($context->assets->getURL('assets/swiper.min.css', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->embedPackage('html5DOMDocument');
    });
