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

    var makeEventTarget = () => {
        if (EventTarget !== undefined && EventTarget.constructor !== undefined) {
            try {
                return new EventTarget();
            } catch (e) {

            }
        }
        // Needed for iOS
        var listeners = [];
        return {
            addEventListener: (type, callback) => {
                if (!(type in listeners)) {
                    listeners[type] = [];
                }
                listeners[type].push(callback);
            },
            removeEventListener: (type, callback) => {
                if (!(type in listeners)) {
                    return;
                }
                var stack = listeners[type];
                for (var i = 0, l = stack.length; i < l; i++) {
                    if (stack[i] === callback) {
                        stack.splice(i, 1);
                        return;
                    }
                }
            },
            dispatchEvent: (event) => {
                if (!(event.type in listeners)) {
                    return true;
                }
                var stack = listeners[event.type].slice();
                for (var i = 0, l = stack.length; i < l; i++) {
                    stack[i].call(this, event);
                }
                return !event.defaultPrevented;
            }
        }
    };

    var touchEvents = (function () {

        var getEventsPointsDistance = function (event1, event2) {
            var event1X = event1.clientX;
            var event1Y = event1.clientY;
            var event2X = event2.clientX;
            var event2Y = event2.clientY;
            return Math.round(Math.sqrt(Math.pow(Math.abs(event2X - event1X), 2) + Math.pow(Math.abs(event2Y - event1Y), 2)));
        };

        var preventDefaultEvents = function (element) {
            element.style.setProperty('user-select', 'none');
            element.addEventListener("touchstart", function (e) {
                e.preventDefault();
            }, { passive: false });
        };

        var getZoomEventTarget = function (element, container) {
            if (typeof container === 'undefined') {
                container = element;
            }
            preventDefaultEvents(container);

            var eventTarget = makeEventTarget();

            var pointers = [];

            var startDistance = null;
            var lastChange = 1;

            var start = function () {
                for (var i = 0; i < pointers.length; i++) { // for new starts after one finder was up
                    var pointer = pointers[i];
                    pointer.downEvent = pointer.moveEvent;
                }

                startDistance = getEventsPointsDistance(pointers[0].downEvent, pointers[1].downEvent);
                var event = new Event('start');
                eventTarget.dispatchEvent(event);
            };

            var end = function () {
                var event = new Event('end');
                event.change = lastChange;
                eventTarget.dispatchEvent(event);
            };

            var pointerDownHandler = function (event) {
                for (var i in pointers) {
                    if (pointers[i].downEvent.pointerId === event.pointerId) {
                        return;
                    }
                }
                pointers.push({
                    downEvent: event,
                    moveEvent: event
                });
                if (pointers.length === 2) {
                    start();
                }
            };

            var pointerMoveHandler = function (event) {
                if (pointers.length === 2) {
                    for (var i = 0; i < 2; i++) {
                        var pointer = pointers[i];
                        var downEvent = pointer.downEvent;
                        if (downEvent.pointerId === event.pointerId) {
                            pointer.moveEvent = event;
                        }
                    }
                    var currentDistance = getEventsPointsDistance(pointers[0].moveEvent, pointers[1].moveEvent);
                    lastChange = currentDistance / startDistance;
                    var event = new Event('change');
                    event.change = lastChange;
                    eventTarget.dispatchEvent(event);
                }
            };

            var pointerUpHandler = function (event) {
                for (var i in pointers) {
                    var pointer = pointers[i];
                    if (pointer.downEvent.pointerId === event.pointerId) {
                        pointers.splice(i, 1);
                        if (pointers.length === 1) {
                            end();
                        }
                    }
                }
            };

            element.addEventListener('pointerdown', pointerDownHandler);
            container.addEventListener('pointermove', pointerMoveHandler);
            container.addEventListener('pointerup', pointerUpHandler);
            container.addEventListener('pointercancel', pointerUpHandler);
            container.addEventListener('pointerout', pointerUpHandler);
            container.addEventListener('pointerleave', pointerUpHandler);

            return eventTarget;
        };

        var getMoveEventTarget = function (element, container) {
            if (typeof container === 'undefined') {
                container = element;
            }
            preventDefaultEvents(container);

            var eventTarget = makeEventTarget();

            var pointers = [];

            var startPosition = [0, 0];
            var lastChange = [0, 0];

            var getMovePointPosition = function (useDownEvent) {
                var pointersLength = pointers.length;
                if (pointersLength === 1) {
                    var event = useDownEvent ? pointers[0].downEvent : pointers[0].moveEvent;
                    return [event.clientX, event.clientY];
                } else if (pointersLength > 1) {
                    var event1 = useDownEvent ? pointers[0].downEvent : pointers[0].moveEvent;
                    var event2 = useDownEvent ? pointers[1].downEvent : pointers[1].moveEvent;
                    return [event1.clientX - (event1.clientX - event2.clientX) / 2, event1.clientY - (event1.clientY - event2.clientY) / 2];
                }
                return [0, 0];
            };

            var start = function () {
                startPosition = getMovePointPosition(true);

                var event = new Event('start');
                eventTarget.dispatchEvent(event);
            };

            var end = function () {
                var event = new Event('end');
                event.changeX = lastChange[0];
                event.changeY = lastChange[1];
                eventTarget.dispatchEvent(event);

                lastChange = [0, 0];

                for (var i = 0; i < pointers.length; i++) {
                    var pointer = pointers[i];
                    pointer.downEvent = pointer.moveEvent;
                }
            };

            var pointerDownHandler = function (event) {
                for (var i in pointers) {
                    if (pointers[i].downEvent.pointerId === event.pointerId) {
                        return;
                    }
                }
                pointers.push({
                    downEvent: event,
                    moveEvent: event
                });
                if (pointers.length > 1) {
                    end();
                }
                start();
            };

            var pointerMoveHandler = function (event) {
                for (var i = 0; i < pointers.length; i++) {
                    var pointer = pointers[i];
                    var downEvent = pointer.downEvent;
                    if (downEvent.pointerId === event.pointerId) {
                        pointer.moveEvent = event;
                    }
                }
                if (pointers.length === 0) {
                    return;
                }
                var movePosition = getMovePointPosition(false);
                lastChange = [Math.round(movePosition[0] - startPosition[0]), Math.round(movePosition[1] - startPosition[1])];
                var event = new Event('change');
                event.changeX = lastChange[0];
                event.changeY = lastChange[1];
                eventTarget.dispatchEvent(event);
            };

            var pointerUpHandler = function (event) {
                for (var i in pointers) {
                    var pointer = pointers[i];
                    if (pointer.downEvent.pointerId === event.pointerId) {
                        pointers.splice(i, 1);
                        end();
                        if (pointers.length > 0) {
                            start();
                        }
                    }
                }
            };

            element.addEventListener('pointerdown', pointerDownHandler);
            container.addEventListener('pointermove', pointerMoveHandler);
            container.addEventListener('pointerup', pointerUpHandler);
            container.addEventListener('pointercancel', pointerUpHandler);
            container.addEventListener('pointerout', pointerUpHandler);
            container.addEventListener('pointerleave', pointerUpHandler);

            return eventTarget;
        };

        var getDoubleTapEventTarget = function (element) {
            preventDefaultEvents(element);

            var eventTarget = makeEventTarget();

            var pointers = [];
            var lastEvents = [];

            var pointerDownHandler = function (event) {
                for (var i in pointers) {
                    if (pointers[i].pointerId === event.pointerId) {
                        return;
                    }
                }
                pointers.push(event);
                if (pointers.length === 1) {
                    lastEvents.push([0, (new Date()).getTime()]); // down + date
                }
            };

            var pointerUpHandler = function (event) {
                for (var i in pointers) {
                    var pointer = pointers[i];
                    if (pointer.pointerId === event.pointerId) {
                        pointers.splice(i, 1);
                        if (pointers.length === 0) {
                            lastEvents.push([1, (new Date()).getTime()]);
                            var startIndex = lastEvents.length - 4;
                            if (startIndex < 0) {
                                startIndex = 0;
                            }
                            lastEvents = lastEvents.slice(startIndex);
                            if (lastEvents.length === 4) {
                                var eventsSum = 0; // expect: 1*0 + 2*1 + 3*0 + 4*1 === 6
                                for (var j = 1; j <= lastEvents.length; j++) {
                                    eventsSum += j * lastEvents[j - 1][0];
                                }
                                if (eventsSum === 6 && lastEvents[3][1] - lastEvents[0][1] < 500) { // check sum and time between the last and the first event
                                    var event = new Event('done');
                                    eventTarget.dispatchEvent(event);
                                };
                            }
                        }
                    }
                }
            };

            element.addEventListener('pointerdown', pointerDownHandler);
            element.addEventListener('pointerup', pointerUpHandler);

            return eventTarget;
        };

        var getSwipeEventTarget = function (element, container) {
            if (typeof container === 'undefined') {
                container = element;
            }
            preventDefaultEvents(container);

            var eventTarget = makeEventTarget();

            var pointers = []; // will be only one

            var lastChange = [0, 0, null];

            var pointerDownHandler = function (event) {
                if (pointers.length === 1) {
                    return;
                }
                for (var i in pointers) {
                    if (pointers[i].downEvent.pointerId === event.pointerId) {
                        return;
                    }
                }
                pointers.push({
                    downEvent: event,
                    moveEvent: event
                });
                var event = new Event('start');
                eventTarget.dispatchEvent(event);
            };

            var pointerMoveHandler = function (event) {
                for (var i = 0; i < pointers.length; i++) {
                    var pointer = pointers[i];
                    var downEvent = pointer.downEvent;
                    if (downEvent.pointerId === event.pointerId) {
                        pointer.moveEvent = event;
                    }
                }
                if (pointers.length === 0) {
                    return;
                }
                var pointer1 = pointers[0];
                var changeX = pointer1.moveEvent.clientX - pointer1.downEvent.clientX;
                var changeY = pointer1.moveEvent.clientY - pointer1.downEvent.clientY;
                var direction = null;
                if (Math.abs(changeX) > Math.abs(changeY)) {
                    direction = changeX < 0 ? 'left' : 'right';
                } else {
                    direction = changeY < 0 ? 'up' : 'down';
                }
                var event = new Event('change');
                event.changeX = changeX;
                event.changeY = changeY;
                event.direction = direction;
                eventTarget.dispatchEvent(event);
                lastChange = [changeX, changeY, direction];
            };

            var pointerUpHandler = function (event) {
                for (var i in pointers) {
                    var pointer = pointers[i];
                    if (pointer.downEvent.pointerId === event.pointerId) {
                        pointers.splice(i, 1);
                        var event = new Event('end');
                        event.changeX = lastChange[0];
                        event.changeY = lastChange[1];
                        event.direction = lastChange[2];
                        eventTarget.dispatchEvent(event);
                        lastChange = [0, 0, null];
                    }
                }
            };

            element.addEventListener('pointerdown', pointerDownHandler);
            container.addEventListener('pointermove', pointerMoveHandler);
            container.addEventListener('pointerup', pointerUpHandler);
            container.addEventListener('pointercancel', pointerUpHandler);
            container.addEventListener('pointerout', pointerUpHandler);
            container.addEventListener('pointerleave', pointerUpHandler);

            return eventTarget;
        };

        return {
            getZoomEventTarget: getZoomEventTarget,
            getMoveEventTarget: getMoveEventTarget,
            getDoubleTapEventTarget: getDoubleTapEventTarget,
            getSwipeEventTarget: getSwipeEventTarget
        }
    })();

    var addZoom = function (element, container) {
        var zoomEventTarget = touchEvents.getZoomEventTarget(element, container);
        var doubleTapEventTarget = touchEvents.getDoubleTapEventTarget(element);
        var moveEventTarget = touchEvents.getMoveEventTarget(element, container);

        var api = makeEventTarget();

        var dispatchStartEvent = function () {
            api.dispatchEvent(new Event('start'));
        };
        var dispatchEndEvent = function () {
            api.dispatchEvent(new Event('end'));
        };

        // var containerRect = container.getBoundingClientRect();
        // var elementRect = element.getBoundingClientRect();

        // var containerHeight = containerRect.height;
        // var originalElementY = elementRect.y - containerRect.y;
        // var originalElementHeight = elementRect.height;

        // var elementOffsetTop = Math.floor(elementRect.y - containerRect.y);
        // var elementOffsetBottom = Math.ceil(containerHeight - elementRect.height - elementOffsetTop);

        element.style.setProperty('transform', 'scale(var(--touch-events-zoom-scale)) translate(var(--touch-events-move-x),var(--touch-events-move-y))');

        var tempScale = null;
        var lastScale = null;
        var lastPosition = [0, 0];

        var setPosition = function (position, temp, relative) {
            var scale = (tempScale !== null ? tempScale : 1);
            //var currentScale = scale * lastScale;
            if (relative && lastScale === 1) {
                return;
            }
            var x = ((relative ? lastPosition[0] : 0) + position[0] / scale / lastScale);
            var y = ((relative ? lastPosition[1] : 0) + position[1] / scale / lastScale);

            if (temp) {
            } else {
                lastPosition = [x, y];
            }
            // var currentHeight = originalElementHeight * currentScale;
            // var verticalOffsetChange = (currentHeight - originalElementHeight) / 2;
            // var currentOffsetTop = y * currentScale + elementOffsetTop - verticalOffsetChange;
            // var currentOffsetBottom = containerHeight - (currentHeight + currentOffsetTop);
            // if (currentHeight > containerHeight) {
            //     if (currentOffsetTop > 0) {
            //         //y = -(elementOffsetTop - verticalOffsetChange) / currentScale; // OK 
            //     }
            //     if (currentOffsetBottom > 0) {
            //         // HOW
            //     }
            // }
            //console.log(currentOffsetTop, currentOffsetBottom);
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
            dispatchStartEvent();
        });
        zoomEventTarget.addEventListener('change', function (event) {
            //console.log('zoom change', event.change);
            setScale(event.change, true, true);
        });
        zoomEventTarget.addEventListener('end', function (event) {
            //console.log('zoom end', event.change);
            setScale(event.change, false, true);
            dispatchEndEvent();
        });

        // Double tap
        doubleTapEventTarget.addEventListener('done', function (event) {
            addTempAnimation();
            setScale(1, false, false);
            setPosition([0, 0], false, false);
            dispatchStartEvent();
            dispatchEndEvent();
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

        api.zoomIn = function (scale) {
            addTempAnimation();
            setScale(typeof scale !== 'undefined' ? scale : 2, false, false);
            dispatchStartEvent();
            dispatchEndEvent();
        };
        api.zoomOut = function () {
            addTempAnimation();
            setScale(1, false, false);
            setPosition([0, 0], false, false);
            dispatchStartEvent();
            dispatchEndEvent();
        };
        api.hasZoom = function () {
            return tempScale !== null || lastScale > 1;
        }
        return api;
    };

    var addSwipe = function (element, container) {
        var swipeEventTarget = touchEvents.getSwipeEventTarget(element, container);

        var api = makeEventTarget();

        swipeEventTarget.addEventListener('start', function (e) {
            //console.log('start');
            var event = new Event('start');
            api.dispatchEvent(event);
        });
        swipeEventTarget.addEventListener('change', function (e) {
            //console.log('change', e.direction, e.changeX, e.changeY);
            var event = new Event('change');
            event.direction = e.direction;
            event.changeX = e.changeX;
            event.changeY = e.changeY;
            api.dispatchEvent(event);
        });
        swipeEventTarget.addEventListener('end', function (e) {
            //console.log('end', e.direction, e.changeX, e.changeY);
            var event = new Event('end');
            event.direction = e.direction;
            event.changeX = e.changeX;
            event.changeY = e.changeY;
            api.dispatchEvent(event);
        });

        return api;
    };

    return {
        addZoom: addZoom,
        addSwipe: addSwipe
    }
})();