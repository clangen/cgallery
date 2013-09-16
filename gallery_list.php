<?php
  header("Content-Type: text/html; charset=utf-8");

  function getOutputPath() {
    return (getcwd() . "/.thumbs/");
  }

  function getOutputFilenameFor($filename) {
    return getOutputPath() . end(explode("/", $filename));
  }

  function getGalleryList() {
    $dir = opendir(".");
    $galleries = array();

    while (($file = readdir($dir)) !== false) {
      if ($file != "." && $file != ".." && is_dir($file)) {
          if (!is_file($file . "/.hidden")) {
            array_push($galleries, $file);
          }
      }
    }

    sort($galleries);
    return $galleries;
  }

  $galleryList = getGalleryList();
?>

<html>

<head>

<title>galleries</title>

<style type="text/css">
    body {
      font-family: sans;
      background-color: rgb(46, 46, 46);
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
    }

    ul, li {
      margin: 0;
      padding: 0;
      margin-bottom: 10px;
      list-style-type: none;
    }

    .item a {
      color: #aaa;
      font-size: 15px;
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
      font-size: 32px;
      color: #666;
      text-shadow: 0 0 8px #222;
      text-decoration: underline;
      padding-bottom: 8px;
    }

    .date {
      font-size: 60%;
      color: #666;
    }

    .gallery-list {
      position: absolute;
      top: 40px;
      bottom: 0;
      left: 0;
      right: 0;
      overflow-x: hidden;
      overflow-y: auto;
       -webkit-overflow-scrolling: touch;
      padding-left: 0px;
    }

    .embedded {
      width: 100%;
      height: 100%;
      border: 0;
    }

    .embedded.hidden {
      visibility: hidden;
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

    var GALLERIES = [
      <?php
        global $galleryList; /* generated above */
        foreach ($galleryList as $gallery) {
          printf('"' . $gallery . '",' . "\n");
        }
      ?>
    ];

    var currentGallery = '';
    var lastHash;

    function writeHash(hash) {
      lastHash = "#" + hash;

      if (hash !== window.location.hash) {
        window.location.hash = hash;
      }
    }

    function getHashFromUrl() {
      /* some browsers decode the hash, we don't want that */
      return "#" + (window.location.href.split("#")[1] || "");
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
      var result =
        window.location.protocol + '//' +
        window.location.hostname + '/' +
        'photos/' + GALLERIES[index];

      if (hash) {
        result += ("#" + hash);
      }

      return result;
    }

    $(window).on('message', function(event) {
      event = event.originalEvent || event;
      var data = event.data;
      if (data && data.message === 'hashChanged' && currentGallery) {
        writeHash(currentGallery + "/" + data.options.hash);
      }
    });

    $(document).ready(function() {
        var $iframe = $('.embedded');
        var hashPollInterval;
        var currentHashPath;

        var select = function(index) {
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
              the back button */
              var el = $iframe.get(0);
              if (el && el.contentWindow && el.contentWindow.postMessage) {
                var msg  = {
                  message: 'changeHash',
                  options: { hash: currentHashPath }
                };

                el.contentWindow.postMessage(msg, "*");
              }
            }
          }
          else {
            /* mark active state */
            var $items = $('.gallery-list .item');
            $items.removeClass('active');
            $items.eq(index).addClass('active');

            /* load */
            $iframe.addClass('hidden');
            $iframe.attr("src", urlAtIndex(index, currentHashPath));

            /* avoid white flash by hiding the iframe for a short
            period of time */
            setTimeout(function() {
              $iframe.removeClass('hidden');
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

        /* generate gallery list, add to DOM */
        var $list = $(".gallery-list");
        var gallery, caption, parts, template, html;
        for (var i = 0; i < GALLERIES.length; i++) {
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

            $list.append(html);
        }

        select(getHashFromUrl());
        scrollToSelectedGallery();
        pollHash();
    });

</script>

</head>

<body onselectstart="return false">
  <div class="left">
    <div class="title">albums:</div>
    <ul class="gallery-list"></ul>
  </div>
  <div class="main">
    <iframe class="embedded hidden"></iframe>
  </div>
</body>
</html>
