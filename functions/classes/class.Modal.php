<?php

/**
 *
 * Bootstrap modal class
 *
 * @author: Miha Petkovsek (miha.petkovsek@gmail.com)
 *
 */

class Modal {

    /**
     *  @modal print ---------------------
     */

    /**
     * Reload count on popup success
     * @var int
     */
    private $reload_count = 2;

    /**
     * Modal id
     * @var string
     */
    public $modal_id = "#modal1";

    /**
     * Print modal item.
     *
     * @access public
     * @param mixed $header
     * @param mixed $content
     * @param string $footer_text (default: "Save")
     * @param mixed $action_script
     * @param mixed $header_class (default: "info")
     * @return void
     */
    public function modal_print ($header, $content, $footer_text = "Save", $action_script = "", $reload = false, $header_class = "info") {
        // set html
        $html[] = $this->modal_header ($header, $header_class);
        $html[] = $this->modal_body ($content);
        $html[] = $this->modal_footer ($footer_text, $reload, $header_class);
        $html[] = $this->modal_action ($action_script);
        // print
        print implode("\n", $html);
    }

    /**
     * Set modal header.
     *
     * @access private
     * @param mixed $header (default: null)
     * @param mixed $header_class (default: "info")
     * @return void
     */
    private function modal_header ($header = null, $header_class = "info") {
        // define
        $html = array();
        // null
        if(is_null($header))    { $header = "Naslov"; }
        // set html
        $html[] = "<div class='modal-status bg-".$header_class."'></div>";
        $html[] = "<div class='modal-header'>";
        $html[] = " <h2 class='modal-title' id='myModalLabel'>";
        $html[] = $header;
        $html[] = " </h2>";
        $html[] = "</div>";
        // return content
        return implode("\n", $html);
    }

    /**
     * Set modal content.
     *
     * @access private
     * @param mixed $content
     * @return void
     */
    private function modal_body ($content) {
        // define
        $html = array();
        // set html
        $html[] = "<div class='modal-body'>";
        $html[] = $content;
        $html[] = "</div>";
        // return content
        return implode("\n", $html);
    }

    /**
     * Set modal footer.
     *
     * @access private
     * @param mixed $footer_text
     * @return void
     */
    private function modal_footer ($footer_text, $reload, $header_class) {
        // reload ?
        $reload_class = $reload ? "reload-window" : "";
        // btn class
        $btn_class = strpos($footer_text, "Delete")===false ? "success" : "danger";
        $btn_class = $header_class;
        // define
        $html = array();
        // set html
        $html[] = "<div class='modal-footer'>";
        $html[] = " <div class='btn-group'>";
        if($this->modal_id=="#modal2")
        $html[] = "     <button type='button' class='btn btn-sm btn-default btn-outline-secondary $reload_class' onclick='$(\"$this->modal_id\").modal(\"hide\");$(\"#modal1\").modal(\"show\");' >"._("Close window")."</button>";
        else
        $html[] = "     <button type='button' class='btn btn-sm btn-default btn-outline-secondary $reload_class' onclick='$(\"$this->modal_id\").modal(\"hide\");' >"._("Close window")."</button>";
        if (strlen($footer_text)>0)
        $html[] = "     <button type='button' class='btn btn-sm btn-outline-$btn_class modal-execute'>$footer_text</button>";
        $html[] = " </div>";
        $html[] = "<br>";
        $html[] = " <div class='modal-result text-left' style='margin-top:10px;width:100%'></div>";
        $html[] = "</div>";
        // return content
        return implode("\n", $html);
    }

    /**
     * Set modal JS action.
     *
     * @access private
     * @param mixed $action_script
     * @return void
     */
    private function modal_action ($action_script = "") {
        // define
        $html = array();
        // set JS for save
        if (strlen($action_script)>0) {
            $html[] = "<script type='text/javascript'>";
            $html[] = "$(document).ready(function() {";

            // show reload function
            $html[] = "var cnt2 = ".($this->reload_count+1);
            $html[] = "var tmout = ''";
            $html[] = "function show_reset () {";
            $html[] = " $('#show_reset').fadeIn()";
            $html[] = " cnt2 = cnt2-1;";
            $html[] = " $('#show_reset ul li#b_'+cnt2).removeClass('disabled').addClass('active')";
            $html[] = " if(cnt2 > 0) { ";
            $html[] = "     tmout = setTimeout(function() {show_reset()},1000)";
            $html[] = " } else { ";
            $html[] = "     window.location.reload();";
            $html[] = " }";
            $html[] = "}";

            // nothing happens on click
            $html[] = "$('ul.pagination li a.disabled').click(function() {";
            $html[] = "    return false;";
            $html[] = "})";

            // cancel reload function
            $html[] = "$('#cancel_reload').click(function () {";
            $html[] = " $('#show_reset').fadeOut('fast')";
            $html[] = " clearTimeout(tmout)";
            $html[] = " return false;";
            $html[] = "})";

            // cancel reload
            $html[] = "$('button[data-dismiss=\"modal\"]').click(function() {";
            $html[] = "    clearTimeout(tmout)";
            $html[] = "})";

    		$html[] = "$('.modal-execute').click(function () {";
    		$html[] = " $('.loading').fadeIn('fast')";
    		$html[] = "	var postdata = $('#modal-form').serialize();";
    		$html[] = "	$.post('$action_script', postdata, function(data) {";
    		$html[] = "		$('.modal-result').html(data).fadeIn('fast');";
            $html[] = "     $('.modal-content').animate({ scrollTop: $(document).height() }, 500);";
    		$html[] = "     if(data.search('alert-danger')===-1 && data.search('alert-warning')===-1) {";
            // $html[] = "         $('.loading').fadeOut('fast')";
            $html[] = "         $('.modal-execute').hide()";
            $html[] = "         show_reset ()";
    		$html[] = "     } else {";
    		$html[] = "         $('.loading').fadeOut('fast')";
    		$html[] = "     } ";
    		$html[] = "	}).fail(function(xhr) {";
            $html[] = "     $('.modal-result').html('<div class=\"alert alert-danger\">There was an error loading resource: http ' + xhr.status + ' ' + xhr.statusText + '</div>').fadeIn('fast');";
            $html[] = " });";
    		$html[] = "	return false;";
    		$html[] = "});";

            // format
            $html[] = "    function formatDesign(item) {";
            $html[] = "     return item.text + '</br>';";
            $html[] = "    };";

    		$html[] = "})";
    		$html[] = "</script>";
		}
        // return content
        return implode("\n", $html);
    }
}