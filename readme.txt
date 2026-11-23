=== GemBooking Simple Booking Form ===
Contributors: kodezen, tusharimran
Tags: bookingform, booking, appointment, scheduling, calendar
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a booking form to any page. Visitors pick a date and time, you confirm by email. No setup screens, no double bookings.

== Description ==

**GemBooking Simple Booking Form** adds one block to the block editor. Drop it on a page, choose your hours, and your visitors can book a time with you. That is the whole setup.

There is no separate settings screen, no dashboard and no account to create. Everything is set in the block's sidebar, and every booking is handled from the emails you receive.

= How it works =

1. **Add the block** to any page or post.
2. **Set your availability** in the block sidebar: days, hours, duration and more.
3. **Visitors book** by choosing a date and a time, then entering their name and email.
4. **You get an email** with a Confirm button and a Decline button. No login needed.
5. **The customer is told** straight away, and can view, reschedule or cancel from a link in their email.

= For your visitors =

* A clean calendar and time picker that works on phones, tablets and desktops.
* Times shown in the form's timezone, with a 12-hour or 24-hour switch.
* Name and email, plus an optional (or required) phone number.
* A confirmation email with links to **view**, **reschedule** or **cancel** the booking.
* Supports right-to-left languages.

= For you =

* **Block sidebar settings.** Event title, form layout (step by step, or everything at once), duration (15, 30, 45, 60 minutes or your own), available weekdays, start and end hours, minimum notice, buffer between bookings, how far ahead people can book, and timezone (searchable).
* **Two approval modes.** Choose **Admin confirms by email** (each booking waits as Pending until you click Confirm or Decline) or **Confirm automatically**.
* **Your brand.** Pick an accent color for the form and the emails, add a logo and write your own footer text.
* **Several forms.** Each block on your site keeps its own bookings, so one form never blocks another.

= No double bookings =

A time slot can never be booked twice, even if two people click at the same moment. A declined, cancelled or unanswered request frees its slot again. If you do not answer a request within **48 hours**, it is cancelled, the customer is told, and the time is open for others.

= Emails =

Booking emails are sent with WordPress's own mail system, in HTML or plain text.

* **To you:** new booking request, new confirmed booking, rescheduled booking, cancelled booking.
* **To your customer:** request received, booking confirmed, rescheduled, declined, cancelled, and not approved in time.
* Set the sender name, sender address and the address that receives booking requests.
* Send yourself a **test email** to see how it looks.
* Links to confirm, decline or cancel open a confirmation page first, so email scanners that pre-load links cannot change a booking by accident.

= Protection from spam and abuse =

* Hidden honeypot field and a minimum fill time.
* Limits on how many requests one visitor or one email address can make.
* The form's settings are signed by your site, so visitors cannot change your hours or duration.
* Email links are signed and expire on their own.

= Works with your theme =

The form uses WordPress's default system font and your accent color, and is built to keep its look inside any theme.

= Privacy =

Bookings store the name, email and optional phone number of the person who booked, plus a salted hash of their IP address for spam control.

* Data can be exported and erased with the WordPress privacy tools. Erasing anonymises the booking but keeps the time slot, so it cannot be double booked.
* Nothing is sent to external services.
* No cookies are set.

= Source code and build =

The JavaScript and CSS in `assets/build/` are compiled from the human-readable source included in this plugin:

* `dev_gsbf/` – React (block editor and front-end form) and Sass source.
* `package.json` and `webpack.config.js` – build configuration, using [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts).

To rebuild the compiled files, run `npm install` and then `npm run build` in the plugin folder.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install it from the Plugins screen, then activate it.
2. Edit a page and add the **GemBooking Simple Booking Form** block.
3. Set your availability in the block sidebar.
4. Open **Email notifications > Configure email** and set the address that receives booking requests.
5. Publish the page and make a test booking.

== Frequently Asked Questions ==

= Where do I see my bookings? =

In the emails you receive. This plugin has no bookings screen.

= Do I have to log in to confirm a booking? =

No. The Confirm and Decline buttons in the email work without logging in.

= What happens if I do not answer a booking request? =

After 48 hours the request is cancelled, the customer is told, and the time slot is free again.

= Can customers change or cancel their booking? =

Yes. Their emails contain links to view, reschedule or cancel the booking. In **Admin confirms by email** mode, a rescheduled time goes back to you for approval.

= Can I confirm bookings automatically? =

Yes. In **Email notifications > Configure email**, set **Approval** to **Confirm automatically**.

= Can I have more than one booking form? =

Yes. Add the block as many times as you like. Each form has its own settings and its own bookings.

= Which timezone is used? =

Each form has its own timezone, set in the block sidebar. Times are shown in that timezone, and the form says so under the calendar.

= Can two people book the same time? =

No. A time slot is locked as soon as it is booked, so it cannot be taken twice.

= Does the plugin send data to other services? =

No. Nothing is sent to external services and no cookies are set.

= Is it translation ready? =

Yes. All text can be translated, and a translation template is included in the `languages` folder.

== Changelog ==

= 1.0.0 =
* First release.
