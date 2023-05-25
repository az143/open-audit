<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);


if (!function_exists('audit_convert')) {
    function audit_convert($input)
    {
        $log = new stdClass();
        $log->discovery_id = '';
        $log->ip = '';
        $log->file = 'audit_helper';
        $log->function = 'audit_convert';
        $log->command = '';

        if (is_string($input)) {
            // See if we have stringified JSON
            $json = html_entity_decode($input);
            if (mb_detect_encoding($json) !== 'UTF-8') {
                $json = utf8_encode($json);
            }
            $json = @json_decode($json);
            if ($json) {
                $audit = new stdClass();
                if (!empty($json->sys)) {
                    $audit->system = $json->sys;
                    unset($json->sys);
                }
                if (!empty($json->system)) {
                    $audit->system = $json->system;
                    unset($json->system);
                }
                foreach ($audit->system as $key => $value) {
                    if (empty($value)) {
                        unset($audit->system->{$key});
                    }
                }
                foreach ($json as $section => $something) {
                    $audit->{$section} = array();
                    if (!empty($json->{$section}->item) and is_array($json->{$section}->item)) {
                        $audit->{$section}[] = $json->{$section}->item[0];
                    } else {
                        if (is_array($json->{$section})) {
                            $audit->{$section} = $json->{$section};
                        }
                    }
                }
                foreach ($audit as $section => $something) {
                    if ($section !== 'system' && $section !== 'sys') {
                        for ($i=0; $i < count($audit->{$section}); $i++) {
                            if (!empty($audit->{$section}[$i])) {
                                foreach ($audit->{$section}[$i] as $key => $value) {
                                    if (empty($value)) {
                                        unset($audit->{$section}[$i]->{$key});
                                    }
                                }
                            }
                        }
                    }
                }
                unset($input);
                $log->message = 'string converted from JSON';
                $input = $audit;
            }
        }

        if (is_string($input)) {
            // See if we have stringified XML
            $xml = html_entity_decode($input);
            if (mb_detect_encoding($xml) !== 'UTF-8') {
                $xml = utf8_encode($xml);
            }
            $xml = iconv('UTF-8', 'UTF-8//TRANSLIT', $xml);
            $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $xml);
            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string($xml);
            if ($xml === false) {
                foreach (libxml_get_errors() as $error) {
                    $log->message = 'Could not convert string to XML';
                    $log->command_status = 'fail';
                    $log->command_output = $error->message . ' at ' . $error->line . ', column ' . $error->column . ', with code ' . $error->code;
                    discovery_log($log);
                }
                return false;
            }
            if (!empty($xml)) {
                $newxml = json_encode($xml);
                $newxml = json_decode($newxml);
                $audit = new stdClass();
                $audit->system = new stdClass();
                if (!empty($newxml->sys)) {
                    foreach ($newxml->sys as $key => $value) {
                        if (gettype($value) !== "object" && @(string)$value !== '') {
                            $audit->system->{$key} = @(string)$newxml->sys->{$key};
                        }
                    }
                }
                if (!empty($newxml->system)) {
                    foreach ($newxml->system as $key => $value) {
                        if (gettype($value) !== "object" && @(string)$value !== '') {
                            $audit->system->{$key} = @(string)$newxml->system->{$key};
                        }
                    }
                }
                unset($newxml);
                foreach ($xml as $section => $something) {
                    if ($section !== 'sys') {
                        $audit->{$section} = array();
                        foreach ($xml->{$section}->item as $item) {
                            $newitem = new stdClass();
                            foreach ($item as $key => $value) {
                                if ($key === 'options' && $section === 'policy') {
                                    $json = false;
                                    $json = @json_decode((string)$value);
                                    if (!empty($json)) {
                                        $values = $json;
                                    } else {
                                        $values = $value;
                                    }
                                    $new = new stdClass();
                                    foreach ($values as $k => $v) {
                                        $new->{$k} = (string) $v;
                                    }
                                    $newitem->options = @json_encode($new);
                                } else if ($key === 'keys' && $section === 'user') {
                                    $new = array();
                                    foreach ($value->key as $k => $v) {
                                        $new[] = (string)$v;
                                    }
                                    $newitem->keys = @json_encode($new);
                                } else {
                                    if ((string)$value !== '') {
                                        $newitem->{$key} = (string)$value;
                                    }
                                }
                            }
                            $audit->{$section}[] = $newitem;
                        }
                    }
                }
                unset($input);
                $input = $audit;
            }
        }

        if (is_string($input)) {
            // We have a string that could not be converted
            $log->severity = 5;
            if (!empty($parameters->discovery_id)) {
                $log->message = 'Could not convert string to JSON or XML';
                $log->command_status = 'fail';
                discovery_log($log);
            } else {
                $log->summary = 'Could not convert string to JSON or XML';
                $log->status = 'fail';
                stdlog($log);
            }
            return false;
        } else {
            if (!empty($audit->system->discovery_id)) {
                $log->discovery_id = intval($audit->system->discovery_id);
            }
            if (!empty($audit->system->id)) {
                $log->device_id = intval($audit->system->id);
            }
            if (!empty($audit->system->ip) && empty($log->ip)) {
                $log->ip = $audit->system->ip;
            }
        }

        $log->severity = 7;
        $log->message = 'Audit converted';
        if (!empty($log->discovery_id)) {
            $log->command_status = 'success';
            discovery_log($log);
        } else {
            $log->status = 'success';
            #stdlog($log);
        }
        return $input;
    }
}

if (! function_exists('deviceMatch')) {
    function deviceMatch(object $device = null, object $log = null, int $discovery_id = 0, object $match = null)
    {
        $db = db_connect();

        if (empty($device) or (empty($log) and empty($discovery_id))) {
            // $mylog = new stdClass();
            // $mylog->severity = 4;
            // $mylog->status = 'fail';
            // $mylog->message = 'Function match called without correct params object';
            // $mylog->file = 'm_device';
            // $mylog->function = 'match';
            // stdlog($mylog);
            // return false;
        }

        // we are searching for a devices.id.
        $details = @$device->details;
        if (empty($details)) {
            $details = @$device->system;
        }
        $details->id = '';

        $log = new stdClass();
        if (!empty($parameters->log)) {
            $log = $parameters->log;
            if (empty($log->discovery_id) and !empty($parameters->discovery_id)) {
                $log->discovery_id = $parameters->discovery_id;
            }
        } else if (!empty($parameters->discovery_id)) {
            $log->discovery_id = $parameters->discovery_id;
        }
        $log->file = 'm_device';
        $log->function = 'match';
        $log->severity = 7;
        $log->ip = '';
        if (!empty($details->ip)) {
            $log->ip = ip_address_from_db($details->ip);
        }
        $log->timestamp = (!empty($device->system->last_seen)) ? $device->system->last_seen : @config('OpenAudit')->timestamp;

        $log_message = array(); // we will store our messages until we get a devices.id, then write them to the log

        $message = new stdClass();
        $message->message = 'Running devices::match function.';
        $message->command_status = 'notice';
        $message->command_output = '';
        $log_message[] = $message;

        if (is_null($match)) {
            $match = new stdClass();
        }

        // Ensure we have a fully populated (even if blank) match list
        $matches = array('match_dbus', 'match_fqdn', 'match_dns_fqdn', 'match_dns_hostname', 'match_hostname', 'match_hostname_dbus', 'match_hostname_serial', 'match_hostname_uuid', 'match_ip', 'match_ip_no_data', 'match_mac', 'match_mac_vmware', 'match_serial', 'match_serial_type', 'match_sysname', 'match_sysname_serial', 'match_uuid');
        foreach ($matches as $item) {
            if (empty($match->{$item})) {
                $match->{$item} = @config('OpenAudit')->{$item};
            }
        }
        $invalid_strings = array('To be filled by O.E.M.');

        // TODO: fix this by making sure (snmp in particular) calls with the proper variable name
        if (!isset($details->mac_address) && (isset($details->mac))) {
            $details->mac_address = $details->mac;
        }

        // check if we have an ip address or a hostname (possibly a fqdn)
        if (!empty($details->hostname)) {
            if (!filter_var($details->hostname, FILTER_VALIDATE_IP)) {
                if (strpos($details->hostname, '.') !== false) {
                    $message = new stdClass();
                    $message->message = "Provided hostname contains a '.' and is not a valid IP. Assuming a FQDN.";
                    $message->command_status = 'notice';
                    $message->command_output = 'Hostname: ' . $details->hostname;
                    $log_message[] = $message;
                    if (empty($details->fqdn)) {
                        $details->fqdn = $details->hostname;
                        $message = new stdClass();
                        $message->message = 'No FQDN provided, storing hostname as FQDN.';
                        $message->command_status = 'notice';
                        $message->command_output = 'FQDN: ' . $details->fqdn;
                        $log_message[] = $message;
                    }
                    $temp = explode('.', $details->hostname);
                    $hostname = $temp[0];
                    $details->hostname = $hostname;
                    $message = new stdClass();
                    $message->message = "Using first split '.' from hostname as hostname.";
                    $message->command_status = 'notice';
                    $message->command_output = 'Hostname: ' . $details->hostname;
                    $log_message[] = $message;
                    unset($temp);
                }
            } else {
                // we have an ip address in the hostname field - remove it
                // possibly because DNS is not fully setup and working correctly
                $message = new stdClass();
                $message->message = 'Provided hostname is actually an IP address.';
                $message->command_status = 'notice';
                $message->command_output = 'Hostname: ' . (string)$details->hostname;
                $log_message[] = $message;
                if (empty($details->ip)) {
                    $details->ip = @($details->hostname);
                    $message = new stdClass();
                    $message->message = 'No IP provided, but provided hostname is an IP. Storing in IP.';
                    $message->command_status = 'notice';
                    $message->command_output = 'IP: ' . $details->ip;
                    $log_message[] = $message;
                }
                unset($details->hostname);
            }
        } else {
            $message = new stdClass();
            $message->message = 'Provided hostname is empty.';
            $message->command_status = 'notice';
            $message->command_output = 'Hostname: ';
            $log_message[] = $message;
        }

        if (!empty($details->hostname) && ! empty($details->domain) && $details->domain !== '.' && empty($details->fqdn)) {
            $details->fqdn = $details->hostname . '.' . $details->domain;
            $message = new stdClass();
            $message->message = 'No FQDN provided, but hostname and domain provided, setting FQDN.';
            $message->command_status = 'notice';
            $message->command_output = 'FQDN: ' . $details->fqdn;
            $log_message[] = $message;
        }

        if (empty($details->fqdn)) {
            $details->fqdn = '';
        }

        if (!empty($details->mac_address)) {
            $details->mac_address = strtolower($details->mac_address);
            if ($details->mac_address === '00:00:00:00:00:00') {
                unset($details->mac_address);
                $message = new stdClass();
                $message->message = 'All 00: mac address provided, removing.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        } else {
            unset($details->mac_address);
        }

        if (empty($details->ip) or $details->ip === '0.0.0.0' or $details->ip === '000.000.000.000') {
            $details->ip = '';
            $message = new stdClass();
            $message->message = "IP possibly not provided, or blank or all zero's, removing.";
            $message->command_status = 'notice';
            $message->command_output = '';
            $log_message[] = $message;
        } else {
            $log->ip = ip_address_from_db($details->ip);
        }

        // Match based on the OMK uuid
        if (!empty($details->omk_uuid) && empty($details->id)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.omk_uuid = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->omk_uuid}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on NMIS uuid';
                $message->command_status = 'success';
                $message->command_output = 'NMIS UUID: ' . $details->omk_uuid . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            } else {
                $message = new stdClass();
                $message->message = 'MISS on NMIS uuid';
                $message->command_status = 'notice';
                $message->command_output = 'NMIS UUID: ' . $details->omk_uuid;
                $log_message[] = $message;
            }
        }

        // Match based on the Google Cloud id (instance_ident)
        if (!empty($details->instance_ident) && empty($details->id)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.instance_ident = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->instance_ident}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on instance_ident';
                $message->command_status = 'success';
                $message->command_output = 'Instance Ident: ' . $details->instance_ident . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            } else {
                $message = new stdClass();
                $message->message = 'MISS on instance_ident';
                $message->command_status = 'notice';
                $message->command_output = 'Instance Ident: ' . $details->instance_ident;
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_hostname_uuid) === 'y' && empty($details->id) && ! empty($details->uuid) && ! empty($details->hostname)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.hostname = ? AND devices.uuid = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->hostname}", "{$details->uuid}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT hostname + uuid';
                $message->command_status = 'success';
                $message->command_output = 'Hostname: ' . $details->hostname . ', UUID: ' . $details->uuid . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on match_hostname_uuid.';
            $message->command_status = 'notice';
            $message->command_output = 'Hostname: ' . $details->hostname . ', UUID: ' . $details->uuid;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_hostname_uuid) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_uuid, matching rule set to: ' . $match->match_hostname_uuid .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_uuid, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->uuid)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_uuid, uuid not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->hostname)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_uuid, hostname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_uuid.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_hostname_dbus) === 'y' && empty($details->id) && ! empty($details->dbus_identifier) && ! empty($details->hostname)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.hostname = ? AND devices.dbus_identifier = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->hostname}", "{$details->dbus_identifier}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on hostname + dbus_identifier.';
                $message->command_status = 'success';
                $message->command_output = 'Hostname: ' . $details->hostname . ', DbusID: ' . $details->dbus_identifier . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on match_hostname_dbus.';
            $message->command_status = 'notice';
            $message->command_output = 'Hostname: ' . $details->hostname . ', DbusID: ' . $details->dbus_identifier;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_hostname_dbus) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_dbus, matching rule set to: ' . $match->match_hostname_dbus .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_dbus, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->dbus_identifier)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_dbus, dbus_identifier not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->hostname)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_dbus, hostname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_dbus.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_hostname_serial) === 'y' && empty($details->id) && ! empty($details->serial) && ! empty($details->hostname) && ! in_array($details->serial, $invalid_strings)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.hostname = ? AND devices.serial = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->hostname}", "{$details->serial}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on hostname + serial.';
                $message->command_status = 'success';
                $message->command_output = 'Hostname: ' . $details->hostname . ', Serial: ' . $details->serial . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on hostname + serial.';
            $message->command_status = 'notice';
            $message->command_output = 'Hostname: ' . $details->hostname . ', Serial: ' . $details->serial;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_hostname_serial) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial, matching rule set to: ' . $match->match_hostname_serial .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->serial)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial, serial not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->hostname)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial, hostname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (in_array($details->serial, $invalid_strings)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial, invalid serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_hostname_serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_dbus) === 'y' && empty($details->id) && ! empty($details->dbus_identifier)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.dbus_identifier = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->dbus_identifier}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on dbus_identifier.';
                    $message->command_status = 'success';
                    $message->command_output = 'DbusID: ' . $details->dbus_identifier . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on dbus_identifier + org_id, but hit on dbus_identifier alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'DbusID: ' . $details->dbus_identifier . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on match_dbus.';
            $message->command_status = 'notice';
            $message->command_output = 'DbusID: ' . $details->dbus_identifier;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_dbus) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_dbus, matching rule set to: ' . $match->match_dbus .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_dbus, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->dbus_identifier)) {
                $message = new stdClass();
                $message->message = 'Not running match_dbus, dbus_identifier not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_dbus.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_dns_fqdn) === 'y' && empty($details->id) && ! empty($details->dns_fqdn)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.dns_fqdn = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->dns_fqdn}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on dns_fqdn.';
                    $message->command_status = 'success';
                    $message->command_output = 'DNS FQDN: ' . $details->dns_fqdn . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on dns_fqdn + org_id, but hit on dns_fqdn alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'DNS FQDN: ' . $details->dns_fqdn . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on dns_fqdn.';
            $message->command_status = 'notice';
            $message->command_output = 'DNS FQDN: ' . $details->dns_fqdn;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_dns_fqdn) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_dns_fqdn, matching rule set to: ' . $match->match_dns_fqdn .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_dns_fqdn, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->dns_fqdn)) {
                $message = new stdClass();
                $message->message = 'Not running match_dns_fqdn, dns_fqdn not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_dns_fqdn.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_dns_hostname) === 'y' && empty($details->id) && ! empty($details->dns_hostname)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.dns_hostname = ? AND devices.status != 'deleted' LIMIT 1";
            $data = array("{$details->dns_hostname}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on dns_hostname.';
                    $message->command_status = 'success';
                    $message->command_output = 'DNS HOSTNAME: ' . $details->dns_hostname . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on dns_hostname + org_id, but hit on dns_hostname alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'DNS Hostname: ' . $details->dns_hostname . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on dns_hostname.';
            $message->command_status = 'notice';
            $message->command_output = 'DNS HOSTNAME: ' . $details->dns_hostname;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_dns_hostname) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_dns_hostname, matching rule set to: ' . $match->match_dns_hostname .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_dns_hostname, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->dns_hostname)) {
                $message = new stdClass();
                $message->message = 'Not running match_dns_hostname, dns_fqdn not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_dns_hostname.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_fqdn) === 'y' && empty($details->id) && ! empty($details->fqdn)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.fqdn = ? AND devices.status != 'deleted' LIMIT 1";
            
            $data = array("{$details->fqdn}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on fqdn.';
                    $message->command_status = 'success';
                    $message->command_output = 'FQDN: ' . $details->fqdn . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on fqdn + org_id, but hit on fqdn alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'FQDN: ' . $details->dns_fqdn . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on fqdn.';
            $message->command_status = 'notice';
            $message->command_output = 'FQDN: ' . $details->fqdn;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_fqdn) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_fqdn, matching rule set to: ' . $match->match_fqdn .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_fqdn, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->fqdn)) {
                $message = new stdClass();
                $message->message = 'Not running match_fqdn, fqdn not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_fqdn.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_serial_type) === 'y' && empty($details->id) && ! empty($details->serial) && ! empty($details->type) && ! in_array($details->serial, $invalid_strings)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.serial = ? AND devices.type = ? AND devices.status != 'deleted' LIMIT 1";
            
            $data = array("{$details->serial}", "{$details->type}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on serial + type.';
                    $message->command_status = 'success';
                    $message->command_output = 'Serial: ' . $details->serial . ', type: ' . $details->type . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on serial + type + org_id, but hit on serial + type. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'Serial: ' . $details->serial . ', Type: ' . $details->type . ', "OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on serial + type.';
            $message->command_status = 'notice';
            $message->command_output = 'Serial: ' . $details->serial . ', Type: ' . $details->type;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_serial_type) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type, matching rule set to: ' . $match->match_serial_type .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->serial)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type, serial not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->type)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type, type not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (in_array($details->serial, $invalid_strings)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type, invalid serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_serial_type.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_serial) === 'y' && empty($details->id) && ! empty($details->serial) && ! in_array($details->serial, $invalid_strings)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.serial = ? AND devices.status != 'deleted' LIMIT 1";
            
            $data = array("{$details->serial}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on serial.';
                    $message->command_status = 'success';
                    $message->command_output = 'Serial: ' . $details->serial . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on serial + org_id, but hit on serial alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'Serial: ' . $details->serial . ' OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on serial.';
            $message->command_status = 'notice';
            $message->command_output = 'Serial: ' . $details->serial;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_serial) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_serial, matching rule set to: ' . $match->match_serial .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->serial)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial, serial not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (in_array($details->serial, $invalid_strings)) {
                $message = new stdClass();
                $message->message = 'Not running match_serial, invalid serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_sysname_serial) === 'y' && empty($details->id) && ! empty($details->serial) && ! empty($details->sysName) && ! in_array($details->serial, $invalid_strings)) {
            $sql = "SELECT devices.id FROM devices WHERE devices.sysName = ? AND devices.serial = ? AND devices.status != 'deleted' LIMIT 1";
            
            $data = array("{$details->sysName}", "{$details->serial}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on sysname + serial.';
                $message->command_status = 'success';
                $message->command_output = 'SysName: ' . $details->sysName . ', Serial: ' . $details->serial . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on sysname + serial.';
            $message->command_status = 'notice';
            $message->command_output = 'SysName: ' . $details->sysName . ', Serial: ' . $details->serial;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_sysname_serial) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial, matching rule set to: ' . $match->match_sysname_serial .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->sysName)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial, sysname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->serial)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial, serial not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (in_array($details->serial, $invalid_strings)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial, invalid serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_sysname) === 'y' && empty($details->id) && ! empty($details->sysName)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE (devices.sysName = ?) AND devices.status != 'deleted'";
            
            $data = array("{$details->sysName}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on sysName.';
                    $message->command_status = 'success';
                    $message->command_output = 'SysName: ' . $details->sysName . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on sysname + org_id, but hit on sysname alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'SysName: ' . $details->sysName . ' OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
        } else {
            if (strtolower($match->match_sysname_serial) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_sysname, matching rule set to: ' . $match->match_sysname .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->sysName)) {
                $message = new stdClass();
                $message->message = 'Not running match_sysname, sysname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_sysname_serial.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_mac) === 'y' && empty($details->id) && ! empty($details->mac_address)) {
            if (strtolower($match->match_mac_vmware) === 'n') {
                $sql = "SELECT devices.id FROM devices LEFT JOIN ip ON (devices.id = ip.device_id AND ip.current = 'y') WHERE ip.mac = ? AND LOWER(ip.mac) NOT LIKE '00:0c:29:%' AND ip.mac NOT LIKE '00:50:56:%' AND ip.mac NOT LIKE '00:05:69:%' AND LOWER(ip.mac) NOT LIKE '00:1c:14:%' AND devices.status != 'deleted' LIMIT 1";
            } else {
                $sql = "SELECT devices.id FROM devices LEFT JOIN ip ON (devices.id = ip.device_id AND ip.current = 'y') WHERE ip.mac = ? AND devices.status != 'deleted' LIMIT 1";
            }
            $data = array("{$details->mac_address}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on Mac Address (ip table).';
                $message->command_status = 'success';
                $message->command_output = 'MAC: ' . $details->mac_address . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on Mac Address (ip table).';
            if (strtolower($match->match_mac_vmware) === 'n') {
                if (strpos($details->mac_address, '00:0c:29:') === 0 or strpos($details->mac_address, '00:50:56:') === 0 or strpos($details->mac_address, '00:05:69:') === 0 or strpos($details->mac_address, '00:1c:14:') === 0) {
                    $message->message = 'MISS on Mac Address, VMware specified not match (ip table).';
                }
            }
            $message->command_status = 'notice';
            $message->command_output = 'MAC: ' . $details->mac_address;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_mac) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip table), matching rule set to: ' . $match->match_mac .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip table), device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->mac_address)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip table), mac_address not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_mach (ip table).';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_mac) === 'y' && empty($details->id) && ! empty($details->mac_address)) {
            if (strtolower($match->match_mac_vmware) === 'n') {
                $sql = "SELECT devices.id FROM devices LEFT JOIN network ON (devices.id = network.device_id AND network.current = 'y') WHERE network.mac = ? AND LOWER(network.mac) NOT LIKE '00:0c:29:%' AND network.mac NOT LIKE '00:50:56:%' AND network.mac NOT LIKE '00:05:69:%' AND LOWER(network.mac) NOT LIKE '00:1c:14:%' AND devices.status != 'deleted' LIMIT 1";
            } else {
                $sql = "SELECT devices.id FROM devices LEFT JOIN network ON (devices.id = network.device_id AND network.current = 'y') WHERE network.mac = ? AND devices.status != 'deleted' LIMIT 1";
            }
            $data = array("{$details->mac_address}");
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                $details->id = $row->id;
                $log->device_id = $details->id;
                $message = new stdClass();
                $message->message = 'HIT on Mac Address (network table).';
                $message->command_status = 'success';
                $message->command_output = 'MAC: ' . $details->mac_address . ', ID: ' . $details->id;
                $log_message[] = $message;
                log_array($log, $log_message);
                return $details->id;
            }
            $message = new stdClass();
            $message->message = 'MISS on Mac Address (network table).';
            if (strtolower($match->match_mac_vmware) === 'n') {
                if (strpos($details->mac_address, '00:0c:29:') === 0 or strpos($details->mac_address, '00:50:56:') === 0 or strpos($details->mac_address, '00:05:69:') === 0 or strpos($details->mac_address, '00:1c:14:') === 0) {
                    $message->message = 'MISS on Mac Address, VMware specified not match (network table).';
                }
            }
            $message->command_status = 'notice';
            $message->command_output = 'MAC: ' . $details->mac_address;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_mac) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table), matching rule set to: ' . $match->match_mac .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table), device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->mac_address)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table), mac_address not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_mach (network table).';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }


        if (strtolower($match->match_mac) === 'y' && empty($details->id) && ! empty($details->mac_addresses)) {
            foreach ($details->mac_addresses as $mac) {
                if (strtolower($match->match_mac_vmware) === 'n') {
                    $sql = "SELECT devices.id FROM devices LEFT JOIN network ON (devices.id = network.device_id AND network.current = 'y') WHERE network.mac = ? AND LOWER(network.mac) NOT LIKE '00:0c:29:%' AND network.mac NOT LIKE '00:50:56:%' AND network.mac NOT LIKE '00:05:69:%' AND LOWER(network.mac) NOT LIKE '00:1c:14:%' AND devices.status != 'deleted' LIMIT 1";
                } else {
                    $sql = "SELECT devices.id FROM devices LEFT JOIN network ON (devices.id = network.device_id AND network.current = 'y') WHERE network.mac = ? AND devices.status != 'deleted' LIMIT 1";
                }
                $data = array("{$mac}");
                $query = $db->query($sql, $data);
                $row = $query->getRow();
                if (!empty($row->id)) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on Mac Address (network table all).';
                    $message->command_status = 'success';
                    $message->command_output = 'MAC: ' . $mac . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                }
                $message = new stdClass();
                $message->message = 'MISS on Mac Address (network table) all.';
                if (strtolower($match->match_mac_vmware) === 'n') {
                    if (strpos($mac, '00:0c:29:') === 0 or strpos($mac, '00:50:56:') === 0 or strpos($mac, '00:05:69:') === 0 or strpos($mac, '00:1c:14:') === 0) {
                        $message->message = 'MISS on Mac Address, VMware specified not match (network table) all.';
                    }
                }
                $message->command_status = 'notice';
                $message->command_output = 'MAC: ' . $mac;
                $log_message[] = $message;
            }
        } else {
            if (strtolower($match->match_mac) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table) all, matching rule set to: ' . $match->match_mac .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table) all, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->mac_address)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table) all, mac_address not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_mac (network table) all.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }


        if (strtolower($match->match_mac) === 'y' && empty($details->id) && ! empty($details->mac_addresses)) {
            // check all MAC addresses - this caters for an actual audit script result
            foreach ($details->mac_addresses as $mac) {
                if (!empty($mac) && (string)$mac !== '00:00:00:00:00:00') {
                    // check the ip table
                    if (strtolower($match->match_mac_vmware) === 'n') {
                        $sql = "SELECT devices.id FROM devices LEFT JOIN ip ON (devices.id = ip.device_id AND ip.current = 'y') WHERE ip.mac = ? AND LOWER(ip.mac) NOT LIKE '00:0c:29:%' AND ip.mac NOT LIKE '00:50:56:%' AND ip.mac NOT LIKE '00:05:69:%' AND LOWER(ip.mac) NOT LIKE '00:1c:14:%' AND devices.status != 'deleted' LIMIT 1";
                    } else {
                        $sql = "SELECT devices.id FROM devices LEFT JOIN ip ON (devices.id = ip.device_id AND ip.current = 'y') WHERE ip.mac = ? AND devices.status != 'deleted' LIMIT 1";
                    }
                    $data = array("{$mac}");
                    $query = $db->query($sql, $data);
                    $row = $query->getRow();
                    if (!empty($row->id)) {
                        $details->id = $row->id;
                        $log->device_id = $details->id;
                        $message = new stdClass();
                        $message->message = 'HIT on Mac Address (addresses).';
                        $message->command_status = 'success';
                        $message->command_output = "MAC: {$mac} , ID: {$details->id}";
                        $log_message[] = $message;
                        log_array($log, $log_message);
                        return $details->id;
                    }
                }
                $message = new stdClass();
                $message->message = 'MISS on Mac Address (addresses).';
                if (strtolower($match->match_mac_vmware) === 'n') {
                    if (strpos($mac, '00:0c:29:') === 0 or strpos($mac, '00:50:56:') === 0 or strpos($mac, '00:05:69:') === 0 or strpos($mac, '00:1c:14:') === 0) {
                        $message->message = 'MISS on Mac Address, VMware specified not match (ip) all.';
                    }
                }
                $message->command_status = 'notice';
                $message->command_output = "MAC: {$mac} , ID: {$details->id}";
                $log_message[] = $message;
            }
        } else {
            if (strtolower($match->match_mac) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip) all, matching rule set to: ' . $match->match_mac .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip) all, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->mac_addresses)) {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip) all, mac_addresses not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_mac (ip) all.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        // check IP Address in devices, then ip tables
        $ip = ip_address_from_db(@$details->ip);
        if (strtolower($match->match_ip) === 'y' && empty($details->id) && ! empty($details->ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
            // first check the ip table as any existing devices that have been seen
            // by more than just Nmap will have an entry here
            $sql = "SELECT devices.id, devices.org_id FROM devices LEFT JOIN ip ON (devices.id = ip.device_id AND ip.current = 'y') WHERE ip.ip = ? AND ip.ip NOT LIKE '127%' AND ip.ip NOT LIKE '1::%' AND devices.status != 'deleted' LIMIT 1";
            
            $data = array(ip_address_to_db($details->ip));
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    if (!empty($details->device_id)) {
                        $log->device_id = $details->id;
                    } else if (!empty($details->id)) {
                        $log->device_id = $details->id;
                    }
                    $message = new stdClass();
                    $message->message = 'HIT on IP Address (network table).';
                    $message->command_status = 'success';
                    $message->command_output = 'IP: ' . $details->ip . ', ID: ' . $details->id . ' OrgID: ' . $details->org_id . ', Config: ' . @$this->config->config['discovery_use_org_id_match'] . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on IP Address (network table) + org_id, but hit on IP (network table) alone. Check assigned_to_org in discovery.' . json_encode($details) . json_encode($row);
                    $message->command_status = 'notice';
                    $message->command_output = 'IP: ' . $details->ip . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }

            // next check the devices table for a ip match
            if (empty($details->id)) {
                $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.ip = ? AND devices.ip NOT LIKE '127%' AND devices.ip NOT LIKE '1::%' AND devices.status != 'deleted'";
                
                $data = array(ip_address_to_db($details->ip));
                $query = $db->query($sql, $data);
                $row = $query->getRow();
                if (!empty($row->id)) {
                    if ((empty($details->org_id)) or
                        (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                        (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                        $details->id = $row->id;
                        $log->device_id = $details->id;
                        $message = new stdClass();
                        $message->message = 'HIT on IP Address (devices table).';
                        $message->command_status = 'success';
                        $message->command_output = 'IP: ' . $details->ip . ', ID: ' . $details->id;
                        $log_message[] = $message;
                        log_array($log, $log_message);
                        return $details->id;
                    } else {
                        $message = new stdClass();
                        $message->message = 'MISS on IP Address (devices table) + org_id, but hit on IP (devices table) alone. Check assigned_to_org in discovery.';
                        $message->command_status = 'notice';
                        $message->command_output = 'IP: ' . $details->ip . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                        $log_message[] = $message;
                    }
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on IP Address.';
            $message->command_status = 'notice';
            $message->command_output = 'IP: ' . $details->ip;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_ip) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_ip, matching rule set to: ' . $match->match_ip .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_ip, device id already set';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->ip)) {
                $message = new stdClass();
                $message->message = 'Not running match_ip, ip not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_ip.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (strtolower($match->match_hostname) === 'y' && empty($details->id) && ! empty($details->hostname)) {
            $sql = "SELECT devices.id, devices.org_id FROM devices WHERE (devices.hostname = ?) AND devices.status != 'deleted'";
            
            $data = array($details->hostname);
            $query = $db->query($sql, $data);
            $row = $query->getRow();
            if (!empty($row->id)) {
                if ((empty($details->org_id)) or
                    (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                    (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                    $details->id = $row->id;
                    $log->device_id = $details->id;
                    $message = new stdClass();
                    $message->message = 'HIT on hostname.';
                    $message->command_status = 'success';
                    $message->command_output = 'Hostname: ' . $details->hostname . ', ID: ' . $details->id;
                    $log_message[] = $message;
                    log_array($log, $log_message);
                    return $details->id;
                } else {
                    $message = new stdClass();
                    $message->message = 'MISS on fqdn + org_id, but hit on fqdn alone. Check assigned_to_org in discovery.';
                    $message->command_status = 'notice';
                    $message->command_output = 'FQDN: ' . $details->dns_fqdn . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                    $log_message[] = $message;
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on hostname.';
            $message->command_status = 'notice';
            $message->command_output = 'Hostname: ' . $details->hostname;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_hostname) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_hostname, matching rule set to: ' . $match->match_hostname .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname, device id already set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->hostname)) {
                $message = new stdClass();
                $message->message = 'Not running match_hostname, hostname not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_hostname.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        if (!empty($log->integrations_id)) {
            foreach ($log_message as $message) {
                $sql = "/* m_integrations::execute */ " . "INSERT INTO integrations_log VALUES (null, ?, null, ?, 'debug', ?)";
                $data = array($log->integrations_id, microtime(true), json_encode($message));
                $query = $db->query($sql, $data);
            }
        }

        // check IP Address in devices table for a device with no other data
        if ((empty($match->match_ip_no_data) or strtolower($match->match_ip_no_data) === 'y') && empty($details->id) && ! empty($details->ip) && filter_var($details->ip, FILTER_VALIDATE_IP)) {
            // Check the devices table for an ip match on a device without a type or serial
            if (empty($details->id)) {
                $sql = "SELECT devices.id, devices.org_id FROM devices WHERE devices.ip = ? AND devices.ip NOT LIKE '127%' AND devices.ip NOT LIKE '1::%' AND devices.status != 'deleted' and (devices.type = 'unknown' or devices.type = 'unclassified') and devices.serial = ''";
                
                $data = array(ip_address_to_db($details->ip));
                $query = $db->query($sql, $data);
                $row = $query->getRow();
                if (!empty($row->id)) {
                    if ((empty($details->org_id)) or
                        (!empty($details->org_id) and $details->org_id == $row->org_id and ! empty($this->config->config['discovery_use_org_id_match']) and $this->config->config['discovery_use_org_id_match'] === 'y') or
                        (empty($this->config->config['discovery_use_org_id_match']) or $this->config->config['discovery_use_org_id_match'] === 'n')) {
                        $details->id = $row->id;
                        $log->device_id = $details->id;
                        $message = new stdClass();
                        $message->message = 'HIT on IP Address No Data (devices table).';
                        $message->command_status = 'success';
                        $message->command_output = 'IP: ' . $details->ip . ', ID: ' . $details->id;
                        $log_message[] = $message;
                        log_array($log, $log_message);
                        return $details->id;
                    } else {
                        $message = new stdClass();
                        $message->message = 'MISS on IP Address No Data (devices table) + org_id, but hit on IP Address No Data (devices table) alone. Check assigned_to_org in discovery.';
                        $message->command_status = 'notice';
                        $message->command_output = 'IP: ' . $details->ip . ', OrgID: ' . $details->org_id . ', Potential ID: ' . $row->id . ', Potential OrgID: ' . $row->org_id;
                        $log_message[] = $message;
                    }
                }
            }
            $message = new stdClass();
            $message->message = 'MISS on IP Address No Data.';
            $message->command_status = 'notice';
            $message->command_output = 'IP: ' . $details->ip;
            $log_message[] = $message;
        } else {
            if (strtolower($match->match_ip) !== 'y') {
                $message = new stdClass();
                $message->message = 'Not running match_ip_no_data, matching rule set to: ' . $match->match_ip .  '.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (!empty($details->id)) {
                $message = new stdClass();
                $message->message = 'Not running match_ip_no_data, device id already set';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else if (empty($details->ip)) {
                $message = new stdClass();
                $message->message = 'Not running match_ip_no_data, ip not set.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            } else {
                $message = new stdClass();
                $message->message = 'Not running match_ip_no_data.';
                $message->command_status = 'notice';
                $message->command_output = '';
                $log_message[] = $message;
            }
        }

        $temp = @(string)$details->id;
        if (is_null($temp) or $temp === '') {
            $message = new stdClass();
            $message->message = 'Could not find any matching attributes for the device with IP '  . ip_address_from_db($details->ip);
            $message->command_status = 'notice';
            $message->command_output = '';
            $log_message[] = $message;
        } else {
            $message = new stdClass();
            $message->message = 'Returning devices.id for device with IP '  . ip_address_from_db($details->ip) . ' (' . $details->id . ')';
            $message->command_status = 'notice';
            $message->command_output = '';
            $log_message[] = $message;
        }
        log_array($log, $log_message);
        unset($log);
        $message->command_output = '';
        return intval($details->id);
    }
}

function log_array($log, $log_array)
{
    $DiscoveryLog = new \App\Models\DiscoveryLogModel();
    foreach ($log_array as $log_item) {
        $log_item->device_id = @$log->device_id;
        $log_item->discovery_id = @$log->discovery_id;
        $log_item->file = 'devices_helper';
        $log_item->function = 'match';
        $log_item->severity = 7;
        $log_item->ip = @$log->ip;
        $DiscoveryLog->create($log_item);
    }
}


function reset_icons($id = '')
{
    $db = db_connect();
    if ($id !== '') {
        $sql = 'SELECT id, type, os_name, os_family, os_group, manufacturer FROM devices WHERE id = ' . intval($id);
    } else {
        $sql = 'SELECT id, type, os_name, os_family, os_group, manufacturer FROM devices';
    }
    $query = $db->query($sql);
    $result = $query->getResult();
    $count = $query->getNumRows();

    // we set computer icons by OS, everything else by type
    foreach ($result as $details) {
        if ($details->type === 'computer') {
            // determine icon for computer
            // most generic to most specific
            $details->icon = 'computer';

            // manufacturer based
            if (strripos($details->manufacturer, 'apple') !== false) {
                $details->icon = 'apple';
            }
            if (strripos($details->manufacturer, 'vmware') !== false) {
                $details->icon = 'vmware';
            }
            if (strripos($details->manufacturer, 'xen') !== false) {
                $details->icon = 'xen';
            }
            if (strripos($details->manufacturer, 'google') !== false) {
                $details->icon = 'google_kvm';
            }

            // os_group based
            if (strripos($details->os_group, 'linux') !== false) {
                $details->icon = 'linux';
            }
            if (strripos($details->os_group, 'apple') !== false) {
                $details->icon = 'apple';
            }
            if (strripos($details->os_group, 'windows') !== false) {
                $details->icon = 'windows';
            }

            // os name based
            if ((strripos($details->os_name, 'osx') !== false) or (strpos(strtolower($details->os_name), 'ios') !== false)) {
                $details->icon = 'apple';
            }
            if (strripos($details->os_name, 'aix') !== false) {
                $details->icon = 'aix';
            }
            if (strripos($details->os_name, 'amazon') !== false) {
                $details->icon = 'amazon';
            }
            if (strripos($details->os_name, 'arch') !== false) {
                $details->icon = 'arch';
            }
            if (strripos($details->os_name, 'bsd') !== false) {
                $details->icon = 'bsd';
            }
            if (strripos($details->os_name, 'centos') !== false) {
                $details->icon = 'centos';
            }
            if (strripos($details->os_name, 'debian') !== false) {
                $details->icon = 'debian';
            }
            if (strripos($details->os_name, 'fedora') !== false) {
                $details->icon = 'fedora';
            }
            if (strripos($details->os_name, 'hp-ux') !== false) {
                $details->icon = 'hp-ux';
            }
            if ((strripos($details->os_name, 'mandriva') !== false) or (strripos($details->os_name, 'mandrake') !== false)) {
                $details->icon = 'mandriva';
            }
            if (strripos($details->os_name, 'mint') !== false) {
                $details->icon = 'mint';
            }
            if (strripos($details->os_name, 'novell') !== false) {
                $details->icon = 'novell';
            }
            if (strripos($details->os_name, 'oracle') !== false) {
                $details->icon = 'oracle';
            }
            if (strripos($details->os_name, 'slackware') !== false) {
                $details->icon = 'slackware';
            }
            if (strripos($details->os_name, 'solaris') !== false) {
                $details->icon = 'solaris';
            }
            if (strripos($details->os_name, 'solus') !== false) {
                $details->icon = 'solus';
            }
            if (strripos($details->os_name, 'suse') !== false) {
                $details->icon = 'suse';
            }
            if ((strripos($details->os_name, 'red hat') !== false) or (strripos($details->os_name, 'redhat') !== false)) {
                $details->icon = 'redhat';
            }
            if (strripos($details->os_name, 'ubuntu') !== false) {
                $details->icon = 'ubuntu';
            }
            if (strripos($details->os_name, 'vmware') !== false) {
                $details->icon = 'vmware';
            }
            if (strripos($details->os_name, 'windows') !== false) {
                $details->icon = 'windows';
            }
            if (strripos($details->os_name, 'microsoft') !== false) {
                $details->icon = 'windows';
            }
        } else {
            // device is not type=computer
            // base the icon on the type
            if (strpos($details->type, '|') === false) {
                // if the type does not contain a |, use it.
                // Nmap will often return a pipe separated list when it guesses
                $details->icon = str_replace(' ', '_', $details->type);
            } else {
                // we have a pipe (likely an nmap list) so an unknown
                $details->icon = 'unknown';
            }
        }

        $sql = 'UPDATE system SET icon = ? WHERE id = ?';
        $query = $db->query($sql, [$details->icon, intval($details->id)]);
    }

    return ($count);
}

function audit_format_system($parameters)
{
    $db = db_connect();
    $config = new \Config\OpenAudit();

    if (!empty($parameters->log)) {
        $log = $parameters->log;
    } else {
        $log = new \stdClass();
    }

    if (!empty($parameters->discovery_id)) {
        $log->discovery_id = $parameters->discovery_id;
    } else if (!empty($parameters->input->discovery_id)) {
        $log->discovery_id = $parameters->input->discovery_id;
    }

    if (!empty($parameters->ip)) {
        $log->ip = ip_address_from_db($parameters->ip);
    } else if (!empty($parameters->input->ip)) {
        $log->ip = ip_address_from_db($parameters->input->ip);
    }

    $log->message = 'Formatting system details';
    $log->file = 'audit_helper';
    $log->function = 'audit_format_system';
    $log->command_ouput = '';
    $log->command_status = 'notice';

    if (empty($parameters)) {
        log_message('error', 'Function audit_format_system called without parameters object.');
        return false;
    }

    if (empty($parameters->input)) {
        log_message('error', 'Function audit_format_system called without parameters->input.');
        return false;
    } else {
        $input = $parameters->input;
    }


    if (empty($input->id)) {
        $input->id = '';
    } else {
        $sql = 'SELECT `status` FROM system WHERE id = ?';
        $query = $db->query($sql, [intval($input->id)]);
        $result = $query->getResult();
        $log->system_id = intval($input->id);
        if (empty($result[0]->status) or $result[0]->status === 'deleted') {
            $log->message = 'Removing supplied system ID (' . intval($input->id) . ') as the device is in a deleted status.';
            $log->ip = $input->ip;
            $log->command_status = 'fail';
            $log->severity = 4;
            if (!empty($log->discovery_id)) {
                discovery_log($log);
            } else {
                stdlog($log);
            }
            $input->id = '';
        }
    }

    $input->audits_ip = '127.000.000.001';
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $input->audits_ip = ip_address_to_db($_SERVER['REMOTE_ADDR']);
    }

    if (empty($input->discovery_id)) {
        $input->discovery_id = '';
    }

    if (empty($input->domain)) {
        $input->domain = '';
    }

    if (empty($input->fqdn)) {
        $input->fqdn = '';
    }

    if (empty($input->hostname)) {
        $input->hostname = '';
    }

    if (empty($input->last_seen)) {
        $input->last_seen = $config->timestamp;
    }

    if (!empty($input->os_installation_date)) {
        $input->os_installation_date = date("Y-m-d", strtotime($input->os_installation_date));
    }

    if (empty($input->timestamp)) {
        $input->timestamp = $config->timestamp;
    }

    if (!empty($input->type)) {
        $input->type = strtolower($input->type);
    }

    if (empty($input->uuid)) {
        $input->uuid = '';
    }

    if (empty($input->vm_uuid)) {
        $input->vm_uuid = '';
    }

    // This is set by m_device::insert or update.
    unset($input->icon);

    if (!filter_var($input->hostname, FILTER_VALIDATE_IP)) {
        if (strpos($input->hostname, '.') !== false) {
            // we have a fqdn in the hostname field
            if (empty($input->fqdn)) {
                $input->fqdn = $input->hostname;
            }
            $temp = explode('.', $input->hostname);
            $input->hostname = $temp[0];
            unset($temp[0]);
            if (empty($input->domain)) {
                $input->domain = implode('.', $temp);
            }
            unset($temp);
            $log->message = 'FQDN supplied in hostname, converting.';
            $log->command_output = 'Hostname: ' . $input->hostname . ' Domain: ' .  $input->domain;
            if (!empty($log->discovery_id)) {
                discovery_log($log);
            } else {
                stdlog($log);
            }
        }
    }

    if (filter_var($input->hostname, FILTER_VALIDATE_IP)) {
        // we have an ip address in the hostname field
        if (empty($input->ip)) {
            $input->ip = $input->hostname;
            $log->message = 'IP supplied in hostname, setting device IP.';
            $log->command_output = 'IP: ' . $input->ip;
            if (!empty($log->discovery_id)) {
                discovery_log($log);
            } else {
                stdlog($log);
            }
        }
        $input->hostname = '';
    }

    $log->command_output = '';

    if (empty($input->fqdn) && ! empty($input->hostname) && ! empty($input->domain)) {
        $input->fqdn = $input->hostname . '.' . $input->domain;
        $log->message = 'No FQDN, but hostname and domain supplied, setting FQDN.';
        if (!empty($log->discovery_id)) {
            discovery_log($log);
        } else {
            stdlog($log);
        }
    }

    if (isset($input->os_name)) {
        $input->os_name = str_ireplace('(r)', '', $input->os_name);
        $input->os_name = str_ireplace('(tm)', '', $input->os_name);
    }

    if (empty($input->ip) or $input->ip === '0.0.0.0' or $input->ip === '000.000.000.000') {
        unset($input->ip);
    }

    if (!empty($input->ip) && filter_var($input->ip, FILTER_VALIDATE_IP)) {
        $input->ip = ip_address_to_db($input->ip);
    }

    if (empty($input->mac_address) or $input->mac_address === '00:00:00:00:00:00') {
        unset($input->mac_address);
    }

    if (!empty($input->mac_address)) {
        $input->mac_address = strtolower($input->mac_address);
    }

    // because Windows doesn't supply an identical UUID, but it does supply the required digits, make a UUID from the serial
    if (!empty($input->uuid) and !empty($input->serial) and stripos($input->serial, 'vmware-') !== false and !empty($input->os_name) and stripos($input->os_name, 'windows') !== false) {
        // serial is taken from Win32_ComputerSystemProduct.IdentifyingNumber
        // Vmware supplies - 564d3739-b4cb-1a7e-fbb1-b10dcc0335e1
        // audit_windows supples - VMware-56 4d 37 39 b4 cb 1a 7e-fb b1 b1 0d cc 03 35 e1
        $log->command_output = $input->serial;
        $input->vm_uuid = str_ireplace('VMware-', '', $input->serial);
        $input->vm_uuid = str_ireplace('-', ' ', $input->vm_uuid);
        $input->vm_uuid = strtolower($input->vm_uuid);
        $input->vm_uuid = str_ireplace(' ', '', $input->vm_uuid);
        $input->vm_uuid = substr($input->vm_uuid, 0, 8) . '-'. substr($input->vm_uuid, 8, 4) . '-' . substr($input->vm_uuid, 12, 4) . '-' . substr($input->vm_uuid, 16, 4) . '-' . substr($input->vm_uuid, 20, 12);
        $log->message = 'Windows VMware style serial detected, creating vm_uuid.';
        $log->command_output .= ' -> ' . $input->vm_uuid;
        if (!empty($log->discovery_id)) {
            discovery_log($log);
        } else {
            stdlog($log);
        }
        $log->command_output = '';
    }

    if (!empty($input->uuid) && empty($input->vm_uuid)) {
        $input->vm_uuid = $input->uuid;
    }

    return $input;
}