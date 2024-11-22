<?php

# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

$intro = '<p>Feeds is our way of keeping you uptodate with the latest fixes, announcements and other items of note.<br>
    <br>
    You don\'t need to keep on top of the wiki or check for new releases, we can send these to you!</p>
    <br><br>
    <h2>Notes</h2>
<br>
<p>Feeds check-in each time you login for updated queries & packages, configuration recommendations, release announcements, blog posts and more.</p>
<p>In order to use this functionality, it must be enabled in the configuration - click <a href="' . url_to('configurationCollection') . '?configuration.name=LIKEfeeds">here</a>.</p>
<p>Along with this request for information, we send back to FirstWave datapoints that will give us some much needed data on feature use and your installation.</p>
<br><br>';

$body = '
<h2>What do we send?</h2>
<br>
<p>We only send the minimum amount of data and definitley nothing of a sensitive nature. We send our license data (name, type, etc), our application data (name, version, platform, timezone, etc), any logged errors, a count of device types and a count of the features used. <i>Any</i> environment has devices and that\'s the only piece of your data we send. And only the type of device and a count. Not the manufacturer, not the model. We send nothing special. No networks. No IP addresses. No OS versions. No software names. Just the data we find useful to improve the product.</p>
<br><br>
<h2>Data</h2>
<p>Because we try to be as transparent as possible, here is your actual data that we send.</p>
<pre>' . json_encode(createFeedData(), JSON_PRETTY_PRINT) . '
<br>';
