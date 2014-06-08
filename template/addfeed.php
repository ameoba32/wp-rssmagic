<form method="post" class="ajax addfeed" data-action="addfeed">
    <?php wp_nonce_field('addfeed'); ?>

    <input name="fid" type="hidden" >

    <table class="form-table">
        <tbody><tr>
            <th scope="row"><label for="furl">Feed URL</label></th>
            <td>
                <input name="furl" id="furl" type="text" value="" class="regular-text">
                <p class="description">Paste feed url here</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ftitle">Title</label></th>
            <td>
                <input name="ftitle" id="ftitle" type="text" value="" class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="fdescription">Description</label></th>
            <td>
                <textarea name="fdescription" id="fdescription"></textarea>
            </td>
        </tr>

        </tbody></table>

    <script type="text/javascript">
        var formData = <?=json_encode($data)?>;
    </script>


    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p></form>