<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

# Vendor Gigamon

$get_oid_details = function ($ip, $credentials, $oid) {
    $details = new \StdClass();
    $details->model = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.26866.2.2.0");
    $details->serial = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.26866.2.8.0");
    $details->memory_count = intval(str_ireplace('mb', '', my_snmp_get($ip, $credentials, "1.3.6.1.4.1.26866.2.8.0")));
    return($details);
};