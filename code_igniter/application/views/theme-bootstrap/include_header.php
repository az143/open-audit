
<div class="row">
    <nav class="navbar navbar-default">
        <div class="container-fluid">

            <!-- The left side 'header' of the navbar -->
            <div class="navbar-header">
                <a class="navbar-brand" href="/open-audit/">
                    <img alt="Brand" src="/open-audit/images/oac.png">
                </a>
                <a class="navbar-brand" href="/open-audit/">
                    Open-AudIT Community <?php echo htmlspecialchars($this->config->item('display_version'), REPLACE_FLAGS, CHARSET); ?>
                </a>
            </div>

            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Discover <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php
                            if ($this->m_users->get_user_permission('', 'credentials', 'r')) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#">Credentials</a>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/credentials'>List Credentials</a></li>
                                        <?php if ($this->m_users->get_user_permission('', 'credentials', 'c')) { ?>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/credentials/create'>Create Credentials</a></li>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/credentials/import'>Import Multiple Credentials</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php
                            } ?>
                            <?php
                            if ($this->m_users->get_user_permission('', 'discoveries', 'r')) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#">Discoveries</a>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/discoveries'>List Discoveries</a></li>
                                        <?php if ($this->m_users->get_user_permission('', 'discoveries', 'c')) { ?>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/discoveries/create'>Create Discovery</a></li>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/discoveries/import'>Import Multiple Discoveries</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php
                            } ?>
                            <?php
                            if ($this->m_users->get_user_permission('', 'files', 'r')) { ?>
                                <li class="dropdown-submenu">
                                    <?php if ($this->config->config['oae_license'] == 'commercial') { ?>
                                    <a href="#">Files</a>
                                    <?php } else { ?>
                                    <a href="#">Files <i class="fa fa-lock" aria-hidden="true" style="color: rgba(43, 41, 43, 0.56)"></i></a>
                                    <?php } ?>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                        <?php if ($this->config->config['oae_license'] == 'commercial') { ?>
                                            <li><a href='<?php echo $this->config->config['oae_url']; ?>/files'>List Files</a></li>
                                        <?php } else { ?>
                                            <li class="disabled"><a href="#">List Files</a></li>
                                        <?php } ?>
                                        <?php if ($this->m_users->get_user_permission('', 'files', 'c')) { ?>
                                            <?php if ($this->config->config['oae_license'] == 'commercial') { ?>
                                                <li><a target="_blank" href="<?php echo $this->config->config['oae_url']; ?>/files/create">Create Files</a></li>
                                                <li><a target="_blank" href="<?php echo $this->config->config['oae_url']; ?>/files/import">Import Multiple Files</a></li>
                                            <?php } else { ?>
                                                <li class="disabled"><a href="#">Create Files</a></li>
                                                <li class="disabled"><a href="#">Import Multiple Files</a></li>
                                            <?php } ?>
                                        <?php } ?>
                                        <?php if ($this->config->config['oae_license'] != 'commercial') { ?>
                                            <li><a target="_blank" style="color: #337ab7;" href='<?php echo $this->config->config['oae_url']; ?>/features/files'>Learn About Files</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php
                            } ?>
                            <?php
                            if ($this->m_users->get_user_permission('', 'scripts', 'r')) { ?>
                                <li class="dropdown-submenu">
                                    <a href="#">Audit Scripts</a>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/scripts'>List Scripts</a></li>
                                        <?php if ($this->m_users->get_user_permission('', 'scripts', 'c')) { ?>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/scripts/create'>Create Script</a></li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php
                            } ?>
                        </ul>
                    </li>

                    <!-- The Report menu -->
                        <?php $categories = array('Change','Device','Hardware','Network','Server','Software','User'); ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Report <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <?php foreach ($categories as $category) { ?>
                                <li class="dropdown-submenu">
                                    <a><?php echo $category ?></a>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                    <?php foreach ($this->response->included as $item) {
                                        if ($item->type == 'queries' and $category == $item->{'attributes'}->{'menu_category'} and $item->{'attributes'}->{'menu_display'} == 'y') { ?>
                                            <li><a href="<?php echo $item->{'type'} ?>/<?php $item->{'id'} ?>/execute"><?php echo $item->{'attributes'}->{'name'} ?></a></li>
                                        <?php } ?>
                                    <?php } ?>
                                    </ul>
                                </li>
                                <?php } ?>
                            </ul>
                        </li>


                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Manage <span class="caret"></span></a>
                        <ul class="dropdown-menu">

                        <?php
                        $collections = array('attributes','baselines','connections','devices','database','fields','groups','ldap_servers','licenses','locations','maps','networks','queries','orgs','roles','summaries','users');
                        foreach ($collections as $collection) {
                            if ($this->m_users->get_user_permission('', $collection, 'r')) { ?>
                                <li class="dropdown-submenu">
                                    <?php if ($this->config->config['oae_license'] != 'commercial' and ($collection == 'baselines' or $collection == 'roles')) { ?>
                                    <a href="#"><?php echo ucwords(str_replace('_', ' ', $collection)); ?> <i class="fa fa-lock" aria-hidden="true" style="color: rgba(43, 41, 43, 0.56)"></i></a>
                                    <?php } else { ?>
                                    <a href="#"><?php echo ucwords(str_replace('_', ' ', $collection)); ?></a>
                                    <?php } ?>
                                    <ul class="dropdown-menu" style="min-width:250px;">
                                        <?php if ($this->config->config['oae_license'] != 'commercial' and $collection == 'baselines') { ?>
                                        <li class="disabled"><a href="#">List <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                        <?php } else { ?>
                                        <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/<?php echo $collection; ?>'>List <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                        <?php } ?>

                                        <?php if ($this->m_users->get_user_permission('', $collection, 'c')) { ?>
                                            <?php if ($this->config->config['oae_license'] != 'commercial' and ($collection == 'baselines' or $collection == 'roles')) { ?>
                                            <li class="disabled"><a href="#">Create <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                            <li class="disabled"><a href="#">Import <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                            <?php } else { ?>
                                            <?php if ($collection == 'baselines' or $collection == 'roles') { $link = $this->config->config['oae_url']; } else { $link = $this->config->config['oa_web_index']; } ?>
                                            <li><a href='<?php echo $link; ?>/<?php echo $collection; ?>/create'>Create <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                            <li><a href='<?php echo $link; ?>/<?php echo $collection; ?>/import'>Import <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($collection == 'devices') { ?>
                                            <?php if ($this->config->config['nmis'] == 'y') { ?>
                                                <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/nmis/create'><?php echo __('NMIS Import')?></a></li>
                                            <?php } ?>
                                        <?php } ?>

                                        <?php if ($this->config->config['oae_license'] != 'commercial' and ($collection == 'baselines' or $collection == 'roles')) { ?>
                                        <li><a style="color: #337ab7;" href='<?php echo $this->config->config['oae_url']; ?>/features/<?php echo $collection; ?>'>Learn About <?php echo ucwords(str_replace('_', ' ', $collection)); ?></a></li>
                                        <?php } ?>



                                    </ul>
                                </li>
                            <?php
                            }
                        } ?>
                        </ul>
                    </li>
                </ul>
                <ul class="nav navbar-nav navbar-right">

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo __('Modules')?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php
                            $modules = json_decode($this->config->config['modules']);
                            foreach ($modules as $modules) {
                                if (!empty($modules->installed)) {
                                    $url = $modules->link;
                                } else {
                                    $url = $modules->url;
                                }
                            ?>
                            <li><a href='<?php echo $url; ?>'><?php echo $modules->name; ?></a></li>
                            <?php
                            }
                            ?>
                        </ul>
                    </li>

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo __('Licenses')?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <?php if ($this->config->config['oae_license'] == 'none') { ?>
                                <li><a target='_blank' href='/omk/oae/license_free'><?php echo __('Activate Free License')?></a></li>
                            <?php } ?>
                            <li><a target='_blank' href='/omk/opLicense'><?php echo __('Manage Licenses')?></a></li>
                            <li><a href='#' id='buy_more_licenses'><?php echo __('Buy More Licenses')?></a></li>
                            <li><a target='_blank' href='/omk/opLicense'><?php echo __('Restore Licenses')?></a></li>
                        </ul>
                    </li>

                    <?php
                    if ($this->m_users->get_user_permission('', 'configuration', 'r') !== false or
                        $this->m_users->get_user_permission('', 'database', 'u') !== false or
                        $this->m_users->get_user_permission('', 'ldap_servers', 'u') !== false or
                        $this->m_users->get_user_permission('', 'logs', 'r') !== false) { ?>
                    <li class="dropdown">
                      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
                      <ul class="dropdown-menu">

                        <?php
                        if ($this->m_users->get_user_permission('', 'configuration', 'r')) { ?>
                            <li class="dropdown-submenu">
                                <a href="#">Configuration</a>
                                <ul class="dropdown-menu" style="min-width:250px;">
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/configuration'>List Configuration</a></li>
                                </ul>
                            </li>
                        <?php
                        } ?>

                        <?php
                        if ($this->m_users->get_user_permission('', 'database', 'u')) { ?>
                            <li class="dropdown-submenu">
                                <a href="#">Database</a>
                                <ul class="dropdown-menu" style="min-width:250px;">
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/database'>List Tables</a></li>
                                </ul>
                            </li>
                        <?php
                        } ?>

                        <?php
                        if ($this->m_users->get_user_permission('', 'ldap_servers', 'u')) { ?>
                            <li class="dropdown-submenu">
                                <a href="#">LDAP Servers</a>
                                <ul class="dropdown-menu" style="min-width:250px;">
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/ldap_servers'>List Servers</a></li>
                                    <?php if ($this->m_users->get_user_permission('', 'ldap_servers', 'c')) { ?>
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/ldap_servers/create'>Create LDAP Server</a></li>
                                    <?php } ?>
                                </ul>
                            </li>
                        <?php
                        } ?>

                        <?php
                        if ($this->m_users->get_user_permission('', 'logs', 'r')) { ?>
                            <li class="dropdown-submenu">
                                <a href="#">Logs</a>
                                <ul class="dropdown-menu" style="min-width:250px;">
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/logs?logs.type=access'>View Access Logs</a></li>
                                    <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/logs?logs.type=system'>View System Logs</a></li>
                                    <?php if ($this->m_users->get_user_permission('', 'logs', 'd')) { ?>
                                    <?php } ?>
                                </ul>
                            </li>
                        <?php } ?>
                      </ul>
                    </li>
                    <?php } ?>

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Help <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help'><?php echo __('About')?></a></li>
                            <li><a href='https://community.opmantek.com/display/OA/Open-AudIT+FAQ'><?php echo __('FAQ')?></a></li>
                            <li><a href='https://community.opmantek.com/display/OA/Home'><?php echo __('Documentation')?></a></li>
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help/enterprise'><?php echo __('Open-AudIT Enterprise')?></a></li>
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help/support'><?php echo __('Support')?></a></li>
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help/queries'><?php echo __('Default Query List')?></a></li>
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help/groups'><?php echo __('Default Group List')?></a></li>
                            <li><a href='<?php echo $this->config->config['oa_web_index']; ?>/help/summaries'><?php echo __('Default Summary List')?></a></li>
                        </ul>
                    </li>

                    <li>
                        <a target='_blank' title="Maps" href="/omk/open-audit/map"><img style="width:22px;" src="/open-audit/images/logo-opmaps.png" alt=""/></a>
                    </li>

                    <?php
                    if (isset($this->config->config['mis_url']) and $this->config->config['nmis_url'] > "") {
                        $link = htmlspecialchars($this->config->item('nmis_url'), REPLACE_FLAGS, CHARSET);
                    } else {
                        $link = "https://opmantek.com";
                    }
                    ?>
                    <li>
                        <a target='_blank' title="NMIS" href="<?php echo $link; ?>"><img style="width:22px;" src="/open-audit/images/logo-nmis.png" alt=""/></a>
                    </li>

                    <li>
                        <a target='_blank' title="Enterprise" href="/omk/open-audit/"><img style="width:22px;" src="/open-audit/images/oae_sml.png" alt=""/></a>
                    </li>

                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars($this->user->full_name, REPLACE_FLAGS, CHARSET); ?> <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $this->config->config['oa_web_index']; ?>/logon/logoff" role="button">Logout</a></li>
                            <li><a href="#" role="button" class="debug">Debug</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav><!-- /.navbar-collapse -->
</div>

<?php include('include_modal.php'); ?>

<br />