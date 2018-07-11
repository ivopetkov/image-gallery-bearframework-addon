/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

var ivoPetkov = ivoPetkov || {};

ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};

if (typeof ivoPetkov.bearFrameworkAddons.imageGallery === 'undefined') {
    ivoPetkov.bearFrameworkAddons.imageGallery = (function () {

        return function (data) {

            var lightboxData = {'images': []};
            for (var i = 0; i < data.imagesCount; i++) {
                lightboxData.images.push({
                    'html': '<div id="' + data.galleryID + 'img' + i + '"></div>',
                    'onBeforeShow': 'window.' + data.galleryID + 'ig.onBeforeShow(' + i + ');',
                    'onShow': 'window.' + data.galleryID + 'ig.onShow(' + i + ');'
                });
            }
            var jsLightbox = new ivoPetkov.bearFrameworkAddons.jsLightbox(lightboxData);

            var imagesData = 0;
            var getImagesData = function (callback) {
                if (imagesData === 0) {
                    imagesData = 1;
                    ivoPetkov.bearFrameworkAddons.serverRequests.send('ivopetkov-image-gallery', {'serverData': data.serverData}, function (responseText) {
                        try {
                            var response = JSON.parse(responseText);
                        } catch (e) {
                            var response = {};
                        }
                        if (typeof response.status !== 'undefined' && response.status === '1') {
                            imagesData = response.result;
                            callback(imagesData);
                        }
                    });
                    return;
                } else if (imagesData === 1) {
                    return;// wait
                }
                callback(imagesData);
            };

            var updateImages = function () {
                if (imagesData === 0 || imagesData === 1) {
                    return;
                }
                var imagesCount = imagesData.length;
                for (var index = 0; index < imagesCount; index++) {
                    var imageContainerID = data.galleryID + 'img' + index;
                    var imageContainer = document.getElementById(imageContainerID);
                    if (imageContainer !== null) {
                        var computedStyle = window.getComputedStyle(imageContainer.parentNode);
                        var maxWidth = parseInt(computedStyle.width.replace('px', ''), 10);
                        var maxHeight = parseInt(computedStyle.height.replace('px', ''), 10);
                        var imageWidth = imagesData[index][0];
                        var imageHeight = imagesData[index][1];
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
                getImagesData(function (imagesData) {
                    var imageContainerID = data.galleryID + 'img' + index;
                    var imageContainer = document.getElementById(imageContainerID);
                    if (imageContainer !== null) {
                        if (imageContainer.innerHTML === '') {
                            html5DOMDocument.insert(imagesData[index][2], [imageContainer]);
                        }
                        updateImages();
                    }
                });
            };

            this.onShow = function (index) {
                if (typeof responsivelyLazy !== 'undefined') {
                    responsivelyLazy.run();
                }
            };

        };
    }());
}
;