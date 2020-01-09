<div class="alert alert-info">
    <span class="glyphicon glyphicon-info-sign"></span> Share videos from edX.
</div>
<div id='edx-tab' class="container-fluid">
    <div class="copy-source">
        <div class="form-group row">
            <label for="edx-username">Username</label>
            <input type="url" class="form-control" name="edx-username" id="edx-username" value="<?php echo htmlentities($_SESSION['edx-username']); ?>" required />
            <div class="error-message"></div>
            <small class="form-text text-muted">Please fill in your username for edx.org</small>
        </div>
        <div class="form-group row">
            <label for="edx-password">Password</label>
            <input type="password" class="form-control" name="edx-password" id="edx-password" value="<?php echo htmlentities($_SESSION['edx-password']); ?>" required />
            <div class="error-message"></div>
            <small class="form-text text-muted">Please fill in your username for edx.org</small>
        </div>
    </div>
    <button type="button" class="btn btn-primary" data-toggle='modal' data-target='#edxModal' style="float:right">Next &raquo;</button>
    <?php require('modal.php'); ?>
</div>
<script src="/view/e-learning-archive/coursera/js/coursera.js"></script>