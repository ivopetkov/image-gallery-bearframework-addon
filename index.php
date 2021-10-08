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
        $package->addJSFile($context->assets->getURL('assets/imageGalleryLightbox.min.js', ['cacheMaxAge' => 999999999, 'version' => 5]));
        $package->get = 'return ivoPetkov.bearFrameworkAddons.imageGalleryLightbox;';
    })
    ->add('-ivopetkov-image-gallery-responsive-attributes', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) {
        // taken from dev/responsiveAttributes.min.js
        $code = 'responsiveAttributes=function(){var u=[],v=!1,t=function(){if(!v){v=!0;for(var z=document.querySelectorAll("[data-responsive-attributes]"),B=z.length,w=0;w<B;w++){var g=z[w],p=g.getBoundingClientRect();p={width:p.width,height:p.height};var f=g.getAttribute("data-responsive-attributes");if("undefined"===typeof u[f]){for(var b=f.split(","),l=b.length,h=[],d=0;d<l;d++){var c=b[d].split("=>");if("undefined"!==typeof c[0]&&"undefined"!==typeof c[1]){var m=c[0].trim();if(0<m.length){var a=c[1].split("=");"undefined"!==typeof a[0]&&"undefined"!==typeof a[1]&&(c=a[0].trim(),0<c.length&&(a=a[1].trim(),0<a.length&&("undefined"===typeof h[c]&&(h[c]=[]),h[c].push([m,a]))))}}}u[f]=h}f=u[f];for(var q in f){b=g.getAttribute(q);null===b&&(b="");b=0<b.length?b.split(" "):[];l=f[q];h=l.length;for(d=0;d<h;d++){m=l[d][1];c=g;a=l[d][0];for(var e=p,r=[],k=0;100>k;k++){var n="f"+r.length,x=a.match(/f\((.*?)\)/);if(null===x)break;a=a.replace(x[0],n);r.push([n,x[1]])}a=a.split("vw").join(window.innerWidth).split("w").join(e.width).split("vh").join(window.innerHeight).split("h").join(e.height);for(k=r.length-1;0<=k;k--)n=r[k],a=a.replace(n[0],n[1]+"(element,details)");try{var y=(new Function("element","details","return "+a))(c,e)}catch(C){y=!1}c=!1;a=b.length;for(e=0;e<a;e++)if(b[e]===m){y?c=!0:b.splice(e,1);break}y&&!c&&b.push(m)}b=b.join(" ");g.getAttribute(q)!==b&&g.setAttribute(q,b)}}v=!1}},A=function(){window.addEventListener("resize",t);window.addEventListener("load",t);"undefined"!==typeof MutationObserver&&(new MutationObserver(function(){t()})).observe(document.querySelector("body"),{childList:!0,subtree:!0})};"loading"===document.readyState?document.addEventListener("DOMContentLoaded",A):A();return{run:t}}();';
        $package->addJSCode($code);
        $package->get = 'return responsiveAttributes;';
    })
    ->add('-ivopetkov-image-gallery-lightbox-requirements', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package) use ($context) {
        $package->addJSFile($context->assets->getURL('assets/swiper.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->addCSSFile($context->assets->getURL('assets/swiper.min.css', ['cacheMaxAge' => 999999999, 'version' => 1]));
        $package->addJSFile($context->assets->getURL('assets/HTML5DOMDocument.min.js', ['cacheMaxAge' => 999999999, 'version' => 1]));
    });
