<?php

# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

$CI_INSTANCE = [];  # It keeps a ref to global CI instance

function register_ci_instance(\App\Controllers\BaseController &$_ci)
{
    global $CI_INSTANCE;
    $CI_INSTANCE[0] = &$_ci;
}

function &get_instance() # : \App\Controllers\BaseController
{
    global $CI_INSTANCE;
    return $CI_INSTANCE[0];
}

function getFeed()
{
    $feedsModel = model('FeedsModel');
    $feed = $feedsModel->show();
    return $feed;
}

function getOs()
{
    $data = new stdClass();
    if (php_uname('s') === 'Linux') {
        $data->os_name = 'linux';
        if (file_exists('/etc/os-release')) {
            $command_string = 'grep PRETTY_NAME= /etc/os-release';
            exec($command_string, $output, $status);
            $data->os_version = str_replace('PRETTY_NAME=', '', $output[0]);
            $data->os_version = str_replace('"', '', $data->os_version);
            if (empty($data->os_version)) {
                $command_string = 'grep ^NAME= /etc/os-release';
                exec($command_string, $output, $status);
                $data->os_version = str_replace('NAME=', '', $output[0]);
                $data->os_version = str_replace('"', '', $data->os_version);
            }
        } elseif (file_exists('/etc/issue.net')) {
            $file_contents = file('/etc/issue.net');
            $data->os_version = trim((string)$file_contents[0]);
            unset($file_contents);
        } elseif (file_exists('/etc/redhat-release')) {
            // RedHat 6 doesn't have /etc/os-release
            $data->os_version = 'RedHat';
        }
        if ((stripos($data->os_version, 'red') !== false) && (stripos($data->os_version, 'hat') !== false)) {
            $data->os_name = 'Linux (Redhat)';
        }
        if (stripos($data->os_version, 'centos') !== false) {
            $data->os_name = 'Linux (Redhat)';
        }
        if (stripos($data->os_version, 'fedora') !== false) {
            $data->os_name = 'Linux (Redhat)';
        }
        if (stripos($data->os_version, 'alma') !== false) {
            $data->os_name = 'Linux (Redhat)';
        }
        if (stripos($data->os_version, 'oracle') !== false) {
            $data->os_name = 'Linux (Redhat)';
        }
        if (stripos($data->os_version, 'rocky') !== false) {
            $data->os_name = 'Linux (Redhat)';
        }

        if (stripos($data->os_version, 'debian') !== false) {
            $data->os_name = 'Linux (Debian)';
        }
        if (stripos($data->os_version, 'ubuntu') !== false) {
            $data->os_name = 'Linux (Debian)';
        }
        if (stripos($data->os_version, 'mint') !== false) {
            $data->os_name = 'Linux (Debian)';
        }
    }
    if (php_uname('s') === 'Darwin') {
        $data->os_name = 'OSX';
        $command_string = 'sw_vers | grep "ProductVersion:" | cut -f2';
        @exec($command_string, $output, $status);
        $data->os_version = $output[0];
    }
    if (php_uname('s') === 'Windows NT') {
        $data->os_name = 'Windows';
        exec('wmic os get caption', $output);
        if (isset($output[1])) {
            $data->os_version = $output[1];
        }
    }
    return $data;
}

function getOsTimezone()
{

    if (php_uname('s') === 'Windows NT') {
        $command_string = 'tzutil /g';
        exec($command_string, $output, $return_var);
        $timezone = @$output[0];
        unset($output);
        return $timezone;
    }
    if (php_uname('s') === 'Darwin') {
        $command_string = '/bin/ls -l /etc/localtime | /usr/bin/cut -d"/" -f8,9';
        exec($command_string, $output, $return_var);
        $timezone = @$output[0];
        unset($output);
        return $timezone;
    }
    if (php_uname('s') === 'Linux') {
        $os = getOs();
        if ($os->os_name === 'Linux (Redhat)') {
            if (file_exists('/etc/sysconfig/clock')) {
                $command_string = 'cat /etc/sysconfig/clock | grep ZONE | cut -d"\"" -f2';
                exec($command_string, $output, $return_var);
                $timezone = @$output[0];
            }
            if ($os->timezone === '') {
                $command_string = 'timedatectl 2>/dev/null | grep zone | cut -d: -f2 | cut -d"(" -f1';
                exec($command_string, $output, $return_var);
                $timezone = @trim((string)$output[0]);
            }
        }
        if ($os->name === 'Linux (Debian)') {
            $command_string = 'cat /etc/timezone';
            exec($command_string, $output, $return_var);
            $timezone = @$output[0];
            unset($output);
            unset($command_string);
        }
        $timezone = trim((string)$timezone);
        return $timezone;
    }
    return '';
}

function getLicenseDetails()
{
    $config = new \Config\OpenAudit();
    $license = new \stdClass();
    $license->product = '';
    $license->company = '';
    $license->email = '';
    $license->count = 0;
    $license->expires = '2000-01-01';
    $license->type = '';

    // License info
    if (!empty($config->enterprise_binary)) {
        if (php_uname('s') === 'Windows NT') {
            $command = "%comspec% /c start /b " . $config->enterprise_binary . " --license";
            $cwd = getcwd();
            chdir('C:\\xampp\\open-audit\\other');
            $output = @exec($command, $output);
            pclose(popen($command, 'r'));
            chdir($cwd);
        } else {
            $command = $config->enterprise_binary . " --license";
            if (!empty($config->enterprise_env) and strpos($command, 'enterprise.bin') !== false) {
                $command = 'export PAR_GLOBAL_TMPDIR=' . $config->enterprise_env . '; ' . $command;
            }
            $output = @exec($command, $output);
        }
        if (!empty($output)) {
            $json = @json_decode($output);
        }
        if (!empty($json)) {
            $license->product = !empty($json->product) ? $json->product : '';
            if (stripos($license->product, 'enterprise') !== false) {
                $license->product = 'Open-AudIT Enterprise';
            }
            if (stripos($license->product, 'professional') !== false) {
                $license->product = 'Open-AudIT Professional';
            }
            $license->company = !empty($json->company) ? $json->company : '';
            $license->email = !empty($json->email) ? $json->email : '';
            $license->count = !empty($json->count) ? intval($json->count) : 0;
            $license->expires = !empty($json->expires) ? date('Y-m-d', $json->expires) : '';
            $license->type = !empty($json->license) ? $json->license : '';
        }
        unset($output);
    }
    return $license;
}

function createFeedData()
{
    $config = new \Config\OpenAudit();
    $db = db_connect();
    $data = new \stdClass();
    $data->product = 'Open-AudIT';
    $data->version = $config->display_version;
    $data->internal_version = $config->internal_version;
    $data->products = array();
    $data->products[] = 'Open-AudIT';
    foreach ($config->modules as $module) {
        if (!empty($module->url) and $module->name !== 'Applications' and $module->name !== 'opLicensing' and stripos($module->url, 'https://firstwave') === false) {
            $data->products[] = $module->name;
        }
    }
    $data->uuid = $config->uuid;
    $data->license = getLicenseDetails();

    $data->server_platform = $config->server_platform;
    $data->server_os = $config->server_os;
    $data->server_timezone = getOsTimezone();
    $data->issues = array();
    $data->features = new \stdClass();

    $path = '/usr/local/open-audit/writable/logs/';
    if (php_uname('s') === 'Windows NT') {
        $logfile = 'c:\\xampp\\open-audit\\writable\\logs\\';
    }
    $files = array_diff(scandir($path), array('.', '..', 'index.html'));
    foreach ($files as $file) {
        if (strpos($file, 'log-') !== false and strpos($file, '.log') !== false) {
            $lines = file($path . $file);
            $count = count($lines);
            # CRITICAL - 2024-11-18 16:06:29 --> ErrorException: Attempt to read property "name" on array in APPPATH/Models/FeedsModel.php on line 191.
            for ($i = 0; $i < $count; $i++) {
                if (strpos($lines[$i], 'CRITICAL') !== false and strpos($lines[$i], 'menuItem, no permission requested') === false) {
                    $line = $lines[$i] . ' ' . $lines[$i + 1];
                    $line = substr($line, strpos($line, '--> ') + 4);
                    $line = str_replace("\n", "", $line);
                    $data->issues[] = 'CRITICAL - ' . $line;
                }
            }
            # ERROR - 2024-11-20 17:12:23 --> Could not convert audit submission.
            for ($i = 0; $i < $count; $i++) {
                if (strpos($lines[$i], 'ERROR') !== false and strpos($lines[$i], 'Response:') === false) {
                    $line = $lines[$i];
                    $line = substr($line, strpos($line, '--> ') + 4);
                    $line = str_replace("\n", "", $line);
                    $data->issues[] = 'ERROR - ' . $line;
                }
            }
            # INFO - 2024-11-21 13:19:56 --> ACCESS:summaries:collection::Administrator
            for ($i = 0; $i < $count; $i++) {
                if (strpos($lines[$i], 'ACCESS') !== false) {
                    $explode = explode(':', $lines[$i]);
                    $data->features->{$explode[3] . ':' . $explode[4]} = !empty($data->features->{$explode[3] . ':' . $explode[4]}) ? intval($data->features->{$explode[3] . ':' . $explode[4]}) + 1 : 1;
                }
            }
        }
    }

    sort($data->issues);
    $data->issues = array_unique($data->issues);
    $data->issues = array_values($data->issues);

    $sql = "SELECT type, COUNT(*) AS `count` FROM devices GROUP BY type";
    $devices = $db->query($sql)->getResult();
    $data->devices = new \stdClass();
    foreach ($devices as $device) {
        $data->devices->{$device->type} = intval($device->count);
    }
    unset($devices);
    return $data;
}

function prereqCheck()
{
    if (php_uname('s') !== 'Windows NT') {
        $prereq = dirPermLinux();
    }
    $prereq['nmap_installed'] = nmapInstalled();
    $prereq['nmap_suid'] = nmapSuid();
    return $prereq;
}

function nmapInstalled($setNotice = false)
{
    if (function_exists('get_instance')) {
        $instance = & get_instance();
    } else {
        $instance = new \stdClass();
        $instance->config = config('Openaudit');
    }

    if (!empty($instance->config->discovery_override_nmap) && $instance->config->discovery_override_nmap === 'y') {
        return 'y';
    }
    $nmap_installed = 'n';
    if (php_uname('s') === 'Windows NT') {
        // check the obvious Windows install locations
        $test_path = 'c:\\Program Files\\Nmap\\Nmap.exe';
        if ($nmap_installed === 'n' and file_exists($test_path)) {
            $nmap_installed = 'y';
        }
        $test_path = 'c:\\Program Files (x86)\\Nmap\\Nmap.exe';
        if ($nmap_installed === 'n' and file_exists($test_path)) {
            $nmap_installed = 'y';
        }
        unset($test_path);
        if ($nmap_installed === 'n' and $setNotice) {
            \Config\Services::session()->setFlashdata('error', "WARNING - Nmap not detected. Get it from <a style='color:#729FCF;' target='_blank' href='http://nmap.org/download.html'>http://nmap.org/download.html</a>.<br />Please see <a href='" . url_to('helpFAQ') . "?name=Open-AudIT+and+Nmap'>Open-AudIT and Nmap</a> for information about why Open-AudIT requires Nmap and how to install it.");
        }
    }
    if (php_uname('s') !== 'Windows NT') {
        $command_string = 'which nmap 2>/dev/null';
        exec($command_string, $output);
        if (isset($output[0]) && strpos($output[0], 'nmap')) {
            $nmap_installed = 'y';
        }
        if (isset($output[1]) && strpos($output[1], 'nmap')) {
            $nmap_installed = 'y';
        }
        if ($nmap_installed === 'n') {
            if (file_exists('/usr/local/bin/nmap')) {
                $nmap_installed = 'y';
            }
        }
        if ($nmap_installed === 'n') {
            if (file_exists('/usr/bin/nmap')) {
                $nmap_installed = 'y';
            }
        }
        if ($nmap_installed === 'n' and $setNotice) {
            \Config\Services::session()->setFlashdata('error', "WARNING - Nmap not detected. Please install it using your package manager.<br />Please see <a href='" . url_to('helpFAQ') . "?name=Open-AudIT+and+Nmap'>Open-AudIT and Nmap</a> for information about why Open-AudIT requires Nmap and how to install it.");
        }
    }
    return $nmap_installed;
}

function nmapSuid($setNotice = false)
{
    if (php_uname('s') === 'Windows NT') {
        return 'y';
    }
    if (function_exists('get_instance')) {
        $instance = & get_instance();
    } else {
        $instance = new \stdClass();
        $instance->config = config('Openaudit');
    }
    if (!empty($instance->config->discovery_override_nmap) && $instance->config->discovery_override_nmap === 'y') {
        return 'y';
    }
    $suid = 'n';
    $command_string = 'which nmap 2>/dev/null';
    $output = shell_exec($command_string);
    if (!empty($output)) {
        $command_string = 'ls -lh `which nmap` | cut -d" " -f1 | cut -c4';
        exec($command_string, $output);
        if (isset($output[0]) and $output[0] === 's') {
            $suid = 'y';
        }
    } elseif (file_exists('/usr/bin/nmap')) {
        $command_string = 'ls -lh /usr/bin/nmap | cut -d" " -f1 | cut -c4';
        exec($command_string, $output);
        if (isset($output[0]) and $output[0] === 's') {
            $suid = 'y';
        }
    } elseif (file_exists('/usr/local/bin/nmap')) {
        $command_string = 'ls -lh /usr/local/bin/nmap | cut -d" " -f1 | cut -c4';
        exec($command_string, $output);
        if (isset($output[0]) and $output[0] === 's') {
            $suid = 'y';
        }
    }
    if ($suid === 'n' and $setNotice) {
        \Config\Services::session()->setFlashdata('error', "WARNING - Nmap SUID not set.<br />Please see <a href='" . url_to('helpFAQ') . "?name=Open-AudIT+and+Nmap'>Open-AudIT and Nmap</a> for information about why Open-AudIT requires Nmap and how to configure it.");
    }
    return $suid;
}

function dirPermLinux()
{
    $perm = array();
    $perm['cron_file_exists'] = 'n';
    if (file_exists('/etc/cron.d/open-audit')) {
        $perm['cron_file_exists'] = 'y';
    }
    $perm['attachments_dir'] = 'n';
    if (is_writable(APPPATH . 'Attachments')) {
        $perm['attachments_dir'] = 'y';
    }
    $perm['uploads_dir'] = 'n';
    if (is_writable(ROOTPATH . 'writable/uploads')) {
        $perm['uploads_dir'] = 'y';
    }
    $perm['scripts_dir'] = 'n';
    if (is_writable(ROOTPATH . 'other/scripts')) {
        $perm['scripts_dir'] = 'y';
    }
    $perm['custom_images_dir'] = 'n';
    if (is_writable(ROOTPATH . 'public/custom_images')) {
        $perm['custom_images_dir'] = 'y';
    }
    return $perm;
}

function format_data($result, $type)
{

    if (empty($result)) {
        // log_message('debug', 'No data provided to format_data function from '. debug_backtrace()[1]['class'] . '::' . debug_backtrace()[1]['function'] . ' for ' . $type);
        return array();
    }

    $return = array();

    if (!is_array($result)) {
        $item = $result;
        $result = array($item);
    }

    $item = new \stdClass();
    $item->attributes = new \stdClass();
    $item->type = $type;

    # format the attributes
    // $db = \Config\Database::connect();
    // $fields = $db->getFieldData($type);
    // foreach ($result as $entry) {
    //     foreach ($fields as $field) {
    //         if ($field->type === 'int') {
    //             $entry->{$field->name} = intval($entry->{$field->name});
    //         }
    //         if ($field->type === 'longtext') {
    //             $entry->{$field->name} = @json_decode($entry->{$field->name});
    //         }
    //     }
    // }

    // taken from output_helper::output_convert
    $names = array('id', 'free', 'used', 'size', 'speed', 'total', 'col_order', 'access_level', 'count');
    foreach ($result as $item) {
        foreach ($item as $key => $value) {
            $temp = explode('.', (string)$key);
            $column = $temp[0];
            if (in_array($column, $names)) {
                // If we have one of the above named columns, it is an integer
                $item->$key = intval($value);
            } elseif (
                (strrpos((string)$key, '_id') === strlen((string)$key) - 3) or
                (strrpos((string)$key, '_count') === strlen((string)$key) - 6) or
                (strrpos((string)$key, '_percent') === strlen((string)$key) - 8) or
                (strrpos((string)$key, '_size') === strlen((string)$key) - 5)
            ) {
                // If the name of the key ends with any of the above, it is an integer
                $item->$key = intval($value);
            } elseif (
                (strrpos((string)$key, 'ip') === strlen((string)$key) - 2) or
                (strrpos((string)$key, 'next_hop') === strlen((string)$key) - 8) or
                (strrpos((string)$key, 'destination') === strlen((string)$key) - 11) or
                (string)$key === 'Device IP'
            ) {
                $temp_name = $key . '_padded';
                $item->$temp_name = ip_address_from_db($value);
                $item->$temp_name = ip_address_to_db($item->$temp_name);
                $item->$key = ip_address_from_db($value);
                if ($item->$temp_name === $item->$key) {
                    unset($item->$temp_name);
                }
            }
            if (strrpos((string)$key, 'device_id') === strlen((string)$key) - 9) {
                $item->{'devices.id'} = intval($value);
            }
            if (strrpos((string)$key, 'org_id') === strlen((string)$key) - 6) {
                $item->{'orgs.id'} = intval($value);
            }
            if (strrpos((string)$key, 'location_id') === strlen((string)$key) - 11) {
                $item->{'locations.id'} = intval($value);
            }
            if ($key === 'ip' or strrpos((string)$key, '.ip') === strlen((string)$key) - 3 or strrpos((string)$key, '_ip') === strlen((string)$key) - 3) {
                $item->{$key . '_padded'} = $value;
                $item->{$key} = ip_address_from_db($value);
            }
        }
    }

    foreach ($result as $item) {
        foreach ($item as $key => $value) {
            if (is_null($value)) {
                $item->$key = '';
            }
        }
    }

    if ($type === 'agents') {
        foreach ($result as $item) {
            if (!empty($item->inputs)) {
                try {
                    $item->inputs = json_decode($item->inputs, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->outputs)) {
                try {
                    $item->outputs = json_decode($item->outputs, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'auth') {
        foreach ($result as $item) {
            if (!empty($item->dn_password)) {
                $decrypted = simpleDecrypt($item->dn_password, config('Encryption')->key);
                if (!empty($decrypted)) {
                    $item->dn_password = $decrypted;
                }
            }
            if (!empty($item->client_secret)) {
                $decrypted = simpleDecrypt($item->client_secret, config('Encryption')->key);
                if (!empty($decrypted)) {
                    $item->client_secret = $decrypted;
                }
            }
        }
    }

    if ($type === 'baselines_policies') {
        foreach ($result as $item) {
            if (!empty($item->tests)) {
                try {
                    $item->tests = json_decode($item->tests, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'baselines_results') {
        foreach ($result as $item) {
            if (!empty($item->baseline)) {
                try {
                    $item->baseline = json_decode($item->baseline, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    // Not logging below as this does appear to work, even though it always throws an exception
                    // log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->result)) {
                try {
                    $item->result = json_decode($item->result, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'benchmarks_policies') {
        foreach ($result as $item) {
            if (!empty($item->remediation)) {
                try {
                    $item->remediation = json_decode($item->remediation, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    // Not logging below as this does appear to work, even though it always throws an exception
                    // log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'dashboards') {
        foreach ($result as $item) {
            if (!empty($item->options)) {
                try {
                    $item->options = json_decode($item->options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'discoveries') {
        foreach ($result as $item) {
            if (!empty($item->scan_options)) {
                try {
                    $item->scan_options = json_decode($item->scan_options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->match_options)) {
                try {
                    $item->match_options = json_decode($item->match_options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->command_options)) {
                try {
                    $item->command_options = json_decode($item->command_options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'integrations') {
        foreach ($result as $item) {
            if (!empty($item->additional_items)) {
                try {
                    $item->additional_items = json_decode($item->additional_items, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->attributes)) {
                try {
                    $item->attributes = json_decode((string)$item->attributes, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->fields)) {
                try {
                    $item->fields = json_decode($item->fields, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'locations') {
        foreach ($result as $item) {
            if (!empty($item->options)) {
                try {
                    $item->options = json_decode($item->options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'networks') {
        foreach ($result as $item) {
            if (!empty($item->options)) {
                try {
                    $item->options = json_decode($item->options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'roles') {
        foreach ($result as $item) {
            if (!empty($item->permissions)) {
                try {
                    $item->permissions = json_decode($item->permissions, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'rules') {
        foreach ($result as $item) {
            if (!empty($item->inputs)) {
                try {
                    $item->inputs = json_decode($item->inputs, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->outputs)) {
                try {
                    $item->outputs = json_decode($item->outputs, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'scripts') {
        foreach ($result as $item) {
            if (!empty($item->options)) {
                try {
                    $item->options = json_decode($item->options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'tasks') {
        foreach ($result as $item) {
            if (!empty($item->options)) {
                try {
                    $item->options = json_decode($item->options, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    if ($type === 'users') {
        foreach ($result as $item) {
            if (!empty($item->roles)) {
                try {
                    $item->roles = json_decode($item->roles, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
            if (!empty($item->orgs)) {
                try {
                    $item->orgs = json_decode($item->orgs, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    log_message('error', 'Could not decode JSON. File:' . basename(__FILE__) . ', Line:' . __LINE__ . ', Error: ' . $e->getMessage());
                }
            }
        }
    }

    foreach ($result as $entry) {
        $item = new \stdClass();
        $item->id = '';
        if (isset($entry->id) and $entry->id != '') {
            $item->id = intval($entry->id);
        } elseif (!empty($entry->{$type . ".id"})) {
            $item->id = intval($entry->{$type . ".id"});
        } elseif (!empty($entry->{'devices.id'})) {
            $item->id = intval($entry->{'devices.id'});
        } elseif ($type == 'errors') {
            $item->id = $entry->code;
        }
        if ($type == 'widgets' and isset($entry->percent)) {
            $entry->percent = intval($entry->percent);
        }
        $item->type = $type;
        $item->attributes = $entry;
        $return[] = $item;
        unset($item);
    }
    return $return;
}

function formatQuery(array $result = array()): array
{
    if (empty($result)) {
        return array();
    }
    $instance = & get_instance();
    $config = config('Openaudit');
    $device_count = $config->device_count;
    $device_known = $config->device_known;
    $license_limit = $config->license_limit;
    if (empty($license_limit)) {
        return $result;
    }
    if (!empty($result[0]->{'devices.id'}) and ($device_known > intval($license_limit * 1.1))) {
        $devices = array();
        foreach ($result as $row) {
            $devices[] = intval($row->{'devices.id'});
        }
        $devices = array_unique($devices);
        if (count($devices) > intval($license_limit * 1.1)) {
            $_SESSION['warning'] = 'Restricting result to ' . $license_limit . ' devices as per license. There are actually ' . $device_count . ' devices in the database.';
            $devices = array_slice($devices, 0, $license_limit);
            $count = count($result);
            for ($i = 0; $i < $count; $i++) {
                if (!in_array($result[$i]->{'devices.id'}, $devices)) {
                    unset($result[$i]);
                }
            }
        }
    }
    return $result;
}

if (!function_exists('my_json_decode')) {
    function my_json_decode(string $string = '')
    {
        if (empty($string)) {
            # TODO - log an error
            return '';
        }

        if (stripos($string, '\"') !== false) {
            $string = str_replace('\"', '"', $string);
        }

        $json = @json_decode($string);
        $error_message = '';

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break; # No errors

            case JSON_ERROR_DEPTH:
                $error_message = 'Maximum stack depth exceeded';
                break;

            case JSON_ERROR_STATE_MISMATCH:
                $error_message = 'Underflow or the modes mismatch';
                break;

            case JSON_ERROR_CTRL_CHAR:
                $error_message = 'Unexpected control character found';
                break;

            case JSON_ERROR_SYNTAX:
                $error_message = 'Syntax error, malformed JSON';
                break;

            case JSON_ERROR_UTF8:
                $error_message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;

            default:
                $error_message = 'Unknown error';
                break;
        }

        if (!empty($error_message)) {
            log_message('error', strtolower(__METHOD__) . ' ' . $error_message);
        }

        if (!empty($json)) {
            return $json;
        } else {
            return '';
        }
    }
}
