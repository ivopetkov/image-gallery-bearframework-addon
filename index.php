<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

$context->assets->addDir('assets');

$app->components->addAlias('image-gallery', 'file:' . $context->dir . '/components/imageGallery.php');
