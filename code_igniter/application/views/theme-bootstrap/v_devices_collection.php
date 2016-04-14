<?php
#  Copyright 2003-2015 Opmantek Limited (www.opmantek.com)
#
#  ALL CODE MODIFICATIONS MUST BE SENT TO CODE@OPMANTEK.COM
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
#  www.opmantek.com or email contact@opmantek.com
#
# *****************************************************************************

/**
 * @author Mark Unwin <marku@opmantek.com>
 *
 * @version 1.12.4
 *
 * @copyright Copyright (c) 2014, Opmantek
 * @license http://www.gnu.org/licenses/agpl-3.0.html aGPL v3
 */
#echo "HERE"; exit();
#echo "<pre>"; print_r($this->response); #exit();
#echo "<pre>\n";
#print_r($this->response);
#exit();
echo "<h2>Devices</h2>\n";
echo "<div style=\"float:left; width:100%;\">\n";
if (!empty($this->response->data)) {
?>
<form action="devices?action=edit" method="post" id="bulk_edit" name="bulk_edit">
<table class="table">
    <thead>
        <tr>
            <?php

            $properties = get_object_vars($this->response->data[0]);
            #echo "<pre>\n"; print_r($properties); echo "</pre>\n";
            foreach ($properties as $key => $value) {
                if ($key == 'man_ip_address' or $key == 'ip_padded') {
                    continue;
                }
                $key = str_replace('man_', '', $key);
                $key = str_replace('_', ' ', $key);
                $key = str_replace('os ', 'OS ', $key);
                $key = str_replace(' id', ' ID', $key);
                $key = ucwords($key);
                if ($key == 'Ip') { $key = 'IP'; }
                if (stripos($key, 'icon') !== false) {
                    echo "\t\t\t<th style=\"text-align: center;\">" . __($key) . "</th>\n";
                } else {
                    echo "\t\t\t<th>" . __($key) . "</th>\n";
                }
            }
            ?>
            <th width="150" class="text-center">
                <button type="button" class="btn btn-primary" onclick="document.bulk_edit.submit();"><?php echo __('Edit') ?></button>&nbsp;
                <input type="checkbox"/>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->response->data as $item) {
        echo "\t\t\t<tr>\n";
        foreach ($properties as $property => $value) {
            $property = trim($property);
            if (strpos($property, '.') !== false) {
                $property = substr($property, 0, strpos($property, '.'));
            }
            if ($property == 'man_ip_address' or $property == 'ip_padded') {
                continue;
            }
            if (!empty($item->$property)) {
                if (strlen($item->$property) > 50) {
                    $item->$property = substr($item->$property, 0, 50) . '....';
                }
                if ($property == 'ip' and !empty($item->ip_padded)) {
                    echo "\t\t\t\t<td><span style='display:none;'>" . str_replace('.', '', $item->ip_padded) . "</span>" . $item->ip . "</td>\n";
                } elseif ($property == 'system_id') {
                    echo "\t\t\t\t<td><a href='devices/" . $item->$property . "'>" . $item->$property . "</td>\n";
                } elseif ($property == 'icon') {
                    echo "\t\t\t\t<td style=\"text-align: center;\"><img src=\"".str_replace("index.php", "", site_url())."device_images/".strtolower(str_replace(" ", "_", htmlentities($item->$property))).".svg\" style='border-width:0px; width:24px;' title=\"".htmlentities($item->$property)."\" alt=\"".htmlentities($item->$property)."\"/></td>\n";
                } else {
                    echo "\t\t\t\t<td>" . $item->$property . "</td>\n";
                }
            } else {
                echo "\t\t\t\t<td></td>\n";
            }
        }
        echo "\t\t\t\t<td align='center'><input type='checkbox' id='ids[]' value='" . intval($item->system_id) . "' name='ids[]' /></td>\n";
        echo "\t\t\t</tr>\n";
    }
    ?>
    </tbody>
</table>
</form>
<?php
}
if (!empty($this->response->error)) {
    echo "<pre>\n";
    print_r($error);
    echo "</pre>\n";
}
?>
</div>
<?php
exit();
?>