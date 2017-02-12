# South High Marathon Dance Photo Orders

See [shmd.org](http://shmd.org) to learn what SHMD is all about.

One of the many groups of participating volunteers is the photo crew. Numerous
photographers take thousands of photos of every aspect of the dance. These are
funneled back to a central server, where they are distributed to printing
stations for purchase by attendees. All proceeds go straight to the SHMD fund
pool.

In 2016, with little notice, the photo crew was informed that their usual set
up would no longer be possible to support. Due to lack of sufficient power
supply for all the printing and networking hardware, the ordering and printing
stations would have to be split into two separate locations. This implied the
need the create some mechanism to relay print orders from the former to the
latter. This application was hastily thrown together in a weekend in an attempt
to address that requirement.

## Setup

This is a PHP app, just point your web server's document root to the `public`
subdir. Make sure that the `orders` and `orders/archive` directories are
writable by the web server user. Run `composer install` to fetch dependencies.
In order to use the facial recognition features, you'll need an Amazon AWS
account. Copy `config.json.dist` to `config.json` and edit as desired.

### Galleries

The app supports an arbitrary number of galleries. The intent is to separate
photos into groups by event and/or time, so that patrons will have an easier
time finding photos to purchase. To create a gallery, create a subdirectory
under the `staging` directory. Use lowercase names with no spaces or special
characters. The directory name will be used as the URL slug for the gallery.
Create another subdirectory with the same name under the `public/photos`
directory. Make sure it's readable by the web server user. If you would like
the app to show a more descriptive name for the gallery, you may create a text
file `public/photos/<gallery>/title` containing a descriptive gallery title.

### Photos

As raw photos from the photographers are acquired, they should be copied into
the appropriate `staging/<gallery>` directory. This is the *only* location that
photos should be manually placed. Note that these files can be quite large and
will never be served raw. At any time, you may run the `bin/resize` script.
This will scan for newly added photos in `staging`, resize them to something
more appropriate for the web, and copy them to `public/photos/<gallery>`.
Photos will not appear in the app until after they have been so processed.
Note that the files in `public/photos/<gallery>` must be readable by the web
server process user.

### Printing

The app supports printing of order slips via a USB ESC/POS receipt printer.
The printer must be connected to the same machine running the server. It is
expected to be connected via `/dev/usb/lp0`.

## Usage

The machine acting as server should be located physically close to the printing
stations so that the printing personnel have access to the order slips. Once
photos are processed with the `bin/resize` script, they will appear in the app
and be available for purchase. Client machines need only a webserver. Laptops,
Chromebooks, iPads, and even SmartBoards work well. Dance patrons and/or sales
agents can use the app to browse the galleries, and select individual photos
for purchase. Submitting the order form will create a new order. If a printer
is connected, an order slip will immediately print. Workers at the printing
stations can grab slips as they appear, print the requested photos, and run the
order out to the patron. Alternatively, print workers may browse the `/orders`
URL to watch for incoming orders. Once fulfilled, they may click the Complete
button, which will archive the order and remove it from the order page so that
it does not get printed twice.

## Data

Pending orders are stored in the `orders` subdirectory. There will be one file
per order, named with a unique hash, and formatted in plain text JSON. Once an
order is fulfilled, the file will be moved to the `orders/archive` directory.
These files can be easily parsed for statistics once the event is complete.
Note that if order slips are pulled from a receipt printer rather than the
`/orders` URL, then the order JSON files will never be moved to the
`orders/archive` directory.
