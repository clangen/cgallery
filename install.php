<?php
  /*
   * this is a simple, command-line php file that can be used to generate a gallery of
   * albums and series based on directory structure.
   *
   * arguments:
   *   -s /path/to/cgallery/src : where album.php and series.php live. default=cwd
   *   -p /path/to/images/dir   : location of root directory that should be indexed. required.
   *   -d true|false            : if true, thumbnails will be deleted, then re-created. optional.
   *   -m static|dynamic|local  : static means generic static html, dynamic will symlink php files
   *   -t series|album          : root type for this run
   */
  function err($msg, $code = 999) {
    if ($code == "quiet") {
      print "\naborted.\n\n";
      exit(998);
    }

    print "\n";

    print "ERROR: $msg\n\n";

    print "required:\n";
    print "  -p /path/to/images/dir   : gallery files will be copied here.\n\n";

    print "optional:\n";
    print "  -m static|dynamic|local  : generation mode. default=dynamic\n";
    print "  -t series|album          : page type. default=series\n";
    print "  -d true|false            : delete thumbnails. default=false\n";
    print "  -s /path/to/cgallery/src : default=`cwd`\n\n";

    print "aborted.\n\n";

    exit($code);
  }

  function fin() {
    print("successful\n\n");
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

  /* install the specified directory as an album. will use album.php
  to generate a static index.html file */
  function install_album($dir) {
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
      print "  note: this will silently create missing thumbnails\n";
      exec("php $album -m $mode > index.html");
      print "  created $mode index.html file\n\n";
    }
    else if ($mode == "dynamic") {
      exec("php $album");
      print "  linking $album to $php\n";
      print "  note: no thumbnails will be generated until the next page visit'\n\n";
      symlink($album, $php);
    }

    chdir($oldcwd);
  }

  /* install the specified directory as a series. will use series.php
  to generate a static index.html file */
  function install_series($dir) {
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
    }
    else if ($mode == "dynamic") {
      print "  linking $series to $php\n";
      symlink($series, $php);
    }

    /* install each sub-directory. install will figure out if the
    specified directory is an album or a nested series */
    foreach (glob("*", GLOB_ONLYDIR) as $current) {
      install(path($dir, $current));
    }

    chdir($oldcwd);
  }

  /* given a directory, installs it as an album or series */
  function install($dir, $override = null) {
    $type = file_exists(path($dir, ".series")) ? "series" : "album";

    print "type: " . $type . "\n";
    print "  path: " . $dir . "\n";

    /* use override if specified, otherwise we'll
    default to album. */
    $type = $override ?: $type;

    if ($type == "series") {
      install_series($dir);
    }
    else {
      install_album($dir);
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
    err("'-m $mode' is not valid. please use " . implode('|', $validModes));
  }

  $validTypes = array("album", "series");
  if (!in_array($type, $validTypes)) {
    err("'-t $type' is not valid. please use " . implode('|', $validTypes));
  }

  if (!$options["p"]) {
    err("-p is required, please specify a target directory.");
  }

  /* check env */
  $album = resolve(path($src, "album.php"));
  $series = resolve(path($src, "series.php"));

  if (!file_exists($album) || !file_exists($series)) { /* src dir invalid */
    err("album.php or series.php not found in $src", 2);
  }

  if (!is_dir($dst)) { /* target dir invalid */
    err("target path $dst does not appear to be a directory", 3);
  }

  /* show configuration to the user */
  print "\n";
  print "gallery will be installed as follows:\n\n";
  print "  install dir   : " . $options["p"] . "\n";
  print "  mode          : " . $mode . "\n";
  print "  type          : " . $type . "\n";
  print "  delete thumbs : " . ($rethumb ? "true" : "false") . "\n";
  print "  source dir    : " . $src . "\n\n";

  $warndir = $options["p"];

  print "warning: this will recursively delete/replace all index.html and index.php files inside '$warndir'\n\n";

  print "confirm? y/n ";

  /* give the user a chance to back out */
  $stdin = fopen('php://stdin', 'r');
  $confirm = strip(fgets($stdin));

  if ($confirm != "y") { /* must be exactly 'y' */
    err("user canceled", "quiet");
  }

  print("\n");

  /* kick the whole thing off! */
  install($dst, $type);
  fin();
?>