=== 30bwidget ===
Contributors: tott
Donate link: http://30bwidget.wordpress.com/donatedonate
Tags: calendar, 30boxes, widget
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: trunk

A simple plugin that provides shortcodes and sidebar widgets to integrate 30Boxes.com calendars within your Blog.

== Description ==

Ever wanted to add a calendar of upcoming events in your company blog or add a simple list of birthdays in your family blog. Using the great service at <a href="http://30boxes.com" target="_blank">30boxes.com</a> this is now possible.

This little plugin gives you the possibility to add one or event lists or calendars in your blogs sidebar or posts/pages.

The script offers three different types for embedding.
* a calendar widget which embeds a full calendar. This type is mainly intended to be used within pages or posts and will work smoothly with widths of 500 and above.
* a sidebar calendar widget which is compatible to the default WordPress post calendar and therefor should fit in most themes without any alterations. This calendar displays a small calendar of events in the current month.
* a eventlist which can be embedded in the sidebar or within posts and pages and shows your events as a list with description if you wish to.

Combined with the features 30Boxes offers, it is possible to collect your information from various places all over the net and aggregate them together within your blog.

See <a href="http://30bwidget.wordpress.com">the 30bwidget blog</a> for details and demo.

== Installation ==

1. Upload the 30boxes folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Where do I get more help and information =

You might want to visit <a href="http://30bwidget.wordpress.com">the 30bwidget blog</a> to get more details and see this script in action.

= Where do I find my 30Boxes URL =

To find the 30Boxes URL needed for this widget, login to your 30Boxes account and go to <a href="http://30boxes.com/account">http://30boxes.com/account</a> and open the "sharing" tab.

The 30Boxes URL you need to enter can be revealed with a click on the Calendar link. 

Depending on the view you selected this URL will have different values. So you can end up with several widgets for certain custom views or widgets which show private events or widgets that show only your public events with a tag 'blog' for example.

= Screenshots ==

1. This screen shot shows a calendar widget that can be used within a post or page. You can use custom themes and adjust the design to your needs.
1. This shot shows an inline event list as it can be used within a post or a page. 
1. As shown in this shot you can also embed a sidebar calendar and eventlist.
1. This screenshot shows you where you can find the 30Boxes Url needed in order to activate any of the widgets.

== Short codes ==

Apart of the sidebar widget you also have the option to embed the various versions of 30boxes in your posts or pages using one of the following short codes.

The all the codes have some common format

`[30boxes type=calwidget,eventlist,sidebarcalendar url=30boxes_calendar_url parameter=value ...]`

* type: the type parameter controls which kind of widget should be displayed. It can be either calwidget for an embedded calendar, eventlist to produce a html eventlist or sidebarcalendar to create a post calendar like event calendar.
* url: this option needs to be filled with your calendar url as described at the FAQ. It controls which items to display
* additional parameters which are different depending on the type of widget you like to embed. Read further for details.

**Calendar widget:**

The calendar widget produces a fully embedded calendar in your blog and can be implemented with this short code.

`[30boxes type=calwidget url=30boxes_calendar_url width=width_in_pixels height=height_in_pixels themeUri=url_to_a_custom_them]`

* width: the width of the calendar in pixels. With the default themes it should be a value bigger than 500
* height: the height of the calendar in pixels. With the default themes best results can be achieved with values above 590
* themUri: this option lets you setup a custom theme for the calendar. This procedure is described at the developer documentation

**Eventlist:**

The eventlist type simply produces a html list of your events. It’s output is tuned up with semantic css selectors to allow easy customization of the layout.

`[30boxes type=eventlist url=30boxes_calendar_url num_events=number_of_events show_description=0,1 pastdays=amount_of_days futuredays=amount_of_days]`

* num_events: the number of events that should be displayed
* show_description: controls if the description of the event should be shown (1) or hidden (0).
* pastdays: amount of days in the past that should be shown. You can show events which are up to 24 days in the past.
* futuredays: amount of days in the future that should be shown. You can show events which are up to 24 days in the future.

**Sidebar calendar:**

This type produces a sidebar calendar which is css compatible to the native WordPress post calendar.

`[30boxes type=sidebarcalendar url=30boxes_calendar_url pastdays=amount_of_days futuredays=amount_of_days]`

* pastdays: amount of days in the past that should be shown. You can show events which are up to 24 days in the past. If left empty it uses the current month.
* futuredays: amount of days in the future that should be shown. You can show events which are up to 24 days in the future. If left empty it uses the current month.

