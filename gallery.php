<?php
    /* cgallery 2.0 beta 1
    *
    * a minimal gallery script by clangen. works in most major browsers.
    * just toss this php file in a directory with images and it will
    * automatically create thumbnails and generate a page layout.
    *
    * uses jquery and spin.js.
    */
    header("Content-Type: text/html; charset=utf-8");

    function getOutputPath() {
        return (getcwd() . "/.thumbs/");
    }

    function getOutputFilenameFor($filename) {
        return getOutputPath() . end(explode("/", $filename));
    }

    function getImageList() {
        $dir = opendir(".");
        $images = array();
        while (($file = readdir($dir)) !== false) {
            $tail = strtolower(substr($file, strlen($file) - 3, 3));

            if (is_file($file)) {
                if (strcmp($tail, "jpg") == 0
                    || strcmp($tail, "gif") == 0
                    || strcmp($tail, "png") == 0)
                {
                    array_push($images, $file);
                }
            }
        }

        sort($images);
        return $images;
    }

    function createImage($filename, $format) {
        if ($format == "png") {
            return imagecreatefrompng($filename);
        }
        else if ($format == "jpg") {
            return imagecreatefromjpeg($filename);
        }
        else if ($format == "gif") {
            return imagecreatefromgif($filename);
        }

        return null;
    }

    function generateThumbFor($filename, $format) {
        $maxSize = 80;

        $imageOrig = createImage($filename, $format);
        $origWidth = imagesx($imageOrig);
        $origHeight = imagesy($imageOrig);
        $ratio = $origWidth / $origHeight;

        $imageWidth = floor($maxSize * $ratio);
        $imageHeight = floor($maxSize);

        $thumb = imagecreatetruecolor($imageWidth, $imageHeight);

        $result = imagecopyresampled(
          $thumb, $imageOrig, 0, 0, 0, 0,
          $imageWidth, $imageHeight, $origWidth, $origHeight);

        if ($result) {
            imagepng($thumb, getOutputFilenameFor($filename));
        }

        imagedestroy($imageOrig);
        imagedestroy($thumb);
    }

    function generateThumbs($imageList) {
        $imagePath = getcwd() . "/";
        $path = getOutputPath();

        @mkdir($path);

        foreach ($imageList as $current) {
            if (($current === ".") || ($current === "..")) {
                continue;
            }

            $current = $imagePath . $current;

            if (file_exists(getOutputFilenameFor($current))) {
                continue;
            }

            $extension = strtolower(end(explode(".", $current)));
            if (($extension == "jpg") || ($extension == "png") || ($extension == "gif")) {
                generateThumbFor($current, $extension);
            }
        }
    }

    $imageList = getImageList();
    generateThumbs($imageList);
?>
<html>
<head>
<style>
    html {
        -webkit-touch-callout: none;
        -webkit-user-select: none;
        -khtml-user-select: none;
        -moz-user-select: moz-none;
        -ms-user-select: none;
        user-select: none;
    }

    ::-moz-selection {
        background: transparent;
    }

    body {
        background-color: #404040;
    }

    .header {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 25px;
        text-align: center;
    }

    ul, li {
        margin: 0;
        padding: 0;
        list-style: none;
        list-style-type: none;
    }

    .strip {
        white-space: nowrap;
        overflow: visible;
        text-align: center;
        margin: 6px;
    }

    .strip img {
        border: 2px solid black;
        margin-right: 6px;
        cursor: pointer;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-shadow: 0 0 4px #111
    }

    .strip img:hover {
        border-color: #777;
    }

    .strip img.active {
        border: 2px solid white;
    }

    .footer {
        -webkit-overflow-scrolling: touch;
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 96px;
        overflow-x: auto;
        overflow-y: hidden;
        background-color: #222;
        border-top: 1px solid black;
        box-shadow: 0 0 4px #111;
    }

    .middle {
        position: absolute;
        left: 0;
        right: 0;
        top: 25px;
        bottom: 96px;
    }

    .color-picker {
        display: inline-block;
        padding-top: 6px;
    }

    .color-button {
        display: inline-block;
        width: 15px;
        height: 15px;
        background-color: #444;
        border: 1px solid #ccc;
        cursor: pointer;
        box-shadow: 0 0 4px #111
    }

    .color-button:hover {
        border-color: #666;
    }

    .pseudo-button {
      position: absolute;
      top: 0;
      bottom: 0;
      /*cursor: pointer;*/
    }

    .pseudo-button.prev {
      left: 0;
      right: 50%;
    }

    .pseudo-button.next {
      right: 0;
      left: 50%;
    }

    .button {
        font-family: monospace;
        position: absolute;
        top: 50%;
        margin-top: -64px; /* height/2 */
        height: 128px;
        width: 40px;
        background-color: #222;
        cursor: pointer;
        color: #ccc;
        font-weight: bold;
        font-size: 18px;
        text-align: center;
        line-height: 128px;
        box-shadow: 0px 0px 4px #222;
    }

    /*.pseudo-button:hover .button,*/
    .button:hover {
        background-color: #2a2a2a;
    }

    .button.prev {
        left: 0;
    }

    .button.next {
        right: 0;
    }

    .center {
        position: absolute;
        pointer-events: none;
        left: 40px;
        right: 40px;
        bottom: 0;
        top: 0;
    }

    .spinner-container {
        display: none;
        position: absolute;
        width: 64px;
        height: 64px;
        top: 50%;
        left: 50%;
        margin-top: -32px; /* height / 2 */
        margin-left: -32px; /* width / 2 */
    }

    .loading .spinner-container {
        display: block;
    }

    .current-image-container {
        position: absolute;
        top: 4px;
        bottom: 8px;
        left: 8px;
        right: 8px;
        text-align: center;
    }

    .current-image-container .current-image {
        max-width: 100%;
        max-height: 100%;
        height: auto;
        width: auto;
        border: 4px solid black;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        pointer-events: all;
        cursor: pointer;
    }

    .loading .current-image {
        display: none;
    }

    ::-webkit-scrollbar {
        height: 10px;
        width: 12px;
        background: transparent;
        margin-bottom: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #666;
        -webkit-border-radius: 0;
        border: 1px solid #111;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #bbb;
    }

    ::-webkit-scrollbar-corner {
        background: #000;
    }

</style>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/spin.js/1.2.7/spin.min.js"></script>

<script>
    /* https://github.com/brandonaaron/jquery-getscrollbarwidth/blob/master/jquery.getscrollbarwidth.js */
    (function($) {
        $.browser = {
            msie: navigator.appName == 'Microsoft Internet Explorer',
            chrome: /Chrome/.test(navigator.userAgent),
            firefox: /Firefox/.test(navigator.userAgent)
        };

        var scrollbarWidth = 0;
        $.getScrollbarWidth = function() {
            if (!scrollbarWidth) {
                if ( $.browser.msie ) {
                    var $textarea1 = $('<textarea cols="10" rows="2"></textarea>')
                        .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body'),
                    $textarea2 = $('<textarea cols="10" rows="2" style="overflow: hidden;"></textarea>')
                        .css({ position: 'absolute', top: -1000, left: -1000 }).appendTo('body');
                    scrollbarWidth = $textarea1.width() - $textarea2.width();
                    $textarea1.add($textarea2).remove();
                }
                else {
                    var $div = $('<div />')
                        .css({ width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: -1000 })
                        .prependTo('body').append('<div />').find('div')
                        .css({ width: '100%', height: 200 });
                    scrollbarWidth = 100 - $div.width();
                    $div.parent().remove();
                }
            }
            return scrollbarWidth;
        };
    })(jQuery);
</script>

<script>
    var BASE_STRIP_HEIGHT = 94;

    var THUMB_TEMPLATE = '<img data-large="{{large}}" src="{{thumb}}">';

    var SPINNER_OPTIONS = {
      lines: 12, length: 7, width: 4, radius: 10, color: '#ffffff',
      speed: 1, trail: 60, shadow: true
    };

    var IMAGES = [
        <?php
            global $imageList; /* generated above */
            foreach ($imageList as $filename) {
              printf('"' . $filename . '",' . "\n");
            }
        ?>
    ];

    var SCROLLBAR_HEIGHT_FUDGE = 0;

    $(document).ready(function() {
        var $body = $("body");
        var $doc = $(document);
        var $win = $(window);
        var $imageContainer = $(".current-image-container");
        var $image = $imageContainer.find(".current-image");
        var $middle = $(".middle");
        var $footer = $(".footer");
        var $strip = $(".strip");
        var spinnerContainer = $(".spinner-container")[0];
        var spinner = new Spinner(SPINNER_OPTIONS);
        var scrollbarSize = $.getScrollbarWidth();
        var hasScrollbar = false;
        var hasVerticalThumbs = false;
        var loadedThumbnails = 0;
        var currentImage, lastHash;
        var hashPollInterval;
        var embedded = (window.parent !== window);

        if (embedded) {
            $(window).on('message', function(event) {
              event = event.originalEvent || event;
              var data = event.data;
              if (data && data.message === 'changeHash') {
                if (data.options.hash !== getHashFromUrl().substring(1)) {
                  render(parseHash(data.options.hash));
                }
              }
            });
        }

        function notifyHashChanged(options) {
            if (window.parent && window.parent.postMessage) {
                var hash = generateHash(options).substring(1);
                var msg = { message: 'hashChanged', options: { hash: hash } };
                window.parent.postMessage(msg, "*");
            }
        }

        function getHashFromUrl() {
            /* some browsers decode the hash, we don't want that */
            return "#" + (window.location.href.split("#")[1] || "");
        }

        function parseHash(hash) {
            var result = { };

            hash = hash || getHashFromUrl();

            if (hash) {
                if (hash.charAt(0) === '#') {
                  hash = hash.substring(1);
                }

                var parts = hash.split("+");
                for (var i = 0; i < parts.length; i++) {
                    var keyValue = parts[i].split(":");
                    if (keyValue.length === 1) {
                        result.i = decodeURIComponent(keyValue[0]);
                    }
                    if (keyValue.length === 2) {
                        result[keyValue[0]] = decodeURIComponent(keyValue[1]);
                    }
                }
            }

            return result;
        }

        function pollHash() {
            if (!embedded && !hashPollInterval) {
                hashPollInterval = setInterval(function() {
                    if (getHashFromUrl() !== lastHash) {
                      render();
                    }
                }, 250);
            }
        }

        function generateHash(options) {
            options = options || { };
            var image = options.image || $image.attr("src");
            var background = options.background || $("body").css("background-color");

            var result =
                "#" +
                encodeURIComponent(image) + "+b:" +
                encodeURIComponent(background.replace(/, /g, ","));

            return result;
        }

        function writeHash(options) {
            if (embedded) {
                return;
            }

            var hash = generateHash(options);

            if (hash !== getHashFromUrl() || hash !== lastHash) {
                window.location.hash = lastHash = hash;
            }
        }

        function findThumbnailForImage(filename) {
            var thumbnails = $(".strip img");

            for (var i = 0; i < thumbnails.length; i++) {
                var $thumbnail = thumbnails.eq(i);
                if ($thumbnail.attr("data-large") === filename) {
                    return $thumbnail;
                }
            }

            return { addClass: function() { /* just to be safe */ } };
        }

        function setImage(filename) {
            if ($image.attr("src") === filename) {
                return false;
            }

            setLoading(true);
            $image.attr("src", "");
            currentImage = filename;

            setTimeout(function() {
                $image.attr("src", filename);
            });

            $(".strip img").removeClass("active");
            findThumbnailForImage(filename).addClass("active");
            updateScrollLeft();

            writeHash({image: filename});
            notifyHashChanged({image: filename});
        }

        function checkForScrollbar() {
            var sb = $strip[0].scrollWidth > $strip[0].clientWidth;

            if (sb !== hasScrollbar) {
                var height = BASE_STRIP_HEIGHT + (sb ? scrollbarSize : 0) + 4;
                $footer.css("height", height + SCROLLBAR_HEIGHT_FUDGE);
                $middle.css("bottom", height + SCROLLBAR_HEIGHT_FUDGE);
                hasScrollbar = sb;
            }
        }

        function thumbnailLoaded(event) {
            checkForScrollbar();

            /* once all the thumbnails have loaded, make sure the selected image
            is scrolled into view */
            if (++loadedThumbnails >= IMAGES.length) {
                updateScrollLeft();
            }
        }

        function updateScrollLeft(options) {
            options = options || { };
            var $active = $strip.find("img.active");

            if ($active.length === 1) {
                var offset = ($footer.width() / 2) - ($active.width() / 2);
                var current = $footer.scrollLeft();
                var pos = (current + $active.eq(0).position().left) - offset;

                $footer.stop(true); /* stops current animations */

                if (options.animate === false) {
                    $footer.scrollLeft(pos);
                }
                else {
                    $footer.animate({scrollLeft: pos}, 250);
                }
            }
        }

        function centerImageVertically() {
            var containerHeight = $imageContainer.height();
            var imageHeight = $image.height();
            var offset = ((containerHeight - imageHeight) / 2 ) - 4;
            $image.css({"margin-top": offset, "visibility": "visible"});
        }

        function dimensionsChanged() {
            centerImageVertically();
            checkForScrollbar();
            updateScrollLeft({animate: false});
        }

        function createThumbnail(filename) {
            return $(THUMB_TEMPLATE
                .replace("{{large}}", filename)
                .replace("{{thumb}}", ".thumbs/" + filename)
            );
        }

        function setLoading(loading) {
            loading = (loading === undefined) ? true : loading;

            if (loading) {
                $body.addClass("loading");

                if (!spinner.spinning) {
                    spinner.spin(spinnerContainer);
                    spinner.spinning = true;
                }
            }
            else {
                $body.removeClass("loading");

                if (spinner.spinning) {
                    spinner.stop();
                    spinner.spinning = false;
                }
            }
        }

        function thumbnailClicked(event) {
            $image.css({"visibility": "hidden"});
            if (!setImage($(event.target).attr("data-large"))) {
              $image.css({"visibility": "visible"}); /* cal: gross but cheap */
            }
        }

        function imageLoaded(event) {
            setLoading(false);
            dimensionsChanged(event);
        }

        function imageClicked(event) {
            window.open($image.attr("src"));
        }

        function colorButtonClicked(event) {
            var bg = $(event.target).attr("data-bg");

            if (bg) {
                $('body').css("background-color", bg);
                writeHash();
                notifyHashChanged();
            }
        }

        function moveBy(offset) {
            var thumbnails = $(".strip img");
            var current = 0;

            for (var i = 0; i < thumbnails.length; i++) {
                if (thumbnails.eq(i).hasClass('active')) {
                    current = i;
                    break;
                }
            }

            /* wrap around */
            var newIndex = current + offset;
            if (newIndex >= thumbnails.length) {
                newIndex = 0;
            }
            else if (newIndex < 0) {
                newIndex = thumbnails.length - 1;
            }

            setImage(IMAGES[newIndex]);
        }

        function previous() {
            moveBy(-1);
        }

        function next() {
            moveBy(1);
        }

        function render(params) {
            params = params || parseHash();

            var image = params.i;
            if (!image || IMAGES.indexOf(image) === -1) {
                image = IMAGES[0];
            }

            if (params.b) {
                $("body").css("background-color", params.b);
            }

            setTimeout(function() {
                setImage(image);
                writeHash({image: image});
                pollHash();
            });
        }

        function keyPressed(event) {
            if (event.altKey || event.metaKey) {
                /* don't swallow browser back/forward shortcuts */
                return true;
            }
            if (event.keyCode === 39) { /* right arrow */
                moveBy(1);
            }
            else if (event.keyCode === 37) { /*left arrow */
                moveBy(-1);
            }
        }

        function main() {
            if ($.browser.chrome) {
                SCROLLBAR_HEIGHT_FUDGE = -4;
            }
            else if ($.browser.firefox) {
                SCROLLBAR_HEIGHT_FUDGE = 0;
            }

            /* initialize the color picker */
            var colorButtons = $(".color-picker .color-button");
            for (var i = 0; i < colorButtons.length; i++) {
                var $button = $(colorButtons[i]);
                var bg = $button.attr("data-bg");

                if (bg) {
                    $button.css({"background-color": bg});
                }
            }

            /* add thumbnails to the strip */
            for (var i = 0; i < IMAGES.length; i++) {
                $strip.append(createThumbnail(IMAGES[i]));
            }

            /* register events */
            $image.on("load", imageLoaded);
            $image.on("click", imageClicked);
            $win.on("resize", dimensionsChanged);
            $doc.on("click", ".strip img", thumbnailClicked);
            $doc.on("click", ".color-picker .color-button", colorButtonClicked);
            $doc.on("click", ".button.prev", previous);
            $doc.on("click", ".button.next", next);
            $body.on("keydown", keyPressed);
            $(".strip img").on("load", thumbnailLoaded);

            render();
        }

        main();
    });
</script>

</head>

<body class="loading">
    <div class="header">
        <ul class="color-picker">
            <li data-bg="#000000" class="color-button"></li>
            <li data-bg="#181818" class="color-button"></li>
            <li data-bg="#404040" class="color-button"></li>
            <li data-bg="#808080" class="color-button"></li>
            <li data-bg="#b0b0b0" class="color-button"></li>
            <li data-bg="#ffffff" class="color-button"></li>
        </ul>
    </div>
    <div class="middle">
        <div class="pseudo-button prev">
          <div class="button prev">&lt;</div>
        </div>
        <div class="pseudo-button next">
          <div class="button next">&gt;</div>
        </div>
        <div class="center">
            <div class="current-image-container">
                <img class="current-image">
                <div class="spinner-container"></div>
            </div>
        </div>
    </div>
    <div class="footer">
        <div class="strip"></div>
    </div>
</body>
</html>
