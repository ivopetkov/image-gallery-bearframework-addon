<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use \BearFramework\App;

$app = App::get();
$context = $app->context->get(__FILE__);

$context->assets->addDir('assets');

$app->components->addAlias('image-gallery', 'file:' . $context->dir . '/components/imageGallery.php');

$app->serverRequests
        ->add('ivopetkov-image-gallery', function($data) use ($app) {
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
                            $size = $app->images->getSize($filename);
                        } catch (\Exception $ex) {
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
