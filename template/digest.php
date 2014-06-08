<form method="post" class="ajax digest" data-action="digest">
    <?php wp_nonce_field('addfeed'); ?>

    <table class="form-table">
        <tbody><tr>
            <th scope="row"><label for="date_interval">Date interval</label></th>
            <td>
                <select name="interval" id="date_interval" class="regular-text">
                    <option value="week">Week</option>
                    <option value="custom">Custom</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="category">Category</label></th>
            <td>
                <?php wp_dropdown_categories('name=category&hide_empty=0')?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="order">Feed order</label></th>
            <td>
                <select name="feed_order" id="order" class="regular-text">
                    <option value="random">Random</option>
                    <option value="lastupdated">Last updated</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="title">Title</label></th>
            <td>
                <input name="title" type="text" id="title" value="" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="template">Template</label></th>
            <td>
                <textarea id="template" name="template"></textarea>
            </td>
        </tr>

        </tbody></table>

    <script type="text/javascript">
        var formData = <?=json_encode($settings)?>;
    </script>

    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Generate"></p>
</form>