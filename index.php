<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use \BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__FILE__);

$context->assets->addDir('assets');

$app->components->addAlias('image-gallery', 'file:' . $context->dir . '/components/imageGallery.php');

$app->serverRequests
        ->add('-ivopetkov-image-gallery-get-images', function($data) use ($app) {
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
                    $getImageSize = function($filename) use ($app) {
                        $cacheKey = 'image-gallery-image-size-' . $filename;
                        $cachedData = $app->cache->getValue($cacheKey);
                        if ($cachedData !== null) {
                            $size = json_decode($cachedData, true);
                            return $size;
                        }
                        try {
                            $details = $app->assets->getDetails($filename, ['width', 'height']);
                            $size = [$details['width'], $details['height']];
                        } catch (\Exception $e) {
                            $size = [1, 1];
                        }
                        $app->cache->set($app->cache->make($cacheKey, json_encode($size)));
                        return $size;
                    };

                    foreach ($filenames as $filename) {
                        $html = $app->components->process('<component style="background-color:#000;" src="lazy-image" filename="' . $filename . '"/>');
                        list($imageWidth, $imageHeight) = $getImageSize($filename);
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
        ->add('-ivopetkov-image-gallery-lightbox', 1, function(IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
            $package->addJSFile($context->assets->getURL('assets/imageGalleryLightbox.min.js', ['cacheMaxAge' => 999999999, 'version' => 4]));
            $package->preparePackage('-ivopetkov-image-gallery-lightbox-requirements');
            $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryLightbox;';
        })
        ->add('-ivopetkov-image-gallery-responsive-attributes', 1, function(IvoPetkov\BearFrameworkAddons\ClientPackage $package) {
            // taken from dev/responsiveAttributes.min.js
            $code = 'responsiveAttributes=function(){var q=[],f=function(){for(var f=document.querySelectorAll("[data-responsive-attributes]"),p=f.length,r=0;r<p;r++){var g=f[r],c=g.getBoundingClientRect();g.responsiveAttributesCache=[Math.round(c.width),Math.round(c.height)];c=g.getAttribute("data-responsive-attributes");if("undefined"===typeof q[c]){for(var b=c.split(","),k=b.length,h=[],e=0;e<k;e++){var a=b[e].split("=>");if("undefined"!==typeof a[0]&&"undefined"!==typeof a[1]){var l=a[0].trim();if(0<l.length){var d=a[1].split("=");"undefined"!==typeof d[0]&&"undefined"!==typeof d[1]&&(a=d[0].trim(),0<a.length&&(d=d[1].trim(),0<d.length&&("undefined"===typeof h[a]&&(h[a]=[]),h[a].push([l,d]))))}}}q[c]=h}var c=q[c],m;for(m in c){b=g.getAttribute(m);null===b&&(b="");b=0<b.length?b.split(" "):[];k=c[m];h=k.length;for(e=0;e<h;e++){for(var l=k[e][1],a=g,a=(new Function("return "+k[e][0].split("w").join(a.responsiveAttributesCache[0]).split("h").join(a.responsiveAttributesCache[1])))(),d=!1,t=b.length,n=0;n<t;n++)if(b[n]===l){a?d=!0:b.splice(n,1);break}a&&!d&&b.push(l)}g.setAttribute(m,b.join(" "))}}},p=function(){window.addEventListener("resize",f);window.addEventListener("load",f);"undefined"!==typeof MutationObserver&&(new MutationObserver(function(){f()})).observe(document.querySelector("body"),{childList:!0,subtree:!0})};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",p):p();return{run:f}}();';
            $package->addJSCode($code);
            $package->get = 'return responsiveAttributes;';
        })
        ->add('-ivopetkov-image-gallery-lightbox-requirements', 1, function(IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
            $package->addJSFile($context->assets->getURL('assets/swiper.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
            $package->addCSSFile($context->assets->getURL('assets/swiper.min.css', ['cacheMaxAge' => 999999999, 'version' => 1]));
            $package->addJSFile($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
        });
