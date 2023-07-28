<?php
# Copyright © 2023 FirstWave. All Rights Reserved.
# SPDX-License-Identifier: AGPL-3.0-or-later
include 'shared/create_functions.php';
?>
        <main class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <?= create_card_header($meta->collection, $meta->icon, $user); ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form class="form-horizontal" method="post" action="<?= url_to($meta->collection.'Create') ?>">
                                <input type="hidden" value="<?= $meta->access_token ?>" id="data[access_token]" name="data[access_token]" />
                                <?= create_text_field('data[attributes][name]', __('Name'), $dictionary->attributes->create) ?>
                                <?= create_select('data[attributes][org_id]', __('Organisation'), $orgs, $dictionary->attributes->create) ?>
                                <?= create_text_field('data[attributes][description]', __('Description'), $dictionary->attributes->create) ?>
                                <?= create_select('data[attributes][menu_display]', __('Menu Display'), '', $dictionary->attributes->create) ?>
                                <div class="row" style="padding-top:16px;">
                                    <div class="offset-2 col-8">
                                        <label class="form-label" for="data[attributes][menu_category]"><?= __('Menu Category'); ?> <span style="color: #dc3545;">*</span></label>
                                        <select class="form-select" name="data[attributes][menu_category]" id="data[attributes][menu_category]" required>
                                            <option value="">Choose</option>
                                            <?php foreach ($included['attributes'] as $category) { ?>
                                                <option value="<?= $category->attributes->value ?>"><?= __($category->attributes->name); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row" style="padding-top:16px;">
                                    <div class="offset-2 col-8">
                                        <label class="form-label" for="data[attributes][sql]"><?= __('SQL'); ?> <span style="color: #dc3545;">*</span></label>
                                        <textarea class="form-control" rows="10" name="data[attributes][sql]" id="data[attributes][sql]"></textarea>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="offset-2 col-8">
                                        <label for="submit" class="form-label">&nbsp;</label>
                                        <button id="submit" name="submit" type="submit" class="btn btn-primary"><?= __('Submit'); ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="offset-2 col-8">
                                <?php if (! empty($dictionary->about)) {
                                    echo "<h4 class=\"text-center\">About</h4><br>";
                                    echo html_entity_decode($dictionary->about);
                                } ?>
                                <?php if (! empty($dictionary->notes)) {
                                    echo "<h4 class=\"text-center\">Notes</h4><br>";
                                    echo html_entity_decode($dictionary->notes);
                                } ?>
                                <h4 class="text-center">Fields</h4><br>
                                <?php $fields = array('name', 'org_id', 'description', 'menu_display', 'menu_category', 'sql'); ?>
                                <?php foreach ($dictionary->columns as $key => $value) {
                                    if (in_array($key, $fields)) {
                                        echo "<code>$key:</code> " . html_entity_decode($value) . "<br><br>";
                                    }
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

<script>
$(document).ready(function () {
    $("#data\\[attributes\\]\\[menu_display\\]").val("y");
});
</script>