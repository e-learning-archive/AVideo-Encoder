<div class="alert alert-info">
    <span class="glyphicon glyphicon-info-sign"></span> Share videos from Coursera.
</div>
<div id='coursera-tab' class="container-fluid">
    <div class="copy-source">
        <div class="form-group row">
            <label for="coursera-link">Course</label>
            <input type="url" class="form-control" name="coursera-link" id="coursera-link" value="<?php echo htmlentities($_SESSION['coursera-url']); ?>" required/>
            <div class="error-message"></div>
            <small class="form-text text-muted">Please provide the link to the Coursera course you want to share.</small>
        </div>
    </div>
    <button type="button" class="btn btn-primary" data-toggle='modal' data-target='#courseraModal' style="float:right">Next &raquo;</button>
    <?php require('modal.php'); ?>
</div>
<link rel="stylesheet" href="/view/e-learning-archive/css/styles.css" />