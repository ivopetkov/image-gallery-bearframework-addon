/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};
ivoPetkov.bearFrameworkAddons.imageGalleryLightbox = ivoPetkov.bearFrameworkAddons.imageGalleryLightbox || (function () {

    var calculateImageWidth = function (width, height) {
        var maxWidth = window.innerWidth - 15 * 2;
        var maxHeight = window.innerHeight - 15 * 2;
        if (height > maxHeight) {
            width = maxHeight / height * width;
        }
        if (width > maxWidth) {
            width = maxWidth;
        }
        return width;
    };

    var cachedDataResponses = {};

    var swiperCounter = 0;

    var updateSizesAdded = false;
    var updateSizes = function () {
        var containerID = 'imggalleryswp' + swiperCounter;
        var container = document.getElementById(containerID);
        if (container) {
            var slides = container.firstChild.childNodes;
            for (var i = 0; i < slides.length; i++) {
                var imageContainer = slides[i].firstChild;
                imageContainer.style.width = calculateImageWidth(imageContainer.getAttribute('data-max-width'), imageContainer.getAttribute('data-max-height')) + 'px';
            }
        }
    };

    var open = function (lightbox, serverData, index) {
        if (!updateSizesAdded) {
            updateSizesAdded = true;
            window.addEventListener('resize', updateSizes);
        }

        var showResponse = function (responseText) {
            try {
                var response = JSON.parse(responseText);
            } catch (e) {
                var response = {};
            }
            if (typeof response.status !== 'undefined' && response.status === '1') {
                clientShortcuts.get('-ivopetkov-image-gallery-swiper').then(function () {
                    var images = response.result;
                    var imagesCount = images.length;
                    var containerID = 'imggalleryswp' + swiperCounter;
                    var html = '<div id="' + containerID + '" class="swiper-container" style="width:100vw;height:100vh;">';

                    html += '<div class="swiper-wrapper">';
                    for (var i = 0; i < imagesCount; i++) {
                        var image = images[i];
                        html += '<div class="swiper-slide" style="padding:15px;box-sizing:border-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-ms-flex-align:center;-webkit-align-items:center;-webkit-box-align:center;align-items:center;-moz-justify-content:center;-webkit-justify-content:center;justify-content:center;">';
                        html += '<div data-max-width="' + image[0] + '" data-max-height="' + image[1] + '" style="width:' + calculateImageWidth(image[0], image[1]) + 'px;"></div>';
                        html += '</div>';
                    }
                    html += '</div>';

                    html += '<div style="z-index:10010001;position:fixed;top:0;left:0;">';
                    var buttonStyle = 'display:none;width:42px;height:42px;position:fixed;top:calc((100% - 42px)/2);cursor:pointer;color:rgba(255,255,255,0.8);font-size:40px;line-height:40px;padding-left:9px;box-sizing:border-box;';
                    html += '<span style="right:0;' + buttonStyle + '">&#10151;</span>';
                    html += '<span style="left:0;' + buttonStyle + 'transform:rotate(180deg);">&#10151;</span>';
                    html += '</div>';

                    html += '</div>';
                    lightbox.open(html, {'spacing': '0px'});

                    var container = document.getElementById(containerID);

                    for (var i = 0; i < imagesCount; i++) {
                        var image = images[i];
                        html5DOMDocument.insert(image[2], [container.firstChild.childNodes[i].firstChild]);
                    }

                    var swiperObject = new Swiper('#' + containerID, {
                        direction: 'horizontal',
                        loop: false,
                        keyboardControl: true,
                        mousewheelControl: true
                    });
                    swiperObject.slideTo(index, 0);

                    var nextButton = container.childNodes[1].childNodes[0];
                    nextButton.addEventListener('click', swiperObject.slideNext);
                    var previousButton = container.childNodes[1].childNodes[1];
                    previousButton.addEventListener('click', swiperObject.slidePrev);

                    var updateButtons = function (index) {
                        if (imagesCount < 2) {
                            return;
                        }
                        nextButton.style.display = index + 1 < imagesCount ? 'block' : 'none';
                        previousButton.style.display = index === 0 ? 'none' : 'block';
                    };

                    swiperObject.on('slideChangeStart', function (swiper) {
                        updateButtons(swiper.activeIndex);
                    });
//                    swiperObject.on('slideChangeEnd', function (swiper) {
//                        
//                    });
                    updateButtons(index);
                });
            }
        };

        if (typeof cachedDataResponses[serverData] !== 'undefined') {
            showResponse(cachedDataResponses[serverData]);
        } else {
            clientShortcuts.get('serverRequests').then(function (serverRequests) {
                serverRequests.send('-ivopetkov-image-gallery-get-images', {'serverData': serverData}).then(function (responseText) {
                    cachedDataResponses[serverData] = responseText;
                    showResponse(responseText);
                });
            });
        }

    };

//    var onShow = function (index) {
//        if (typeof responsivelyLazy !== 'undefined') {
//            responsivelyLazy.run();
//        }
//    };

    return {
        'open': open
    };
}());