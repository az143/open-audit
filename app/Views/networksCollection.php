<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later
include 'shared/collection_functions.php';
?>
        <main class="container-fluid">
            <?php if (!empty($config->license) and $config->license !== 'none') { ?>
            <div class="card">
                <div class="card-header" style="height:57px;">
                    <div class="row">
                        <div class="col-9 clearfix">
                            <h6 style="padding-top:10px;"><span class="fa fa-sliders oa-icon"></span><?= __('Advanced') ?></h6>
                        </div>
                        <div class="col-3 clearfix pull-right">
                            <div class="btn-group btn-group-sm float-end mb-2" role="group">
                                <button class="btn btn-outline-secondary panel-button c_change_primary" type="button" data-bs-toggle="collapse" data-bs-target="#advanced" aria-expanded="false" aria-controls="advanced"><span class="fa fa-angle-down text-primary"></span></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body collapse" id="advanced">
                    <div class="row">
                        <div class="col-4">
                            <span class="panel-title"><?= __('Networks using a CIDR Mask of') ?></span>
                            <table class="table table-striped cidr_table" id="table_cidr" data-order='[[1,"asc"]]'>
                                <thead>
                                    <tr>
                                        <th data-orderable="false" class="text-center"><?= __('Details') ?></th>
                                        <th class="text-center"><?= __('CIDR Mask') ?></th>
                                        <th class="text-center"><?= __('Count') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($included['cidrs'] as $cidr) { ?>
                                    <tr>
                                        <td class="text-center"><a title="<?= __('CIDR') ?>" role="button" class="btn btn-sm btn-primary" href="<?= url_to('networksCollection') ?>?networks.network=like/<?= $cidr->cidr ?>"><span class="fa fa-eye" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= $cidr->cidr ?></td>
                                        <td class="text-center"><?= $cidr->count ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-4">
                            <span class="panel-title"><?= __('Networks Generated By') ?></span>
                            <table class="table table-striped cidr_table" id="table_generated_by" data-order='[[1,"asc"]]'>
                                <thead>
                                    <tr>
                                        <th data-orderable="false" class="text-center"><?= __('Details') ?></th>
                                        <th class="text-center"><?= __('Generated By') ?></th>
                                        <th class="text-center"><?= __('Count') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center"><a title="<?= __('Discoveries') ?>" role="button" class="btn btn-sm btn-success" href="<?= url_to('networksCollection') ?>?networks.edited_by=auto-generated%20by%20discoveries::index"><span class="fa fa-binoculars" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= __('Discoveries') ?></td>
                                        <td class="text-center"><?= $included['discoveries'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><a title="<?= __('Audits') ?>" role="button" class="btn btn-sm btn-devices" href="<?= url_to('networksCollection') ?>?networks.description=Inserted%20from%20audit%20result."><span class="fa fa-desktop" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= __('Device Audits') ?></td>
                                        <td class="text-center"><?= $included['devices'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><a title="<?= __('Collectors') ?>" role="button" class="btn btn-sm btn-info" href="<?= url_to('networksCollection') ?>?networks.description=LIKEcollector%20address"><span class="fa fa-cogs" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= __('Collectors') ?></td>
                                        <td class="text-center"><?= $included['collectors'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-center"><a title="<?= __('Clouds') ?>" role="button" class="btn btn-sm btn-warning" href="<?= url_to('networksCollection') ?>?networks.options=!="><span class="fa fa-cloud" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= __('Clouds') ?></td>
                                        <td class="text-center"><?= $included['clouds'] ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-md-4">
                            <span class="panel-title"><?= __('Network Types') ?></span>
                            <table class="table table-striped cidr_table" id="table_types" data-order='[[1,"asc"]]'>
                                <thead>
                                    <tr>
                                        <th data-orderable="false" class="text-center"><?= __('Details') ?></th>
                                        <th class="text-center"><?= __('Type') ?></th>
                                        <th class="text-center"><?= __('Count') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($included['types'] as $type) {
                                        $url_type = $type->type;
                                        $url_type = str_replace(' ', '%20', $url_type); ?>
                                    <tr>
                                        <td class="text-center"><a title="<?= $url_type ?>" role="button" class="btn btn-sm btn-primary" href="<?= url_to('networksCollection') ?>?networks.type=<?= $url_type ?>"><span class="fa fa-eye" aria-hidden="true"></span></a></td>
                                        <td class="text-center"><?= $type->type ?></td>
                                        <td class="text-center"><?= $type->count ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            <br>
            <?php } ?>

            <div class="card">
                <div class="card-header">
                    <?= collection_card_header($meta->collection, $meta->icon, $user, '', $meta->query_string) ?>
                </div>
                <div class="card-body">
                    <br>
                    <div class="table-responsive">
                        <table class="table <?= $GLOBALS['table'] ?> table-striped table-hover dataTable" data-order='[[2,"asc"]]'>
                            <thead>
                                <tr>
                                    <th data-orderable="false" class="text-center"><?= __('Details') ?></th>
                                    <th data-orderable="false" class="text-center"><?= __('Devices') ?></th>
                                    <?php foreach ($meta->data_order as $key) {
                                        if ($key === 'id' or $key === 'orgs.id') {
                                            continue;
                                        } ?>
                                        <th><?= collection_column_name($key) ?></th>
                                    <?php } ?>
                                    <?php if (strpos($user->permissions[$meta->collection], 'd') !== false) { ?>
                                    <th data-orderable="false" class="text-center"><?= __('Delete') ?></th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($data)) { ?>
                                <?php foreach ($data as $item) { ?>
                                <tr>
                                    <?= collection_button_read($meta->collection, $item->id) ?>
                                    <?= collection_button_devices(url_to('devicesCollection') . '?ip.network=' . $item->attributes->network . '&properties=devices.id,devices.icon,devices.type,devices.name,devices.domain,ip.ip,devices.description,devices.manufacturer,devices.os_family,devices.status') ?>
                                    <?php foreach ($meta->data_order as $key) {
                                        if ($key === 'id' or $key === 'orgs.id') {
                                            continue;
                                        }
                                        if ($key === 'resource') {
                                            echo "<td><a href=\"" . url_to($meta->collection.'Collection') . "?" . $meta->collection . ".resource=" . $item->attributes->resource . "\">" . $item->attributes->resource . "</a></td>\n";
                                        } elseif ($key === 'type') {
                                            echo "<td><a href=\"" . url_to($meta->collection.'Collection') . "?" . $meta->collection . ".type=" . $item->attributes->type . "\">" . $item->attributes->type . "</a></td>\n";
                                        } elseif ($key === 'orgs.name' and !empty($item->attributes->{'orgs.id'})) {
                                            echo "<td><a href=\"" . url_to($meta->collection.'Collection') . "?" . $meta->collection . ".org_id=" . $item->attributes->{'orgs.id'} . "\">" . $item->attributes->{$key} . "</a></td>\n";
                                        } else {
                                            echo "<td>" . $item->attributes->{$key} . "</td>\n";
                                        }
                                        ?>
                                    <?php } ?>
                                    <?php if (strpos($user->permissions[$meta->collection], 'd') !== false) { ?>
                                        <?= collection_button_delete(intval($item->id)) ?>
                                    <?php } ?>
                                </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
