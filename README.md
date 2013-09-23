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

> why use cgallery instead of <my current image hosting service>?

there's not a good reason, honestly. image hosting services are generally cheap/free and are easy to use. i personally like hosting my photos on my own servers, and want viewers browse them the way i want. i created cgallery for very selfish reasons. 

> why php, and not something awesome like ruby/go/node/etc?

*sigh*. php is easy and it runs everywhere. it's also only used for thumbnail creation, so feel free to swap this part out with you want. all of the more complicated logic is in javascript anyway.

> why are are js and css included in the php files?

this was a conscience decision. although externalizing these resources would be trivial, the scripts seem more useful when they are completely self-contained. just drop the php file in a directory and go. the consequence is that, in a couple cases, there is a small amount of code duplicated between gallery.php and gallery_list.php. yes, this makes maintenance slightly more annoying, but it also makes the system easier to use. 

> i angrily disagree with the above answer and think you're an idiot!

the license allows you to change the implementation as you wish, so feel free!

> why aren't the javascript and css minified?

it introduces (what what i believe to be) unnecessary release overhead. the php files are still small enough that saving a few kilobytes over the wire is negligible -- especially compared to the size of the images that will be viewed.