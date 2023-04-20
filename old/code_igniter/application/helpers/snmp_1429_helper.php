<?php
/**
#  Copyright 2022 Firstwave (www.firstwave.com)
#
#  This file is part of Open-AudIT.
#
#  Open-AudIT is free software: you can redistribute it and/or modify
#  it under the terms of the GNU Affero General Public License as published
#  by the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  Open-AudIT is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU Affero General Public License for more details.
#
#  You should have received a copy of the GNU Affero General Public License
#  along with Open-AudIT (most likely in a file named LICENSE).
#  If not, see <http://www.gnu.org/licenses/>
#
#  For further information on Open-AudIT or for a license other than AGPL please see
#  www.firstwave.com or email sales@firstwave.com
#
# *****************************************************************************
*
* PHP version 5.3.3
* 
* @category  Helper
* @author    Mark Unwin <mark.unwin@firstwave.com>
* @copyright 2022 Firstwave
* @license   http://www.gnu.org/licenses/agpl-3.0.html aGPL v3
* @version   GIT: Open-AudIT_4.3.2
* @link      http://www.open-audit.org
*/

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

# Vendor Scientific-Atlanta, Inc.

$get_oid_details = function ($ip, $credentials, $oid) {
    $details = new stdClass();
    if ($oid == '1.3.6.1.4.1.1429.2.2.6.2') {
        $details->serial = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.1429.2.2.4.1.7.0");
    }
    # attempt to refine the model number
    $temp_model = '';
    $temp_model = my_snmp_get($ip, $credentials, "1.3.6.1.4.1.1429.2.2.4.1.6.0");
    if (!empty($temp_model)) {
        $temp_array = explode('_', $temp_model);
        if ($temp_array[0] != '') {
            $details->model = $temp_array[0].' Program Receiver';
        }
    }
    unset($temp_model);
    unset($tmp_array);
    return($details);
};