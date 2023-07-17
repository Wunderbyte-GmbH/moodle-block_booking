## Version 4.0.0 (2023071700)
**Bugfixes:**
* Bugfix: optiondates_handler of mod_booking was renamed to dates_handler - so fix the reference.

## Version 3.4.3 (2022111500)
**Bugfixes:**
* Fixed export in block_booking, mod_booking and local_wunderbyte_table and changed required versions.
* Several fixes in local_wunderbyte_table concerning sorting and styling of columns.
* Fixed a bug leading to a syntax error when quotes (" or ') were part of an option name.

**Improvements:**
* Improved autocompletes showing only entries of options in the future.
* Special autocomplete for courses, showing only courses having a group that matches the value
  of a custom user profile filed chosen in the block settings.
* If the custom user profile field is not set or in admin view, than the autocomplete for courses
  will be only reduced to courses having at least one booking instance.
* Different way of outputting the table (->outhtml(...)).

## Version 3.4.2 (2022101301)
**Bugfixes:**
* Location-autocomplete will now only show locations of booking options that match the given dates of the search.
* Fix broken student view.

## Version 3.4.1 (2022101300)
**Bugfixes:**
* Fixed autocompletes.
**Improvements:**
* Style sortable columns.