<div class="form-group">
    <div class="logical-form-group">
    <div class="copy-target"><!-- This will contain the course URL --></div>
    </div>

    <label for="cauth">Authentication code</label>
    <div class="logical-form-group">
    <input class="form-control" id="cauth" name="cauth" required value="<?php echo htmlentities($_SESSION['coursera-cauth']); ?>" type="text"/>
    <div class="error-message"></div>
    </div>
    <small class="form-text text-muted">
        The easiest way to get this authentication code is by
        <a id="open-browser-extension-tooltip"
           data-tooltip-content='#courseraModal .tooltip'
           data-tooltip-title="Authentication code helper"
           href="#"
        >installing a browser extension</a>.
        Then, click on the
        <img src="/view/e-learning-archive/assets/extension.png"
             width="19" height="19"
             onclick="alert('Don\'t click on this one, but on the one on top of your window (next to the browser\'s website address bar).')"
        />
        icon in your browser, and follow directions there. Once you have copied the
        authentication code, paste it in the text field above (by clicking on the input
        field and pressing Ctrl+V or Cmd+V).
    </small>
    <div class="tooltip hidden">
        <a class="btn btn-default btn-sm browser browser-chrome"
           href="https://chrome.google.com/webstore/detail/fkbgecmligfnknoclmgaebbcoodcacnf?hl=en"
           target="_blank">
            <img src="/view/e-learning-archive/assets/chrome.svg" alt="chrome logo" width="25"
                 height="25"/>
            Install Chrome extension
        </a>
        <a class="btn btn-default btn-sm browser browser-firefox"
           href="https://addons.mozilla.org/firefox/downloads/file/3469645/coursera_authentication_helper-1.0-fx.xpi?src=dp-btn-primary"
           target="_blank">
            <img src="/view/e-learning-archive/assets/firefox.svg" alt="firefox logo" width="25"
                 height="25"/>
            Install Firefox extension
        </a>
        <small class="browser browser-other">Unfortunately the browser extension is currently
            only available for Chrome and Firefox.</small>
    </div>

</div>

