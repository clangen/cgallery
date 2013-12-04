<?php
  /*
   * this is a simple, command-line php file that can be used to generate
   * albums and series based on directory structure. this is especially
   * useful if you're serving lots of traffic and don't want php to scan
   * the filesystem for updated images on each request. the basic logic
   * is as follows:
   *
   * arguments:
   *   -s /path/to/cgallery/src  : where album.php and series.php live. default=cwd
   *   -p /path/to/images/dir      : location of root directory that should be indexed. required.
   *   -d true|false         : if true, thumbnails will be deleted, then re-created. optional.
   *   -m static|dynamic|local  : static means generic static html, dynamic will symlink php files
   *   -t series|album          : root type for this run
   */

  function err($msg, $code = 999) {
    print "\n  * $msg *\n\naborted.\n\n";
    exit($code);
  }

  function fin() {
    print("\nfinished\n\n");
  }

  /* ugh, http://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php.
  this is a path join function, it will basically trim up and slash delimit the argv */
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

  function strip($str) {
    return trim(preg_replace('/\s\s+/', ' ', $str));
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
    global $rethumb;
    global $mode;

    $oldcwd = getcwd();

    check_rm($html);
    check_rm($php);

    if ($rethumb && is_dir($thumbs)) {
      print "  re-generating thumbnails\n";
      chdir($thumbs);

      foreach (glob("*.jpg") as $thumb) {
          check_rm($thumb);
      }
    }

    chdir($dir);

    if ($mode == "static" || $mode == "local") {
      print "  generating thumbnails to $thumbs using $php\n";
      print "    note: this will silently create missing thumbnails\n";
      exec("php $album -m $mode > index.html");
      print "  created $mode index.html file\n";
    }
    else if ($mode == "dynamic") {
      exec("php $album");
      print "  linking $album to $php\n";
      print "    note: no thumbnails will be generated until the next page visit'\n";
      symlink($album, $php);
    }

    chdir($oldcwd);
    print "  done.\n\n";
  }

  /* compile the specified directory as a series. will use series.php
  to generate a static index.html file */
  function compile_series($dir) {
    $html = path($dir, "index.html");
    $php = path($dir, "index.php");
    global $series;
    global $rethumb;
    global $mode;

    $oldcwd = getcwd(); /* will restore at the end... */

    check_rm($html);
    check_rm($php);

    chdir($dir);

    if ($mode == "static" || $mode == "local") { /* generate a static html file. this
      will implicitly trigger a thumbnail refresh */
      print "  generating $mode index.html using series.php\n";
      exec("php $series -m $mode > index.html"); /* ugh, but it works and is easy */
      print "  done\n\n";
    }
    else if ($mode == "dynamic") {
      print "  linking $series to $php\n";
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
  function compile($dir, $override = null) {
    $type = file_exists(path($dir, ".series")) ? "series" : "album";

    print "compiling: " . $dir . "\n\n";
    print "  type: " . $type . "\n";

    /* use override if specified, otherwise we'll
    default to album. */
    $type = $override ?: $type;

    if ($type == "series") {
      compile_series($dir);
    }
    else {
      compile_album($dir);
    }
  }

  /* these are the input arguments we accept */
  $options = getopt("s:p:d:m:t:");

  /* resolve working paths */
  $src = realpath($options["s"] ?: ".");
  $rethumb = $options["c"] == "true";
  $dst = resolve(realpath($options["p"]));
  $mode = $options["m"] ?: "static";
  $type = $options["t"] ?: "series";

  $validModes = array("dynamic", "static", "local");
  if (!in_array($mode, $validModes)) {
    err("ERROR: --mode $mode is not valid. please use dynamic, static, or local");
  }

  /* show configuration to the user */
  print "\nbuild will use the following config:\n\n";
  print "  src dir: " . $src . "\n";
  print "  dst dir: " . $options["p"] . "\n";
  print "  re-generate thumbnails? " . ($rethumb ? "yes" : "no") . "\n";
  print "  type: " . $type . "\n";
  print "  mode: " . $mode . "\n";

  if (!$options["p"]) {
    err("ERROR: -p is required, please specify a target directory.");
  }

  print "\nthis will overwrite any existing index.html and index.php files\n";

  print "\nconfirm? y/n ";

  /* give the user a chance to back out */
  $stdin = fopen('php://stdin', 'r');
  $confirm = strip(fgets($stdin));

  if ($confirm != "y") { /* must be exactly 'y' */
    err("user canceled", 1);
  }

  print("\n");

  /* check env */
  $album = resolve(path($src, "album.php"));
  $series = resolve(path($src, "series.php"));

  if (!file_exists($album) || !file_exists($series)) { /* src dir invalid */
    err("ERROR: album.php or series.php not found in $src", 2);
  }

  if (!is_dir($dst)) { /* target dir invalid */
    err("ERROR: target path $dst does not appear to be a directory", 3);
  }

  /* kick the whole thing off! */
  compile($dst, $type);
  fin();
?>
