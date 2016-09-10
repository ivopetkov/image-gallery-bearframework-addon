# Image Gallery
Addon for Bear Framework

This addon enables you to easily create lazy-loaded image galleries that are SEO friendly. It's based on the popular library [Responsively Lazy](https://github.com/ivopetkov/responsively-lazy/). Multiple versions with different sizes are created on the fly for each image and only the best ones are loaded. This saves bandwidth and loads the website faster.

[![Build Status](https://travis-ci.org/ivopetkov/image-gallery-bearframework-addon.svg)](https://travis-ci.org/ivopetkov/image-gallery-bearframework-addon)
[![Latest Stable Version](https://poser.pugx.org/ivopetkov/image-gallery-bearframework-addon/v/stable)](https://packagist.org/packages/ivopetkov/image-gallery-bearframework-addon)
[![codecov.io](https://codecov.io/github/ivopetkov/image-gallery-bearframework-addon/coverage.svg?branch=master)](https://codecov.io/github/ivopetkov/image-gallery-bearframework-addon?branch=master)
[![License](https://poser.pugx.org/ivopetkov/image-gallery-bearframework-addon/license)](https://packagist.org/packages/ivopetkov/image-gallery-bearframework-addon)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/2f58257779ed456ba7f4d9a2667c4e36)](https://www.codacy.com/app/ivo_2/image-gallery-bearframework-addon)

## Download and install

**Install via Composer**

```shell
composer require ivopetkov/image-gallery-bearframework-addon
```

**Download an archive**

Download the [latest release](https://github.com/ivopetkov/image-gallery-bearframework-addon/releases) from the [GitHub page](https://github.com/ivopetkov/image-gallery-bearframework-addon) and include the autoload file.
```php
include '/path/to/the/addon/autoload.php';
```

## Enable the addon
Enable the addon for your Bear Framework application.

```php
$app->addons->add('ivopetkov/image-gallery-bearframework-addon');
```


## Usage

```html
<component src="image-gallery">
    <file filename="/path/to/file1.jpg"/>
    <file filename="/path/to/file2.jpg"/>
    <file filename="/path/to/file3.jpg"/>
</component>
```

### Attributes

`onClick`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Defines the behaviour on image click. Available values: fullscreen, url, custom, none

`imageAspectRatio`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The proportional relationship between the width and the height of every image. It is useful for cropping and resizing the images. Example values: 1:1, 1:2, 1.5:1, etc.

`columnsCount`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The number of columns that will be filled with the images

`imageSize`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The size of the images, if columnsCount is not specified. Available values: tiny, small, medium, large, huge

`spacing`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The spacing between the images. Example values: 10px, 1rem, etc.

`class`

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;HTML class attribute value

### Examples

3 columns of square images that will be opened in fullscreen when clicked
```html
<component src="image-gallery" columnsCount="3" imageAspectRatio="1:1" onClick="fullscreen">
    <file filename="/path/to/file1.jpg"/>
    <file filename="/path/to/file2.jpg"/>
    <file filename="/path/to/file3.jpg"/>
</component>
```

## License
Lazy image addon for Bear Framework is open-sourced software. It's free to use under the MIT license. See the [license file](https://github.com/ivopetkov/image-gallery-bearframework-addon/blob/master/LICENSE) for more information.

## Author
This addon is created by Ivo Petkov. Feel free to contact me at [@IvoPetkovCom](https://twitter.com/IvoPetkovCom) or [ivopetkov.com](https://ivopetkov.com).
