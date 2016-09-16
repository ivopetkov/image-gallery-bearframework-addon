/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFramework = ivoPetkov.bearFramework || {};
ivoPetkov.bearFramework.addons = ivoPetkov.bearFramework.addons || {};

ivoPetkov.bearFramework.addons.imageGallery = (function () {

    return function (data) {

        var jsLightbox = new ivoPetkov.bearFramework.addons.jsLightbox(data.lightboxData);

        var updateImages = function () {
            var imagesCount = data.images.length;
            for (var index = 0; index < imagesCount; index++) {
                var imageContainerID = data.containerID + 'img' + index;
                var imageContainer = document.getElementById(imageContainerID);
                if (imageContainer !== null) {
                    var computedStyle = window.getComputedStyle(imageContainer.parentNode);
                    var maxWidth = parseInt(computedStyle.width.replace('px', ''), 10);
                    var maxHeight = parseInt(computedStyle.height.replace('px', ''), 10);
                    var imageWidth = data.images[index][0];
                    var imageHeight = data.images[index][1];
                    if (imageWidth > maxWidth) {
                        imageHeight = maxWidth / imageWidth * imageHeight;
                        imageWidth = maxWidth;
                    }
                    if (imageHeight > maxHeight) {
                        imageWidth = maxHeight / imageHeight * imageWidth;
                        //imageHeight = maxHeight;
                    }
                    imageContainer.style.width = imageWidth + 'px';
                }
            }
        };

        this.open = function (index) {
            jsLightbox.open(index);
            window.addEventListener('resize', updateImages);
        };

        this.onBeforeShow = function (index) {
            html5DOMDocument.insert(data.images[index][2]);
            updateImages();
        };

        this.onShow = function (index) {
            responsivelyLazy.run();
        };

    };
}());