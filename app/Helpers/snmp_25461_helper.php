<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

# Vendor Palo Alto

$get_oid_details = function ($ip, $credentials, $oid) {
    $details = new \StdClass();
    $details->serial = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.25461.2.1.2.1.3.0");
    $details->os_group = 'Pan-OS';
    $details->os_version = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.25461.2.1.2.1.1.0");
    $details->os_name = 'Pan-OS ' . $details->os_version;
    $details->os_cpe = 'o:paloaltonetworks:pan-os:' . $details->os_version;
    return($details);
};