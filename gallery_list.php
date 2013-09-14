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
      overflow-x: hidden;
      overflow-y: auto;
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

    .gallery-list {
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
</style>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>

<script type="text/javascript">
    var LIST_ITEM_TEMPLATE =
        '<li class="item gallery-name">' +
          '<a href="{{url}}" data-index="{{index}}">' +
            '{{caption}}' +
          '</div>' +
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

        $('.embedded').on('load', function() {
          $('.embedded').removeClass('hidden');
        });

        $('.gallery-list').on('click', 'a', function(event) {
          event.preventDefault();
          var $el = $(event.currentTarget);
          var index = parseInt($el.attr("data-index"), 10);
          select(index);
        });

        var $list = $(".gallery-list");
        for (var i = 0; i < GALLERIES.length; i++) {
            var gallery = GALLERIES[i];
            var caption = gallery.replace(/_/g, " ");
            var html = LIST_ITEM_TEMPLATE
                .replace("{{url}}", gallery)
                .replace("{{caption}}", caption)
                .replace("{{index}}", i);

            $list.append(html);
        }

        function pollHash() {
            if (!hashPollInterval) {
                hashPollInterval = setInterval(function() {
                    var currentHash = getHashFromUrl();
                    if (currentHash !== lastHash) {
                      select(currentHash);
                      lastHash = currentHash;
                    }
                }, 250);
            }
        }

        select(getHashFromUrl());
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
