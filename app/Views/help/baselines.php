<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

$intro = '<p>Being able to determine which machines are configured in the same way is a major part of systems administration and auditing – and now reporting on that will be made simple and automated. Once you define your baseline it will automatically run against a set of devices on a predetermined schedule. The output of these executed baselines will be available for web viewing, importing into a third party system or even as a printed report.</p>
    <br>
    <h2>How Does it Work?</h2>
    <p>Baselines enable you to combine audit data with a set of attributes you have previously defined (your baseline) to determine compliance of devices.
    <br><br>
    For example - you might create a baseline from a device running Centos 6 which acts as one of your Apache servers in a cluster. You know this particular server is configured just the way you want it but you\'re unsure if other servers in the cluster are configured exactly the same. Baselines enables you to determine this.
    <br><br>
    You can create a baseline, run it against a group of devices and view the results, add scheduled execution, add more tables for comparison (currently only software, netstat ports and users are enabled), in place baseline editing, archiving of results and more.
    <br><br>
    WARNING - When creating a baseline using software policies, at present Centos and RedHat package the kernel using the names \'kernel\' and \'kernel-devel\'. There can be multiple packages with this name and different versions concurrently installed. Debian based distributions use names like \'linux-image-3.13.0-24-generic\', note the version number is included in the package name. Because RedHat based OS\'s use this format and subsequently have multiple identical package names with different versions we currently exclude \'kernel\' and \'kernel-devel\' from software policies. This may be addressed in a future update.</p>';

$body = '<h2>Notes</h2>
Baselines can compare netstat ports, users and software.<br><br>
<h5>Software</h5>
<p>To compare software we check the name and version. Because version numbers are not all standardised in format, when we receive an audit result we create a new attribute called software_padded which we store in the database along with the rest of the software details for each package. For this reason, baselines using software policies will not work when run against a device that has not been audited by 1.10 (at least). Software policies can test against the version being "equal to", "greater than" or "equal to or greater than".</p>
<br/>
<h5>Netstat Ports</h5>
<p>Netstat Ports use a combination of port number, protocol and program. If all are present the policy passes.</p>
<br>
<h5>Users</h5>
<p>Users work similar to Netstat Ports. If a user exists with a matching name, status and password details (changeable, expires, required) then the policy passes.</p>

<br>';