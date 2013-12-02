cgallery
=========
**a simple photo management system** that works well with webkit, gecko, and ie10+.

cgallery is written in less than 2000 lines of php+js+css+html. it also uses jquery and spin.js. the most basic use case requires no configuration: just drop **album.php** in a directory of images and you're done.

more advanced use cases support nesting, and require minimal configuration. **series.php** is used to index a directory that contains directories of albums and other series. cgallery sources include a build script (**build.php**) that can be used to manage these types of configurations.

cgallery also supports keyboard navigation and deep-linkable urls.

[try out a demo here.](http://casey.io/cgallery/demo)

####cgallery requires:
* a **web server** with **php** and the **gd** image library

```sh
git clone git@bitbucket.org:clangen/cgallery.git
```

### album.php:
* drop it in a directory of images and call it index.php
* uses php to scan its containing directory and generate thumbnails
* outputs is a single-file html app

for example: let's say you have a directory full of images called *my_album_2013-09*
```sh
cd my_album_2013-09
mv album.php index.php
```
now, every time someone vists http://yoursite.com/my_album_2013-09 they'll see a photo album instead of a directory listing.

you can (and **should** if you're serving a lot of traffic) use album.php to generate static html. using php to scan the filesystem for new thumbnails during every page load is a bad idea.

```sh
php album.php > index.html
```

### series.php:
* indexes a directory of albums and/or series.
* sub-directories are excluded from a series if they contain an empty file called **.hidden**.
* a  series may also contain other series. this is detected when the sub-directory has an empty file called **.series**.
* output is a single-file html app

```sh
mkdir family_photos
touch family_photos/.series
ln -s ~/cgallery/series.php index.php
```

### faq:

> why does this even exist?

i like messing around with photos and just wanted a simple, directory-based management system.

> which browsers are supported?

all major browser revisions less than a year or two old should work fine. current versions of chrome, safari, firefox, and internet explorer are tested regularly and should be fully supported.

> why use cgallery instead of [my current image hosting service]?

there's not a good reason, honestly. image hosting services are generally cheap/free and are easy to use. i personally like hosting my photos on my servers, and want viewers browse the way i want. i created cgallery for selfish reasons, but realized it may be useful to others.

> why php, and not something awesome like ruby/go/node/etc?

*sigh*. php is easy and it runs everywhere. it's also only used for thumbnail creation, so feel free to swap that part out with whatever you want. all of the more interesting logic is in javascript, anyway.

> why are are js and css included in the php files?

this was a conscience decision. the scripts seem more useful when they are completely self-contained. just drop the php file in a directory and go. the consequence is that, in a couple cases, there is a small amount of code duplicated between album.php and series.php. yes, this makes maintenance slightly more annoying, but it also makes the system easier to use.

> why aren't the javascript and css minified?

it introduces (what what i believe to be) unnecessary release overhead for now. the php files are still small enough that saving a few kilobytes over the net is negligible -- especially compared to the size of the images that will be viewed. as the project continues to grow i will periodically re-evaluate this decision.

> i angrily disagree with some of your answers and think you're idiot!

the license allows you to change the implementation as you wish, so feel free...
