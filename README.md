cgallery
=========
**a simple photo management system** that works well with webkit, gecko, and ie10+.

cgallery is small, (relatively) self-contained photo gallery wirtten in php+js+css+html. it also uses jquery and spin.js. the most basic use case requires no configuration: just drop **album.php** in a directory of images and you're done.

more advanced use cases support nested sub-directories, and require minimal configuration. **series.php** is used to index a directory that contains directories of albums and other series. cgallery sources include an install script named **install.php** that can be used to manage these types of configurations.

cgallery also supports keyboard navigation and deep-linkable urls.

[a demo is available here.](http://casey.io/cgallery/demo)

### screenshot

cgallery looks something like this:

![windows screenshot](https://raw.githubusercontent.com/clangen/clangen-projects-static/master/cgallery/screenshots/cgallery01.png)


### requirements
**cgallery** requires a **web server** with **php** and the **gd** image library.

### download
```sh
git clone git@github.com:clangen/cgallery.git
```

### using install.php
the easiest way to use cgallery is **install.php**, a simple command-line utility that comes with the source code. install.php can be used to generate albums, series, and complex galleries that contain nested albums and series.

here's a list of arguments that install.php supports:

```
required:
  -p[ath to image dir]

optional:
  -m[ode] static|dynamic|local|uninstall     default: static
  -t[ype] series|album|auto                  default: auto
  -d[elete thumbnails]
  -s[ource directory]                        default:`cwd`
```

#### -p[ath]
* the destination directory. this is where cgallery will be installed.

#### -m[ode]
* **static**: scans the input directory for images, generates thumbnails, and creates static index.html files. whenever you add new images to your directory structure, you will need to run the script again to pick them up. if you're serving a lot of traffic, this is your best bet.
* **local**: a variant of static, this is used to generate galleries that will not be served by a webserver (i.e. viewed from a local filesystem).
* **dynamic**: symlinks to album.php, or series.php (depending on configuration). every time a user visits the page, the working directory will be re-scanned for new images will be thumbnails will be generated. this is most useful while building your gallery and during development.
* **uninstall**: recursively removes all index.php and index.html files in the specified path. note this will **not** delete thumbnails, but may be used in conjunction with the **-d** option.

#### -t[ype]
* **series**: creates a series in the specified path, then recursively creates series and albums from the sub-directories.
* **album**: creates an album in the specified path.
* **auto**: attempts to guess the type of installation.

#### -d[elete thumbnails]
* if specified, thumbnails will be deleted, recursively, from the specified path.

#### -s[ource directory]
* specifies where the cgallery sources live. by default, this is the current working directory.

#### simple example:

```sh
php install.php -p ~/public_html/images/
```

### manual "installation"

#### album.php:
* drop it in a directory of images and call it index.php
* uses php to scan its containing directory and generate thumbnails
* outputs is a single-file html app

for example: let's say you have a directory full of images called *my_album_2013-09*
```sh
cd my_album_2013-09
cp /path/to/cgallery/album.php ./index.php
```
now, every time someone vists http://yoursite.com/my_album_2013-09 they'll see a photo album instead of a directory listing.

you can (and **should** if you're serving a lot of traffic) use album.php to generate static html. using php to scan the filesystem for new thumbnails during every page load is a bad idea.

```sh
php album.php > index.html
```

#### series.php:
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
