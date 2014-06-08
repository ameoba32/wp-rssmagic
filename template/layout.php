<div class="wrap">
    <div>
        <h1><?php echo $plugin->getPluginName()?> plugin</h1>

        <div id="rss-message" class="updated below-h2">
            <p>Post updated.</p>
        </div>

        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?=$page=='feed'?'nav-tab-active':''?>" href="?page=<?php echo $plugin->pageName('setup')?>&action=feedlist">Feeds</a>
            <?php if ($page=='updatenow') {?>
                <a class="nav-tab nav-tab-active" href="#">Update now</a>
            <?php } ?>
            <?php if ($page=='viewfeed') {?>
                <a class="nav-tab nav-tab-active" href="#">View feed</a>
            <?php } ?>
            <a class="nav-tab <?=$page=='settings'?'nav-tab-active':''?>" href="?page=<?php echo $plugin->pageName('setup')?>&action=settings">Settings</a>
            <a class="nav-tab <?=$page=='digest'?'nav-tab-active':''?>" href="?page=<?php echo $plugin->pageName('setup')?>&action=digest">Digest</a>
            <a class="nav-tab <?=$page=='feedback'?'nav-tab-active':''?>" href="?page=<?php echo $plugin->pageName('setup')?>&action=feedback">Feedback</a>
        </h2>

        <?=$content?>
    </div>
</div>