cgallery
=========

cgallery is a very simple photo gallery management system. it only uses two (2) php (yes, php) files.

```sh
git clone git@bitbucket.org:clangen/cgallery.git
```

### gallery.php:
- index page for a single gallery, to be dropped into a directory of images 
- automatically creates thumbnails and generates layout

```sh
mkdir my_gallery_2013-09
cd my_gallery_2013-09
ln -s ~/cgallery/gallery.php index.php
```

- can also be used to generate static index.html files

```sh
php ~/cgallery/gallery.php > index.html
```

### gallery_list.php:
- indexes a directory of galleries

```sh
mkdir galleries
ln -s ~/cgallery/gallery_list.php index.php
```