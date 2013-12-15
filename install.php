<?php
  /*
   * cgallery v2.2
   *
   * install.php:
   *
   * this is a simple, command-line php file that can be used to generate a
   * gallery of albums and series based on directory structure.
   *
   * arguments:
   *   -p[ath to image dir]
   *   -m[ode] static|dynamic|local|uninstall     default: static
   *   -t[ype] series|album|auto                  default: auto
   *   -d[elete thumbnails]
   *   -s[ource directory]                        default:`cwd`
   */
  function err($msg, $code = 999) {
    if ($code == "quiet") {
      print "\naborted.\n\n";
      exit(998);
    }

    print "\n";

    print "ERROR: $msg\n\n";

    print "required:\n";
    print "  -p[ath to image dir]\n\n";

    print "optional:\n";
    print "  -m[ode] static|dynamic|local|uninstall     default: static\n";
    print "  -t[ype] series|album|auto                  default: auto\n";
    print "  -d[elete thumbnails]\n";
    print "  -s[ource directory]                        default:`cwd`\n\n";

    print "aborted.\n\n";

    exit($code);
  }

  /* ugh, http://stackoverflow.com/questions/1091107/how-to-join-filesystem-path-strings-in-php.
  this is a path join function, it will basically trim up and slash delimit the args */
  function path() {
    return strip(preg_replace('~[/\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, array_filter(func_get_args(), function($p) {
      return $p !== '';
    }))));
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

  function exec_to_stdout($cmd) {
    /* http://stackoverflow.com/questions/8370628/php-shell-exec-with-realtime-updating */
    if(($fp = popen($cmd, "r"))) {
        while(!feof($fp)){
            echo fread($fp, 1024);
            flush();
        }

        fclose($fp);
    }
  }

  $dirstack = array();

  function pushdir($dir) {
    global $dirstack;
    array_push($dirstack, getcwd());
    chdir($dir);
  }

  function popdir() {
    global $dirstack;
    $dir = array_pop($dirstack);
    chdir($dir);
    return $dir;
  }

  function delete_if_auto_series($dir) {
    $f = path($dir, '.series');
    if (is_file($f)) {
      if (strip(file_get_contents($f)) == "auto") {
        check_rm($f);
      }
    }
  }

  function write_auto_series($dir) {
    $f = path($dir, '.series');
    if (!is_file($f)) {
      print "  writing .series file to $f\n";
      file_put_contents($f, "auto");
    }
  }

  /* install the specified directory as an album. will use album.php
  to generate a static index.html file */
  function install_album($dir) {
    $html = path($dir, "index.html");
    $php = path($dir, "index.php");
    global $album;
    global $rethumb;
    global $mode;

    pushdir($dir);

    $thumbs = path($dir, ".thumbs");
    if ($rethumb && is_dir($thumbs)) {
      pushdir($thumbs);

      foreach (preg_grep('/(\.jpg|\.png|\.gif)$/i', glob("*")) as $thumb) {
          check_rm($thumb);
      }

      popdir();
    }

    if ($mode == "static" || $mode == "local") {
      print "  generating thumbnails to $thumbs using $album\n";
      exec_to_stdout("php $album -t 1"); /* create thumbnails (and log to stdout) */
      exec("php $album -m $mode > index.html"); /* generate html */
      print "  created $mode index.html file\n\n";
    }
    else if ($mode == "dynamic") {
      exec("php $album");
      print "  linking $album to $php\n";
      print "  note: no thumbnails will be generated until the next page visit\n\n";
      symlink($album, $php);
    }

    popdir();
  }

  /* install the specified directory as a series. will use series.php
  to generate a static index.html file */
  function install_series($dir) {
    $html = path($dir, "index.html");
    $php = path($dir, "index.php");
    global $series;
    global $rethumb;
    global $mode;

    pushdir($dir);

    /* install each sub-directory. install will figure out if the
    specified directory is an album or a nested series. IMPORTANT,
    this must occur before generating the index file so subseries
    can be marked correctly */
    foreach (glob("*", GLOB_ONLYDIR) as $current) {
      $current = path($dir, $current);
      $type = guess_type($current);
      install($current, $type);
    }

    if ($mode == "static" || $mode == "local") { /* generate a static html file. this
      will implicitly trigger a thumbnail refresh */
      print "  generating $mode $html using $series\n";
      exec("php $series -m $mode > $html"); /* ugh, but it works and is easy */
    }
    else if ($mode == "dynamic") {
      print "  linking $series to $php\n";
      symlink($series, $php);
    }

    if ($mode !== "uninstall") {
      write_auto_series($dir);
    }

    popdir();
  }

  /* given a directory, installs  an album or series */
  function install($dir, $override = null) {
    $type = file_exists(path($dir, ".series")) ? "series" : "album";

    print "type: " . $type . "\n";
    print "  path: " . $dir . "\n";

    /* use override if specified, otherwise we'll
    default to album. */
    $type = $override ?: $type;

    check_rm($dir . "/index.html");
    check_rm($dir . "/index.php");

    delete_if_auto_series($dir);

    if ($dir[0] != ".") {
      if ($type == "series") {
        install_series($dir);
      }
      else {
        install_album($dir);
      }
    }
  }

  function guess_type($dir) {
    if (is_file(path($dir, '.series'))) {
      return "series";
    }

    $type = "album";

    pushdir($dir);

    /* if the specified directory has any sub-directories with
    images, assume series. otherwise, album */
    foreach (glob("*", GLOB_ONLYDIR) as $current) {
      if ($current[0] != ".") {
        $current = path($dir, $current);

        pushdir($current);
        $images = preg_grep('/(\.jpg|\.png|\.gif)$/i', glob("*"));
        popdir();

        if (count($images) > 0) {
          $type = "series";
          break;
        }
      }
    }

    popdir();

    return $type;
  }

  /* these are the input arguments we accept */
  $options = getopt("s:p:dm:t:");

  /* resolve working paths */
  $src = realpath($options["s"] ?: ".");
  $rethumb = array_key_exists("d", $options);
  $dst = resolve(realpath($options["p"]));
  $mode = $options["m"] ?: "static";
  $type = $options["t"] ?: "auto";

  $validModes = array("dynamic", "static", "local", "uninstall");
  if (!in_array($mode, $validModes)) {
    err("'-m $mode' is not valid. please use " . implode('|', $validModes));
  }

  if (!$options["p"]) {
    err("-p is required, please specify a target directory.");
  }

  if (!is_dir($dst)) {
    err("specified path $dst is invalid");
  }

  if ($type == "auto") {
    $type = guess_type($dst);
  }

  $validTypes = array("album", "series");
  if (!in_array($type, $validTypes)) {
    err("'-t $type' is not valid. please use " . implode('|', $validTypes));
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

  print "warning: this will recursively delete/replace all index.html and index.php files inside '$warndir'. ";

  if ($rethumb) {
    print "all images contained within any '.thumbs' sub-directories will also be deleted.";
  }

  print "\n\nconfirm? y/n ";

  /* give the user a chance to back out */
  $stdin = fopen('php://stdin', 'r');
  $confirm = strip(fgets($stdin));

  if ($confirm != "y") { /* must be exactly 'y' */
    err("user canceled", "quiet");
  }

  print("\n");

  /* kick the whole thing off! */
  install($dst, $type);
  print("successful\n\n");
?>
