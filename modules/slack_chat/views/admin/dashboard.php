<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin">Chat Module Dashboard</h4>
        <hr />
        <div class="alert alert-success">
            <?php echo _l('Module installed successfully'); ?>
        </div>
        <a href="<?php echo admin_url('slack_chat/chat'); ?>" class="btn btn-primary">
            <i class="fa fa-comments"></i> <?php echo _l('Go to Chat'); ?>
        </a>
        <!-- Add more dashboard content here -->
    </div>
</div>
