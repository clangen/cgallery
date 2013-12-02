<?php
  /*
   * this is a simple, command-line php file that can be used to generate
   * a static gallery. this is useful if you're serving lots of traffic,
   * and don't want php to scan the filesystem for each request. works
   * recursively.
   *
   * arguments:
   *   --cgallery=/path/to/cgallery (i.e. where album.php and series.php live) 
   *   --path=/path/to/images 
   *   --clean=true|false (if true, thumbnails will be regenerated)
   */

  /* ugh, http://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php */
  function path() {
    return preg_replace('~[/\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_filter(func_get_args(), function($p) {
      return $p !== '';
    })));
  }

  /* resolves a full path, then follows the symlink if applicable */
  function resolve($path) {
    $path = realpath($path);
    if (is_link($path)) {
      $path = readlink($path);
    }
    return $path;
  }

  /* compile the specified directory as an album. will use album.php
  to generate a static index.html file */
  function compile_album($dir) {
    $html = path($dir, "index.html");
    $thumbs = path($dir, ".thumbs");
    global $album;
    global $clean;

    $oldcwd = getcwd();

    if (file_exists($html)) {
      print "  deleting old index.html\n";
      unlink($html);
    }

    if ($clean && is_dir($thumbs)) {
      print "  cleaning thumbnails\n";
      chdir($thumbs);

      foreach (glob("*.jpg") as $thumb) {
          unlink($thumb);
      }
    }

    chdir($dir);
    print "  generating album\n";
    exec("php $album > index.html");

    chdir($oldcwd);
    print "  done\n\n";
  }

  /* compile the specified directory as a series. will use series.php
  to generate a static index.html file */
  function compile_series($dir) {
    $html = path($dir, "index.html");
    global $series;
    global $clean;

    $oldcwd = getcwd();

    if (file_exists($html)) {
      print "  deleting old index.html\n";
      unlink($html);
    }

    chdir($dir);
    print "  generating series\n";
    exec("php $series > index.html");
    print "  done\n\n";

    /* compile each sub-directory. compile will figure out if the
    specified directory is an album or a nested series */
    foreach (glob("*", GLOB_ONLYDIR) as $current) {
      compile(path($dir, $current));
    }

    chdir($oldcwd);
  }

  /* given a directory, compiles it as an album or series */
  function compile($dir) {
    $type = file_exists(path($dir, ".series")) ? "series" : "album";

    print "processing: " . $dir . "\n";
    print "  type: " . $type . "\n";

    if ($type == "series") {
      compile_series($dir);
    }
    else {
      compile_album($dir);
    }
  }

  /* these are the input arguments we expect */
  $keys = array(
    "cgallery:",  /* not required: default=cwd */
    "path:",      /* required: path to photo directory tree */
    "clean:"      /* not required. if true, will re-generate thumbnails */
  );

  $options = getopt("", $keys);

  /* resolve working paths */
  $src = realpath($options["cgallery"] ?: ".");
  $clean = $options["clean"] == "true";
  $path = resolve(realpath($options["path"]));

  /* show configuration to the user */
  print "configuration:\n";
  print "  cgallery path: " . $src . "\n";
  print "  directory to index: " . $path . "\n";
  print "  clean: " . ($clean ? "true" : "false");
  print "\n\n";

  $album = resolve(path($src, "album.php"));
  $series = resolve(path($src, "series.php"));

  if (!file_exists($album) || !file_exists($series)) {
    print("ERROR: album.php or series.php not found in $src");
    exit(1);
  }

  if (!is_dir($path)) {
    print("ERROR: specified path $PATH does not appear to be a directory");
    exit(1);
  }

  /* kick the whole thing off! */
  compile($path);
?>