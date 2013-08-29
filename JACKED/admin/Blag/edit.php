<?php
    try{
        if(trim($_POST['guid']) == ''){
            throw new Exception('No post id defined.');
        }
        $guid = $_POST['guid'];

        $JACKED->loadDependencies(array('Syrup', 'Curator'));
        $tags = $JACKED->Curator->getAllTags();

        $existingEdit = FALSE;
        if(isset($_POST['existingEdit']) && trim($_POST['existingEdit']) != ''){
            $existingEdit = TRUE;
            $post = array(
                'guid' => $_POST['guid'],
                'title' => isset($_POST['inputTitle'])? $_POST['inputTitle'] : '',
                'headline' => isset($_POST['inputHeadline'])? $_POST['inputHeadline'] : '',
                'content' => isset($_POST['inputContent'])? $_POST['inputContent'] : '',
                'category' => isset($_POST['inputCategory'])? $_POST['inputCategory'] : ''
            );
            $postTagsString = isset($_POST['inputTags'])? $_POST['inputTags'] : '';
        }else{
            $post = $JACKED->Syrup->Blag->findOne(array('guid' => $guid));
            if(!$post){
                throw new Exception('Post not found.');
            }

            $postTagsString = '';
            foreach($post->Curator as $tag){
                $postTagsString .= $tag->name . ',';
            }
            $postTagsString = rtrim($postTagsString, ',');
        }
    }catch(Exception $e){
        $JACKED->Sessions->write('admin.error.editpost', $e->getMessage());
    }

?>
<link href="/admin/assets/js/select2/select2.css" rel="stylesheet" />
<script type="text/javascript" src="/admin/assets/js/select2/select2.min.js"></script>
<script type="text/javascript">
    
    var editor;
    var editorStorageName = 'JACKED_Blag_edit_<?php echo $guid; ?>';
    var autoDraftExists = !(localStorage.getItem(editorStorageName) === null);

    if(autoDraftExists){
        draft = JSON.parse(localStorage.getItem(editorStorageName));
        draftDate = new Date(draft[editorStorageName]['modified']);
        if(confirm('You have an existing draft for this post that was autosaved on ' + draftDate.toLocaleString() + '. Would you like to load it or cancel the draft and load the saved post?')){

        }

    }

    var opts = {
        container: 'editoroverlay',
        textarea: 'inputContent',
        basePath: '',
        clientSideStorage: true,
        localStorageName: editorStorageName,
        file: {
            name: editorStorageName,
        },
        useNativeFullscreen: true,
        parser: marked,
        theme: {
            base: '/admin/assets/js/EpicEditor/themes/base/epiceditor.css',
            preview: '/admin/assets/js/EpicEditor/themes/preview/github.css',
            editor: '/admin/assets/js/EpicEditor/themes/editor/epic-dark.css'
        },
        button: {
            preview: true,
            fullscreen: true,
            bar: "auto"
        },
        focusOnLoad: false,
        shortcut: {
            modifier: 18,
            fullscreen: 70,
            preview: 80
        },
        string: {
            togglePreview: 'Toggle Preview Mode',
            toggleEdit: 'Toggle Edit Mode',
            toggleFullscreen: 'Enter Fullscreen'
        },
        autogrow: {
            minHeight: 500
        }
    }

    $(document).ready(function(){
        editor = new EpicEditor(opts);
        editor.load();

        $("#cancelButton").click(function(eo){
            var confirmCancel = confirm('Discard your changes to this post?');
            if(confirmCancel){
                window.onbeforeunload = null;
                editor.unload();
                $('#inputContent').val('');
                editor.remove(editorStorageName);
                localStorage.removeItem(editorStorageName);
            }else{
                eo.preventDefault();
                return false;
            }
        });

        $("#savepost").click(function(eo){
            window.onbeforeunload = null;
            editor.remove(editorStorageName);
            localStorage.removeItem(editorStorageName);
            $("#saveType").val('live');
        });

        $("#savedraft").click(function(eo){
            window.onbeforeunload = null;
            editor.remove(editorStorageName);
            localStorage.removeItem(editorStorageName);
            $("#saveType").val('draft'); 
        });

        $('#inputTags').select2({
            tags: [<?php
                    if($tags){
                        foreach($tags as $tag){
                            echo "'" . addslashes($tag['name']) . "', ";
                        }
                    }
                    ?>],
            tokenSeparators: [","]
        });
    });

    var confirmOnPageExit = function(winev){
        winev = winev || window.event;

        var message = 'You haven\'t saved your post yet. Do you want to leave without saving?';

        // For IE6-8 and Firefox prior to version 4
        if(winev){
            winev.returnValue = message;
        }

        // For Chrome, Safari, IE8+ and Opera 12+
        return message;
    };
    window.onbeforeunload = confirmOnPageExit;

</script>

<h2>Edit Post</h2>

<?php
    if($JACKED->Sessions->check('admin.error.editpost')){
        echo '<div class="alert alert-error alert-block">
                  <a href="#" class="close" data-dismiss="alert">&times;</a>
                  <p><strong>Error: </strong>"' . $JACKED->Sessions->read('admin.error.editpost') .  '" </p>
        </div>';
        $JACKED->Sessions->delete('admin.error.editpost');
    }
?>

<form class="form-horizontal" method="POST" action="/admin/module/Blag">
    <input type="hidden" name="manage_handler" value="post-edit-handler" />
    <input type="hidden" name="editAction" value="save" />
    <input type="hidden" name="existingEdit" value="true" />
    <input type="hidden" name="guid" value="<?php echo $guid; ?>" />
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="inputTitle">Title</label>
            <div class="controls">
                <input type="text" class="input-xxlarge" name="inputTitle" id="inputTitle" value="<?php echo ($existingEdit? $post['title'] : $post->title); ?>">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="inputHeadline">Headline/Preview Text</label>
            <div class="controls">
                <textarea rows="6" class="input-xxlarge" name="inputHeadline" id="inputHeadline"><?php echo ($existingEdit? $post['headline'] : $post->headline); ?></textarea>
            </div>
        </div>
        
        <div class="control-group">
            <label class="control-label" for="inputContent">Content</label>
            <div class="controls">
                <textarea rows="6" class="input-xxlarge" style="display:none;" name="inputContent" id="inputContent"><?php echo ($existingEdit? $post['content'] : $post->content); ?></textarea>
                <div id="editoroverlay"></div>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="inputCategory">Category</label>
            <div class="controls">
                <select name="inputCategory" id="inputCategory">
                    <?php
                        $cats = $JACKED->Syrup->BlagCategory->find();
                        foreach($cats as $cat){
                            if((!$existingEdit && $cat->guid == $post->category->guid) || ($existingEdit && $cat->guid == $post['category'])){
                                echo '<option selected value="' . $cat->guid . '">' . $cat->name . "</option>\n";
                            }else{
                                echo '<option value="' . $cat->guid . '">' . $cat->name . "</option>\n";
                            }
                        }
                    ?>
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="inputTags">Tags</label>
            <div class="controls">
                <input type="text" id="inputTags" name="inputTags" class="input-xxlarge" value="<?php echo $postTagsString; ?>" />
            </div>
        </div>

        <div class="form-actions pull-right span9">
            <input type="hidden" id="saveType" name="saveType" />
            <button id="savepost" type="submit" class="btn btn-success pull-right" style="margin-left:10px">Save and Post</button>
            <button id="savedraft" type="submit" class="btn btn-warning pull-right" style="margin-left:10px">Save as Draft</button>
            <a id="cancelButton" class="pull-right btn btn-danger" href="/admin/module/Blag">Cancel</a>
        </div>
    </fieldset>
</form>