cgallery
=========
a simple photo management system.

####cgallery requires:
* a **web server** with **php** and the **gd** image library

```sh
git clone git@bitbucket.org:clangen/cgallery.git
```

####cgallery consists of:
* two php files:
    * **gallery.php**
    * **gallery_list.php**
* cdn versions of:
    * **jquery**
    * **spin.js**

### gallery.php:
* indexes a single gallery (defined by a directory of images)
* uses php to scan directory and generate thumbnails -- optionally on the fly

here's an example. let's say you have a directory full of images called *my_gallery_2013-09*
```sh
cd my_gallery_2013-09
ln -s ~/src/cgallery/gallery.php ./index.php
```
now, every time someone vists http://yoursite.com/my_gallery_2013-09 they'll see a photo gallery instead of a directory listing.

you can (and **should** if you're serving a lot of traffic) use gallery.php to generate static html. using php to scan the filesystem for new thumbnails during every page load is a bad idea.

```sh
php ~/cgallery/gallery.php > index.html
```

### gallery_list.php:
* indexes a collection of individual galleries (e.g. family photos)

```sh
mkdir galleries
ln -s ~/cgallery/gallery_list.php index.php
```

### faq:

> why does exist?

i like messing around with photos and just wanted a simple, directory-based management system.

> which browsers are supported?

current versions of chrome, firefox, and internet explorer are fully supported. older versions of chrome and firefox should also work fine.

> why use cgallery instead of [my current image hosting service]?

there's not a good reason, honestly. image hosting services are generally cheap/free and are easy to use. i personally like hosting my photos on my servers, and want viewers browse the way i want. i created cgallery for selfish reasons, but realized it may be useful to others.

> why php, and not something awesome like ruby/go/node/etc?

*sigh*. php is easy and it runs everywhere. it's also only used for thumbnail creation, so feel free to swap that part out with whatever you want. all of the more interesting logic is in javascript, anyway.

> why are are js and css included in the php files?

this was a conscience decision. although externalizing these resources would be easy, the scripts seem more useful when they are completely self-contained. just drop the php file in a directory and go. the consequence is that, in a couple cases, there is a small amount of code duplicated between gallery.php and gallery_list.php. yes, this makes maintenance slightly more annoying, but it also makes the system easier to use.

> why aren't the javascript and css minified?

it introduces (what what i believe to be) unnecessary release overhead for now. the php files are still small enough that saving a few kilobytes over the net is negligible -- especially compared to the size of the images that will be viewed. as the project continues to grow i will periodically re-evaluate this decision.

> i angrily disagree with some of your answers and think you're idiot!

the license allows you to change the implementation as you wish, so feel free.
