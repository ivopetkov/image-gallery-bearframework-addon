/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/* global clientPackages, html5DOMDocument */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};
ivoPetkov.bearFrameworkAddons.imageGalleryLightbox = ivoPetkov.bearFrameworkAddons.imageGalleryLightbox || (function () {

    var calculateImageWidth = function (width, height) {
        var maxWidth = window.innerWidth - 5 * 2;
        var maxHeight = window.innerHeight - 5 * 2;
        if (height > maxHeight) {
            width = maxHeight / height * width;
        }
        if (width > maxWidth) {
            width = maxWidth;
        }
        return Math.floor(width);
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
                clientPackages.get('-ivopetkov-image-gallery-lightbox-requirements').then(function () {
                    var images = response.result;
                    var imagesCount = images.length;
                    var containerID = 'imggalleryswp' + swiperCounter;
                    var html = '<div id="' + containerID + '" class="swiper-container" style="width:100vw;height:100vh;">';

                    html += '<div class="swiper-wrapper">';
                    for (var i = 0; i < imagesCount; i++) {
                        var image = images[i];
                        html += '<div class="swiper-slide" style="padding:5px;box-sizing:border-box;display:-ms-flexbox;display:-webkit-flex;display:flex;-ms-flex-align:center;-webkit-align-items:center;-webkit-box-align:center;align-items:center;-moz-justify-content:center;-webkit-justify-content:center;justify-content:center;overflow:hidden;">';
                        html += '<div data-max-width="' + image[0] + '" data-max-height="' + image[1] + '" style="width:' + calculateImageWidth(image[0], image[1]) + 'px;font-size:0;line-height:0;"></div>';
                        html += '</div>';
                    }
                    html += '</div>';

                    html += '<div style="z-index:10010001;position:fixed;top:0;left:0;">';
                    html += '<span data-image-gallery-button="next"></span>';
                    html += '<span data-image-gallery-button="previous"></span>';
                    html += '<span data-image-gallery-button="close"></span>';
                    html += '<span data-image-gallery-button="zoomin"></span>';
                    html += '<span data-image-gallery-button="zoomout"></span>';
                    //html += '<span data-image-gallery-button="download"></span>';
                    html += '</div>';

                    html += '</div>';
                    lightbox.open(html, { 'spacing': '0px', showCloseButton: false }).then(function () {
                        var container = document.getElementById(containerID);
                        var slidesContainer = container.firstChild;
                        var buttonsContainer = container.childNodes[1];

                        for (var i = 0; i < imagesCount; i++) {
                            var image = images[i];
                            html5DOMDocument.insert(image[2], [slidesContainer.childNodes[i].firstChild]);
                        }

                        var lastShownSlideIndex = null;
                        var setLastShownSlideIndex = function (index) {
                            lastShownSlideIndex = index;
                        };
                        var imagesZoomAPI = [];

                        var slidesElements = slidesContainer.childNodes;

                        // var getDownloadURL = function (index) {
                        //     return images[index][3] !== null && images[index][3].length > 0 ? images[index][3] : null;
                        // };

                        var getImageContainer = function (index) {
                            return slidesContainer.childNodes[index].firstChild;
                        };

                        var getAvailableZoomScale = function (index) {
                            var imageContainer = getImageContainer(index);
                            return parseInt(imageContainer.getAttribute('data-max-width')) / parseInt(imageContainer.style.width.replace('px', ''));
                        };

                        var updateButtons = null;

                        var loadOriginalImage = function (index) {
                            var imageContainer = getImageContainer(index);
                            var imageElement = imageContainer.querySelector('[data-responsively-lazy]');
                            if (imageElement !== null) {
                                var attributeName = 'data-responsively-lazy-preferred-option';
                                var currentValue = imageElement.getAttribute(attributeName);
                                if (currentValue === null) {
                                    imageElement.setAttribute(attributeName, '999999'); // the max available option
                                    try {
                                        responsivelyLazy.run();
                                    } catch (e) {

                                    }
                                }
                            }
                        };

                        var previousChecks = [];
                        var checkHasZoom = function (index) {
                            if (typeof previousChecks[index] === 'undefined') {
                                previousChecks[index] = null;
                            }
                            var hasZoom = imagesZoomAPI[index].hasZoom();
                            if (hasZoom !== previousChecks[index]) {
                                updateButtons();
                            }
                        };

                        for (var i = 0; i < slidesElements.length; i++) {
                            var slideContainer = slidesElements[i];
                            imagesZoomAPI[i] = ivoPetkov.bearFrameworkAddons.imageGalleryImageZoom.addZoom(slideContainer.firstChild, slideContainer);
                            imagesZoomAPI[i].addEventListener('start', (function (index) {
                                loadOriginalImage(index);
                                setTimeout(function () {
                                    checkHasZoom(index);
                                }, 50);
                            }).bind(null, i));
                            imagesZoomAPI[i].addEventListener('end', (function (index) {
                                checkHasZoom(index);
                            }).bind(null, i));
                            // var preventEvents = ((function (index) {
                            //     return function (e) {
                            //         if (imagesZoomAPI[index].hasZoom()) {
                            //             console.log(index);
                            //             e.stopPropagation();
                            //         }
                            //     }
                            // }).bind(null, i))();
                            // console.log(preventEvents);
                            // console.log(slideContainer);
                            // slideContainer.addEventListener("touchstart", preventEvents, { passive: false });
                            // slideContainer.addEventListener("pointerdown", preventEvents, { passive: false });
                            // slideContainer.addEventListener("mousedown", preventEvents, { passive: false });
                        }

                        var swiperObject = new Swiper('#' + containerID, {
                            direction: 'horizontal',
                            loop: false,
                            keyboardControl: true,
                            mousewheelControl: true
                        });
                        swiperObject.slideTo(index, 0);

                        var nextButton = buttonsContainer.childNodes[0];
                        var previousButton = buttonsContainer.childNodes[1];
                        var closeButton = buttonsContainer.childNodes[2];
                        var zoomInButton = buttonsContainer.childNodes[3];
                        var zoomOutButton = buttonsContainer.childNodes[4];
                        //var downloadButton = buttonsContainer.childNodes[5];

                        updateButtons = function () {
                            var index = lastShownSlideIndex;
                            if (imagesCount > 1) {
                                nextButton.style.display = index + 1 < imagesCount ? 'flex' : 'none';
                                previousButton.style.display = index === 0 ? 'none' : 'flex';
                            }
                            var imageZoomAPI = imagesZoomAPI[index];
                            var showZoomButtons = getAvailableZoomScale(index) > 1;
                            var imageHasZoom = imageZoomAPI.hasZoom();
                            zoomInButton.style.display = showZoomButtons && !imageHasZoom ? 'block' : 'none';
                            zoomOutButton.style.display = showZoomButtons && imageHasZoom ? 'block' : 'none';
                            //var hasDownloadButton = getDownloadURL(index);
                            //downloadButton.style.display = hasDownloadButton ? 'block' : 'none';
                        };

                        nextButton.addEventListener('click', swiperObject.slideNext);
                        previousButton.addEventListener('click', swiperObject.slidePrev);
                        closeButton.addEventListener('click', lightbox.close);
                        zoomInButton.addEventListener('click', function () {
                            imagesZoomAPI[lastShownSlideIndex].zoomIn(getAvailableZoomScale(lastShownSlideIndex));
                            updateButtons();
                        });
                        zoomOutButton.addEventListener('click', function () {
                            imagesZoomAPI[lastShownSlideIndex].zoomOut();
                            updateButtons();
                        });
                        // downloadButton.addEventListener('click', function () {
                        //     var downloadURL = getDownloadURL(lastShownSlideIndex);
                        //     if (downloadURL !== null) {
                        //         window.open(downloadURL, '_self');
                        //     }
                        // });

                        swiperObject.on('slideChangeStart', function (swiper) {
                            if (lastShownSlideIndex !== null) {
                                imagesZoomAPI[lastShownSlideIndex].zoomOut(); // zoom out the last one
                            }
                            setLastShownSlideIndex(swiper.activeIndex);
                            updateButtons();
                        });
                        setLastShownSlideIndex(index);
                        updateButtons();
                    });
                });
            }
        };

        if (typeof cachedDataResponses[serverData] !== 'undefined') {
            showResponse(cachedDataResponses[serverData]);
        } else {
            clientPackages.get('serverRequests').then(function (serverRequests) {
                serverRequests.send('-ivopetkov-image-gallery-get-images', { 'serverData': serverData }).then(function (responseText) {
                    cachedDataResponses[serverData] = responseText;
                    showResponse(responseText);
                });
            });
        }

    };

    return {
        'open': open
    };
}());