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

$context->assets->addDir('assets');

$app->components->addAlias('image-gallery', 'file:' . $context->dir . '/components/imageGallery.php');

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
                $getImageSize = function ($filename) use ($app) {
                    // $cacheKey = 'image-gallery-image-size-' . $filename;
                    // $cachedData = $app->cache->getValue($cacheKey);
                    // if ($cachedData !== null) {
                    //     $size = json_decode($cachedData, true);
                    //     return $size;
                    // }
                    try {
                        $details = $app->assets->getDetails($filename, ['width', 'height']);
                        $size = [$details['width'], $details['height']];
                    } catch (\Exception $e) {
                        $size = [1, 1];
                    }
                    //$app->cache->set($app->cache->make($cacheKey, json_encode($size)));
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
    ->add('-ivopetkov-image-gallery-lightbox', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->addJSFile($context->assets->getURL('assets/imageGalleryLightbox.min.js', ['cacheMaxAge' => 999999999, 'version' => 5]));
        $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryLightbox;';
    })
    ->add('-ivopetkov-image-gallery-responsive-attributes', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) {
        // taken from dev/responsiveAttributes.min.js
        $code = 'responsiveAttributes=function(){var u=[],g=function(){for(var g=document.querySelectorAll("[data-responsive-attributes]"),t=g.length,v=0;v<t;v++){var l=g[v],r=l.getBoundingClientRect();r={width:r.width,height:r.height};var f=l.getAttribute("data-responsive-attributes");if("undefined"===typeof u[f]){for(var c=f.split(","),m=c.length,h=[],e=0;e<m;e++){var b=c[e].split("=>");if("undefined"!==typeof b[0]&&"undefined"!==typeof b[1]){var n=b[0].trim();if(0<n.length){var a=b[1].split("=");"undefined"!==typeof a[0]&&"undefined"!==typeof a[1]&&(b=a[0].trim(),0<b.length&&(a=a[1].trim(),0<a.length&&("undefined"===typeof h[b]&&(h[b]=[]),h[b].push([n,a]))))}}}u[f]=h}f=u[f];for(var w in f){c=l.getAttribute(w);null===c&&(c="");c=0<c.length?c.split(" "):[];m=f[w];h=m.length;for(e=0;e<h;e++){n=m[e][1];b=l;a=m[e][0];for(var p=r,d=[],k=0;100>k;k++){var q="f"+d.length,x=a.match(/f\((.*?)\)/);if(null===x)break;a=a.replace(x[0],q);d.push([q,x[1]])}a=a.split("w").join(p.width).split("h").join(p.height);for(k=d.length-1;0<=k;k--)q=d[k],a=a.replace(q[0],q[1]+"(element,details)");b=(new Function("element","details","return "+a))(b,p);a=!1;p=c.length;for(d=0;d<p;d++)if(c[d]===n){b?a=!0:c.splice(d,1);break}b&&!a&&c.push(n)}l.setAttribute(w,c.join(" "))}}},t=function(){window.addEventListener("resize",g);window.addEventListener("load",g);"undefined"!==typeof MutationObserver&&(new MutationObserver(function(){g()})).observe(document.querySelector("body"),{childList:!0,subtree:!0})};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",t):t();return{run:g}}();';
        $package->addJSCode($code);
        $package->get = 'return responsiveAttributes;';
    })
    ->add('-ivopetkov-image-gallery-lightbox-requirements', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->addJSFile($context->assets->getURL('assets/swiper.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->addCSSFile($context->assets->getURL('assets/swiper.min.css', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->addJSFile($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
    });
