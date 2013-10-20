<?php
  /* series.php: v2.0
  *
  * indexes a directory of albums and/or (sub)-series.  Renders
  * a simple webapp to view. works on most newish versions of major
  * browsers.
  *  
  * references cdn versions of jQuery and spin.js.
  */
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
      left: 10px;
      top: 10px;
      bottom: 20px;
      width: 180px;
      padding-left: 10px;
      padding-top: 10px;
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

    a:active { /* ie draws a grey background by default */
      background-color: transparent;
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

    .album-list {
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

    .no-albums.visible {
      display: block;
    }

    .no-albums {
      display: none;
      position: absolute;
      height: 1.2em;
      top: 50%;
      margin-top: -0.6em; /* height / 2 */
      left: 0;
      right: 0;
      text-align: center;
      color: #bbb;
      text-shadow: 0 0 8px #222;
    }

    .no-albums .no-albums,
    .no-albums .spinner-container,
    .loading .spinner-container {
      display: block;
    }

    .back {
      display: none;
      padding: 5px 8px;
      padding-right: 10px;
      border-radius: 4px;
      background-color: #444;
      margin-bottom: 8px;
      color: #aaa;
      font-size: 12px;
      -webkit-box-shadow: 0 0 5px #222;
      -moz-box-shadow: 0 0 5px #222;
      box-shadow: 0 0 5px #222;
    }

    .back.show {
      display: inline-block;
    }

    .back:hover {
      background-color: #4c4c4c;
      color: #ccc;
      text-decoration: underline;
      cursor: pointer;
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
    var backHash;
    var query = parseQueryParams();
    var model = createDataModel();

    function parseQueryParams() {
        var result = { };

        var query = window.location.search;
        if (query && query[0] === '?') {
            var parts = query.substring(1).split('&'), keyValue;
            for (var i = 0; i < parts.length; i++) {
                keyValue = parts[i].split('=');
                if (keyValue.length === 2) {
                    result[keyValue[0]] = decodeURIComponent(keyValue[1])
                }
            }
        }

        return result;
    }

    /* merges and sorts the different types of albums we support */
    function createDataModel() {
      var result = [];

      var add = function(arr, type) {
        for (var i = 0; i < arr.length; i++) {
          result.push({type: type, name: arr[i]});
        }
      };

      add(ALBUMS, 'album');
      add(SERIES, 'series');

      result = result.sort(function(a, b) {
        return a.name.localeCompare(b.name);
      });

      /* finds an item by name and type */
      result.find = function(name, type) {
        for (var i = 0; i < result.length; i++) {
          if (result[i].name === name && result[i].type === type) {
            return i;
          }
        }
        return -1;
      }

      /* given a start index and type, find the previous or next element of
      the same type. by default this will also wrap around to the beginning/end
      (i.e. is circular find). TODO: clean up if possible */
      result.adjacent = function(start, type, options) {
        start = start || 0;
        options = options || { };
        options.wrap = (options.wrap === undefined) ? true : options.wrap;
        var index, i;

        var iterate = function(from, to, dir) {
          if (dir === 'b') { /* backward */
            for (i = from; i >= to; i--) {
              if (model[i] && model[i].type === type) {
                return i;
              }
            }
          }
          else if (dir === 'f') { /* forward */
            for (i = from; i <= to; i++) {
              if (model[i] && model[i].type === type) {
                return i;
              }
            }
          }
        }

        if (options.reverse) {
          index = iterate(start - 1, 0, 'b');
          if (index === undefined && options.wrap) {
            index = iterate(model.length - 1, start - 1, 'b');
          }
        }
        else {
          index = iterate(start + 1, model.length - 1, 'f');
          if (index === undefined && options.wrap) {
            index = iterate(0, start - 1, 'f');
          }
        }

        return (index === undefined) ? start : index;
      };

      return result;
    }

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

      hash = "#" + hash;

      if (hash !== window.location.hash) {
        if (options && options.replace) {
          if (window.history.replaceState) {
            window.history.replaceState({ }, document.title, hash);
          }
          else {
            /* stolen from backbone */
            var href = location.href.replace(/(javascript:|#).*$/, '');
            location.replace(href + hash);
          }
        }
        else {
          window.location.hash = hash;
        }

        lastHash = hash;
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
      var album = model[index].name;
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

      var port = window.location.port;
      if (port && port !== "80" && port !== "443") {
        port = ":" + port;
      }
      else {
        port = "";
      }

      var result =
        window.location.protocol + '//' +
        window.location.hostname + port +
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
        var $iframe = null;
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
                  /* here the image has already updated internally, so we want
                  to just write the hash without triggering a message back to
                  the embedded gallery. stop polling, update, resume polling */
                  pollHash(false);
                  var hash = data.options.hash;
                  writeHash(currentAlbum + "/" + hash);
                  setSelectedImageForAlbum(currentAlbum, hash);
                  updateBackgroundColor();
                  pollHash();
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

          /* NOTE: if we set the source asynchronously, e.g. in the setTimeout
          just below, it will mess up our back stack in chrome. basically, when
          the user presses back the iframe will move back, instead of the outer
          frame */
          $iframe.attr('src', url);

          setTimeout(function() {
            /* setting src isn't good enough. in some browsers (older firefox,
            newer chrome), navigating forward (e.g. to an album series), then back
            will cause the wrong iframe url to be loaded. solution was found:
            http://stackoverflow.com/questions/2648053/preventing-iframe-caching-in-browser */
            $iframe[0].contentWindow.location.href.replace(url);
          });
        };

        var select = function(index) {
          var currentHashPath; /* only set if index is typeof string */

          var firstAlbumIndex = model.adjacent(-1, 'album');
          index = index || firstAlbumIndex;

          if (typeof index === 'string') {
            if (index.charAt(0) === '#') {
              index = index.substring(1);
            }

            var parts = index.split('/');
            index = parts[0];
            currentHashPath = parts[1];

            index = Math.max(firstAlbumIndex, model.find(index, 'album'));
          }

          var album = model[index].name;
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

            currentAlbum = model[index].name;

            /* write it back to the url */
            finalHashPath = currentHashPath || "";
            /* needs leading path char */
            if (finalHashPath.length && finalHashPath.charAt(0) !== "/") {
              finalHashPath = "/" + finalHashPath;
            }

            writeHash(model[index].name + finalHashPath);
          }
        };

        /* things like back button change the hash without us knowing,
        so we need to monitor for changes. ugh. */
        var pollHash = function(poll) {
          if (poll === false) {
            clearInterval(hashPollInterval);
            hashPollInterval = undefined;
          }
          else if (!hashPollInterval) {
            hashPollInterval = setInterval(function() {
              var currentHash = getHashFromUrl();
              if (currentHash !== lastHash) {
                select(currentHash);
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
          select(model.adjacent(getSelectedIndex(), 'album'));
        };

        var selectPrevAlbum = function() {
          select(model.adjacent(getSelectedIndex(), 'album', {reverse: true}));
        };

        var scrollToSelectedAlbum = function() {
          var $active = $('li.item.active');
          if ($active.length) {
            var pos = $active.position().top;
            $('.album-list').animate({scrollTop: pos});
          }
        };

        var initEventListeners = function() {
          $('.embedded').on('load', function() {
            $('.embedded').removeClass('hidden');
          });

          /* links in a album list open in the iframe. override the default
          action so url can still be right clicked and deep linked */
          $('.album-list').on('click', 'a', function(event) {
            event.preventDefault();
            var $el = $(event.currentTarget);
            var index = parseInt($el.attr("data-index"), 10) || 0;

            if (model[index].type === 'album') {
              select(index);
            }
            else if (model[index].type === 'series') {
              var url = $(event.currentTarget).attr('href');
              url += '#back:' + encodeURIComponent(getHashFromUrl());
              window.location.href = url;
            }
          });

          /* override default back behavior so we can add the back hash if
          we have one. maintains right click -> copy url functionality */
          $('.back').on('click', function(event) {
            event.preventDefault();
            var url = $(event.currentTarget).attr('href');
            url += backHash || '';
            window.location.href = url;
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
        };

        var getBackBaseUrl = function() {
            var path = window.location.pathname.split('/');
            while (path.length) {
              if (path.pop() !== '') {
                break;
              }
            }

            return path.join('/');
        };

        var render = function() {
          /* generate album list, add to DOM */
          var $albumList = $(".album-list");
          var item, caption, parts, template, html;
          for (i = 0; i < model.length; i++) {
              item = model[i];
              caption = item.name.replace(/_/g, " ");
              parts = parseListItem(caption);

              if (item.type === 'album') {
                template = parts.date ?
                  LIST_ITEM_TEMPLATE_WITH_DATE : LIST_ITEM_TEMPLATE;

                html = template
                  .replace("{{url}}", item.name)
                  .replace("{{caption}}", parts.caption)
                  .replace("{{index}}", i)
                  .replace("{{date}}", parts.date);
              }
              else if (item.type === 'series') {
                template = LIST_ITEM_SERIES_TEMPLATE;

                html = template
                  .replace("{{url}}", item.name + "?b=1")
                  .replace("{{caption}}", parts.caption)
                  .replace("{{index}}", i);
              }

              $albumList.append(html);
          }

          var initialHash = getHashFromUrl() || '';

          /* we were given a back route, parse it out */
          if (initialHash.indexOf("#back:") === 0) {
            backHash = decodeURIComponent(initialHash.split(":")[1]);
          }

          /* if we have ?b=1 or a #back: route show the back button. if we
          only have ?b=1 then the previously selected image will not be
          displayed. also, doing this users can still deep link to a nested
          series without an excessively long url */
          if (backHash || query.b) {
            var backUrl = getBackBaseUrl();
            $('.back').addClass('show');
            $('.back').attr('href', backUrl + '/');

            /* make things a bit more user friendly if we can figure out the
            name of the series we want to go back to */
            var prevPath = backUrl.split("/").pop();
            if (prevPath) {
              $('.back .back-text').text('back to ' + prevPath.replace(/_/g, " "));
            }
          }

          $('.no-albums').toggleClass('visible', !(ALBUMS.length > 0));

          select(getHashFromUrl());
          scrollToSelectedAlbum();
          pollHash();
        };

        initEventListeners();
        render();
    });
</script>

</head>

<body onselectstart="return false">
  <div class="left">
    <a href="#" class="back">
      <span class="link">
        <span class="arrow">&#x21fd;</span> <span class="back-text">back</span>
      </span>
    </a>
    <div class="albums">
      <div class="title">albums:</div>
      <ul class="album-list"></ul>
    </div>
  </div>
  <div class="main loading">
    <!-- iframe inserted dynamically -->
    <div class="spinner-container"></div>
    <div class="no-albums">
      <span class="no-albums-text">there are no albums here.
    </div>
  </div>
  <div class="footer">
    <a href="https://bitbucket.org/clangen/cgallery" target="_new">https://bitbucket.org/clangen/cgallery</a>
  </div>
</body>

</html>
