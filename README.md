# External links

[![CI](https://github.com/silverstripe/silverstripe-externallinks/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-externallinks/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Introduction

The external links module is a task and ModelAdmin to track and to report on broken external links.

## Maintainer Contact

 * Damian Mooyman (@tractorcow) <damian@silverstripe.com>

## Features

* Add external links to broken links reports
* Add a task to track external broken links

## Installation

```sh
composer require silverstripe/externallinks
```

## Report

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

## Dev task

Run the following task *http://path.to.silverstripe/dev/tasks/CheckExternalLinksTask* to check your site for external
broken links.

## Queued job

If you have the queuedjobs module installed you can set the task to be run every so often.

## Whitelisting codes

If you want to ignore or whitelist certain HTTP codes this can be setup via `ignore_codes` in the config.yml
file in `mysite/_config`:

```yml
SilverStripe\ExternalLinks\Tasks\CheckExternalLinksTask:
  ignore_codes:
    - 401
    - 403
    - 501
```

## Follow 301 redirects

You may want to follow a redirected URL a example of this would be redirecting from http to https
can give you a false poitive as the http code of 301 will be returned which will be classed
as a working link.

To allow redirects to be followed setup the following config in your config.yml

```yaml
# Follow 301 redirects
SilverStripe\ExternalLinks\Tasks\CurlLinkChecker:
  follow_location: 1
```

## Bypass cache

By default the task will attempt to cache any results the cache can be bypassed with the
following config in config.yml.

```yaml
# Bypass SS_Cache
SilverStripe\ExternalLinks\Tasks\CurlLinkChecker::
  bypass_cache: 1
```

## Headers

You may want to set headers to be sent with the CURL request (eg: user-agent) to avoid website rejecting the request thinking it is a bot.
You can set them with the following config in config.yml.

```yaml
# Headers
SilverStripe\ExternalLinks\Tasks\CurlLinkChecker:
  headers:
    - 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0'
    - 'accept-encoding: gzip, deflate, br'
    - 'referer: https://www.domain.com/'
    - 'sec-fetch-mode: navigate'
    ...
```
