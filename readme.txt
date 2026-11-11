=== GemBooking Simple Booking Form ===
Contributors: kodezen
Tags: block, booking, appointment, scheduling, calendar
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple booking form block. Visitors pick a date and a time, you confirm by email. No setup screens, double-booking protected.

== Description ==

GemBooking Simple Booking Form adds one block, **GemBooking Simple Booking Form**, to the block editor.

* Visitors choose a date and a time, then enter their name, email and an optional phone number.
* You set the event, duration, weekdays, hours, notice, buffer and timezone right in the block sidebar.
* Choose **Admin confirms by email** (the booking waits as Pending until you click Confirm or Decline in the email, no login needed) or **Confirm automatically**.
* A time slot can never be booked twice. A declined, cancelled or unanswered request (48 hours) frees its slot again.
* Customers get an email right away saying their request is pending (when you confirm by email), then a confirmation email, each with links to view, reschedule or cancel their booking.
* Works with any theme. Uses WordPress's default font and the accent color you pick.

It does not need any other plugin.

= Emails =

Admin and customer emails use a built-in layout (HTML or plain text). In the block you can set the sender name and address, the admin address, the logo, the footer text and send yourself a test email.

= Privacy =

Bookings store the name, email and optional phone number of the person who booked, plus a salted hash of their IP address for spam control. Data can be exported and erased with the WordPress privacy tools (erasing anonymises the booking but keeps the time slot so it cannot be double booked). Nothing is sent to external services and no cookies are set.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` and activate it.
2. Edit a page and add the **GemBooking Simple Booking Form** block.
3. Set the availability in the block sidebar, then open **Email notifications > Configure email** to set the address that receives booking requests.

== Frequently Asked Questions ==

= Do I need GemBooking? =

No. This plugin is independent.

= Where do I see my bookings? =

In the emails you receive. This plugin has no bookings screen.

= What happens if I do not answer a booking request? =

After 48 hours the request is cancelled, the customer is told, and the time slot is free again.

== Changelog ==

= 1.0.0 =
* First version.
