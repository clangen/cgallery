<?php
  header("Content-Type: text/html; charset=utf-8");

  $galleries = array();
  $sets = array();

  $cwd = dirname($_SERVER['SCRIPT_FILENAME']);
  $cwd = array_pop(explode('/', $cwd));

  $dir = opendir(".");
  $result = array();

  while (($file = readdir($dir)) !== false) {
    if ($file != "." && $file != ".." && is_dir($file)) {
        $hidden = $file . "/.hidden";

        if (is_file($hidden) || is_file($hidden . "-" . $cwd)) {
          continue;
        }

        if (is_file($file . "/.set")) {
          array_push($sets, $file);
        }
        else {
          array_push($galleries, $file);
        }
    }
  }

  sort($galleries);
  sort($sets);
?>

<html>

<head>

<title>galleries</title>

<style type="text/css">
    body > * {
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      -khtml-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      cursor: default;
    }

    body {
      font-family: sans-serif;
      background-color: rgb(46, 46, 46);
    }

    a {
      outline: 0;
    }

    .main {
      position: absolute;
      left: 210px;
      top: 20px;
      right: 20px;
      bottom: 20px;
      background-color: rgb(64, 64, 64);
      border: 1px solid rgb(32, 32, 32);
      -webkit-box-shadow: 0 0 15px #222;
      -moz-box-shadow: 0 0 15px #222;
      box-shadow: 0 0 15px #222;
    }

    .left {
      position: absolute;
      left: 20px;
      top: 20px;
      bottom: 20px;
      width: 180px;
      overflow-x: hidden;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
    }

    ul, li {
      margin: 0;
      padding: 0;
      padding-bottom: 6px;
      list-style-type: none;
    }

    .item a {
      color: #aaa;
      font-size: 12px;
      text-decoration: none;
    }

    .item.active a,
    .item.active a:hover {
      color: #eee;
      text-shadow: 1px 1px 6px #111;
      font-weight: bold;
      text-decoration: underline;
    }

    .item a:hover {
      color: #ddd;
      text-decoration: underline;
      cursor: pointer;
    }

    .title {
      font-weight: bold;
      font-size: 30px;
      color: #666;
      text-shadow: 0 0 8px #222;
      text-decoration: underline;
      padding-bottom: 4px;
    }

    .galleries {
      display: block;
    }

    .hidden {
      display: none;
    }

    .date {
      font-size: 60%;
      color: #666;
    }

    .gallery-list,
    .set-list {
      padding-left: 0px;
    }

    .footer {
      position: absolute;
      text-align: center;
      line-height: 1.2em;
      left: 210px;
      right: 0;
      bottom: 0;
      height: 20px;
    }

    .footer a {
      color: #666;
      font-size: 11px;
      text-decoration: none;
    }

    .footer a:hover {
      color: #bbb;
      text-shadow: 0 0 3px #000;
      text-decoration: underline;
    }

    .embedded {
      display: block;
      width: 100%;
      height: 100%;
      border: 0;
    }

    .loading .embedded {
      display: none;
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

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/spin.js/1.2.7/spin.min.js"></script>

<script type="text/javascript">
    /* YYYY-MM-DD or YYYY-MM */
    var SIMPLE_DATE_REGEX = /^\d{4}-\d{2}(-\d{2})?$/;

    var LIST_ITEM_TEMPLATE =
      '<li class="item gallery-name">' +
        '<a href="{{url}}" data-index="{{index}}">' +
          '{{caption}}' +
        '</a>' +
      '</li>';

    var LIST_ITEM_TEMPLATE_WITH_DATE =
      '<li class="item gallery-name">' +
        '<a href="{{url}}" data-index="{{index}}">' +
          '{{caption}}' +
        '</a>' +
        '<span class="date"> ({{date}})</span>' +
      '</li>';

    var SPINNER_OPTIONS = {
      lines: 12, length: 7, width: 4, radius: 10, color: '#ffffff',
      speed: 1, trail: 60, shadow: true
    };

    var GALLERIES = [
      <?php
        global $galleries; /* generated above */
        foreach ($galleries as $gallery) {
          printf('"' . $gallery . '",' . "\n");
        }
      ?>
    ];

    var SETS = [
      <?php
        global $sets; /* generated above */
        foreach ($sets as $set) {
          printf('"' . $set . '",' . "\n");
        }
      ?>
    ];

    var lastSelected = { }; /* key=gallery, value=image */
    var currentGallery = '';
    var lastHash;

    function writeHash(hash, options) {
      options = options || { };

      console.log(hash);

      /* if the hash doesn't contain a specific image, for example it
      looks like this: 'foo.com/photos/#gallery, and the new url has
      an image, e.g. foo.com/photos/#gallery/test.png then replace the
      existing url. this is a bit of a hack to keep the backstack clean. */
      var currentHash = getHashFromUrl().split('/');

      if (currentHash.length === 1) {
        currentHash[0] = currentHash[0].substring(1); /* remove # */
        /* if there is no hash then we just loaded -- we also replace the
        url in this state so to the user the first url appears to be
        the first selected gallery */
        if (currentHash[0] === '' || currentHash[0] === hash.split('/')[0]) {
          options.replace = true;
        }
      }

      lastHash = "#" + hash;

      if (hash !== window.location.hash) {
        if (options && options.replace) {
          if (window.history.replaceState) {
            window.history.replaceState({ }, document.title, '#' + hash);
          }
          else {
            /* stolen from backbone */
            var href = location.href.replace(/(javascript:|#).*$/, '');
            location.replace(href + '#' + hash);
          }
        }
        else {
          window.location.hash = hash;
        }
      }
    }

    function getHashFromUrl() {
      /* some browsers decode the hash, we don't want that */
      return "#" + (window.location.href.split("#")[1] || "");
    }

    function parseGalleryHash(hash) {
      /* note this same method lives in gallery.php. it wouldn't be a big
      deal to exteranlize this in a .js file, but then that file would need
      to be distributed as well. to make this slightly less ugly from the
      user's standpoint we could use php to dynamically insert this (and
      other potentially shared functionality) into the pages as they are
      rendered. but for now we live with this duplicated method. */
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

    function getBackgroundColor(options) {
      options = options || { };
      options.format = options.format || 'hash';
      var parts = parseGalleryHash(options.hash);

      if (parts.b) {
        if (options.format === 'css') {
          return parts.b;
        }
        else {
          return 'b:' + encodeURIComponent(parts.b);
        }
      }

      return '';
    }

    function updateBackgroundColor() {
      var color = getBackgroundColor({format: 'css'});
      if (color.length) {
        $('.main').css('background-color', color);
      }
    }

    function parseListItem(caption) {
      parts = (caption || "").split(" ");
      var len = parts.length;

      var date;
      if (len > 1 && SIMPLE_DATE_REGEX.test(parts[len - 1])) {
        date = parts.pop();
        caption = parts.join(' ');
      }

      return { date: date, caption: caption }
    }

    function urlAtIndex(index, hash) {
      var gallery = GALLERIES[index];
      var selected = "";

      if (hash) {
        /* first try to grab the URL from the hash, if it exists.
        this has highest priority */
        selected = parseGalleryHash(hash).i;
      }

      /* couldn't parse an image from the specified hash, see if
      we've viewed this gallery before. if we have, the last viewed
      image will be cached, so we'll load that */
      if (!selected) {
        selected = getSelectedImageForGallery(gallery);
      }

      if (selected) {
        selected = selected + '+';
      }

      var result =
        window.location.protocol + '//' +
        window.location.hostname +
        window.location.pathname + gallery +
        "#" + selected + getBackgroundColor();

      return result;
    }

    function setSelectedImageForGallery(gallery, hash) {
      var parts = parseGalleryHash(hash);
      if (parts.i && sessionStorage) {
        lastSelected[gallery] = parts.i;
      }
    }

    function getSelectedImageForGallery(gallery) {
      return lastSelected[gallery] || '';
    }

    $(document).ready(function() {
        var $iframe = $('.embedded');
        var $main = $('.main');
        var $spinnerContainer = $('.spinner-container');
        var spinner = new Spinner(SPINNER_OPTIONS);
        var galleryLoading = false;

        var hashPollInterval;

        $(window).on('message', function(event) {
          event = event.originalEvent || event;

          var data = event.data;
          if (data) {
            switch (data.message) {
              case 'hashChanged':
                if (currentGallery) {
                  var hash = data.options.hash;
                  writeHash(currentGallery + "/" + hash);
                  setSelectedImageForGallery(currentGallery, hash);
                  updateBackgroundColor();
                }
                break;

              case 'prevGallery': selectPrevGallery(); break;
              case 'nextGallery': selectNextGallery(); break;
            }
          }
        });

        var post = function(name, options) {
            var el = $iframe.get(0);
            if (el && el.contentWindow && el.contentWindow.postMessage) {
              var msg  = { message: name, options: options || { } };
              el.contentWindow.postMessage(msg, "*");
            }
        };

        var setLoading = function(loading) {
          loading = (loading === undefined) ? true : loading;

          if (loading === galleryLoading) {
            return;
          }

          if (loading) {
            galleryLoading = true;
            $main.addClass('loading');
            spinner.spin($spinnerContainer[0]);
          }
          else {
            galleryLoading = false;
            $main.removeClass('loading');
            spinner.stop();
          }
        };

        /* if we don't destroy then re-create the iframe every time the gallery
        switches, its history gets updated and corrupts our backstack */
        resetIFrame = function(url) {
          if ($iframe) {
            $iframe.remove();
          }

          $iframe = $('<iframe src="' + url + '" class="embedded foo"></iframe>');
          $('.main').append($iframe);
        };

        var select = function(index) {
          var currentHashPath; /* only set if index is typeof string */

          if (typeof index === 'string') {
            if (index.charAt(0) === '#') {
              index = index.substring(1);
            }

            var parts = index.split('/');
            index = parts[0];
            currentHashPath = parts[1];

            index = Math.max(0, GALLERIES.indexOf(index));
          }

          var gallery = GALLERIES[index];
          if (gallery === currentGallery) {
            var lastHashPath = (lastHash || "").split("/")[1];
            if (lastHashPath !== currentHashPath) {
              /* gallery is the same, but the image changed. user probably pressed
              the back button, so let the current gallery know */
              post('changeHash', {hash: currentHashPath});
            }
          }
          else {
            /* mark active state */
            var $items = $('.gallery-list .item');
            $items.removeClass('active');
            $items.eq(index).addClass('active');

            /* load */
            setLoading(true);
            resetIFrame(urlAtIndex(index, currentHashPath));

            /* avoid white flash by hiding the iframe for a short
            period of time */
            $iframe.one('load', function() {
              setLoading(false);
            });

            setTimeout(function() {
            }, 500);

            currentGallery = GALLERIES[index];

            /* write it back to the url */
            finalHashPath = currentHashPath || "";
            if (finalHashPath.length && finalHashPath.charAt(0) !== "/") {
              finalHashPath = "/" + finalHashPath;
            }

            writeHash(GALLERIES[index] + finalHashPath);
          }
        };

        var pollHash = function() {
          if (!hashPollInterval) {
            hashPollInterval = setInterval(function() {
              var currentHash = getHashFromUrl();
              if (currentHash !== lastHash) {
                select(currentHash);
                lastHash = currentHash;
              }
            }, 250);
          }
        };

        var getSelectedIndex = function() {
          var $el = $('.gallery-list .active a');

          if ($el.length === 1) {
            return parseInt($el.eq(0).attr("data-index"), 10);
          }

          return 0;
        };

        var selectNextGallery = function() {
          var next = getSelectedIndex() + 1;
          select(next >= GALLERIES.length ? 0 : next);
        };

        var selectPrevGallery = function() {
          var prev = getSelectedIndex() - 1;
          select(prev < 0 ? GALLERIES.length - 1 : prev);
        };

        var scrollToSelectedGallery = function() {
          var $active = $('li.item.active');
          if ($active.length) {
            var pos = $active.position().top;
            $('.gallery-list').animate({scrollTop: pos});
          }
        };

        $('.embedded').on('load', function() {
          $('.embedded').removeClass('hidden');
        });

        $('.gallery-list').on('click', 'a', function(event) {
          event.preventDefault();
          var $el = $(event.currentTarget);
          var index = parseInt($el.attr("data-index"), 10);
          select(index);
        });

        $("body").on("keydown", function(event) {
            if (event.altKey || event.metaKey) {
                return true; /* don't swallow browser back/forward shortcuts */
            }

            switch (event.keyCode) {
              case 37: post('prev'); break;
              case 38: selectPrevGallery(); break;
              case 39: post('next'); break;
              case 40: selectNextGallery(); break;
            }
        });

        var i;

        /* generate gallery list, add to DOM */
        var $galleryList = $(".gallery-list");
        var gallery, caption, parts, template, html;
        for (i = 0; i < GALLERIES.length; i++) {
            gallery = GALLERIES[i];

            caption = gallery.replace(/_/g, " ");
            parts = parseListItem(caption);

            template = parts.date ?
              LIST_ITEM_TEMPLATE_WITH_DATE : LIST_ITEM_TEMPLATE;

            html = template
                .replace("{{url}}", gallery)
                .replace("{{caption}}", parts.caption)
                .replace("{{index}}", i)
                .replace("{{date}}", parts.date);

            $galleryList.append(html);
        }

        /* generate set list */
        var $setList = $(".set-list"), set;
        for (i = 0; i < SETS.length; i++) {
            html = LIST_ITEM_TEMPLATE
                .replace("{{url}}", SETS[i])
                .replace("{{caption}}", SETS[i].replace(/_/g, " "))
                .replace("{{index}}", i);

            $setList.append(html);
        }

        $('.galleries').toggleClass('hidden', GALLERIES.length === 0);
        $('.sets').toggleClass('hidden', SETS.length === 0);

        select(getHashFromUrl());
        scrollToSelectedGallery();
        pollHash();
    });

</script>

</head>

<body onselectstart="return false">
  <div class="left">
    <div class="galleries">
      <div class="title">albums:</div>
      <ul class="gallery-list"></ul>
    </div>
    <div class="sets hidden">
      <div class="title">sets:</div>
      <ul class="set-list"></ul>
    </div>
  </div>
  <div class="main loading">
    <!-- iframe inserted dynamically -->
    <div class="spinner-container"></div>
  </div>
  <div class="footer">
    <a href="https://bitbucket.org/clangen/cgallery" target="_new">https://bitbucket.org/clangen/cgallery</a>
  </div>
</body>

</html>
