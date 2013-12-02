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

  function check_rm($file) {
    if (file_exists($file)) {
      print "  deleting " . $file . "\n";
      unlink($file);
    }
  }

  /* compile the specified directory as an album. will use album.php
  to generate a static index.html file */
  function compile_album($dir) {
    $html = path($dir, "index.html");
    $php = path($dir, "index.php");
    $thumbs = path($dir, ".thumbs");
    global $album;
    global $clean;
    global $mode;

    $oldcwd = getcwd();

    check_rm($html);
    check_rm($php);

    if ($clean && is_dir($thumbs)) {
      print "  cleaning thumbnails\n";
      chdir($thumbs);

      foreach (glob("*.jpg") as $thumb) {
          unlink($thumb);
      }
    }

    chdir($dir);

    if ($mode == "static") {
      print "  generating thumbnails and index.html using album.php\n";
      exec("php $album > index.html");
    }
    else if ($mode == "dynamic") {
      print "  generating thumbnails using album.php\n";
      exec("php $album");
      print "  linking index.php to album.php\n";
      symlink($album, $php);
    }

    chdir($oldcwd);
    print "  done\n\n";
  }

  /* compile the specified directory as a series. will use series.php
  to generate a static index.html file */
  function compile_series($dir) {
    $html = path($dir, "index.html");
    $php = path($dir, "index.php");
    global $series;
    global $clean;
    global $mode;

    $oldcwd = getcwd();

    check_rm($html);
    check_rm($php);

    chdir($dir);

    if ($mode == "static") {
      print "  generating index.html using series.php\n";
      exec("php $series > index.html");
      print "  done\n\n";
    }
    else if ($mode == "dynamic") {
      print "  linking index.php to series.php\n";
      symlink($series, $php);
      print "  done\n\n";
    }

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
    "clean:",     /* not required. if true, will re-generate thumbnails */
    "mode:"       /* not required. values=(static, dynamic). default=static */
  );

  $options = getopt("", $keys);

  /* resolve working paths */
  $src = realpath($options["cgallery"] ?: ".");
  $clean = $options["clean"] == "true";
  $path = resolve(realpath($options["path"]));
  $mode = $options["mode"] == "dynamic" ? "dynamic" : "static";

  /* show configuration to the user */
  print "configuration:\n";
  print "  cgallery path: " . $src . "\n";
  print "  directory to index: " . $path . "\n";
  print "  clean: " . ($clean ? "true" : "false") . "\n";
  print "  mode: " . $mode . "\n";
  print "\n";

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
