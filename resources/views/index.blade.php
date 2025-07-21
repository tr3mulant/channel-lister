@dd($platform_json);

ob_start();
?>
<style>
    div#loading {
        position: absolute;
        height: 100vh;
        width: 100vw;
        background: white;
        z-index: 15;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 5em;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
<script>
    document.getElementsByTagName("body")[0]
        .insertAdjacentHTML("afterbegin", '<div id="loading"><img src="images/load_large.gif"></div>');
</script>
<link rel="stylesheet" type="text/css" href="master_lister.css">
<div id="form">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs nav-justified">
        <li id="licommon" class="active"><a href="#common" data-toggle="tab">Common</a></li>
        <li id="dropdown" role="presentation" class="dropdown">
            <a class="dropdown-toggle glyphicon glyphicon-plus" data-toggle="dropdown" href="#" role="button"
                aria-haspopup="true" aria-expanded="false"></a>
            <ul id="dropdownadd" class="dropdown-menu">
            </ul>
        </li>
    </ul>

    <!-- Tab panes -->
    <form name="user_input" autocomplete="off" id="user_input" action="api/ChannelLister/submitProductData"
        accept-charset="UTF-8" method="post" target="_blank">
        <div id="pantab" class="tab-content">
            <div class="tab-pane active platform-container" id="common">
                <?php
                echo $this->npiFillHtml();
                echo $this->draftFillTable();
                echo $this->writeTabContents('common');
                ?>
            </div>
        </div>
        <div class="form-group row" id="buttons_div"
            style="display: flex; align-items: center; justify-content: center;">
            <div class="col-sm-3">
                <input class="btn btn-primary" id='submit_button' value="Submit" type="submit">
            </div>
            <div class="col-sm-3">
                <div class="row">
                    <div class="col">
                        <input class="form-check-input" type="checkbox" name="gs1" id="gs1-id">
                        <label class="form-check-label">Generate GS1 Csv</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <input class="form-check-input" type="checkbox" name="auto_upload" id="auto_upload-id">
                        <label class="form-check-label">Automatic File Upload to ChannelAdvisor</label>
                    </div>
                </div>
            </div>
            <!--<input class="btn btn-default" name="Clear" type="button" value="Clear Code" onClick="clearCode()">-->
        </div>
        <div id="databaseResponse"></div>
    </form>
    <div style="display: none;" id="easter_egg"></div>
</div>
<?php

// do I need to add the html from CAMLUpdateTableHtml in listing control here into this file? 
echo $this->CAMLUpdateTableHtml();

?>
<script type="text/javascript">
    var platforms = <?= $platform_json ?>
</script>
<?php
return ob_get_clean();