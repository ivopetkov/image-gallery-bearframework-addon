<?php

/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

BearFramework\Addons::register('ivopetkov/image-gallery-bearframework-addon', __DIR__, [
    'require' => [
        'ivopetkov/html-server-components-bearframework-addon',
        'ivopetkov/lazy-image-bearframework-addon',
        'ivopetkov/js-lightbox-bearframework-addon'
    ]
]);
