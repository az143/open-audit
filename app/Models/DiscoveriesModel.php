<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace App\Models;

use stdClass;

class DiscoveriesModel extends BaseModel
{

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table('discoveries');
        # Use the below to execute BaseModel::__construct
        # parent::__construct();
    }

    /**
     * Read the collection from the database
     *
     * @param  $resp object An object containing the properties, filter, sort and limit as passed by the user
     *
     * @return array        An array of formatted entries
     */
    public function collection(object $resp): array
    {
        $properties = $resp->meta->properties;
        $properties[] = "orgs.name as `orgs.name`";
        $properties[] = "orgs.id as `orgs.id`";
        $this->builder->select($properties, false);
        $this->builder->join('orgs', $resp->meta->collection . '.org_id = orgs.id', 'left');
        foreach ($resp->meta->filter as $filter) {
            if (in_array($filter->operator, ['!=', '>=', '<=', '=', '>', '<'])) {
                $this->builder->{$filter->function}($filter->name . ' ' . $filter->operator, $filter->value);
            } else {
                $this->builder->{$filter->function}($filter->name, $filter->value);
            }
        }
        $this->builder->orderBy($resp->meta->sort);
        $this->builder->limit($resp->meta->limit, $resp->meta->offset);
        $query = $this->builder->get();
        if ($this->sqlError($this->db->error())) {
            return array();
        }
        return format_data($query->getResult(), $resp->meta->collection);
    }

    /**
     * Create an individual item in the database
     *
     * @param  object $data The data attributes
     *
     * @return int|false    The Integer ID of the newly created item, or false
     */
    public function create($data = null)
    {
        $instance = & get_instance();
        if (empty($data)) {
            return false;
        }
        if (empty($data->type)) {
            log_message('error', 'No type provided to DiscoveriesModel::create.');
            return false;
        }
        if ($data->type !== 'subnet' && $data->type !== 'active directory' && $data->type !== 'cloud' && $data->type !== 'integration' && $data->type !== 'seed') {
            log_message('error', 'Invalid type provided to Discoveries::create (' . $data->type . ')');
            return false;
        }
        if (empty(config('Openaudit')->discovery_default_scan_option)) {
            config('Openaudit')->discovery_default_scan_option = 1;
        }
        if (empty($data->devices_assigned_to_org)) {
            unset($data->devices_assigned_to_org);
        } else {
            $data->devices_assigned_to_org = intval($data->devices_assigned_to_org);
        }
        if (empty($data->devices_assigned_to_location)) {
            unset($data->devices_assigned_to_location);
        } else {
            $data->devices_assigned_to_location = intval($data->devices_assigned_to_location);
        }
        if (isset($data->device_id)) {
            $data->device_id = intval($data->device_id);
        }
        if (! empty($data->discard)) {
            if ($data->discard !== 'n' && $data->discard !== 'y') {
                unset($data->discard);
            }
        }
        if (! empty($data->subnet)) {
            if (! preg_match('/^[\d,\.,\/,-]*$/', $data->subnet)) {
                $data->subnet = '';
            }
        }
        if (empty($data->match_options)) {
            $data->match_options = new \stdClass();
        }
        if (is_string($data->match_options)) {
            $data->match_options = json_decode($data->match_options);
        }
        $match_options = array('match_dbus', 'match_fqdn', 'match_dns_fqdn', 'match_dns_hostname', 'match_hostname', 'match_hostname_dbus', 'match_hostname_serial', 'match_hostname_uuid', 'match_ip', 'match_ip_no_data', 'match_mac', 'match_mac_vmware', 'match_serial', 'match_serial_type', 'match_sysname', 'match_sysname_serial', 'match_uuid');
        foreach ($match_options as $match_option) {
            if (!isset($data->match_options->{$match_option})) {
                $data->match_options->{$match_option} = '';
            }
            if ($data->match_options->{$match_option} !== 'y' && $data->match_options->{$match_option} !== 'n') {
                $data->match_options->{$match_option} = '';
            }
        }
        if (!empty($data->scan_options) && is_string($data->scan_options)) {
            $data->scan_options = json_decode($data->scan_options);
        }
        if (empty($data->scan_options)) {
            $data->scan_options = new \stdClass();
            $data->scan_options->id = intval(config('Openaudit')->discovery_default_scan_option);
        }
        if (!isset($data->scan_options->id)) {
            $data->scan_options->id = intval(config('Openaudit')->discovery_default_scan_option);
        }
        if (isset($data->scan_options->ping)) {
            if ($data->scan_options->ping !== 'y' && $data->scan_options->ping !== 'n') {
                $data->scan_options->ping = '';
            }
        } else {
            $data->scan_options->ping = '';
        }
        if (isset($data->scan_options->service_version)) {
            if ($data->scan_options->service_version !== 'y' && $data->scan_options->service_version !== 'n') {
                $data->scan_options->service_version = '';
            }
        } else {
            $data->scan_options->service_version = '';
        }
        if (isset($data->scan_options->{'open|filtered'})) {
            if ($data->scan_options->{'open|filtered'} !== 'y' && $data->scan_options->{'open|filtered'} !== 'n') {
                $data->scan_options->{'open|filtered'} = '';
            }
        } else {
            $data->scan_options->{'open|filtered'} = '';
        }
        if (isset($data->scan_options->filtered)) {
            if ($data->scan_options->filtered !== 'y' && $data->scan_options->filtered !== 'n') {
                $data->scan_options->filtered = '';
            }
        } else {
            $data->scan_options->filtered = '';
        }
        if (isset($data->scan_options->timeout)) {
            if (is_int($data->scan_options->timeout) or is_numeric($data->scan_options->timeout)) {
                $data->scan_options->timeout = intval($data->scan_options->timeout);
            } else {
                $data->scan_options->timeout = '';
            }
        } else {
            $data->scan_options->timeout = '';
        }
        if (isset($data->scan_options->timing)) {
            if (is_int($data->scan_options->timing) or is_numeric($data->scan_options->timing)) {
                $data->scan_options->timing = intval($data->scan_options->timing);
            } else {
                $data->scan_options->timing = '';
            }
        } else {
            $data->scan_options->timing = '';
        }
        if (isset($data->scan_options->nmap_tcp_ports)) {
            if (is_int($data->scan_options->nmap_tcp_ports) or is_numeric($data->scan_options->nmap_tcp_ports)) {
                $data->scan_options->nmap_tcp_ports = intval($data->scan_options->nmap_tcp_ports);
            } else {
                $data->scan_options->nmap_tcp_ports = '';
            }
        } else {
            $data->scan_options->nmap_tcp_ports = '';
        }
        if (isset($data->scan_options->nmap_udp_ports)) {
            if (is_int($data->scan_options->nmap_udp_ports) or is_numeric($data->scan_options->nmap_udp_ports)) {
                $data->scan_options->nmap_udp_ports = intval($data->scan_options->nmap_udp_ports);
            } else {
                $data->scan_options->nmap_udp_ports = '';
            }
        } else {
            $data->scan_options->nmap_udp_ports = '';
        }
        if (isset($data->scan_options->tcp_ports)) {
            if (! preg_match('/^[\d,\/,-]*$/', $data->scan_options->tcp_ports)) {
                $data->scan_options->tcp_ports = '';
            }
        } else {
            $data->scan_options->tcp_ports = '';
        }
        if (isset($data->scan_options->udp_ports)) {
            if (! preg_match('/^[\d,\/,-]*$/', $data->scan_options->udp_ports)) {
                $data->scan_options->udp_ports = '';
            }
        } else {
            $data->scan_options->udp_ports = '';
        }
        if (isset($data->scan_options->exclude_tcp_ports)) {
            if (! preg_match('/^[\d,\/,-]*$/', $data->scan_options->exclude_tcp_ports)) {
                $data->scan_options->exclude_tcp_ports = '';
            }
        } else {
            $data->scan_options->exclude_tcp_ports = '';
        }
        if (isset($data->scan_options->exclude_udp_ports)) {
            if (! preg_match('/^[\d,\/,-]*$/', $data->scan_options->exclude_udp_ports)) {
                $data->scan_options->exclude_udp_ports = '';
            }
        } else {
            $data->scan_options->exclude_udp_ports = '';
        }
        if (isset($data->scan_options->exclude_ip)) {
            if (! preg_match('/^[\d,\.,\/,-]*$/', $data->scan_options->exclude_ip)) {
                $data->scan_options->exclude_ip = '';
            }
        } else {
            $data->scan_options->exclude_ip = '';
        }
        if (isset($data->scan_options->ssh_ports)) {
            if (! preg_match('/^[\d,\/,-]*$/', $data->scan_options->ssh_ports)) {
                $data->scan_options->ssh_ports = '';
            }
        } else {
            $data->scan_options->ssh_ports = '';
        }
        if (isset($data->scan_options->script_timeout)) {
            if (is_int($data->scan_options->script_timeout) or is_numeric($data->scan_options->script_timeout)) {
                $data->scan_options->script_timeout = intval($data->scan_options->script_timeout);
            } else {
                $data->scan_options->script_timeout = '';
            }
        } else {
            $data->scan_options->script_timeout = '';
        }
        if (isset($data->scan_options->snmp_timeout)) {
            if (is_int($data->scan_options->snmp_timeout) or is_numeric($data->scan_options->snmp_timeout)) {
                $data->scan_options->snmp_timeout = intval($data->scan_options->snmp_timeout);
            } else {
                $data->scan_options->snmp_timeout = '';
            }
        } else {
            $data->scan_options->snmp_timeout = '';
        }
        if (isset($data->scan_options->ssh_timeout)) {
            if (is_int($data->scan_options->ssh_timeout) or is_numeric($data->scan_options->ssh_timeout)) {
                $data->scan_options->ssh_timeout = intval($data->scan_options->ssh_timeout);
            } else {
                $data->scan_options->ssh_timeout = '';
            }
        } else {
            $data->scan_options->ssh_timeout = '';
        }
        if (isset($data->scan_options->wmi_timeout)) {
            if (is_int($data->scan_options->wmi_timeout) or is_numeric($data->scan_options->wmi_timeout)) {
                $data->scan_options->wmi_timeout = intval($data->scan_options->wmi_timeout);
            } else {
                $data->scan_options->wmi_timeout = '';
            }
        } else {
            $data->scan_options->wmi_timeout = '';
        }
        if ($data->type === 'subnet') {
            if (empty($data->subnet)) {
                \Config\Services::session()->setFlashdata('error', 'Missing or invalid subnet provided to Discoveries::Create.');
                log_message('error', 'Missing or invalid subnet provided to Discoveries::create.');
                return false;
            } else {
                $data->description = 'Subnet - ' . $data->subnet;
            }
        } else if ($data->type === 'active directory') {
            if (empty($data->ad_server) or empty($data->ad_domain)) {
                $temp = 'Active Directory Domain';
                if (empty($data->ad_server)) {
                    $temp = 'Active Directory Server';
                }
                \Config\Services::session()->setFlashdata('error', 'Object in discoveries could not be created - no ' . $temp . ' supplied.');
                log_message('error', 'Object in discoveries could not be created - no ' . $temp . ' supplied.');
                return false;
            } else {
                $data->description = 'Active Directory - ' . $data->ad_domain;
            }
        } else if ($data->type === 'seed') {
            if (empty($data->seed_ip)) {
                \Config\Services::session()->setFlashdata('error', 'Missing or invalid field: seed_ip');
                log_message('error', 'Missing or invalid field: seed_ip');
                return false;
            }
            if (empty($data->seed_restrict_to_subnet)) {
                $data->seed_restrict_to_subnet = 'y';
            }
            if (empty($data->seed_restrict_to_private)) {
                $data->seed_restrict_to_private = 'y';
            }
        } else if ($data->type === 'cloud') {
            // TODO
        } else {
            $data->description = '';
        }

        if ($data->type === 'subnet') {
            if (stripos($data->subnet, '-') === false and filter_var($data->subnet, FILTER_VALIDATE_IP) !== false) {
                // We have a single IP - ie 192.168.1.1
                // TODO - we should pass the OrgID
                $data->description = 'IP - ' . $data->subnet;
                $test = check_ip($data->subnet);
                if (!$test) {
                    // This IP is not in any existing subnets - insert a /30
                    // TODO - account for Org ID in existing as check_ip returns only true/false, and does not acount for orgs
                    $temp = network_details($data->subnet.'/30');
                    $network = new \stdClass();
                    $network->name = $temp->network . '/' . $temp->network_slash;
                    $network->network = $temp->network . '/' . $temp->network_slash;
                    $network->org_id = (!empty($data->devices_assigned_to_org)) ? intval($data->devices_assigned_to_org) : intval($data->org_id);
                    $network->location_id = (!empty($data->devices_assigned_to_location)) ? intval($data->devices_assigned_to_location) : 1;
                    $network->description = $data->name;
                    $instance->networksModel->upsert($network);
                }
            }

            if (stripos($data->subnet, '-') === false and strpos($data->subnet, '/') !== false) {
                // We have a regular subnet - ie 192.168.1.0/24
                $temp = network_details($data->subnet);
                if (! empty($temp->error)) {
                    \Config\Services::session()->setFlashdata('error', 'Discovery not created - invalid subnet attribute supplied.');
                    log_message('error', 'Discovery not created - invalid subnet attribute supplied (' . $data->subnet . ').');
                    return;
                }
                $network = new \stdClass();
                $network->name = $temp->network . '/' . $temp->network_slash;
                $network->network = $temp->network . '/' . $temp->network_slash;
                $network->org_id = (!empty($data->devices_assigned_to_org)) ? intval($data->devices_assigned_to_org) : intval($data->org_id);
                $network->location_id = (!empty($data->devices_assigned_to_location)) ? intval($data->devices_assigned_to_location) : 1;
                $network->description = $data->name;
                $instance->networksModel->upsert($network);
            }

            if (stripos($data->subnet, '-') !== false) {
                // We have a range and cannot insert a network
                $warning = 'IP range, instead of subnet supplied. No network entry created.';
                if (config('Openaudit')->blessed_subnets_use !== 'n') {
                    $warning .= '<br />Because you are using blessed subnets, please ensure a valid network for this range exists.';
                }
                \Config\Services::session()->setFlashdata('warning', $warning);
            }
        }

        $data->scan_options = json_encode($data->scan_options);
        $data->match_options = json_encode($data->match_options);

        $data = $this->createFieldData('discoveries', $data);
        // log_message('debug', json_encode($data));
        $this->builder->insert($data);
        // log_message('debug', str_replace("\n", " ", (string)$this->db->getLastQuery()));
        if ($error = $this->sqlError($this->db->error())) {
            \Config\Services::session()->setFlashdata('error', json_encode($error));
            return null;
        }
        return ($this->db->insertID());
    }

    /**
     * Delete an individual item from the database, by ID
     *
     * @param  int $id The ID of the requested item
     *
     * @return bool    true || false depending on success
     */
    public function delete($id = null, bool $purge = false): bool
    {
        // Delete any associated tasks
        $sql = "DELETE FROM tasks WHERE sub_resource_id = ? and type = 'discoveries'";
        $result = $this->db->query($sql, [$id]);
        // Delete the discovery itself
        $this->builder->delete(['id' => intval($id)]);
        if ($this->sqlError($this->db->error())) {
            return false;
        }
        if ($this->db->affectedRows() !== 1) {
            return false;
        }
        return true;
    }


    /**
     * Take a discovery ID and optionally a device ID
     * Return an array of credentials in the order of device specific, device previously worked, discovery associated
     * IE: discovery.org_id (and children) = orgs.id = credentials.org_id
     * @param  [type] $device_id    devices.id
     * @param  [type] $discovery_id discoveries.id
     * @param  [type] $ip_address   The IP of the device in question (for logging)
     * @return [type]               [description]
     */
    public function getDeviceDiscoveryCredentials(int $device_id = 0, int $discovery_id = 0, string $ip_address = ''): array
    {
        $instance = & get_instance();
        $credentialsModel = new \App\Models\CredentialsModel();
        $discoveryLogModel = new \App\Models\DiscoveryLogModel();
        helper('security');
        $credentials = array();

        if (empty($discovery_id)) {
            return array();
        }

        $retrieved_types = array();
        // set up a log object
        $log = new \StdClass();
        $log->discovery_id = $discovery_id;
        $log->file = 'm_device';
        $log->function = 'get_device_discovery_credentials';
        $log->ip = $ip_address;
        $log->message = 'Credentials retrieved for: ';
        $log->pid = getmypid();
        $log->severity = 7;
        $log->command_status = 'notice';
        $log->device_id = $device_id;
        $log->timestamp = null;
        // Get the discovery details
        $result = $this->read($discovery_id);
        $discovery = $result[0]->attributes;
        $org_id = (!empty($discovery->org_id)) ? intval($discovery->org_id) : 1;
        // Device specific credentials
        $sql = 'SELECT * FROM `credential` WHERE `device_id` = ?';
        $result = $this->db->query($sql, [$device_id])->getResult();
        if (!empty($result)) {
            for ($i=0; $i < count($result); $i++) {
                $result[$i]->credentials = json_decode(simpleDecrypt($result[$i]->credentials, config('Encryption')->key));
            }
            $credentials = $result;
            $retrieved_types[] = 'Device specific';
        }
        // Previous working credentials
        $sql = 'SELECT `credentials` FROM `devices` WHERE id = ?';
        $result = $this->db->query($sql, $device_id)->getResult();
        // $result[0]->credentials is a string. A JSON encoded array of integers referring to credentials.id
        if (!empty($result[0]->credentials)) {
            $temp = @json_decode($result[0]->credentials);
            if (!empty($temp)) {
                $id_list = implode(', ', $temp);
                $sql = "SELECT credentials.*, 'credentials' AS `foreign` FROM `credentials` WHERE id IN (" . $id_list . ')';
                $result = $this->db->query($sql)->getResult();
                if (! empty($result)) {
                    for ($i=0; $i < count($result); $i++) {
                        $result[$i]->credentials = json_decode(simpleDecrypt($result[$i]->credentials, config('Encryption')->key));
                    }
                }
                $credentials = array_merge($credentials, $result);
            }
            $retrieved_types[] = 'Device previously working';
        }
        unset($temp);

        // Discovery associated credentials
        // get our Orgs List
        $temp = $instance->orgsModel->getUserDescendants([$org_id], $instance->orgs);
        $temp[] = $org_id;
        $org_list = implode(', ', $temp);
        unset($temp);
        // And now get any credentials
        $sql = "SELECT credentials.*, 'credentials' AS `foreign` FROM `credentials` WHERE `org_id` IN (" . $org_list . ')';
        $result = $this->db->query($sql)->getResult();
        if (!empty($result)) {
            for ($i=0; $i < count($result); $i++) {
                $result[$i]->credentials = json_decode(simpleDecrypt($result[$i]->credentials, config('Encryption')->key));
            }
            $credentials = array_merge($credentials, $result);
            $retrieved_types[] = 'Discovery related';
        }
        if (count($credentials) === 0) {
            $log->message = 'No credentials retrieved.';
            $log->severity = 5;
            $log->command_status = 'warning';
            #discovery_log($log);
            $discoveryLogModel->create($log);
        } else {
            $log->message = $log->message . implode(', ', $retrieved_types) . '.';
            #discovery_log($log);
            $discoveryLogModel->create($log);
        }
        return $credentials;
    }

    /**
     * Return an array containing arrays of related items to be stored in resp->included
     *
     * @param  int $id The ID of the requested item
     * @return array  An array of anything needed for screen output
     */
    public function includedRead(int $id = 0): array
    {

        $include = array();

        $locationsModel = new \App\Models\LocationsModel();
        $include['locations'] = $locationsModel->listUser();

        $sql = "SELECT * FROM discovery_log WHERE discovery_id = ? ORDER BY id";
        $query = $this->db->query($sql, [$id]);
        $include['discovery_log'] = $query->getResult();

        return $include;
    }

    /**
     * Return an array containing arrays of related items to be stored in resp->included
     *
     * @param  int $id The ID of the requested item
     * @return array  An array of anything needed for screen output
     */
    public function includedCreateForm(int $id = 0): array
    {
        // $scanOptionsModel = new \App\Models\DiscoveryScanOptionsModel();
        // $scanOptions = $scanOptionsModel->listUser();
        // $include['discovery_scan_options'] = $scanOptions;

        $sql = "SELECT * FROM discovery_scan_options";
        $query = $this->db->query($sql);
        $include = array();
        $include['discovery_scan_options'] = $query->getResult();

        $locationsModel = new \App\Models\LocationsModel();
        $locations = $locationsModel->listUser();
        $include['locations'] = $locations;

        return $include;
    }


    public function issuesRead(int $id = 0): array
    {
        $issues = array();

        # Windows issues from Linx
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, command_output AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.device_id IN (select device_id from discovery_log a where message LIKE '%WMI detected but no valid Windows credentials%') AND discovery_log.message LIKE '%Attempting to execute command using winexe-static-2' AND discoveries.id = " . $id;
        $issues = $this->db->query($sql)->getResult();

        # Windows issues from Windows
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, command_output AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.device_id IN (select device_id from discovery_log a where message LIKE '%WMI detected but no valid Windows credentials%') AND discovery_log.message LIKE '%Attempting to execute command' AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        # Invalid SSH Credentials
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, message AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.message LIKE '%SSH detected but no valid SSH credentials%' AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        # Invalid SNMP credentials
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, message AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.message LIKE '%SNMP detected, but no valid SNMP credentials%' AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        # No management protcols and an unknown or unidentified device
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, message AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.message LIKE '%No management protocols%' AND (devices.type = 'unknown' OR devices.type = 'unclassified') AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        # Could not copy audit result back to server
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, message AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.message LIKE '%Could not SCP GET to%' AND command_status = 'fail' AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        # Invalid result XML
        $sql = "SELECT discovery_log.device_id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, discovery_log.timestamp AS `discovery_log.timestamp`, message AS `output` from discovery_log LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id) LEFT JOIN devices ON (discovery_log.device_id = devices.id) WHERE discovery_log.message LIKE '%Could not convert audit result from XML%' AND command_status = 'fail' AND discoveries.id = " . $id;
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        foreach ($issues as $issue) {
            // Derive the description and action
            $issue = $this->issueMap($issue);
            // Format the IP
            if (!empty($issue->{'devices.ip'})) {
                $issue->padded_ip = $issue->{'devices.ip'};
                $issue->{'devices.ip'} = ip_address_from_db($issue->{'devices.ip'});
            }
        }
        return $issues;
    }
    public function issuesCollection(int $user_id = 0): array
    {

        $instance = & get_instance();

        $org_list = array_unique(array_merge($instance->user->orgs, $instance->orgsModel->getUserDescendants($instance->user->orgs, $instance->orgs)));
        $org_list[] = 1;
        $org_list = array_unique($org_list);

        $issues = array();

        # Windows issues, limited to the 50 most recent rows
        $sql = "SELECT discovery_log.id AS `discovery_log.id`, discovery_log.discovery_id AS `discovery_id`, discoveries.name AS `discovery_name`, devices.id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, devices.identification AS `devices.identification`, discovery_log.timestamp AS `timestamp`, command_output AS `output`
        FROM discovery_log
            LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id)
            LEFT JOIN devices ON (discovery_log.device_id = devices.id)
        WHERE
            (discovery_log.device_id IN (select device_id from discovery_log a where message like '%WMI detected but no valid Windows credentials%') AND discovery_log.message LIKE '%Attempting to execute command using winexe-static-2' ) OR
            (discovery_log.device_id IN (select device_id from discovery_log a where message like '%WMI detected but no valid Windows credentials%') AND discovery_log.message LIKE '%Attempting to execute command')
        AND discoveries.org_id IN (" . implode(',', $org_list) . ") GROUP BY devices.id ORDER BY discovery_log.id DESC LIMIT 50";
        $issues = $this->db->query($sql)->getResult();

        # Other issues, limited to the 50 most recent rows
        $sql = "SELECT discovery_log.id AS `discovery_log.id`, discovery_log.discovery_id AS `discovery_id`, discoveries.name AS `discovery_name`, devices.id AS `devices.id`, devices.name AS `devices.name`, devices.ip AS `devices.ip`, devices.type AS `devices.type`, devices.icon AS `devices.icon`, devices.identification AS `devices.identification`, discovery_log.timestamp AS `timestamp`, `message` AS `output`
        FROM discovery_log
            LEFT JOIN discoveries ON (discovery_log.discovery_id = discoveries.id)
            LEFT JOIN devices ON (discovery_log.device_id = devices.id)
        WHERE
            (discovery_log.message LIKE '%SSH detected but no valid SSH credentials%') OR
            (discovery_log.message LIKE '%SNMP detected, but no valid SNMP credentials%') OR
            (discovery_log.message LIKE '%No management protocols%' AND (devices.type = 'unknown' OR devices.type = 'unclassified')) OR
            (discovery_log.message LIKE '%Could not SCP GET to%' AND command_status = 'fail') OR
            (discovery_log.message LIKE '%Could not convert audit result from XML%' AND command_status = 'fail') OR
            (discovery_log.message LIKE 'No credentials array passed to%') OR
            (discovery_log.message LIKE 'No valid credentials for%')
        AND discoveries.org_id IN (" . implode(',', $org_list) . ") GROUP BY devices.id ORDER BY discovery_log.id DESC LIMIT 50";
        $result = $this->db->query($sql)->getResult();
        $issues = array_merge($issues, $result);

        // Refine the issue list to the latest discovery_log.id per device
        // $issue_devices[$device_id] = $discovery_log.id
        // We want the highest discovery_log.id per device_id
        $issue_devices = array();
        foreach ($issues as $issue) {
            if (empty($issue_devices[$issue->{'devices.id'}]) or $issue_devices[$issue->{'devices.id'}] < $issue->{'discovery_log.id'}) {
                $issue_devices[$issue->{'devices.id'}] = $issue->{'discovery_log.id'};
            }
        }

        // Select only items from $issues where they match discovery_log.id from $issue_devices
        $new_issues = array();
        for ($i=0; $i < count($issues); $i++) {
            if ($issue_devices[$issues[$i]->{'devices.id'}] === $issues[$i]->{'discovery_log.id'}) {
                $new_issues[] = $issues[$i];
            }
        }
        $issues = $new_issues;

        for ($i=0; $i < count($issues); $i++) {
            // Derive the description and action
            $issues[$i] = $this->issue_map($issues[$i]);
            // Format the IP
            if (!empty($issues[$i]->{'devices.ip'})) {
                $issues[$i]->{'devices.ip_padded'} = ip_address_to_db($issues[$i]->{'devices.ip'});
                $issues[$i]->{'devices.ip'} = ip_address_from_db($issues[$i]->{'devices.ip'});
            }
            // Need to do the below to cater to /discoveries/:id and /discoveries as the page URL when generating relative links
            if (!empty($issues[$i]->description)) {
                $issues[$i]->description = str_replace('../help', 'help', $issues[$i]->description);
            }
        }
        return $issues;
    }

    public function issueMap(object $issue): object
    {
        if (empty($issue)) {
            return new \stdclass();
        }
        if ($issue->output === '["ERROR: Failed to open connection - NT_STATUS_LOGON_FAILURE"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Check your credentials and that they are of a machine Administrator account. Check <a href="../help/discovery_issues/1">here</a>.';
            $issue->action = 'add credentials';
        } else if ($issue->output ==='["ERROR: Failed to open connection - NT_STATUS_IO_TIMEOUT"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Check your credentials and that they are of a machine Administrator account. Check <a href="../help/discovery_issues/1">here</a>.';
            $issue->action = 'add credentials';
        } else if ($issue->output ==='["ERROR: Failed to open connection - NT_STATUS_CONNECTION_RESET"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'It is likely SMB1 was used in an attept to talk to Windows. SMB1 has been deprecated and now removed from most Windows install by Microsoft. Check <a href="../help/discovery_issues/2">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: Failed to save ADMIN$/winexesvc.exe - NT_STATUS_ACCESS_DENIED"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Are the ADMIN$ and IPC$ shares enabled? Check <a href="../help/discovery_issues/3">here</a>.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'NT_STATUS_NO_LOGON_SERVERS') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Does the target PC has a DNS resolvable name? Is the machine on the domain? Check <a href="../help/discovery_issues/4">here</a>.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'NT_STATUS_CONNECTION_REFUSED') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Is the Windows firewall restricting incoming connections? Check <a href="../help/discovery_issues/5">here</a>.'; #We would try disabling the Windows Firewall, testing and seeing if it works. Then be sure to reenable the firewall. If it did work, create a new firewall rule to allow this connection.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'NT_STATUS_NETLOGON_NOT_STARTED') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Is the network logon service is running on the target machine? Check <a href="../help/discovery_issues/6">here</a>.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'NT_STATUS_ACCOUNT_EXPIRED') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'The credentials have expired. Check <a href="../help/discovery_issues/1">here</a>.';
            $issue->action = 'add credentials';
        } else if (strpos($issue->{'output'}, 'NT_STATUS_INVALID_PARAMETER') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'It is likely SMB1 was used in an attept to talk to Windows. SMB1 has been deprecated and now removed from most Windows install by Microsoft. Check <a href="../help/discovery_issues/2">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: UploadService failed - NT_STATUS_ACCESS_DENIED"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Are the ADMIN$ and IPC$ shares enabled? Check <a href="../help/discovery_issues/3">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: StartService failed. NT_STATUS_CANT_WAIT."]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Most likely you are trying to audit a 32bit Windows machine. We support 64bit only for discovery (it\'s a winexe thing). You can copy the audit script to the target and run it manually until such time as you decommission the 32bit machine. Check <a href="../help/discovery_issues/7">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: Failed to install service winexesvc - NT_STATUS_ACCESS_DENIED"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'This likely means the user account being used does not have sufficient rights on the target machine. It may also be that the ADMIN$ share is not available on the target machine. Check <a href="../help/discovery_issues/7">here</a>.';
            $issue->action = 'add credentials';
        } else if ($issue->output ==='["ERROR: StartService failed. NT_STATUS_PLUGPLAY_NO_DEVICE."]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Does the target PC has a DNS resolvable name? Is the machine on the domain? Check <a href="../help/discovery_issues/4">here</a>.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'STATUS_SHARING_VIOLATION') !== false) {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'This may be an issue on some Windows 7 and 2008 machines. We suggest a disk defrag on the target machine as a first step. See this <a href="https://support.microsoft.com/en-us/topic/-status-sharing-violation-error-message-when-you-try-to-open-a-highly-fragmented-file-on-a-computer-that-is-running-windows-7-or-windows-server-2008-r2-be899c3b-8c5a-c883-ce0d-055d258a9178" target="_blank">link</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: CreateService failed. NT_STATUS_ACCESS_DENIED."]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'It appears that the winexesvc.exe file has been copied to the target but the service could not be registered. Check your credentials and that they are of a machine Administrator account. Check <a href="../help/discovery_issues/1">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: StartService failed. NT_STATUS_ACCESS_DENIED."]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'It appears that the winexesvc.exe file has been copied to the target and the service registered, however it fails to start. Check <a href="../help/discovery_issues/8">here</a>.';
            $issue->action = '';
        } else if ($issue->output ==='["ERROR: Failed to open connection - NT_STATUS_NOT_SUPPORTED"]') {
            # Windows connection from Linux Open-AudIT server
            $issue->description = 'Most likely this is a result of attempting to connect using SMB2 to a Windows machine that only has SMB1 enabled. You should be using SMB2 as Microsoft has deprecated SMB1 due to security vulnerabilities.';
            $issue->action = '';
        } else if ($issue->output ==='["",""]') {
            # Windows connection from Windows Open-AudIT server
            $issue->description = 'Check your credentials and that they are of a machine Administrator account. Check <a href="../help/discovery_issues/1">here</a>.';
            $issue->action = 'add credentials';
        } else if (strpos($issue->{'output'}, 'SSH detected but no valid SSH credentials') !== false) {
            # SSH connection from Open-AudIT server
            $issue->description = 'Check you have valid SSH credentials and that the Open-AudIT server IP is allowed to connect.';
            $issue->action = 'add credentials';
        } else if (strpos($issue->{'output'}, 'SNMP detected, but no valid SNMP credentials') !== false) {
            # SNMP connection from Open-AudIT server
            $issue->description = 'Check you have valid SNMP credentials and that the Open-AudIT server IP is allowed to connect.';
            $issue->action = 'add credentials';
        } else if (strpos($issue->{'output'}, 'No management protocols for') !== false) {
            # No management protcols and an unknown or unidentified device
            $issue->description = 'There are no open management ports to this device. This may be an issue or it may be a device of a type we cannot audit (in this case, please set the device type).';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'Could not SCP GET to') !== false) {
            $issue->description = 'Could not copy audit result from target to Open-AudIT Server. Check directory permissions.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'Could not convert audit result from XML') !== false) {
            $issue->description = 'The audit result contains invalid XML. Please check the file. Consider increasing the configuration item discovery_ssh_timeout.';
            $issue->action = '';
        } else if (strpos($issue->{'output'}, 'No credentials array passed to') !== false) {
            $issue->description = 'Ensure you have credentials for this type.';
            $issue->action = 'add credentials';
        } else {
            $issue->description = 'Unknown';
            $issue->action = '';
        }
        return $issue;
    }
    /**
     * Read the entire collection from the database that the user is allowed to read
     *
     * @return array  An array of formatted entries
     */
    public function listUser($where = array()): array
    {
        $instance = & get_instance();
        $org_list = array_unique(array_merge($instance->user->orgs, $instance->orgsModel->getUserDescendants($instance->user->orgs, $instance->orgs)));
        $org_list[] = 1;
        $org_list = array_unique($org_list);

        $properties = array();
        $properties[] = 'discoveries.*';
        $properties[] = 'orgs.name as `orgs.name`';
        $this->builder->select($properties, false);
        $this->builder->join('orgs', 'discoveries.org_id = orgs.id', 'left');
        $this->builder->whereIn('orgs.id', $org_list);
        if (!empty($where[0]) and !empty($where[1])) {
            $this->builder->where($where[0], $where[1]);
        }
        if (!empty($where[2]) and !empty($where[3])) {
            $this->builder->where($where[2], $where[3]);
        }
        $query = $this->builder->get();
        if ($this->sqlError($this->db->error())) {
            return array();
        }
        return format_data($query->getResult(), 'discoveries');
    }

    /**
     * Read the entire collection from the database
     *
     * @return array  An array of all entries
     */
    public function listAll(): array
    {
        $query = $this->builder->get();
        if ($this->sqlError($this->db->error())) {
            return array();
        }
        return $query->getResult();
    }

    /**
     * [queue description]
     * @param  int $id The ID of the discovery to start
     * @return bool True on success, false on failure
     */
    public function queue(int $id = 0): bool
    {
        $item = $this->read($id);
        $discovery = $item[0];
        if (empty($discovery)) {
            return false;
        }
        $instance = & get_instance();
        $queueModel = new \App\Models\QueueModel();
        $sql = 'DELETE from discovery_log WHERE discovery_id = ?';
        $this->db->query($sql, [$id]);
        $sql = "UPDATE `discoveries` SET `status` = 'running', `ip_all_count` = 0, `ip_responding_count` = 0, `ip_scanned_count` = 0, `ip_discovered_count` = 0, `ip_audited_count` = 0, `last_run` = NOW(), `last_finished` = DATE_ADD(NOW(), interval 1 second) WHERE id = ?";
        $this->db->query($sql, [$id]);
        $data = new \stdClass();
        $data->type = $discovery->attributes->type;
        $data->details = new \stdClass();
        $data->details->name = $discovery->attributes->name;
        $data->details->org_id = $discovery->attributes->org_id;
        $data->details->discovery_id = $id;
        $temp = $queueModel->create($data);
        if (!empty($temp)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Read an individual item from the database, by ID
     *
     * @param  int $id The ID of the requested item
     *
     * @return array   The array containing the requested item
     */
    public function read(int $id = 0): array
    {
        $query = $this->builder->getWhere(['id' => intval($id)]);
        if ($this->sqlError($this->db->error())) {
            return array();
        }
        $result = format_data($query->getResult(), 'discoveries');
        $sql = "SELECT discovery_scan_options.name from discovery_scan_options where id = ?";
        $query = $this->db->query($sql, [$result[0]->attributes->scan_options->id]);
        $dco = $query->getResult();
        $result[0]->attributes->scan_options->{'discovery_scan_options.name'} = $dco[0]->name;
        return $result;
    }


    /**
     * Read an individual item from the database, by ID and populate scan_options and match_options with upstream defaults
     *
     * @param  int $id The ID of the requested item
     * @return array The array of requested items
     */
    public function readForDiscovery(int $id = 0): array
    {
        $result = $this->builder->getWhere(['id' => intval($id)])->getResult();
        if (empty($result)) {
            log_message('error', 'Bad ID provided to readForDiscovery (' . $id . ').');
            return array();
        }

        if (empty($result[0]->subnet) and $result[0]->type === 'subnet' and $result[0]->name === 'Default Discovery') {
            $ips = server_ip();
            $ips = explode(',', $ips);
            $subnet = '';
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) and !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    $ip = explode('.', $ip);
                    $ip[3] = 0;
                    $ip = implode('.', $ip);
                    $subnet = $ip . '/24';
                    break;
                }
            }
            $sql = "UPDATE discoveries SET subnet = ?, description = 'Automatically created default discovery for $subnet.' WHERE id = ?";
            $this->db->query($sql, [$subnet, $id]);
            $result[0]->subnet = $subnet;
        }
        if (empty($result[0]->scan_options)) {
            $result[0]->scan_options = new \stdClass();
        }
        if (is_string($result[0]->scan_options)) {
            $result[0]->scan_options = json_decode($result[0]->scan_options);
        }
        if (empty($result[0]->match_options)) {
            $result[0]->match_options = new \stdClass();
        }
        if (is_string($result[0]->match_options)) {
            $result[0]->match_options = json_decode($result[0]->match_options);
        }
        if (!isset($result[0]->scan_options->id) or !is_numeric($result[0]->scan_options->id)) {
            if (!empty(config('Openaudit')->discovery_default_scan_option)) {
                $result[0]->scan_options->id = intval(config('Openaudit')->discovery_default_scan_option);
            } else {
                $result[0]->scan_options->id = 1;
            }
        }
        $sql = "SELECT * FROM discovery_scan_options WHERE id = ?";
        $options = $this->db->query($sql, [$result[0]->scan_options->id])->getResult();
        $discovery_scan_options = $options[0];
        unset($discovery_scan_options->id);
        unset($discovery_scan_options->name);
        unset($discovery_scan_options->org_id);
        unset($discovery_scan_options->description);
        unset($discovery_scan_options->options);
        unset($discovery_scan_options->edited_by);
        unset($discovery_scan_options->edited_date);
        if (empty($discovery_scan_options->command_options)) {
            $discovery_scan_options->command_options = new \stdClass();
        }
        if (is_string($discovery_scan_options->command_options)) {
            $discovery_scan_options->command_options = json_decode($discovery_scan_options->command_options);
        }
        unset($discovery_scan_options->command_options);
        foreach ($discovery_scan_options as $key => $value) {
            if (empty($result[0]->scan_options->{$key}) && isset($discovery_scan_options->{$key})) {
                $result[0]->scan_options->{$key} = $discovery_scan_options->{$key};
            }
        }

        if (empty($result[0]->match_options)) {
            $result[0]->match_options = '{}';
        }
        if (is_string($result[0]->match_options)) {
            $result[0]->match_options =json_decode($result[0]->match_options);
        }
        foreach (config('Openaudit') as $key => $value) {
            if (strpos($key, 'match_') !== false) {
                if (empty($result[0]->match_options->{$key}) && ! empty($CI->config->config->{$key})) {
                    $result[0]->match_options->{$key} = $CI->config->config->{$key};
                }
            }
        }
        if (!empty(config('Openaudit')->discovery_ip_exclude)) {
            // Account for users adding multiple spaces which would be converted to multiple comma's.
            $exclude_ip = preg_replace('!\s+!', ' ', config('Openaudit')->discovery_ip_exclude);
            // Convert spaces to comma's
            $exclude_ip = str_replace(' ', ',', $exclude_ip);
            if (!empty($result[0]->scan_options->exclude_ip)) {
                $result[0]->scan_options->exclude_ip .= ',' . $exclude_ip;
            } else {
                $result[0]->scan_options->exclude_ip = $exclude_ip;
            }
        }
        // Ensure we only have valid characters of digit, dot, slash, dash and comma in attribute
        if (! preg_match('/^[\d,\.,\/,\-,\,]*$/', $result[0]->scan_options->exclude_ip)) {
            $result[0]->scan_options->exclude_ip = '';
        }

        if ($result[0]->status === 'failed') {
            $sql = "SELECT * FROM `discovery_log` WHERE `id` IN (SELECT MAX(`id`) FROM `discovery_log` WHERE `ip` NOT IN (SELECT DISTINCT(`ip`) FROM discovery_log WHERE (`command_status` = 'device complete' or `message` LIKE 'IP % not responding, ignoring.' or `ip` = '127.0.0.1') AND discovery_id = ?) AND discovery_id = ? GROUP BY `ip`) AND discovery_id = ?";
            $result[0]->last_logs_for_failed_devices = $this->db->query($sql, [$id, $id, $id])->getResult();
        }
        // $result = $this->format_data($result, 'discoveries');
        return ($result);
    }


    /**
     * Reset a table
     *
     * @return bool Did it work or not?
     */
    public function reset(string $table = ''): bool
    {
        if ($this->tableReset('discoveries')) {
            return true;
        }
        return false;
    }

    /**
     * Update an individual item in the database
     *
     * @param  object  $data The data attributes
     *
     * @return bool    true || false depending on success
     */
    public function update($id = null, $data = null): bool
    {
        $data = $this->updateFieldData('discoveries', $data);
        $this->builder->where('id', intval($id));
        $this->builder->update($data);
        if ($this->sqlError($this->db->error())) {
            return false;
        }
        return true;
    }

    /**
     * The dictionary item
     *
     * @return object  The stdClass object containing the dictionary
     */
    public function dictionary(): object
    {
        $instance = & get_instance();

        $collection = 'discoveries';
        $dictionary = new \stdClass();
        $dictionary->table = $collection;
        $dictionary->columns = new \stdClass();

        $dictionary->attributes = new \stdClass();
        $dictionary->attributes->collection = array('id', 'name', 'description', 'type', 'orgs.name');
        $dictionary->attributes->create = array('name','org_id','type'); # We MUST have each of these present and assigned a value
        $dictionary->attributes->fields = $this->db->getFieldNames($collection); # All field names for this table
        $dictionary->attributes->fieldsMeta = $this->db->getFieldData($collection); # The meta data about all fields - name, type, max_length, primary_key, nullable, default
        $dictionary->attributes->update = $this->updateFields($collection); # We MAY update any of these listed fields

        $dictionary->about = '<p>Discoveries are at the very heart of what Open-AudIT does.<br /><br />How else would you know "What is on my network?"<br /><br />Discoveries are preprepared data items that enable you to run a discovery upon a network in a single click, without entering the details of that network each and every time.<br /><br />' . $instance->dictionary->link . '<br /><br /></p>';

        $dictionary->notes = '<p>Some examples of valid Subnet attributes are: 192.168.1.1 (a single IP address), 192.168.1.0/24 (a subnet), 192.168.1-3.1-20 (a range of IP addresses).<br /><br /><em>NOTE</em> - Only a subnet (as per the examples - 192.168.1.0/24) will be able to automatically create a valid network for Open-AudIT. <br /><br />If you use an Active Directory type, make sure you have appropriate credentials to talk to your Domain Controller already in <a href="../credentials">credentials</a>.<br /><br /></p>';

        $dictionary->product = 'community';
        $dictionary->columns->id = $instance->dictionary->id;
        $dictionary->columns->name = $instance->dictionary->name;
        $dictionary->columns->org_id = $instance->dictionary->org_id;
        $dictionary->columns->description = 'This description is auto-populated and should ideally be left as-is.';
        $dictionary->columns->type = "Supported types are 'subnet', 'seed' and 'active directory'.";
        $dictionary->columns->devices_assigned_to_org = "Any discovered devices will be assigned to this Org if set. If not set, they are assigned to the 'org_id' of this discovery. Links to <code>orgs.id</code>.";
        $dictionary->columns->devices_assigned_to_location = 'Any discovered devices will be assigned to this Location if set. Links to <code>locations.id</code>.';
        $dictionary->columns->network_address = 'The URL the audit scripts should submit their result to.';
        $dictionary->columns->last_run = 'A calculated field that is updated each time the discovery has been executed.';
        $dictionary->columns->complete = 'An internal field that indicates if the discovery has completed.';
        $dictionary->columns->scan_options = 'A JSON document containing the required attributes overriding the chosen discovery_scan_options.';
        $dictionary->columns->match_options = 'A JSON document containing the required attributes overriding the default device match options.';
        $dictionary->columns->subnet = 'The network subnet to execute the discovery on.';
        $dictionary->columns->ad_server = 'The Active Directory server to retrieve a list of subnets from.';
        $dictionary->columns->ad_domain = 'The Active Directory domain to retrieve a list of subnets from.';
        $dictionary->columns->device_id = 'Used internally when discovering a single device. Links to <code>devices.id</code>.';
        $dictionary->columns->device_count = 'The number of devices found by this discovery.';
        $dictionary->columns->limit = 'The number of devices to limit this discovery to.';
        $dictionary->columns->discard = 'Used internally when discovering a single device.';
        $dictionary->columns->seed_ip = 'The IP of the device to start a seed discovery with.';
        $dictionary->columns->seed_ping = 'For a seed discovery, should I ping the subnet before running the discovery.';
        $dictionary->columns->seed_restrict_to_subnet = 'For a seed discovery, should I only discover IPs on the chosen subnet.';
        $dictionary->columns->seed_restrict_to_private = 'For a seed discovery, should I only discover IPs in the private IP address space.';
        $dictionary->columns->status = 'The current status of the discovery.';
        $dictionary->columns->edited_by = $instance->dictionary->edited_by;
        $dictionary->columns->edited_date = $instance->dictionary->edited_date;

        $dictionary->columns->match_dbus = 'Should we match a device based on its dbus id.';
        $dictionary->columns->match_fqdn = 'Should we match a device based on its fqdn.';
        $dictionary->columns->match_dns_fqdn = 'Should we match a device based on its DNS fqdn.';
        $dictionary->columns->match_dns_hostname = 'Should we match a device based on its DNS hostname.';
        $dictionary->columns->match_hostname = 'Should we match a device based only on its hostname.';
        $dictionary->columns->match_hostname_dbus = 'Should we match a device based on its hostname and dbus id.';
        $dictionary->columns->match_hostname_serial = 'Should we match a device based on its hostname and serial.';
        $dictionary->columns->match_hostname_uuid = 'Should we match a device based on its hostname and UUID.';
        $dictionary->columns->match_ip = 'Should we match a device based on its ip.';
        $dictionary->columns->match_ip_no_data = 'Should we match a device based on its ip if we have an existing device with no data.';
        $dictionary->columns->match_mac = 'Should we match a device based on its mac address.';
        $dictionary->columns->match_mac_vmware = 'Should we match a device based mac address even if its a known likely duplicate from VMware.';
        $dictionary->columns->match_serial = 'Should we match a device based on its serial number.';
        $dictionary->columns->match_serial_type = 'Should we match a device based on its serial and type.';
        $dictionary->columns->match_sysname = 'Should we match a device based only on its SNMP sysName.';
        $dictionary->columns->match_sysname_serial = 'Should we match a device based only on its SNMP sysName and serial.';
        $dictionary->columns->match_uuid = 'Should we match a device based on its UUID.';

        $dictionary->columns->{'scan_options.id'} = 'Links to discovery_scan_options.id.';
        $dictionary->columns->{'scan_options.ping'} = 'The device must respond to an Nmap ping before it is considered online.';
        $dictionary->columns->{'scan_options.service_version'} = 'This will considerably slow the discovery scan.';
        $dictionary->columns->{'scan_options.open|filtered'} = 'If a port responds with open|filtered, should we consider it available.';
        $dictionary->columns->{'scan_options.filtered'} = 'If a port responds with filtered, should we consider it available.';
        $dictionary->columns->{'scan_options.timing'} = 'The Nmap timing preset.';
        $dictionary->columns->{'scan_options.nmap_tcp_ports'} = 'Scan the Nmap top number of TCP ports.';
        $dictionary->columns->{'scan_options.nmap_udp_ports'} = 'Scan the Nmap top number of UDP ports.';
        $dictionary->columns->{'scan_options.tcp_ports'} = 'A list of custom TCP ports to scan.';
        $dictionary->columns->{'scan_options.udp_ports'} = 'A list of custom UDP ports to scan.';
        $dictionary->columns->{'scan_options.timeout'} = 'How long should Nmap wait for a response, per device.';
        $dictionary->columns->{'scan_options.exclude_tcp_ports'} = 'Do not scan these TCP ports.';
        $dictionary->columns->{'scan_options.exclude_udp_ports'} = 'Do not scan these UDP ports.';
        $dictionary->columns->{'scan_options.exclude_ip'} = 'Exclude these IP addresses from being Nmap scanned.';
        $dictionary->columns->{'scan_options.ssh_ports'} = 'Check this port for any SSH service.';
        return $dictionary;
    }
}