<?php
  header("Content-Type: text/html; charset=utf-8");

  $albums = array();
  $series = array();

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

        if (is_file($file . "/.series")) {
          array_push($series, $file);
        }
        else {
          array_push($albums, $file);
        }
    }
  }

  sort($albums);
  sort($series);
?>

<html>

<head>

<title>albums</title>

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
      font-size: 24px;
      color: #666;
      text-shadow: 0 0 8px #222;
      text-decoration: underline;
      padding-bottom: 4px;
    }

    .albums {
      display: block;
    }

    .hidden {
      display: none;
    }

    .date {
      font-size: 60%;
      color: #666;
    }

    .album-list,
    .series-list {
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

    .arrow {
      color: #ccc;
      font-weight: bold;
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
      '<li class="item album-name">' +
        '<a href="{{url}}" data-index="{{index}}">' +
          '{{caption}}' +
        '</a>' +
      '</li>';

    var LIST_ITEM_TEMPLATE_WITH_DATE =
      '<li class="item album-name">' +
        '<a href="{{url}}" data-index="{{index}}">' +
          '{{caption}}' +
        '</a>' +
        '<span class="date"> ({{date}})</span>' +
      '</li>';

    var LIST_ITEM_SERIES_TEMPLATE = 
      '<li class="item album-name">' +
        '<a href="{{url}}" data-index="{{index}}">' +
          '{{caption}}' +
          '<span class="arrow"> \u21fe</span>' +
        '</a>' +
      '</li>';

    var SPINNER_OPTIONS = {
      lines: 12, length: 7, width: 4, radius: 10, color: '#ffffff',
      speed: 1, trail: 60, shadow: true
    };

    var ALBUMS = [
      <?php
        global $albums; /* generated above */
        foreach ($albums as $a) {
          printf('"' . $a . '",' . "\n");
        }
      ?>
    ];

    var SERIES = [
      <?php
        global $series; /* generated above */
        foreach ($series as $s) {
          printf('"' . $s . '",' . "\n");
        }
      ?>
    ];

    var lastSelected = { }; /* key=album, value=image */
    var currentAlbum = '';
    var lastHash;

    function writeHash(hash, options) {
      options = options || { };

      /* if the hash doesn't contain a specific image, for example it
      looks like this: 'foo.com/photos/#album, and the new url has
      an image, e.g. foo.com/photos/#album/test.png then replace the
      existing url. this is a bit of a hack to keep the backstack clean. */
      var currentHash = getHashFromUrl().split('/');

      if (currentHash.length === 1) {
        currentHash[0] = currentHash[0].substring(1); /* remove # */
        /* if there is no hash then we just loaded -- we also replace the
        url in this state so to the user the first url appears to be
        the first selected album */
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

    function parseAlbumHash(hash) {
      /* note this same method lives in album.php. it wouldn't be a big
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
      var parts = parseAlbumHash(options.hash);

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
      var album = ALBUMS[index];
      var selected = "";

      if (hash) {
        /* first try to grab the URL from the hash, if it exists.
        this has highest priority */
        selected = parseAlbumHash(hash).i;
      }

      /* couldn't parse an image from the specified hash, see if
      we've viewed this album before. if we have, the last viewed
      image will be cached, so we'll load that */
      if (!selected) {
        selected = getSelectedImageForAlbum(album);
      }

      if (selected) {
        selected = selected + '+';
      }

      var result =
        window.location.protocol + '//' +
        window.location.hostname +
        window.location.pathname + album +
        "#" + selected + getBackgroundColor();

      return result;
    }

    function setSelectedImageForAlbum(album, hash) {
      var parts = parseAlbumHash(hash);
      if (parts.i && sessionStorage) {
        lastSelected[album] = parts.i;
      }
    }

    function getSelectedImageForAlbum(album) {
      return lastSelected[album] || '';
    }

    $(document).ready(function() {
        var $iframe = $('.embedded');
        var $main = $('.main');
        var $spinnerContainer = $('.spinner-container');
        var spinner = new Spinner(SPINNER_OPTIONS);
        var albumLoading = false;

        var hashPollInterval;

        $(window).on('message', function(event) {
          event = event.originalEvent || event;

          var data = event.data;
          if (data) {
            switch (data.message) {
              case 'hashChanged':
                if (currentAlbum) {
                  var hash = data.options.hash;
                  writeHash(currentAlbum + "/" + hash);
                  setSelectedImageForAlbum(currentAlbum, hash);
                  updateBackgroundColor();
                }
                break;

              case 'prevAlbum': selectPrevAlbum(); break;
              case 'nextAlbum': selectNextAlbum(); break;
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

          if (loading === albumLoading) {
            return;
          }

          if (loading) {
            albumLoading = true;
            $main.addClass('loading');
            spinner.spin($spinnerContainer[0]);
          }
          else {
            albumLoading = false;
            $main.removeClass('loading');
            spinner.stop();
          }
        };

        /* if we don't destroy then re-create the iframe every time the album
        switches, its history gets updated and corrupts our backstack */
        resetIFrame = function(url) {
          if ($iframe) {
            $iframe.remove();
          }

          $iframe = $('<iframe class="embedded"></iframe>');
          $('.main').append($iframe);

          setTimeout(function() {
            $iframe.attr('src', url);

            /* setting src isn't good enough. in some browsers (older firefox,
            newer chrome), navigating forward (e.g. to an album series), then back
            will cause the wrong iframe url to be loaded. solution was found:
            http://stackoverflow.com/questions/2648053/preventing-iframe-caching-in-browser */
            $iframe[0].contentWindow.location.href = url;
          });
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

            index = Math.max(0, ALBUMS.indexOf(index));
          }

          var album = ALBUMS[index];
          if (album === currentAlbum) {
            var lastHashPath = (lastHash || "").split("/")[1];
            if (lastHashPath !== currentHashPath) {
              /* album is the same, but the image changed. user probably pressed
              the back button, so let the current album know */
              post('changeHash', {hash: currentHashPath});
            }
          }
          else {
            /* mark active state */
            var $items = $('.album-list .item');
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

            currentAlbum = ALBUMS[index];

            /* write it back to the url */
            finalHashPath = currentHashPath || "";
            /* needs leading path char */
            if (finalHashPath.length && finalHashPath.charAt(0) !== "/") {
              finalHashPath = "/" + finalHashPath;
            }

            writeHash(ALBUMS[index] + finalHashPath);
          }
        };

        /* things like back button change the hash without us knowing,
        so we need to monitor for changes. ugh. */
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
          var $el = $('.album-list .active a');

          if ($el.length === 1) {
            return parseInt($el.eq(0).attr("data-index"), 10);
          }

          return 0;
        };

        var selectNextAlbum = function() {
          var next = getSelectedIndex() + 1;
          select(next >= ALBUMS.length ? 0 : next);
        };

        var selectPrevAlbum = function() {
          var prev = getSelectedIndex() - 1;
          select(prev < 0 ? ALBUMS.length - 1 : prev);
        };

        var scrollToSelectedAlbum = function() {
          var $active = $('li.item.active');
          if ($active.length) {
            var pos = $active.position().top;
            $('.album-list').animate({scrollTop: pos});
          }
        };

        $('.embedded').on('load', function() {
          $('.embedded').removeClass('hidden');
        });

        /* links in a album list open in the iframe */
        $('.album-list').on('click', 'a', function(event) {
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
              case 38: selectPrevAlbum(); break;
              case 39: post('next'); break;
              case 40: selectNextAlbum(); break;
            }
        });

        var i;

        /* generate album list, add to DOM */
        var $albumList = $(".album-list");
        var album, caption, parts, template, html;
        for (i = 0; i < ALBUMS.length; i++) {
            album = ALBUMS[i];

            caption = album.replace(/_/g, " ");
            parts = parseListItem(caption);

            template = parts.date ?
              LIST_ITEM_TEMPLATE_WITH_DATE : LIST_ITEM_TEMPLATE;

            html = template
                .replace("{{url}}", album)
                .replace("{{caption}}", parts.caption)
                .replace("{{index}}", i)
                .replace("{{date}}", parts.date);

            $albumList.append(html);
        }

        /* generate series items */
        var $seriesList = $(".series-list"), series;
        for (i = 0; i < SERIES.length; i++) {
            html = LIST_ITEM_SERIES_TEMPLATE
                .replace("{{url}}", SERIES[i])
                .replace("{{caption}}", SERIES[i].replace(/_/g, " "))
                .replace("{{index}}", i);

            $seriesList.append(html);
        }

        $('.albums').toggleClass('hidden', ALBUMS.length === 0);
        $('.series').toggleClass('hidden', SERIES.length === 0);

        select(getHashFromUrl());
        scrollToSelectedAlbum();
        pollHash();
    });

</script>

</head>

<body onselectstart="return false">
  <div class="left">
    <div class="albums">
      <div class="title">albums:</div>
      <ul class="album-list"></ul>
    </div>
    <div class="series hidden">
      <div class="title">series:</div>
      <ul class="series-list"></ul>
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
