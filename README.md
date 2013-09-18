cgallery
=========

cgallery is a two-file gallery management system. it only uses two (2) php (yes, php) files.

```sh
git clone [repo]
ls cgallery/
```

### gallery.php:
- index for a single gallery -- a directory of images 
- automatically creates thumbnails

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