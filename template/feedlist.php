<br/>
<a href="?page=<?php echo $plugin->pageName('setup')?>&action=addfeed" class="add-new-h2">Add New</a>
<a href="?page=<?php echo $plugin->pageName('setup')?>&action=updatenow" class="add-new-h2">Update now</a>

<form method="post" action="<?php menu_page_url($plugin->pageName('setup'))?>">
<?php $list->display();?>
</form>