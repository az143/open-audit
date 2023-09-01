<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace App\Controllers;

use \stdClass;

/**
 * PHP version 7.4
 *
 * @category  Controller
 * @package   Open-AudIT\Controller
 * @author    Mark Unwin <mark.unwin@firstwave.com>
 * @copyright 2023 FirstWave
 * @license   http://www.gnu.org/licenses/agpl-3.0.html aGPL v3
 * @version   GIT: Open-AudIT_5.0.0
 * @link      http://www.open-audit.org
 */

/**
 * Base Object Attributes
 *
 * @access   public
 * @category Object
 * @package  Open-AudIT\Controller\Attributes
 * @author   Mark Unwin <mark.unwin@firstwave.com>
 * @license  http://www.gnu.org/licenses/agpl-3.0.html aGPL v3
 * @link     http://www.open-audit.org
 */
class Collections extends BaseController
{

    /**
     * Update a list of items
     *
     * @access public
     * @return NULL
     */
    public function bulkUpdate()
    {
        if (empty($this->resp->meta->received_data->ids)) {
            return false;
        }
        $ids = explode(',', $this->resp->meta->received_data->ids);
        foreach ($ids as $id) {
            $status = $this->{$this->resp->meta->collection.'Model'}->update($id, $this->resp->meta->received_data->attributes);
        }
        if ($status) {
            output($this);
            return;
        }
        $this->response->setStatusCode(400);
        if (!empty($GLOBALS['stash'])) {
            print_r(json_encode($GLOBALS['stash']));
        }
        return;
    }

    /**
     * Collection of items
     *
     * @access public
     * @return NULL
     */
    public function collection(string $export = '')
    {
        $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->collection($this->resp);
        $this->resp->meta->total = count($this->{strtolower($this->resp->meta->collection) . "Model"}->listUser());
        $this->resp->meta->filtered = count($this->resp->data);
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        if (empty($this->resp->meta->properties[0]) or $this->resp->meta->properties[0] === $this->resp->meta->collection . '.*') {
            $this->resp->meta->data_order = $dictionary->attributes->collection;
        } else {
            $this->resp->meta->data_order = array();
            foreach ($this->resp->meta->properties as $key) {
                $this->resp->meta->data_order[] = str_replace($this->resp->meta->collection . '.', '', $key);
            }
        }
        if ($this->resp->meta->collection === 'discoveries') {
            $this->resp->included['issues'] = $this->discoveriesModel->issuesCollection(intval($this->user->id));
            // $this->collectorsModel = new \App\Models\CollectorsModel;
            // $this->resp->included['collectors'] = $this->collectorsModel->listUser();
        }
        if ($this->resp->meta->collection === 'clouds' or
            $this->resp->meta->collection === 'discoveries' or
            $this->resp->meta->collection === 'networks') {
            $this->resp->included = array_merge($this->resp->included, $this->{strtolower($this->resp->meta->collection) . "Model"}->includedCollection());
        }

        if ($this->resp->meta->format !== 'html') {
            output($this);
            return;
        }
        $view = $this->resp->meta->collection . ucfirst($this->resp->meta->action);
        if (empty($this->resp->included)) {
            $this->resp->included = array();
        }
        // A special case for the Bulk Update Form
        if ($this->resp->meta->request_method === 'GET' and strpos($this->resp->meta->query_string, 'action=bulkupdateform') !== false) {
            $view = $this->resp->meta->collection . 'BulkUpdateForm';
            $this->resp->included = $this->{strtolower($this->resp->meta->collection) . "Model"}->includedBulkUpdate();
            $this->resp->meta->action = 'bulkupdateform';
        }
        if ($export === 'export') {
            $view = 'collectionExport';
        }
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'meta' => filter_response($this->resp->meta),
            'queries' => filter_response($this->queriesUser),
            'roles' => filter_response($this->roles),
            'user' => filter_response($this->user)]) .
            view($view, ['data' => filter_response($this->resp->data), 'included' => filter_response($this->resp->included)]);
        return true;
    }

    /**
     * Accept a POST and create the item
     *
     * @return void
     */
    public function create()
    {
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->enterprise_collections)) {
            if (strpos(config('Openaudit')->enterprise_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise') {
                log_message('debug', config('Openaudit')->product);
                log_message('debug', config('Openaudit')->enterprise_collections[$this->resp->meta->collection]);
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs an Enterprise license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->professional_collections)) {
            if (strpos(config('Openaudit')->professional_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise' and config('Openaudit')->product !== 'professional') {
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a Professional license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }

        if (!empty($this->resp->meta->id)) {
            $id = intval($this->resp->meta->id);
        }

        if (empty($this->resp->meta->id)) {
            $id = $this->{strtolower($this->resp->meta->collection) . "Model"}->create($this->resp->meta->received_data->attributes);
        }

        if (!empty($id)) {
            if ($this->resp->meta->format !== 'html') {
                $this->resp->meta->header = 201;
                if ($this->resp->meta->collection !== 'components') {
                    $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->read($id);
                } else {
                    if ($this->resp->meta->received_data->attributes->component_type === 'discovery') {
                        $this->resp->data = $this->discoveriesModel->read($id);
                        // Sleep for a couple of seconds so some logs get a change happen
                        // and the resulting discovery screen doesn't look empty (as if nothing has happened).
                        sleep(2);
                    }
                }
                $this->resp->meta->id = $id;
                output($this);
                return true;
            } else {
                if ($this->resp->meta->collection !== 'components') {
                    \Config\Services::session()->setFlashdata('success', "Item in {$this->resp->meta->collection} created successfully.");
                    if ($this->resp->meta->collection === 'baselines_policies') {
                        return redirect()->route('baselinesRead', [$this->resp->meta->received_data->attributes->baseline_id]);
                    }
                    return redirect()->route($this->resp->meta->collection.'Read', [$id]);
                } else {
                    \Config\Services::session()->setFlashdata('success', ucwords($this->resp->meta->received_data->attributes->component_type) . " created successfully.");
                    if (!empty($this->resp->meta->received_data->attributes->device_id)) {
                        return redirect()->route('devicesRead', [$this->resp->meta->received_data->attributes->device_id]);
                    } else {
                        return redirect()->route('devicesCollection');
                    }
                }
            }
        } else {
            if ($this->resp->meta->format !== 'html') {
                $this->resp->meta->header = 500;
                if (!empty(\Config\Services::session()->getFlashdata('warning'))) {
                    $this->resp->errors = \Config\Services::session()->getFlashdata('warning');
                }
                if (!empty(\Config\Services::session()->getFlashdata('error'))) {
                    $this->resp->errors = \Config\Services::session()->getFlashdata('error');
                }
                output($this);
                return true;
            } else {
                log_message('error', 'Item in ' . $this->resp->meta->collection . ' not created.');
                return redirect()->route($this->resp->meta->collection.'Collection');
            }
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function createForm()
    {
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->enterprise_collections)) {
            if (strpos(config('Openaudit')->enterprise_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise') {
                log_message('debug', config('Openaudit')->product);
                log_message('debug', config('Openaudit')->enterprise_collections[$this->resp->meta->collection]);
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs an Enterprise license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->professional_collections)) {
            if (strpos(config('Openaudit')->professional_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise' and config('Openaudit')->product !== 'professional') {
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a Professional license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }
        $this->resp->included = $this->{$this->resp->meta->collection.'Model'}->includedCreateForm();
        $this->resp->included['orgs'] = $this->orgsModel->listUser();
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        if ($this->resp->meta->format !== 'html') {
            $this->resp->dictionary = $dictionary;
            output($this);
            return true;
        }
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'dictionary' => $dictionary,
            'included' => $this->resp->included,
            'meta' => filter_response($this->resp->meta),
            'queries' => filter_response($this->queriesUser),
            'roles' => filter_response($this->roles),
            'orgs' => filter_response($this->orgsUser),
            'user' => filter_response($this->user)]) .
            view($this->resp->meta->collection . ucfirst($this->resp->meta->action));
    }

    /**
     * Returnt the default items as per a new install
     *
     * @return void
     */
    public function defaults()
    {
        $this->baseModel = model('App\Models\BaseModel');
        $defaults = $this->baseModel->tableDefaults($this->resp->meta->collection);
        $this->databaseModel = model('App\Models\DatabaseModel');
        $data = $this->databaseModel->read($this->resp->meta->collection);
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        if ($this->resp->meta->format !== 'html') {
            $this->resp->dictionary_rows = $this->databaseModel->read($this->resp->meta->collection);
            $this->resp->dictionary = $dictionary;
            $this->resp->data = $defaults;
            output($this);
            return true;
        }
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'defaults' => filter_response($defaults),
            'dictionary' => $dictionary,
            'meta' => filter_response($this->resp->meta),
            'queries' => filter_response($this->queriesUser),
            'user' => filter_response($this->user)]) .
            view('collectionDefaults', ['data' => $data]);
    }

    /**
     * Delete the item
     *
     * @return void
     */
    public function delete()
    {
        if ($this->{$this->resp->meta->collection.'Model'}->delete($this->resp->meta->id)) {
            \Config\Services::session()->setFlashdata('success', 'Item in ' . $this->resp->meta->collection . ' deleted.');
            $temp = new stdClass();
            $temp->type = $this->resp->meta->collection;
            $this->resp->data = array();
            $this->resp->data[] = $temp;
        } else {
            $this->resp->meta->header = 500;
            \Config\Services::session()->setFlashdata('error', 'Item in ' . $this->resp->meta->collection . ' not deleted.');
            if (!empty(\Config\Services::session()->getFlashdata('error'))) {
                $this->resp->errors = \Config\Services::session()->getFlashdata('error');
            } else if (!empty(\Config\Services::session()->getFlashdata('warning'))) {
                $this->resp->errors = \Config\Services::session()->getFlashdata('warning');
            } else {
                $this->resp->errors = 'Item in ' . $this->resp->meta->collection . ' not deleted.';
            }
        }
        output($this);
    }

    public function dictionary($model)
    {
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        echo json_encode($dictionary);
    }

    /**
     * Help
     *
     * @return void
     */
    public function help()
    {
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'dictionary' => $dictionary,
            'meta' => filter_response($this->resp->meta),
            'orgs' => filter_response($this->orgsUser),
            'queries' => filter_response($this->queriesUser),
            'roles' => filter_response($this->roles),
            'user' => filter_response($this->user)]) .
            view('collectionHelp', ['data' => $dictionary]);
    }

    /**
     * Import multiple uploaded items
     *
     * @return void
     */
    public function import()
    {
        $file = $this->request->getFile('file_import');
        if (!$file->isValid()) {
            \Config\Services::session()->setFlashdata('error', 'File import error. ' . $file->getErrorString() . ' ' . $file->getError());
        }
        $csv = @array_map('str_getcsv', file($file->getTempName(), FILE_IGNORE_NEW_LINES));
        if (!$csv) {
            if ($this->response->meta->format === 'json') {
                output($this->resp);
                return;
            }
            \Config\Services::session()->setFlashdata('error', 'CSV error.');
            return redirect()->route($this->resp->meta->collection.'Collection');
        }
        $attributes = $csv[0];
        $row_count = count($csv);
        $column_count = count($attributes);
        $count_create = 0;
        $count_create_fail = 0;
        $count_update = 0;
        $count_update_fail = 0;
        $id = array();
        for ($i=1; $i < $row_count; $i++) {
            $data = new stdClass();
            for ($j=0; $j < $column_count; $j++) {
                $data->{$attributes[$j]} = $csv[$i][$j];
            }
            if ($this->resp->meta->collection === 'devices' and empty($data->last_seen_by)) {
                $data->last_seen_by = 'user';
            }
            foreach ($data as $key => $value) {
                $data->{$key} = str_replace("\\n", "\n", $value);
                $data->{$key} = str_replace("\\r", "\r", $data->{$key});
            }

            if (($this->resp->meta->collection === 'credential' or $this->resp->meta->collection === 'credentials' or $this->resp->meta->collection === 'clouds') and empty($data->credentials)) {
                $data->credentials = new \stdClass();
                foreach ($data as $key => $value) {
                    if (strpos($key, 'credentials.') !== false) {
                        $data->credentials->{str_replace('credentials.', '', $key)} = $value;
                    }
                }
            }

            if (($this->resp->meta->collection === 'dashboards' or $this->resp->meta->collection === 'scripts' or $this->resp->meta->collection === 'tasks') and empty($data->options)) {
                $data->options = new \stdClass();
                foreach ($data as $key => $value) {
                    if (strpos($key, 'options.') !== false) {
                        $data->options->{str_replace('options.', '', $key)} = $value;
                    }
                }
            }

            if ($this->resp->meta->collection === 'discoveries' and empty($data->scan_options)) {
                $data->options = new \stdClass();
                foreach ($data as $key => $value) {
                    if (strpos($key, 'scan_options.') !== false) {
                        $data->options->{str_replace('scan_options.', '', $key)} = $value;
                    }
                }
            }

            if ($this->resp->meta->collection === 'discoveries' and empty($data->match_options)) {
                $data->options = new \stdClass();
                foreach ($data as $key => $value) {
                    if (strpos($key, 'match_options.') !== false) {
                        $data->options->{str_replace('match_options.', '', $key)} = $value;
                    }
                }
            }

            if (!empty($data->id)) {
                $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->read(intval($data->id));
                if (empty($test)) {
                    log_message('warning', 'ID provided to JSON import of ' . $data->id . ' for ' . $this->resp->meta->collection . ' but that row does not exist, removing ID and creating, not updating.');
                    unset($data->id);
                }
            }

            if (!empty($data->id)) {
                $test = $this->{$this->resp->meta->collection.'Model'}->update(intval($data->id), $data);
                if (!empty($test)) {
                    $id[] = $data->id;
                    $count_update += 1;
                } else {
                    $count_update_fail += 1;
                }
            } else {
                $test = $this->{$this->resp->meta->collection.'Model'}->create($data);
                if (!empty($test)) {
                    $id[] = $test;
                    $count_create += 1;
                } else {
                    $count_create_fail += 1;
                }
            }
        }
        if (!empty($id)) {
            if ($this->resp->meta->format !== 'html') {
                if (count($id) > 1) {
                    $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->listUser();
                } else {
                    $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->read($id[0]);
                }
                output($this);
                return true;
            } else {
                $this->resp->meta->header = 200;
                if ($this->resp->meta->collection !== 'components') {
                    if (count($id) > 1) {
                        $message = $count_create . ' ' . $this->resp->meta->collection . ' created successfully.<br />';
                        $message .= $count_update . ' ' . $this->resp->meta->collection . ' updated successfully.<br />';
                        $message .= $count_create_fail . ' ' . $this->resp->meta->collection . ' failed to create.<br />';
                        $message .= $count_update_fail . ' ' . $this->resp->meta->collection . ' failed to update.';
                        \Config\Services::session()->setFlashdata('success', $message);
                        return redirect()->route($this->resp->meta->collection.'Collection');
                    } else {
                        $message = "1 Item in {$this->resp->meta->collection} created successfully.";
                        if ($count_update === 1) {
                            $message = "1 Item in {$this->resp->meta->collection} updated successfully.";
                        }
                        \Config\Services::session()->setFlashdata('success', $message);
                        return redirect()->route($this->resp->meta->collection.'Read', [$id[0]]);
                    }
                } else {
                    \Config\Services::session()->setFlashdata('success', ucwords($this->resp->meta->received_data->attributes->component_type) . " created successfully.");
                    if (!empty($this->resp->meta->received_data->attributes->device_id)) {
                        return redirect()->route('devicesRead', [$this->resp->meta->received_data->attributes->device_id]);
                    } else {
                        return redirect()->route('devicesCollection');
                    }
                }
            }
        } else {
            if ($this->resp->meta->format !== 'html') {
                $this->resp->meta->header = 500;
                output($this);
                return true;
            } else {
                log_message('error', 'Item in ' . $this->resp->meta->collection . ' not created.');
                return redirect()->route($this->resp->meta->collection.'Collection');
            }
        }
    }

    /**
     * Provide a html form to upload multiple items
     *
     * @return void
     */
    public function importForm()
    {
        $this->databaseModel = model('App\Models\DatabaseModel');
        $this->resp->data = $this->databaseModel->read($this->resp->meta->collection);
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'dictionary' => $dictionary,
            'meta' => $this->resp->meta,
            'orgs' => $this->orgsUser,
            'queries' => $this->queriesUser,
            'roles' => $this->roles,
            'user' => $this->user ]) .
            view('collectionImport', ['data' => $dictionary]);
        return;
    }

    /**
     * Provide a html form to upload multiple items
     *
     * @return void
     */
    public function importJSONForm()
    {
        $this->databaseModel = new \App\Models\DatabaseModel();
        $this->resp->data = $this->databaseModel->read($this->resp->meta->collection);
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        return view('shared/header', [
            'config' => $this->config,
            'dashboards' => filter_response($this->dashboards),
            'dictionary' => $dictionary,
            'meta' => $this->resp->meta,
            'orgs' => $this->orgsUser,
            'queries' => $this->queriesUser,
            'roles' => $this->roles,
            'user' => $this->user ]) .
            view('collectionImportJsonForm', ['data' => $dictionary]);
        return;
    }

    /**
     * Accept a POST, use the JSON to create the item
     *
     * @return void
     */
    public function importJSON()
    {
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->enterprise_collections)) {
            if (strpos(config('Openaudit')->enterprise_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise') {
                log_message('debug', config('Openaudit')->product);
                log_message('debug', config('Openaudit')->enterprise_collections[$this->resp->meta->collection]);
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs an Enterprise license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }
        if (array_key_exists($this->resp->meta->collection, config('Openaudit')->professional_collections)) {
            if (strpos(config('Openaudit')->professional_collections[$this->resp->meta->collection], 'c') !== false and config('Openaudit')->product !== 'enterprise' and config('Openaudit')->product !== 'professional') {
                log_message('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a Professional license.');
                \Config\Services::session()->setFlashdata('error', 'Creating an item in ' . $this->resp->meta->collection . ' needs a commercial license.');
                return redirect()->route($this->config->homepage);
            }
        }
        // Account for old style collection JSON export
        if (!empty($this->resp->meta->received_data->json->data)) {
            $data = $this->resp->meta->received_data->json->data;
            unset($this->resp->meta->received_data->json->data);
            $this->resp->meta->received_data->json = array();
            foreach ($data as $item) {
                $this->resp->meta->received_data->json[] = $item;
            }
        }
        // Account for old style singular JSON export
        if (!is_array($this->resp->meta->received_data->json)) {
            $this->resp->meta->received_data->json = array($this->resp->meta->received_data->json);
        }
        $id = array();
        $count_create = 0;
        $count_create_fail = 0;
        $count_update = 0;
        $count_update_fail = 0;
        foreach ($this->resp->meta->received_data->json as $item) {
            if (!empty($item->id)) {
                $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->read(intval($item->id));
                if (empty($test)) {
                    log_message('warning', 'ID provided to JSON import of ' . $item->id . ' for ' . $this->resp->meta->collection . ' but that row does not exist, removing ID and creating, not updating.');
                    unset($item->id);
                }
            }
            if (!empty($item->id)) {
                if (empty($item->attributes)) {
                    // Account for old style individual JSON export
                    $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->update(intval($item->id), $item);
                } else {
                    $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->update(intval($item->id), $item->attributes);
                }
                if (!empty($test)) {
                    $id[] = $item->id;
                    $count_update += 1;
                } else {
                    $count_update_fail += 1;
                }
            } else {
                if (empty($item->attributes)) {
                    // Account for old style individual JSON export
                    $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->create($item);
                } else {
                    $test = $this->{strtolower($this->resp->meta->collection) . "Model"}->create($item->attributes);
                }
                if (!empty($test)) {
                    $id[] = $test;
                    $count_create += 1;
                } else {
                    $count_create_fail += 1;
                }
            }
        }
        if (!empty($id)) {
            if ($this->resp->meta->format !== 'html') {
                if (count($id) > 1) {
                    $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->listUser();
                } else {
                    $this->resp->data = $this->{strtolower($this->resp->meta->collection) . "Model"}->read($id[0]);
                }
                output($this);
                return true;
            } else {
                $this->resp->meta->header = 200;
                if ($this->resp->meta->collection !== 'components') {
                    if (count($id) > 1) {
                        $message = $count_create . ' ' . $this->resp->meta->collection . ' created successfully.<br />';
                        $message .= $count_update . ' ' . $this->resp->meta->collection . ' updated successfully.<br />';
                        $message .= $count_create_fail . ' ' . $this->resp->meta->collection . ' failed to create.<br />';
                        $message .= $count_update_fail . ' ' . $this->resp->meta->collection . ' failed to update.';
                        \Config\Services::session()->setFlashdata('success', $message);
                        return redirect()->route($this->resp->meta->collection.'Collection');
                    } else {
                        $message = "1 Item in {$this->resp->meta->collection} created successfully.";
                        if ($count_update === 1) {
                            $message = "1 Item in {$this->resp->meta->collection} updated successfully.";
                        }
                        \Config\Services::session()->setFlashdata('success', $message);
                        return redirect()->route($this->resp->meta->collection.'Read', [$id[0]]);
                    }
                } else {
                    \Config\Services::session()->setFlashdata('success', ucwords($this->resp->meta->received_data->attributes->component_type) . " created successfully.");
                    if (!empty($this->resp->meta->received_data->attributes->device_id)) {
                        return redirect()->route('devicesRead', [$this->resp->meta->received_data->attributes->device_id]);
                    } else {
                        return redirect()->route('devicesCollection');
                    }
                }
            }
        } else {
            if ($this->resp->meta->format !== 'html') {
                $this->resp->meta->header = 500;
                output($this);
                return true;
            } else {
                log_message('error', 'Item in ' . $this->resp->meta->collection . ' not created.');
                return redirect()->route($this->resp->meta->collection.'Collection');
            }
        }
    }


    /**
     * Read a single item
     *
     * @access public
     * @return void
     */
    public function read($id, string $export = '')
    {
        if ($this->resp->meta->collection !== 'database') {
            $this->resp->meta->id = intval($this->resp->meta->id);
        }
        $this->resp->data = $this->{$this->resp->meta->collection.'Model'}->read($this->resp->meta->id);
        $this->resp->meta->total = count($this->{$this->resp->meta->collection.'Model'}->listUser());
        $this->resp->meta->filtered = count($this->resp->data);
        $dictionary = $this->{$this->resp->meta->collection.'Model'}->dictionary();
        if ($this->resp->meta->collection === 'database') {
            $filename = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->resp->meta->id)));
            if (file_exists(APPPATH . '/Models/' . $filename . 'Model.php')) {
                $namespace = "\\App\\Models\\" . $filename . "Model";
                $IdModel = new $namespace;
                $dictionary =  $IdModel->dictionary();
                if ($this->resp->meta->id === 'integrations') {
                    $dictionary->columns->attributes = 'A JSON encoded set of details for accessing the external system.';
                }
            }
        }

        if ($this->resp->meta->format !== 'html') {
            if ($this->resp->meta->collection === 'devices' or !empty($this->resp->meta->requestor)) {
                $this->resp->included = $this->{$this->resp->meta->collection.'Model'}->includedRead($this->resp->meta->id);
                $this->resp->dictionary = $dictionary;
            }
            output($this);
            return true;
        } else {
            if (empty($this->resp->data)) {
                log_message('warning', 'Invalid ID provided to read function. ID: ' . $this->resp->meta->id . ', collection: ' . $this->resp->meta->collection);
                \Config\Services::session()->setFlashdata('warning', 'Invalid ID provided to ' . $this->resp->meta->collection . ' read function (ID: ' . $this->resp->meta->id . ')');
                return redirect()->route($this->resp->meta->collection.'Collection');
            } else {
                if ($this->resp->meta->collection === 'baselines_results') {
                    $this->resp->meta->breadcrumbs = array();
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselinesCollection');
                    $breadcrumb->name = 'Baselines';
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselinesRead', $this->resp->data[0]->attributes->baseline_id);
                    $breadcrumb->name = $this->resp->data[0]->attributes->{'baselines.name'};
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselines_resultsCollection') . '?baselines.id=' . $this->resp->data[0]->attributes->baseline_id;
                    $breadcrumb->name = 'Results';
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselines_resultsRead', $this->resp->data[0]->id);
                    $breadcrumb->name = $this->resp->data[0]->attributes->name;
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                }
                if ($this->resp->meta->collection === 'baselines_policies') {
                    $this->resp->meta->breadcrumbs = array();
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselinesCollection');
                    $breadcrumb->name = 'Baselines';
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselinesRead', $this->resp->data[0]->attributes->baseline_id);
                    $breadcrumb->name = $this->resp->data[0]->attributes->{'baselines.name'};
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselines_policiesCollection') . '?baselines.id=' . $this->resp->data[0]->attributes->baseline_id;
                    $breadcrumb->name = 'Policies';
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                    $breadcrumb = new stdClass();
                    $breadcrumb->url = url_to('baselines_policiesRead', $this->resp->data[0]->id);
                    $breadcrumb->name = $this->resp->data[0]->attributes->name;
                    $this->resp->meta->breadcrumbs[] = $breadcrumb;
                }
                $update = false;
                if (strpos($this->user->permissions[$this->resp->meta->collection], 'u') !== false) {
                    $update = true;
                }
                $this->resp->included = $this->{$this->resp->meta->collection.'Model'}->includedRead($this->resp->meta->id);
                $template = $this->resp->meta->collection . ucfirst($this->resp->meta->action);
                if (!empty($export)) {
                    $template = 'collectionExport';
                }
                return view('shared/header', [
                    'config' => $this->config,
                    'dashboards' => filter_response($this->dashboards),
                    'dictionary' => $dictionary,
                    'included' => filter_response($this->resp->included),
                    'meta' => filter_response($this->resp->meta),
                    'orgs' => filter_response($this->orgsUser),
                    'queries' => filter_response($this->queriesUser),
                    'roles' => filter_response($this->roles),
                    'user' => filter_response($this->user),
                    'name' => @$this->resp->data[0]->attributes->name]) .
                    view($template, ['data' => filter_response($this->resp->data), 'resource' => filter_response($this->resp->data[0]->attributes), 'update' => $update]);
            }
        }
    }

    /**
     * Delete all items, restore the original items and reset the table
     *
     * @return void
     */
    public function reset()
    {
        $this->{$this->resp->meta->collection.'Model'}->reset();
        if ($this->resp->meta->format !== 'html') {
            # Note the below data is only for Enterprise to accept data back after a POST
            $this->resp->data[0] = 'Table reset';
            output($this);
            return true;
        } else {
            return redirect()->route($this->resp->meta->collection.'Collection');
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function update()
    {
        if ($this->{$this->resp->meta->collection.'Model'}->update($this->resp->meta->received_data->id, $this->resp->meta->received_data->attributes)) {
            output($this);
        } else {
            $this->response->setStatusCode(400);
            if (!empty($GLOBALS['stash'])) {
                print_r(json_encode($GLOBALS['stash']));
            }
        }
        return;
    }
}
