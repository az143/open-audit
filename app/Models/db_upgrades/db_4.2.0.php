<?php
$output .= "Upgrade database to 4.2.0 commenced.\n\n";


$sql = "DROP TABLE IF EXISTS `integrations`";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$sql = "CREATE TABLE `integrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `org_id` int(10) unsigned NOT NULL DEFAULT '1',
  `description` text NOT NULL,
  `type` varchar(45) NOT NULL DEFAULT 'nmis',
  `additional_items` longtext NOT NULL,
  `attributes` longtext NOT NULL,
  `create_external_count` int(10) unsigned DEFAULT NULL,
  `create_external_from_internal` enum('y','n') NOT NULL DEFAULT 'n',
  `create_internal_count` int(10) unsigned DEFAULT NULL,
  `create_internal_from_external` enum('y','n') NOT NULL DEFAULT 'n',
  `devices` longtext NOT NULL,
  `locations` longtext NOT NULL,
  `debug` enum('y','n') NOT NULL DEFAULT 'n',
  `discovery_id` int(10) unsigned DEFAULT NULL,
  `discovery_run` enum('y','n') NOT NULL DEFAULT 'n',
  `fields` longtext NOT NULL,
  `select_external_attribute` varchar(200) NOT NULL DEFAULT '',
  `select_external_count` int(10) unsigned DEFAULT NULL,
  `select_external_type` enum('','all','none','attribute') DEFAULT 'all',
  `select_external_value` varchar(200) NOT NULL DEFAULT '',
  `select_internal_attribute` varchar(200) NOT NULL DEFAULT '',
  `select_internal_count` int(10) unsigned DEFAULT NULL,
  `select_internal_type` enum('','none','attribute','group','query') DEFAULT 'attribute',
  `select_internal_value` varchar(200) NOT NULL DEFAULT '',
  `update_external_count` int(10) unsigned DEFAULT NULL,
  `update_external_from_internal` enum('y','n') NOT NULL DEFAULT 'y',
  `update_internal_count` int(10) unsigned DEFAULT NULL,
  `update_internal_from_external` enum('y','n') NOT NULL DEFAULT 'y',
  `delete_external_from_internal` enum('y','n') NOT NULL DEFAULT 'n',
  `status` varchar(200) NOT NULL DEFAULT '',
  `last_run` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `duration` int(10) unsigned DEFAULT NULL,
  `edited_by` varchar(200) NOT NULL DEFAULT '',
  `edited_date` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$sql = "DROP TABLE IF EXISTS `integrations_log`";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$sql = "CREATE TABLE `integrations_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `integrations_id` int(10) unsigned DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `microtime` decimal(16,6) DEFAULT NULL,
  `severity_text` enum('debug','info','notice','warning','error','critical','alert','emergency') NOT NULL DEFAULT 'notice',
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$integration = new \stdClass();
$integration->attributes = '{"username" : "","url" : "http://localhost/omk/","password" : ""}';
$integration->create_external_from_internal = "y";
$integration->create_internal_from_external = "y";
$integration->delete_external_from_internal = "n";
$integration->description = "";
$integration->discovery_run = "y";
$integration->name = "Default NMIS Integration";
$integration->org_id = 1;
$integration->select_external_attribute = "";
$integration->select_external_type = "all";
$integration->select_external_value = "";
$integration->select_internal_attribute = "nmis_manage";
$integration->select_internal_type = "attribute";
$integration->select_internal_value = "y";
$integration->type = "nmis";
$integration->update_external_from_internal = "y";
$integration->update_internal_from_external = "y";
$integration->fields = '[{
     "external_field_type" : "text",
     "priority" : "internal",
     "external_field_name" : "configuration.businessService",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_business_service"
  },
  {
     "external_field_type" : "text",
     "external_field_name" : "server_name",
     "priority" : "external",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_poller"
  },
  {
     "internal_field_name" : "system.nmis_notes",
     "matching_attribute" : "n",
     "priority" : "external",
     "external_field_name" : "configuration.notes",
     "default_value" : "",
     "external_field_type" : "text"
  },
  {
     "internal_field_name" : "system.nmis_manage",
     "matching_attribute" : "n",
     "external_field_name" : "",
     "priority" : "internal",
     "default_value" : "y",
     "external_field_type" : "text"
  },
  {
     "external_field_type" : "text",
     "default_value" : "",
     "external_field_name" : "configuration.sysDescr",
     "priority" : "external",
     "matching_attribute" : "n",
     "internal_field_name" : "system.sysDescr"
  },
  {
     "default_value" : "Default Location",
     "external_field_name" : "configuration.location",
     "priority" : "internal",
     "external_field_type" : "text",
     "internal_field_name" : "locations.name",
     "matching_attribute" : "n"
  },
  {
     "matching_attribute" : "n",
     "internal_field_name" : "credentials.windows_username",
     "external_field_type" : "text",
     "priority" : "internal",
     "external_field_name" : "configuration.wmiusername",
     "default_value" : ""
  },
  {
     "matching_attribute" : "n",
     "internal_field_name" : "credentials.windows_password",
     "external_field_type" : "text",
     "external_field_name" : "configuration.wmipassword",
     "priority" : "internal",
     "default_value" : ""
  },
  {
     "default_value" : "",
     "external_field_name" : "configuration.version",
     "priority" : "internal",
     "external_field_type" : "text",
     "internal_field_name" : "credentials.version",
     "matching_attribute" : "n"
  },
  {
     "matching_attribute" : "n",
     "internal_field_name" : "credentials.snmp_community",
     "external_field_type" : "text",
     "priority" : "internal",
     "external_field_name" : "configuration.community",
     "default_value" : ""
  },
  {
     "priority" : "internal",
     "external_field_name" : "configuration.username",
     "default_value" : "",
     "external_field_type" : "text",
     "internal_field_name" : "credentials.security_name",
     "matching_attribute" : "n"
  },
  {
     "external_field_type" : "text",
     "default_value" : "",
     "priority" : "internal",
     "external_field_name" : "configuration.customer",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_customer"
  },
  {
     "priority" : "internal",
     "external_field_name" : "configuration.authpassword",
     "default_value" : "",
     "external_field_type" : "text",
     "internal_field_name" : "credentials.authentication_passphrase",
     "matching_attribute" : "n"
  },
  {
     "internal_field_name" : "credentials.authentication_protocol",
     "matching_attribute" : "n",
     "default_value" : "",
     "external_field_name" : "configuration.authprotocol",
     "priority" : "internal",
     "external_field_type" : "text"
  },
  {
     "default_value" : "",
     "external_field_name" : "configuration.privpassword",
     "priority" : "internal",
     "external_field_type" : "text",
     "internal_field_name" : "credentials.privacy_passphrase",
     "matching_attribute" : "n"
  },
  {
     "external_field_type" : "text",
     "default_value" : "",
     "external_field_name" : "configuration.privprotocol",
     "priority" : "internal",
     "matching_attribute" : "n",
     "internal_field_name" : "credentials.privacy_protocol"
  },
  {
     "priority" : "external",
     "external_field_name" : "configuration.active",
     "default_value" : "1",
     "external_field_type" : "bool_one_zero",
     "internal_field_name" : "fields.nmis_active",
     "matching_attribute" : "n"
  },
  {
     "internal_field_name" : "fields.nmis_collect",
     "matching_attribute" : "n",
     "default_value" : "1",
     "external_field_name" : "configuration.collect",
     "priority" : "external",
     "external_field_type" : "bool_one_zero"
  },
  {
     "internal_field_name" : "fields.nmis_model",
     "matching_attribute" : "n",
     "default_value" : "automatic",
     "priority" : "external",
     "external_field_name" : "configuration.model",
     "external_field_type" : "text"
  },
  {
     "external_field_type" : "text",
     "default_value" : "wan",
     "external_field_name" : "configuration.netType",
     "priority" : "external",
     "matching_attribute" : "n",
     "internal_field_name" : "fields.nmis_netType"
  },
  {
     "internal_field_name" : "fields.nmis_ping",
     "matching_attribute" : "n",
     "default_value" : "true",
     "external_field_name" : "configuration.ping",
     "priority" : "external",
     "external_field_type" : "bool"
  },
  {
     "internal_field_name" : "fields.nmis_port",
     "matching_attribute" : "n",
     "priority" : "external",
     "external_field_name" : "configuration.port",
     "default_value" : "161",
     "external_field_type" : "integer"
  },
  {
     "matching_attribute" : "n",
     "internal_field_name" : "system.name",
     "external_field_type" : "text",
     "default_value" : "",
     "priority" : "internal",
     "external_field_name" : "name"
  },
  {
     "external_field_type" : "text",
     "external_field_name" : "name",
     "priority" : "internal",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_name"
  },
  {
     "matching_attribute" : "n",
     "internal_field_name" : "system.name",
     "external_field_type" : "text",
     "default_value" : "",
     "priority" : "internal",
     "external_field_name" : "configuration.display_name"
  },
  {
     "external_field_type" : "text",
     "default_value" : "Open-AudIT",
     "external_field_name" : "configuration.group",
     "priority" : "internal",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_group"
  },
  {
     "external_field_type" : "text",
     "external_field_name" : "configuration.host",
     "priority" : "internal",
     "default_value" : "",
     "matching_attribute" : "y",
     "internal_field_name" : "system.ip"
  },
  {
     "internal_field_name" : "system.omk_uuid",
     "matching_attribute" : "y",
     "default_value" : "",
     "priority" : "external",
     "external_field_name" : "uuid",
     "external_field_type" : "text"
  },
  {
     "external_field_type" : "text",
     "external_field_name" : "configuration.roleType",
     "priority" : "internal",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_role"
  },
  {
     "external_field_type" : "capitalise",
     "priority" : "internal",
     "external_field_name" : "configuration.serviceStatus",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.environment"
  },
  {
     "external_field_type" : "text",
     "priority" : "external",
     "external_field_name" : "cluster_id",
     "default_value" : "",
     "matching_attribute" : "n",
     "internal_field_name" : "system.nmis_poller_uuid"
  }
]';
$integration->fields = str_replace("\n", "", $integration->fields);
$integrationsModel = new \App\Models\IntegrationsModel();
$integrationsModel->create($integration);

$sql = 'UPDATE `tasks` set `sub_resource_id` = 1 WHERE `type` = "integrations"';
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

if ($db->fieldExists('nmis_poller_uuid', 'system')) {
    $sql = "ALTER TABLE `system` ADD `nmis_poller_uuid` varchar(45) NOT NULL DEFAULT '' AFTER `nmis_poller`";
    $db->query($sql);
    $output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";
}

$sql = "DELETE FROM `rules` WHERE `name` = 'NMIS Manage for SNMP devices'";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$sql = "INSERT INTO `rules` VALUES (NULL,'NMIS Manage for SNMP devices',1,'Set nmis_manage to y if we detect an SNMP OID.',100,'[{\"attribute\":\"snmp_oid\",\"operator\":\"gt\",\"table\":\"system\",\"value\":\"\"}]','[{\"attribute\":\"nmis_manage\",\"table\":\"system\",\"value\":\"y\",\"value_type\":\"string\"}]','system','2001-01-01 00:00:00')";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

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

if ($subnet !== '') {
    $sql = "INSERT INTO `discoveries` (id, name, org_id, description, type, subnet, edited_date, edited_by) VALUES (null,'Default Discovery',1,'Automatically created default discovery for $subnet.','subnet','$subnet',NOW(),'system')";
    $db->query($sql);
    $output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";
} else {
    $output .= "WARNING - Could not determine a private IP for server, no default discovery created.\n\n";
}

// set our versions
$sql = "UPDATE `configuration` SET `value` = '20210810' WHERE `name` = 'internal_version'";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$sql = "UPDATE `configuration` SET `value` = '4.2.0' WHERE `name` = 'display_version'";
$db->query($sql);
$output .= str_replace("\n", " ", (string)$db->getLastQuery()) . "\n\n";

$output .= "Upgrade database to 4.2.0 completed.\n\n";
config('Openaudit')->internal_version = 20210810;
config('Openaudit')->display_version = '4.2.0';
