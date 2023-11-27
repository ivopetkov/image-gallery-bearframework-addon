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

    var padding = 5;

    var calculateImageWidth = function (width, height) {
        var maxWidth = window.innerWidth - padding * 2;
        var maxHeight = window.innerHeight - padding * 2;
        if (height > maxHeight) {
            width = maxHeight / height * width;
        }
        if (width > maxWidth) {
            width = maxWidth;
        }
        return Math.floor(width);
    };

    var cachedDataResponses = {};

    var lightboxesCounter = 0;

    var open = function (lightbox, serverData, indexToShow) {
        lightboxesCounter++;

        var showResponse = function (responseText) {
            try {
                var response = JSON.parse(responseText);
            } catch (e) {
                var response = {};
            }
            if (typeof response.status !== 'undefined' && response.status === '1') {
                clientPackages.get('-ivopetkov-image-gallery-lightbox-requirements').then(function (imageGalleryImageZoom) {
                    if (!lightbox.isActive()) {
                        return;
                    }
                    var images = response.result;
                    var imagesCount = images.length;
                    var containerID = 'imggalleryswp' + lightboxesCounter;
                    var html = '<div id="' + containerID + '" style="width:100%;height:100%;position:fixed;top:0;left:0;user-select:none;">';

                    html += '<div style="position:absolute;top:0;left:0;width:100%;height:100%;">';
                    for (var i = 0; i < imagesCount; i++) {
                        var image = images[i];
                        html += '<div style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;padding:' + padding + 'px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;overflow:hidden;">';
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

                        var currentSlideIndex = indexToShow;
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

                        var addSlideTransition = function (index) {
                            var slideContainer = slidesElements[index];
                            slideContainer.style.setProperty('transition', 'transform 0.3s ease-out');
                        };

                        var addSlideTemporaryTransition = function (index) {
                            addSlideTransition(index);
                            setTimeout(function () {
                                var slideContainer = slidesElements[index];
                                slideContainer.style.removeProperty('transition');
                            }, 300 + 16);
                        };

                        var updateSlidePosition = function (index) {
                            var value = '0px';
                            if (index < currentSlideIndex) {
                                value = '-100vw';
                            } else if (index > currentSlideIndex) {
                                value = '100vw';
                            }
                            var slideContainer = slidesElements[index];
                            slideContainer.style.setProperty('--igi-position', value);
                        };

                        var setSlideSwipe = function (index, value, animate) {
                            if (typeof images[index] === 'undefined') {
                                return;
                            }
                            if (animate) {
                                addSlideTemporaryTransition(index);
                            }
                            var slideContainer = slidesElements[index];
                            slideContainer.style.setProperty('--igi-swipe', value);
                        };

                        var showSlide = function (index) {
                            if (typeof images[index] === 'undefined') {
                                return false;
                            }
                            currentSlideIndex = index;
                            for (var i = 0; i < imagesCount; i++) {
                                updateSlidePosition(i);
                            }
                            setSlideSwipe(index - 1, '0px', true);
                            setSlideSwipe(index, '0px', true);
                            setSlideSwipe(index + 1, '0px', true);
                            updateButtons();
                            return true;
                        };

                        for (var i = 0; i < slidesElements.length; i++) {
                            (function (index) {
                                var slideContainer = slidesElements[index];

                                updateSlidePosition(index);
                                setSlideSwipe(index, '0px', false);
                                slideContainer.style.setProperty('transform', 'translate(calc(var(--igi-position) + var(--igi-swipe)),0)');
                                slideContainer.style.setProperty('display', 'flex');
                                var imageElement = slideContainer.querySelector('img');
                                if (imageElement !== null) {
                                    imageElement.style.setProperty('pointer-events', 'none'); // prevent drag
                                }

                                imagesZoomAPI[index] = imageGalleryImageZoom.addZoom(
                                    slideContainer.firstChild,
                                    slideContainer,
                                    function () {
                                        loadOriginalImage(index);
                                        setTimeout(function () {
                                            checkHasZoom(index);
                                        }, 50);
                                    },
                                    function () {
                                        checkHasZoom(index);
                                    }
                                );

                                imageGalleryImageZoom.addSwipe(
                                    slideContainer,
                                    function (e) {
                                        if (imagesZoomAPI[index].hasZoom()) {
                                            return;
                                        }
                                        if (imagesCount <= 1) {
                                            return;
                                        }
                                        var changeValue = e.changeX + 'px';
                                        setSlideSwipe(index - 1, changeValue, false);
                                        setSlideSwipe(index, changeValue, false);
                                        setSlideSwipe(index + 1, changeValue, false);
                                    },
                                    function (e) {
                                        if (imagesZoomAPI[index].hasZoom()) {
                                            return;
                                        }
                                        if (imagesCount <= 1) {
                                            return;
                                        }
                                        var changeX = e.changeX;
                                        if (Math.abs(changeX) > 40) { // swipe
                                            if (showSlide(changeX > 0 ? currentSlideIndex - 1 : currentSlideIndex + 1)) {
                                                return;
                                            }
                                        }
                                        setSlideSwipe(index - 1, '0px', true);
                                        setSlideSwipe(index, '0px', true);
                                        setSlideSwipe(index + 1, '0px', true);
                                    }
                                );

                            })(i);
                        }

                        var nextButton = buttonsContainer.childNodes[0];
                        var previousButton = buttonsContainer.childNodes[1];
                        var closeButton = buttonsContainer.childNodes[2];
                        var zoomInButton = buttonsContainer.childNodes[3];
                        var zoomOutButton = buttonsContainer.childNodes[4];
                        //var downloadButton = buttonsContainer.childNodes[5];

                        updateButtons = function () {
                            if (imagesCount > 1) {
                                nextButton.style.display = currentSlideIndex + 1 < imagesCount ? 'flex' : 'none';
                                previousButton.style.display = currentSlideIndex === 0 ? 'none' : 'flex';
                            }
                            var imageZoomAPI = imagesZoomAPI[currentSlideIndex];
                            var showZoomButtons = getAvailableZoomScale(currentSlideIndex) > 1;
                            var imageHasZoom = imageZoomAPI.hasZoom();
                            zoomInButton.style.display = showZoomButtons && !imageHasZoom ? 'block' : 'none';
                            zoomOutButton.style.display = imageHasZoom ? 'block' : 'none';
                            //var hasDownloadButton = getDownloadURL(currentSlideIndex);
                            //downloadButton.style.display = hasDownloadButton ? 'block' : 'none';
                        };

                        var showSiblingSlide = function (newIndex) {
                            if (imagesZoomAPI[currentSlideIndex].hasZoom()) {
                                imagesZoomAPI[currentSlideIndex].zoomOut();
                            }
                            showSlide(newIndex);
                        };
                        nextButton.addEventListener('click', function () {
                            showSiblingSlide(currentSlideIndex + 1);
                        });
                        previousButton.addEventListener('click', function () {
                            showSiblingSlide(currentSlideIndex - 1);
                        });
                        closeButton.addEventListener('click', lightbox.close);
                        zoomInButton.addEventListener('click', function () {
                            imagesZoomAPI[currentSlideIndex].zoomIn(getAvailableZoomScale(currentSlideIndex));
                            updateButtons();
                        });
                        zoomOutButton.addEventListener('click', function () {
                            imagesZoomAPI[currentSlideIndex].zoomOut();
                            updateButtons();
                        });
                        // downloadButton.addEventListener('click', function () {
                        //     var downloadURL = getDownloadURL(currentSlideIndex);
                        //     if (downloadURL !== null) {
                        //         window.open(downloadURL, '_self');
                        //     }
                        // });

                        updateButtons();

                        document.body.addEventListener('keydown', function (e) {
                            var container = document.getElementById(containerID);
                            if (container) {
                                var keyCode = e.keyCode;
                                if (keyCode === 39) {
                                    showSiblingSlide(currentSlideIndex + 1);
                                } else if (keyCode === 37) {
                                    showSiblingSlide(currentSlideIndex - 1);
                                }
                            }
                        });

                        window.addEventListener('resize', function () {
                            var container = document.getElementById(containerID);
                            if (container) {
                                var slides = container.firstChild.childNodes;
                                for (var i = 0; i < slides.length; i++) {
                                    var imageContainer = slides[i].firstChild;
                                    imageContainer.style.width = calculateImageWidth(imageContainer.getAttribute('data-max-width'), imageContainer.getAttribute('data-max-height')) + 'px';
                                }
                                updateButtons();
                            }
                        });
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

    // if (window.location.search.indexOf('debugjs') !== -1) {
    //     window.addEventListener('error', function (e) {
    //         alert(e.message);
    //     });
    //     window.addEventListener('unhandledrejection', function (e) {
    //         alert(e.reason);
    //     });
    // }

    return {
        'open': open
    };
}());