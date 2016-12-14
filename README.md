# External links

[![Build Status](https://travis-ci.org/silverstripe-labs/silverstripe-externallinks.svg?branch=master)](https://travis-ci.org/silverstripe-labs/silverstripe-externallinks)

## Introduction

The external links module is a task and ModelAdmin to track and to report on broken external links.

## Maintainer Contact

 * Damian Mooyman (@tractorcow) <damian@silverstripe.com>

## Requirements

	* SilverStripe 3.1 +

## Features

* Add external links to broken links reports
* Add a task to track external broken links

See the [changelog](CHANGELOG.md) for version history.

## Installation

 1. If you have composer you can use `composer require silverstripe/externallinks:*`. Otherwise, 
    download the module from GitHub and extract to the 'externallinks' folder. Place this directory
    in your sites root directory. This is the one with framework and cms in it.
 2. Run in your browser - `/dev/build` to rebuild the database.
 3. Run the following task *http://path.to.silverstripe/dev/tasks/CheckExternalLinks* to check for
    broken external links

## Report ##

A new report is added called 'External Broken links report'. When viewing this report, a user may press
the "Create new report" button which will trigger an ajax request to initiate a report run.

In this initial ajax request this module will do one of two things, depending on which modules are included:

* If the queuedjobs module is installed, a new queued job will be initiated. The queuedjobs module will then
  manage the progress of the task.
* If the queuedjobs module is absent, then the controller will fallback to running a buildtask in the background.
  This is less robust, as a failure or error during this process will abort the run.

In either case, the background task will loop over every page in the system, inspecting all external urls and
checking the status code returned by requesting each one. If a URL returns a response code that is considered
"broken" (defined as < 200 or > 302) then the `ss-broken` css class will be assigned to that url, and 
a line item will be added to the report. If a previously broken link has been corrected or fixed, then
this class is removed.

In the actual report generated the user can click on any broken link item to either view the link in their browser,
or edit the containing page in the CMS.

While a report is running the current status of this report will be displayed on the report details page, along
with the status. The user may leave this page and return to it later to view the ongoing status of this report.

Any subsequent report may not be generated until a prior report has completed.

## Dev task ##

Run the following task *http://path.to.silverstripe/dev/tasks/CheckExternalLinks* to check your site for external
broken links.

## Queued job ##

If you have the queuedjobs module installed you can set the task to be run every so ofter
Add the following yml config to config.yml in mysite/_config have the the task run once every day (86400 seconds)

    CheckExternalLinks:
      Delay: 86400

## Whitelisting codes ##

If you want to ignore or whitelist certain http codes this can be setup via IgnoreCodes in the config.yml
file in mysite/_config

    CheckExternalLinks:
      Delay: 60
      IgnoreCodes:
        - 401
        - 403
        - 501

## Follow 301 redirects ##

You may want to follow a redirected URL a example of this would be redirecting from http to https
can give you a false poitive as the http code of 301 will be returned which will be classed
as a working link.

To allow redirects to be followed setup the following config in your config.yml

    # Follow 301 redirects
    CurlLinkChecker:
      FollowLocation: 1

## Bypass cache ##

By default the task will attempt to cache any results the cache can be bypassed with the
following config in config.yml.

    # Bypass SS_Cache
    CurlLinkChecker:
      BypassCache: 1
