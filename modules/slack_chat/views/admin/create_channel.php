<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('Create Channel'); ?></h4>
                        <hr />
                        <?php echo form_open(admin_url('slack_chat/create_channel')); ?>
                        <div class="form-group">
                            <label for="name"><?php echo _l('Channel Name'); ?></label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="description"><?php echo _l('Description'); ?></label>
                            <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" id="is_private" name="is_private" value="1">
                                <label for="is_private"><?php echo _l('Private Channel'); ?></label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success"><?php echo _l('Create'); ?></button>
                        <a href="<?php echo admin_url('slack_chat/chat'); ?>" class="btn btn-default"><?php echo _l('Cancel'); ?></a>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
