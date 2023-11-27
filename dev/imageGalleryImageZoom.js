/*
 * Image gallery addon for Bear Framework
 * https://github.com/ivopetkov/image-gallery-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/* global clientPackages, html5DOMDocument */

var ivoPetkov = ivoPetkov || {};
ivoPetkov.bearFrameworkAddons = ivoPetkov.bearFrameworkAddons || {};
ivoPetkov.bearFrameworkAddons.imageGalleryImageZoom = ivoPetkov.bearFrameworkAddons.imageGalleryImageZoom || (function () {

    var touchEvents = ivoPetkov.bearFrameworkAddons.touchEvents;

    var addZoom = function (element, container, onStart, onEnd) {
        var zoomEventTarget = touchEvents.addZoom(element, container);
        var doubleTapEventTarget = touchEvents.addDoubleTap(element);
        var moveEventTarget = touchEvents.addMove(element, container);

        element.style.setProperty('transform', 'scale(var(--touch-events-zoom-scale)) translate(var(--touch-events-move-x),var(--touch-events-move-y))');

        var tempScale = null;
        var lastScale = null;
        var lastPosition = [0, 0];

        var setPosition = function (position, temp, relative) {
            var scale = (tempScale !== null ? tempScale : 1);
            if (relative && lastScale === 1) {
                return;
            }
            var x = ((relative ? lastPosition[0] : 0) + position[0] / scale / lastScale);
            var y = ((relative ? lastPosition[1] : 0) + position[1] / scale / lastScale);

            if (temp) {
            } else {
                lastPosition = [x, y];
            }
            element.style.setProperty('--touch-events-move-x', x + 'px');
            element.style.setProperty('--touch-events-move-y', y + 'px');
        };
        setPosition([0, 0], false, false); // initialize

        var setScale = function (scale, temp, relative) {
            var newScale = relative ? lastScale * scale : scale;
            if (newScale < 1) {
                newScale = 1;
            }
            if (temp) {
                tempScale = scale;
            } else {
                tempScale = null;
                lastScale = newScale;
            }
            element.style.setProperty('--touch-events-zoom-scale', newScale);
            if (!temp && lastScale === 1) {
                setPosition([0, 0], false, false);
            }
        };
        setScale(1, false, false); // initialize

        var addTempAnimation = function () {
            element.style.setProperty('transition', 'transform 0.2s ease-out');
            setTimeout(function () {
                element.style.removeProperty('transition');
            }, 200 + 10);
        };

        // Zoom
        zoomEventTarget.addEventListener('start', function (event) {
            //console.log('zoom start');
            onStart();
        });
        zoomEventTarget.addEventListener('change', function (event) {
            //console.log('zoom change', event.change);
            setScale(event.change, true, true);
        });
        zoomEventTarget.addEventListener('end', function (event) {
            //console.log('zoom end', event.change);
            setScale(event.change, false, true);
            onEnd();
        });

        // Double tap
        doubleTapEventTarget.addEventListener('done', function (event) {
            addTempAnimation();
            setScale(1, false, false);
            setPosition([0, 0], false, false);
            onStart();
            onEnd();
        });

        // Move
        moveEventTarget.addEventListener('start', function (event) {
            //console.log('move start');
        });
        moveEventTarget.addEventListener('change', function (event) {
            //console.log('move change', event.changeX, event.changeY);
            setPosition([event.changeX, event.changeY], true, true);
        });
        moveEventTarget.addEventListener('end', function (event) {
            //console.log('move end', event.changeX, event.changeY);
            setPosition([event.changeX, event.changeY], false, true);
        });

        var api = {};
        api.zoomIn = function (scale) {
            addTempAnimation();
            setScale(typeof scale !== 'undefined' ? scale : 2, false, false);
            onStart();
            onEnd();
        };
        api.zoomOut = function () {
            addTempAnimation();
            setScale(1, false, false);
            setPosition([0, 0], false, false);
            onStart();
            onEnd();
        };
        api.hasZoom = function () {
            return tempScale !== null || lastScale > 1;
        }
        return api;
    };

    var addSwipe = function (element, onChange, onEnd) {
        var swipeEventTarget = touchEvents.addSwipe(element);
        swipeEventTarget.addEventListener('change', onChange);
        swipeEventTarget.addEventListener('end', onEnd);
    };

    return {
        addZoom: addZoom,
        addSwipe: addSwipe
    }
})();