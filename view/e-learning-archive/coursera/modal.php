<?php
// This is how they used to make interfaces last century
?>
<div class="modal multi-step fade" id="courseraModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">Share from Coursera</h4>
            </div>
            <div class="modal-body">
                <form id='modal-form' action="index.php" method="post">
                    <div class="modal-split">
                        <?php require(__DIR__ . "/modal/step-1.php"); ?>
                    </div>
                    <div class="modal-split">
                        <?php require(__DIR__ . "/modal/step-2.php"); ?>
                    </div>
                    <div class="modal-split">
                        <?php require(__DIR__ . "/modal/step-3.php"); ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
            </div>
        </div>
    </div>
</div>
