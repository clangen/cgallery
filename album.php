<?php /** album.php **/
  /*
  * cgallery v2.2
  *
  * album.php:
  * - indexes a directory of images and creates thumbnails.
  * - output is a client-side webapp (a single html file with css+js).
  * - uses cdn versions of jquery and spin.js.
  * - works on most newish versions of webkit, ff, ie.
  */
  header("Content-Type: text/html; charset=utf-8");

  $options = getopt("m:t:"); /* mode */
  $protocol = $options['m'] == 'local' ? "http:" : "";
  $thumbOnly = ($options['t'] == '1');

  function listImages() {
    $dir = opendir(".");
    $result = array();

    while (($fn = readdir($dir)) !== false) {
      if (($fn === ".") || ($fn === "..") || !is_file($fn)) {
        continue;
      }

      $extension = strtolower(substr($fn, strlen($fn) - 3, 3));

      if (strcmp($extension, "jpg") == 0
        || strcmp($extension, "gif") == 0
        || strcmp($extension, "png") == 0)
      {
        array_push($result, $fn);
      }
    }

    sort($result);
    return $result;
  }

  function createThumbnail($inFn, $outFn) {
    global $thumbOnly;
    if ($thumbOnly) {
      print "  creating thumbnail: $outFn\n";
    }

    $maxHeight = 80;
    $format = strtolower(end(explode(".", $inFn)));

    /* load the input image and get dimensions */
    $inImage = null;
    switch ($format) {
      case "png": $inImage = imagecreatefrompng($inFn); break;
      case "jpg": $inImage = imagecreatefromjpeg($inFn); break;
      case "gif": $inImage = imagecreatefromgif($inFn); break;
      default: return;
    }

    $inWidth = imagesx($inImage);
    $inHeight = imagesy($inImage);
    $ratio = $inWidth / $inHeight;

    /* calculate output dimensions, create image */
    $outWidth = floor($maxHeight * $ratio);
    $outHeight = $maxHeight;
    $outImage = imagecreatetruecolor($outWidth, $outHeight);

    /* generate the thumbnail into $outImage */
    $result = imagecopyresampled(
      $outImage,
      $inImage,
      0, 0, 0, 0,
      $outWidth, $outHeight,
      $inWidth, $inHeight
    );

    /* write to disk, clean up. we always create the thumbnail in
    the same format as the input image, makes loading them on the
    client side much simpler */
    if ($result) {
      switch ($format) {
        case "png": imagepng($outImage, $outFn); break;
        case "jpg": imagejpeg($outImage, $outFn); break;
        case "gif": imagegif($outImage, $outFn); break;
        default: return;
      }
    }

    imagedestroy($inImage);
    imagedestroy($outImage);
  }

  function createThumbnails($imageList) {
    $outPath = (getcwd() . "/.thumbs/");
    $inPath = (getcwd() . "/");

    @mkdir($outPath);

    foreach ($imageList as $imageFn) {
      $outFn = $outPath . $imageFn;
      if (file_exists($outFn)) {
        continue; /* thumbnail exists */
      }

      $inFn = $inPath . $imageFn;
      createThumbnail($inFn, $outFn);
    }
  }

  $imageList = listImages();
  createThumbnails($imageList);

  if ($thumbOnly) {
    exit(0);
  }
?>
<html>
<head>
<title>photos</title>
<style>
  body > * {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    cursor: default;
  }

  ::-moz-selection {
    background: transparent;
  }

  html {
    /* sometimes webkit displays a vertical scrollbar when we're embedded.
    this fixes that. */
    overflow: hidden;
  }

  body {
    font-family: sans-serif;
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
    border: 3px solid black;
    margin-right: 6px;
    cursor: pointer;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-shadow: 0 0 4px #111;
    opacity: 0.75;
  }

  .strip img.small {
    height: 46px;
  }

  .strip img:hover {
    opacity: 1.0;
  }

  .strip img.active {
    border: 3px solid white;
    opacity: 1.0;
  }

  .bitbucket {
    display: none;
    background-color: rgb(46, 46, 46);
    position: absolute;
    text-align: center;
    line-height: 1.2em;
    left: 0;
    right: 0;
    bottom: 0;
    height: 20px;
  }

  .bitbucket a {
    color: #666;
    text-shadow: 0 0 3px #222;
    font-size: 11px;
    text-decoration: none;
  }

  .bitbucket a:hover {
    color: #bbb;
    text-shadow: 0 0 3px #000;
    text-decoration: underline;
  }

  .project-link .bitbucket {
    display: block;
  }

  .footer {
    -webkit-overflow-scrolling: touch;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    overflow-x: auto;
    overflow-y: hidden;
    background-color: #222;
    border-top: 1px solid black;
    border-bottom: 1px solid #111;
    box-shadow: 0 0 4px #111;
  }

  .project-link .footer {
    bottom: 20px;
  }

  .middle {
    position: absolute;
    left: 0;
    right: 0;
    top: 25px;
    bottom: 100px;
  }

  .project-link .middle {
    bottom: 120px;
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
  }

  .pseudo-button.prev {
    left: 8px;
    right: 50%;
  }

  .pseudo-button.next {
    right: 8px;
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
    left: 50px;
    right: 50px;
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

  .no-images-text {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    text-align: center;
    top: 50%;
    margin-top: -0.6em;
    height: 1.2em;
    color: #bbb;
    text-shadow: 0 0 8px #222;
  }

  .no-images .no-images-text {
    display: block;
  }

  ::-webkit-scrollbar {
    height: 9px;
    width: 9px;
    border-radius: 8px;
    background: transparent;
    margin-bottom: 4px;
  }

  ::-webkit-scrollbar-thumb {
    background: #666;
    border: 1px solid #111;
    border-radius: 6px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #bbb;
  }

  ::-webkit-scrollbar-corner {
    background: #000;
  }

  ::-webkit-scrollbar-track-piece {
    border-top: 1px solid black;
    background-color: rgba(0, 0, 0, 0.2);
  }
</style>

<?php
  global $protocol;
  printf('<script src="' . $protocol . '//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>' . "\n");
  printf('<script src="' . $protocol . '//cdnjs.cloudflare.com/ajax/libs/spin.js/1.2.7/spin.min.js"></script>' . "\n");
?>

<script>
  /* https://github.com/brandonaaron/jquery-getscrollbarwidth/blob/master/jquery.getscrollbarwidth.js */
  (function($) {
      $.browser = {
        msie: navigator.appName == 'Microsoft Internet Explorer',
        chrome: /Chrome/.test(navigator.userAgent),
        firefox: /Firefox/.test(navigator.userAgent)
      };
  })(jQuery);
</script>

<script>
  (function() {
    var THUMB_TEMPLATE_NORMAL = '<img data-large="{{large}}" src="{{thumb}}">';
    var THUMB_TEMPLATE_SMALL = '<img class="small" data-large="{{large}}" src="{{thumb}}">';

    var NORMAL_THUMB_MINIMUM_HEIGHT = 680;

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

    IMAGES = IMAGES.sort(function(a, b) {
      return a.localeCompare(b);
    });

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
      var hasSmallThumbs = shouldDisplaySmallThumbs();
      var loadedThumbnails = 0;
      var currentImage, lastHash;
      var hashPollInterval;
      var embedded = (window.parent !== window);
      var disableHistory = /[&?](nohistory|n)=1/.test(window.location.search);
      var back;

      if (!embedded) {
        $body.addClass('project-link');
      }

      if (embedded) {
        $(window).on('message', function(event) {
          event = event.originalEvent || event;
          var data = event.data || { };
          switch (data.message) {
            case 'changeHash':
              if (data.options.hash !== getHashFromUrl().substring(1)) {
                render(parseHash(data.options.hash));
              }
              break;
            case 'next': moveBy(1); break;
            case 'prev': moveBy(-1); break;
          }
        });
      }

      function shouldDisplaySmallThumbs() {
        return (window.innerHeight < NORMAL_THUMB_MINIMUM_HEIGHT);
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
            else if (keyValue.length === 2) {
              result[keyValue[0]] = decodeURIComponent(keyValue[1]);
            }
          }
        }

        return result;
      }

      function pollHash() {
        /* don't poll the hash when we're embedded -- let the outer container
        control the url stack. we send events via postMessage elsewhere when
        the selected image changes. */
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
        var result = "#" + encodeURIComponent(image);

        if (background) {
          result += "+b:" + encodeURIComponent(background.replace(/, /g, ","));
        }

        return result;
      }

      function writeHash(options) {
        /* don't write the hash when embedded, otherwise history information
        will seep up into the outer container and mess with back behavior.
        we will notify the outer container via postMessage instead */
        if (embedded) {
          return;
        }

        var hash = generateHash(options);
        var replace = disableHistory;

        /* if there's no hash in the url yet, then don't maintain it in the
        back stack. only remember the first actual image selected */
        if (getHashFromUrl() === "#") {
          replace = true;
        }

        if (hash !== getHashFromUrl() || hash !== lastHash) {
          if (replace) {
            var href = location.href.replace(/(javascript:|#).*$/, '');
            location.replace(href + hash);
          }
          else {
            window.location.hash = lastHash = hash;
          }

          lastHash = hash;
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
        var strip = $footer.outerHeight();
        var branding = embedded ? 0 : $('.bitbucket').outerHeight();
        $middle.css("bottom", strip + branding);
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
        offset = Math.max(offset, 0); /* no smaller than 0 */
        $image.css({"margin-top": offset, "visibility": "visible"});
      }

      function dimensionsChanged() {
        var smallThumbs = shouldDisplaySmallThumbs();
        
        if (smallThumbs != hasSmallThumbs) {
          hasSmallThumbs = smallThumbs;
          renderThumbnails();
        }

        /* we use an old trick to auto-scale the image inside of its 
        container by setting max-width and max-height to 100%. as of 
        10/18/2014 this is broken in Chrome, cross platform. instead,
        we need to manually set the max-height and max-width to pixel
        values. hopefully this is fixed soon; this hack should be 
        checked and removed at some point... */
        $image.css({
          'max-height': $imageContainer.outerHeight(),
          'max-width': $imageContainer.outerWidth()
        });

        centerImageVertically();
        checkForScrollbar();
        updateScrollLeft({animate: false});
      }

      function createThumbnail(filename, options) {
        var small = options && options.small;
        var template = small ? THUMB_TEMPLATE_SMALL : THUMB_TEMPLATE_NORMAL;
        return $(
          template.replace("{{large}}", filename)
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
          $image.css({"visibility": "visible"}); /* gross but cheap */
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

      function updateTitle() {
        var parts = window.location.pathname.split("/");

        /* last part will be empty due to trailing slash in url */
        document.title = "photos - " + parts[parts.length - 2].replace(/_/g, " ");
      }

      function render(params) {
        if (IMAGES.length === 0) {
          $body.addClass('no-images');
        }
        else {
          params = params || parseHash();

          var image = params.i;
          if (!image || IMAGES.indexOf(image) === -1) {
            image = IMAGES[0];
          }

          if (params.b) {
            $body.css("background-color", params.b);
          }

          updateTitle();

          setTimeout(function() {
            setImage(image);
            writeHash({image: image});
            pollHash();
          }, 250);
        }
      }

      function keyPressed(event) {
        if (event.altKey || event.metaKey) {
          return true; /* don't swallow browser back/forward shortcuts */
        }

        if (event.keyCode === 39) { /* right arrow */
          moveBy(1);
        }
        else if (event.keyCode === 37) { /*left arrow */
          moveBy(-1);
        }
        else if (embedded && event.keyCode === 38) { /* up */
          window.parent.postMessage({ message: 'prevAlbum' }, "*");
        }
        else if (embedded && event.keyCode === 40) { /* down */
          window.parent.postMessage({ message: 'nextAlbum' }, "*");
        }
      }
        
      function renderThumbnails() {
        $strip.empty();
        for (i = 0; i < IMAGES.length; i++) {
          $strip.append(createThumbnail(IMAGES[i], {
            small: hasSmallThumbs
          }));
        }
      }

      function main() {
        var i;

        /* initialize the color picker */
        var colorButtons = $(".color-picker .color-button");
        for (i = 0; i < colorButtons.length; i++) {
          var $button = $(colorButtons[i]);
          var bg = $button.attr("data-bg");

          if (bg) {
            $button.css({"background-color": bg});
          }
        }

        /* add thumbnails to the strip */
        renderThumbnails();

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
        $('img').on('dragstart', function(event) { event.preventDefault(); });

        render();
      }

      main();
    });
  }());
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
                <div class="no-images-text">there are no images here.</div>
                <div class="spinner-container"></div>
            </div>
        </div>
    </div>
    <div class="footer">
        <div class="strip"></div>
    </div>
  <div class="bitbucket">
    <a href="https://bitbucket.org/clangen/cgallery" target="_new">https://bitbucket.org/clangen/cgallery</a>
  </div>
</body>
</html>
