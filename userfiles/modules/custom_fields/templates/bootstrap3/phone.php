<div class="col-md-<?php echo $settings['field_size']; ?>">
    <div class="form-group">
        <label class="control-label">
            <?php echo $data['name']; ?>
            <?php if ($settings['required']): ?>
                <span style="color: red;">*</span>
            <?php endif; ?>
        </label>
        <?php if ($data['help']): ?>
            <small class="mw-custom-field-help"><?php echo $data['help']; ?></small>
        <?php endif; ?>
        <input type="text" class="form-control" <?php if ($settings['required']): ?>required="true"<?php endif; ?> data-custom-field-id="<?php echo $data['id']; ?>" name="<?php echo $data['name']; ?>" placeholder="<?php echo $data['placeholder']; ?>"/>
    </div>
</div>