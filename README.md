cgallery
=========

cgallery is a simple photo management system. it only uses two php files with no dependencies other than  CDN-hosted versions of jQuery and spin.js.

```sh
git clone git@bitbucket.org:clangen/cgallery.git
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

### faq:

> why does this even exist?

practice. i like messing around with photos and just wanted a simple, directory-based management system. 

> why php, and not something awesome like ruby/go/node/etc?

*sigh*. php is easy and it runs everywhere. it's also only used for thumbnail creation, so feel free to swap out the backend with anything you want.

