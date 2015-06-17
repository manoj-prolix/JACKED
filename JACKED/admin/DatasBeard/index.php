<?php
    $section_dir = substr(__FILE__, 0, strrpos(__FILE__, '/'));
    $admin_dir = substr($section_dir, 0, strrpos($section_dir, '/'));

    try{
        include($section_dir . '/header.php');
    }catch(Exception $e){}
    
    try{
        if(isset($_REQUEST['manage_section'])){
            include($section_dir . '/' . $_REQUEST['manage_section'] . '.php');
        }else if(isset($_REQUEST['manage_handler'])){
            include($section_dir . '/' . $_REQUEST['manage_handler'] . '.php');
        }else{
            include($section_dir . '/menu.php');
        }
    }catch(Exception $e){
        echo '<div class="alert alert-error alert-block">
                  <a href="#" class="close" data-dismiss="alert">&times;</a>
                  <p><strong>Error: </strong>"' . $e->getMessage() .  '" <em>(' . $e->getFile() . ':' . $e->getLine() . ')</em>:</p>
                  <p><pre><code>' . $e->getTraceAsString() . '</code></pre></p>
        </div>';
        include($admin_dir . '/404.php');
    }
?>