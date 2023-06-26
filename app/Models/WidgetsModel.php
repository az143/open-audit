<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace App\Models;

use stdClass;

class WidgetsModel extends BaseModel
{

    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table('widgets');
    }

    public function cmp_name($a, $b)
    {
        if (!empty($a->name) and !empty($b->name)) {
            return strcmp(strtolower((string)$a->name), strtolower((string)$b->name));
        } else {
            return;
        }
    }

    public function cmp_timestamp($a, $b)
    {
        if (!empty($a->timestamp) and !empty($b->timestamp)) {
            return strcmp(strtolower((string)$a->timestamp), strtolower((string)$b->timestamp));
        } else {
            return;
        }
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
    public function create($data = null): ?int
    {
        if (empty($data)) {
            return null;
        }
        if (!empty($data->sql)) {
            if (stripos($data->sql, 'update ') !== false or stripos($data->sql, 'update`') !== false) {
                log_message('error', 'SQL cannot contain UPDATE clause');
                \Config\Services::session()->setFlashdata('error', 'SQL cannot contain UPDATE clause');
                return false;
            }
            if (stripos($data->sql, 'delete from ') !== false or stripos($data->sql, 'delete from`') !== false) {
                log_message('error', 'SQL cannot contain DELETE clause.');
                \Config\Services::session()->setFlashdata('error', 'SQL cannot contain DELETE clause');
                return false;
            }
            if (stripos($data->sql, 'insert into ') !== false or stripos($data->sql, 'insert into`') !== false) {
                log_message('error', 'SQL cannot contain INSERT clause.');
                \Config\Services::session()->setFlashdata('error', 'SQL cannot contain INSERT clause');
                return false;
            }
        }
        $data = $this->createFieldData('widgets', $data);
        $this->builder->insert($data);
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
        $this->builder->delete(['id' => intval($id)]);
        if ($this->sqlError($this->db->error())) {
            return false;
        }
        if ($this->db->affectedRows() !== 1) {
            return false;
        }
        return true;
    }

    public function execute(int $id = 0, object $user = null)
    {
        $result = $this->builder->getWhere(['id' => intval($id)])->getResult();
        $widget = $result[0];
        if ($widget->type === 'pie') {
            $result = $this->pieData($widget, $user->org_list);
        }
        if ($widget->type === 'line') {
            $result = $this->lineData($widget, $user->org_list);
        }
        $result = format_data($result, 'widgets');
        return ($result);
    }

    /**
     * Return an array containing arrays of related items to be stored in resp->included
     *
     * @param  int $id The ID of the requested item
     * @return array  An array of anything needed for screen output
     */
    public function includedRead(int $id = 0): array
    {
        return array();
    }

    /**
     * Return an array containing arrays of related items to be stored in resp->included
     *
     * @param  int $id The ID of the requested item
     * @return array  An array of anything needed for screen output
     */
    public function includedCreateForm(int $id = 0): array
    {
        return array();
    }

    /**
     * [line_data description]
     * @param  [type] $widget   [description]
     * @param  [type] $org_list [description]
     * @return [type]           [description]
     */
    private function lineData($widget, $org_list)
    {
        $instance = & get_instance();
        if (!empty($widget->sql)) {
            $sql = $widget->sql;
            if (stripos($sql, 'where @filter and') === false && stripos($sql, 'where @filter group by') === false) {
                // These entries musy only be created by a user with Admin role as no filter allows anything in the DB to be queried (think multi-tenancy).
            } else {
                $filter = "devices.org_id IN ({$org_list})";
                if (!empty($instance->resp->meta->requestor)) {
                    $filter = "devices.org_id IN ({$org_list}) AND devices.oae_manage = 'y'";
                }
                $sql = str_replace('@filter', $filter, $sql);
            }
            $result = $this->db->query($sql)->getResult();
            if (!empty($result)) {
                foreach ($result as $row) {
                    $row->timestamp = strtotime($row->date);
                }
                usort($result, array($this,'cmp_timestamp'));
                for ($i=0; $i < count($result); $i++) {
                    $result[$i]->link = $widget->link;
                    if (isset($result[$i]->name)) {
                        $result[$i]->link = str_ireplace('@name', $result[$i]->name, $result[$i]->link);
                    }
                    if (isset($result[$i]->description)) {
                        $result[$i]->link = str_ireplace('@description', $result[$i]->description, $result[$i]->link);
                    }
                    if (isset($result[$i]->ternary)) {
                        $result[$i]->link = str_ireplace('@ternary', $result[$i]->ternary, $result[$i]->link);
                    }
                    if (isset($result[$i]->date)) {
                        $result[$i]->link = str_ireplace('@date', $result[$i]->date, $result[$i]->link);
                    }
                    if (isset($result[$i]->timestamp)) {
                        $result[$i]->link = str_ireplace('@timestamp', $result[$i]->timestamp, $result[$i]->link);
                    }
                }

                if (count($result) < 2) {
                    $start = date('Y-m-d', strtotime('-' . $widget->limit . ' days'));
                    $begin = new \DateTime($start);
                    $finish = date('Y-m-d', strtotime('+1 days'));
                    $end = new \DateTime($finish);
                    $interval = new \DateInterval('P1D');
                    $period = new \DatePeriod($begin, $interval, $end);
                } else {
                    $start = date('Y-m-d', strtotime($result[0]->date));
                    $begin = new \DateTime($start);
                    $i = count($result)-1;
                    $end = new \DateTime($result[$i]->date);
                    $interval = \DateInterval::createFromDateString('1 day');
                    $period = new \DatePeriod($begin, $interval, $end);
                }

                foreach ($period as $dt) {
                    $the_date = $dt->format('Y-m-d');
                    $add_row = true;
                    for ($i=0; $i < count($result); $i++) {
                        if (!empty($result[$i]->date) and $result[$i]->date == $the_date) {
                            $add_row = false;
                            $result[$i]->timestamp = strtotime($the_date);
                        }
                    }
                    if ($add_row) {
                        $row = new \stdClass();
                        $row->timestamp = strtotime($the_date);
                        $row->date = $the_date;
                        $row->count = 0;
                        $row->link = '';
                        $result[] = $row;
                    }
                }
            } else {
                $item = new \stdClass();
                $item->timestamp = strtotime(date('Y-m-d'));
                $item->date = date('Y-m-d');
                $item->count = 0;
                $item->link = '';
                $result[] = $item;
            }
            usort($result, array($this,'cmp_timestamp'));
            return $result;
        }

        if (empty($widget->sql)) {
            if ($widget->primary === 'system') {
                $widget->primary = 'devices';
            }
            $device_tables = array('bios','devices','disk','dns','ip','log','memory','module','monitor','motherboard','netstat','network','nmap','optical','pagefile','partition','print_queue','processor','route','san','scsi','server','server_item','service','share','software','software_key','sound','task','user','user_group','variable','video','vm','warranty','windows');
            if (!in_array($widget->primary, $device_tables)) {
                return false;
            }
            $sql = "SELECT DATE(change_log.timestamp) AS `date`, count(DATE(change_log.timestamp)) AS `count`  FROM change_log LEFT JOIN devices ON (devices.id = change_log.device_id) WHERE @filter AND change_log.timestamp >= DATE_SUB(CURDATE(), INTERVAL " . intval($widget->limit) . " DAY) AND change_log.db_table = '" . $widget->primary . "'  AND change_log.db_action = '" . $widget->secondary . "' GROUP BY DATE(change_log.timestamp)";
            $filter = "devices.org_id IN (" . $org_list . ")";
            if (!empty($instance->resp->meta->requestor)) {
                $filter = "devices.org_id IN (" . $org_list . ") AND devices.oae_manage = 'y'";
            }
            if (!empty($widget->where)) {
                $sql .= " AND " . $widget->where;
            }
            $sql = str_replace('@filter', $filter, $sql);
            $result = $this->db->query($sql)->getResult();
            if (!empty($result)) {
                foreach ($result as $row) {
                    if (empty($widget->link)) {
                        $row->name = strtotime($row->date);
                        #$row->link = 'devices?sub_resource=change_log&change_log.db_table=' . $widget->primary . '&change_log.db_action=' . $widget->secondary . '&change_log.timestamp=LIKE' . $row->date;
                        $row->link = 'components?components.type=change_log&change_log.db_table=' . $widget->primary . '&change_log.db_action=' . $widget->secondary . '&change_log.timestamp=LIKE' . $row->date;
                    } else {
                        $row->link = $widget->link;
                        if (isset($row->name)) {
                            $row->link = str_ireplace('@name', $row->name, $row->link);
                        }
                        if (isset($row->description)) {
                            $row->link = str_ireplace('@description', $row->description, $row->link);
                        }
                        if (isset($row->ternary)) {
                            $row->link = str_ireplace('@ternary', $row->ternary, $row->link);
                        }
                        if (isset($row->date)) {
                            $row->link = str_ireplace('@date', $row->date, $row->link);
                        }
                        if (isset($row->timestamp)) {
                            $row->link = str_ireplace('@timestamp', $row->timestamp, $row->link);
                        }
                    }
                }
            }
            $start = date('Y-m-d', strtotime('-' . $widget->limit . ' days'));
            $begin = new \DateTime($start);
            $finish = date('Y-m-d', strtotime('+1 days'));
            $end = new \DateTime($finish);
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($begin, $interval, $end);

            foreach ($period as $dt) {
                $the_date = $dt->format('Y-m-d');
                $add_row = true;
                if (!empty($result)) {
                    for ($i=0; $i < count($result); $i++) {
                        if (!empty($result[$i]->date) and $result[$i]->date == $the_date) {
                            $add_row = false;
                            $result[$i]->timestamp = strtotime($the_date);
                        }
                    }
                }
                if ($add_row) {
                    $row = new \stdClass();
                    $row->timestamp = strtotime($the_date);
                    $row->date = $the_date;
                    $row->count = 0;
                    $row->link = '';
                    $result[] = $row;
                }
            }
            usort($result, array($this,'cmp_timestamp'));
            return $result;
        }
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
        $properties[] = 'widgets.*';
        $properties[] = 'orgs.name as `orgs.name`';
        $this->builder->select($properties, false);
        $this->builder->join('orgs', 'widgets.org_id = orgs.id', 'left');
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
        return format_data($query->getResult(), 'widgets');
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
     * [pie_data description]
     * @param  [type] $widget   [description]
     * @param  [type] $org_list [description]
     * @return [type]           [description]
     */
    private function pieData(object $widget, $org_list)
    {
        $device_tables = array('bios','connections','disk','dns','ip','log','memory','module','monitor','motherboard','netstat','network','nmap','optical','pagefile','partition','print_queue','processor','route','san','scsi','server','server_item','service','share','software','software_key','sound','task','user','user_group','variable','video','vm','warranty','windows');

        $other_tables = array('agents','attributes','collectors','connections','credentials','dashboards','discoveries','fields','files','groups','ldap_servers','licenses','locations','networks','orgs','queries','scripts','summaries','tasks','users','widgets');

        $sql = '';
        $group_by = $widget->group_by;
        if (empty($group_by)) {
            $group_by = $widget->primary;
        }

        $pattern = "/[^A-Za-z0-9._]/";

        $temp = explode('.', $widget->primary);
        $primary_table = $temp[0];
        $primary_table = preg_replace($pattern, "", $primary_table);
        unset($temp);

        $temp = explode('.', $widget->secondary);
        $secondary_table = $temp[0];
        $secondary_table = preg_replace($pattern, "", $secondary_table);
        unset($temp);

        $instance = & get_instance();

        if (!empty($widget->sql)) {
            // remove excessive white space and line breaks
            $sql = preg_replace('/\s+/u', ' ', $widget->sql);
            if (stripos($sql, 'where @filter and') === false && stripos($sql, 'where @filter group by') === false) {
                // invalid query
                log_message('error', 'Invalid widget SQL query.');
                \Config\Services::session()->setFlashdata('error', 'Invalid widget SQL query.');
                return false;
            }
            $temp = explode(' ', $sql);
            $full = $temp[1];

            $temp = explode('.', $full);
            $primary_table = $temp[0];
            $primary_table = preg_replace($pattern, "", $primary_table);

            $attribute = $full;
            unset($temp);

            if ($primary_table === 'devices' or in_array($primary_table, $device_tables)) {
                $collection = 'devices';
                $filter = "devices.org_id IN ({$org_list})";
                if (!empty($instance->resp->meta->requestor)) {
                    $filter = "devices.org_id IN ({$org_list}) AND devices.oae_manage = 'y'";
                }
                $sql = str_replace('@filter', $filter, $sql);
            } else if (in_array($primary_table, $other_tables)) {
                $collection = $primary_table;
                if ($collection !== 'orgs') {
                    $sql = str_replace('@filter', $primary_table . '.org_id IN (' . $org_list . ')', $sql);
                } else {
                    $filter = "devices.org_id in ({$org_list})";
                    if (!empty($instance->resp->meta->requestor)) {
                        $filter = "devices.org_id in ({$org_list}) AND devices.oae_manage = 'y'";
                    }
                    $sql = str_replace('@filter', $filter, $sql);
                }
            } else {
                // invalid query
                $collection = 'devices';
                $filter = "devices.org_id in ({$org_list})";
                if (!empty($instance->resp->meta->requestor)) {
                    $filter = "devices.org_id in ({$org_list}) AND devices.oae_manage = 'y'";
                }
                $sql = str_replace('@filter', $filter, $sql);
            }
        } else if (in_array($primary_table, $device_tables)) {
            $collection = 'devices';
            $attribute = $widget->primary;
            $primary = "''";
            if (!empty(preg_replace($pattern, "", $widget->primary))) {
                $primary = preg_replace($pattern, "", $widget->primary);
            }
            $secondary = "''";
            if (!empty(preg_replace($pattern, "", $widget->secondary))) {
                $secondary = preg_replace($pattern, "", $widget->secondary);
            }
            $ternary = "''";
            if (!empty(preg_replace($pattern, "", $widget->ternary))) {
                $ternary = preg_replace($pattern, "", $widget->ternary);
            }
            $sql = 'SELECT ' .  $primary . ' AS `name`, ' .
                                $secondary . ' AS `description`, ' .
                                $ternary . ' AS `ternary`, ' .
                                " COUNT(" . $primary . ') AS `count`, ' .
                                " FROM devices LEFT JOIN " . $primary_table .
                                " ON (devices.id = " . $primary_table . '.device_id' .
                                " AND " . $primary_table . ".current = 'y' ) " .
                                " WHERE @filter GROUP BY " . preg_replace($pattern, "", $group_by);
            $filter = "devices.org_id in (" . $org_list . ")";
            if (!empty($instance->resp->meta->requestor)) {
                $filter = "devices.org_id in (" . $org_list . ") AND devices.oae_manage = 'y'";
            }
            if (!empty($widget->where)) {
                $filter .= " AND " . $widget->where;
            }
            $sql = str_replace('@filter', $filter, $sql);
            if (!empty($widget->limit)) {
                $sql .= ' LIMIT ' . intval($widget->limit);
            }
        } else if ($primary_table === 'devices') {
            $collection = 'devices';
            $attribute = $widget->primary;
            $primary = "''";
            if (!empty(preg_replace($pattern, "", $widget->primary))) {
                $primary = preg_replace($pattern, "", $widget->primary);
            }
            $secondary = "''";
            if (!empty(preg_replace($pattern, "", $widget->secondary))) {
                $secondary = preg_replace($pattern, "", $widget->secondary);
            }
            $ternary = "''";
            if (!empty(preg_replace($pattern, "", $widget->ternary))) {
                $ternary = preg_replace($pattern, "", $widget->ternary);
            }
            $sql = 'SELECT ' .  $primary . ' AS `name`, ' .
                                $secondary . ' AS `description`, ' .
                                $ternary . ' AS `ternary`, ' .
                                " COUNT(" . $primary . ') AS `count`, ' .
                                " CAST((COUNT(*) / (SELECT COUNT(" . $primary . ") FROM " . $primary_table . " WHERE" .
                                " devices.org_id IN (" . $org_list . ")) * 100) AS unsigned) AS `percent`" .
                                " FROM devices" .
                                " WHERE @filter GROUP BY " . preg_replace($pattern, "", $group_by);
            $filter = "devices.org_id in (" . $org_list . ")";
            if (!empty($instance->resp->meta->requestor)) {
                $filter = "devices.org_id in (" . $org_list . ") AND devices.oae_manage = 'y'";
            }
            if (!empty($widget->where)) {
                $filter .= " AND " . $widget->where;
            }
            $sql = str_replace('@filter', $filter, $sql);
            if (!empty($widget->limit)) {
                $limit = intval($widget->limit);
                $sql .= ' ORDER BY `count` DESC LIMIT ' . $limit;
            }
        } else {
            log_message('debug', 'Widget definition is bad. Widget: ' . json_encode($widget));
        }

        $result = $this->db->query($sql)->getResult();
        if (!empty($result)) {
            for ($i=0; $i < count($result); $i++) {
                if (empty($result[$i]->name) and empty($result[$i]->count)) {
                    unset($result[$i]);
                }
            }
        }
        if (!empty($result)) {
            $result = array_values($result);
        }
        $total_count = 0;
        // We need to allow for grouping using a column name that is NOT 'name' as this can clash with existing schema.
        //   In this case (always in custom SQL), you should use my_name instead
        if (!empty($result)) {
            for ($i=0; $i < count($result); $i++) {
                foreach ($result[$i] as $key => $value) {
                    if (strpos($key, 'my_') === 0) {
                        $new_key = str_replace('my_', '', $key);
                        $result[$i]->{$new_key} = $value;
                        unset($result[$i]->{$key});
                    }
                }
            }
            for ($i=0; $i < count($result); $i++) {
                $total_count += intval($result[$i]->count);
                if (intval($result[$i]->count) === 0 and is_null($result[$i]->name)) {
                    unset($result[$i]);
                }
            }
            $result = array_values($result);
            for ($i=0; $i < count($result); $i++) {
                if (!empty($result[$i]->count) and !empty($total_count)) {
                    $result[$i]->percent = intval(($result[$i]->count / $total_count) * 100);
                } else {
                    $result[$i]->percent = 0;
                }
                if (!empty($widget->link)) {
                    $result[$i]->link = $widget->link;
                    if (isset($result[$i]->name)) {
                        $result[$i]->link = str_ireplace('@name', $result[$i]->name, $result[$i]->link);
                    }
                    if (isset($result[$i]->description)) {
                        $result[$i]->link = str_ireplace('@description', $result[$i]->description, $result[$i]->link);
                    }
                    if (isset($result[$i]->ternary)) {
                        $result[$i]->link = str_ireplace('@ternary', $result[$i]->ternary, $result[$i]->link);
                    }
                } else {
                    $result[$i]->link = $collection . '?' . $attribute . '=' . $result[$i]->name;
                }
            }
        } else {
            $item = new \stdClass();
            $item->name = '';
            $item->description = '';
            $item->ternary = '';
            $item->count = 0;
            $item->percent = 100;
            $item->link = '';
            $result[] = $item;
        }
        return $result;
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
        return format_data($query->getResult(), 'widgets');
    }

    /**
     * Reset a table
     *
     * @return bool Did it work or not?
     */
    public function reset(string $table = ''): bool
    {
        if ($this->tableReset('widgets')) {
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
        $data = $this->updateFieldData('widgets', $data);
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

        $collection = 'widgets';
        $dictionary = new \stdClass();
        $dictionary->table = $collection;
        $dictionary->columns = new \stdClass();

        $dictionary->attributes = new \stdClass();
        $dictionary->attributes->collection = array('id', 'name', 'type', 'orgs.name', 'edited_by', 'edited_date');
        $dictionary->attributes->create = array('name','org_id','type');
        $dictionary->attributes->fields = $this->db->getFieldNames($collection);
        $dictionary->attributes->fieldsMeta = $this->db->getFieldData($collection);
        $dictionary->attributes->update = $this->updateFields($collection);

        $dictionary->about = '<p>Widgets can easily be created to show whatever is specific to your environment on your dashboards.<br /><br />' . $instance->dictionary->link . '<br /><br /></p>';

        $dictionary->notes = '<p>The primary and optional secondary items should be fully qualified - ie, devices.type or software.name.<br /><br /></p>';

        $dictionary->product = 'professional';
        $dictionary->columns->id = $instance->dictionary->id;
        $dictionary->columns->name = $instance->dictionary->name;
        $dictionary->columns->org_id = $instance->dictionary->org_id;
        $dictionary->columns->description = $instance->dictionary->description;
        $dictionary->columns->table = 'The primary database table upon which to base this widget.';
        $dictionary->columns->primary = 'The fully qualified column upon which to group by.';
        $dictionary->columns->secondary = 'The optional secondary column.';
        $dictionary->columns->ternary = 'The optional third column.';
        $dictionary->columns->where = 'Any required filter.';
        $dictionary->columns->limit = 'Limit the query to the first X items.';
        $dictionary->columns->options = 'unused';
        $dictionary->columns->group_by = 'This is generally the primary column, unless otherwise configured.';
        $dictionary->columns->type = "Can be 'line' or 'pie'.";
        $dictionary->columns->dataset_title = 'The text for the bottom of the chart in a line chart (only).';
        $dictionary->columns->sql = "For advanced entry of a raw SQL query. As per Queries, you must include 'WHERE @filter AND' in your SQL.";
        $dictionary->columns->link = 'The template for the link to be generated per result line.';
        $dictionary->columns->edited_by = $instance->dictionary->edited_by;
        $dictionary->columns->edited_date = $instance->dictionary->edited_date;

        $dictionary->valid_columns = array('bios.current','bios.description','bios.manufacturer','bios.version','disk.current','disk.description','disk.interface_type','disk.manufacturer','disk.model','disk.model_family','disk.partition_count','disk.status','disk.version','ip.cidr','ip.current','ip.netmask','ip.network','ip.version','log.current','log.file_name','log.name','memory.current','memory.detail','memory.form_factor','memory.size','memory.speed','memory.type','module.class_text','module.current','module.description','monitor.aspect_ratio','monitor.current','monitor.description','monitor.manufacturer','monitor.model','monitor.size','motherboard.current','motherboard.manufacturer','motherboard.memory_slot_count','motherboard.model','motherboard.processor_slot_count','network.connection_status','network.current','network.dhcp_enabled','network.dhcp_server','network.dns_domain','network.dns_server','network.manufacturer','network.model','network.type','optical.current','optical.model','optical.mount_point','pagefile.current','pagefile.max_size','pagefile.name','pagefile_initial_size','partition.bootable','partition.current','partition.description','partition.format','partition.mount_point','partition.mount_type','partition.name','partition.type','print_queue.color','print_queue.current','print_queue.duplex','print_queue.location','print_queue.manufacturer','print_queue.model','print_queue.port_name','print_queue.shared','print_queue.status','print_queue.type','processor.architecture','processor.core_count','processor.current','processor.description','processor.logical_count','processor.manufacturer','processor.physical_count','processor.socket','route.current','route.destination','route.mask','route.next_hop','route.type','server.current','server.description','server.edition','server.full_name','server.name','server.status','server.type','server.version','server.version_string','server_item.current','server_item.type','service.current','service.executable','service.name','service.start_mode','service.state','service.user','share.current','share.name','share.path','software.current','software.install_source','software.name','software_key.current','software_key.edition','software_key.name','software_key.rel','software_key.string','sound.current','sound.manufacturer','sound.model','devices.class','devices.cloud_id','devices.contact_name','devices.environment','devices.form_factor','devices.function','devices.icon','devices.instance_provider', 'devices.instance_state', 'devices.instance_type','devices.invoice_id','devices.last_seen_by','devices.lease_expiry_date','devices.location_id','devices.location_latitude','devices.location_level','devices.location_longitude','devices.location_rack','devices.location_rack_position','devices.location_rack_size','devices.location_room','devices.location_suite','devices.manufacturer','devices.memory_count','devices.model','devices.oae_manage','devices.org_id','devices.os_bit','devices.os_family','devices.os_group','devices.os_installation_date','devices.os_name','devices.os_version','devices.owner','devices.patch_panel','devices.printer_color','devices.printer_duplex','devices.printer_port_name','devices.printer_shared','devices.printer_shared_name','devices.processor_count','devices.purchase_amount','devices.purchase_cost_center','devices.purchase_date','devices.purchase_invoice','devices.purchase_order_number','devices.purchase_service_contract_number','devices.purchase_vendor','devices.service_network','devices.service_number','devices.service_plan','devices.service_provider','devices.service_type','devices.snmp_oid','devices.status','devices.sysContact','devices.sysDescr','devices.sysLocation','devices.sysObjectID','devices.type','devices.wall_port','devices.warranty_duration','devices.warranty_expires','devices.warranty_type','user.current','user.domain','user.password_changeable','user.password_required','user.status','user.type','user_group.current','user_group.name','video.current','video.manufacturer','video.model','video.size','vm.current','vm.cpu_count','vm.memory_count','vm.status','windows.active_directory_ou','windows.boot_device','windows.build_number','windows.client_site_name','windows.country_code','windows.current','windows.domain_controller_address','windows.domain_controller_name','windows.domain_role','windows.domain_short','windows.id_number','windows.install_directory','windows.language','windows.organisation','windows.part_of_domain','windows.registered_user','windows.service_pack','windows.time_caption','windows.time_daylight','windows.version','windows.workgroup');

        $dictionary->valid_tables = array('bios','devices','disk','dns','ip','log','memory','module','monitor','motherboard','netstat','network','nmap','optical','pagefile','partition','print_queue','processor','route','san','scsi','server','server_item','service','share','software','software_key','sound','task','user','user_group','variable','video','vm','warranty','windows');

        return $dictionary;
    }
}