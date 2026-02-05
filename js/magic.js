$(document).ready(function () {

/* loading spinner functions */
function showSpinner() { $('div.loading').css("display","flex") }
function hideSpinner() { $('div.loading').fadeOut('fast'); }

/**
 * Show / hide checkboxes
 */
$('.toggle-show-multiple').click(function() {
	$('.checkbox-hidden').toggleClass('visually-hidden');
	return false;
})

/**
 * Select all checkboxes
 */
$('input.select-all').change(function() {
    if(this.checked) { $('.select-current').prop( "checked", true ); }
    else             { $('.select-current').prop( "checked", false ); }
 })

/**
 * Load modal window
 */
$(document).on("click", '[data-bs-toggle=modal]', function() {
    // index
    var index = $(this).attr('data-bs-target')
    // default modeal1
    if(index===undefined) { index = "#modal1"; }
    // set default
    modal_html =  '<div class="modal-status"></div>'
    modal_html += '<div class="modal-header">Loading</div>'
    modal_html += '<div class="modal-body text-center">'
    modal_html += '     <div class="text-secondary mb-3">Please wait, loading content...</div>'
    modal_html += '     <div class="progress progress-sm" style="height:.25rem">'
    modal_html += '         <div class="progress-bar progress-bar-indeterminate"></div>'
    modal_html += '     </div>'
    modal_html += '</div>'
    modal_html += '<div class="modal-footer"><button type="button" class="btn btn-sm btn-default btn-outline-secondary" onclick="$(&quot;#modal1&quot;).modal(&quot;hide&quot;);">Close window</button></div>';

    // set default content
    $(index+' .modal-content').html(modal_html);
    // load
    $(index+' .modal-content').load($(this).attr('href'), function(response, status, xhr) {
        if ( status == "error" ) {
            $(index+' .modal-status').addClass("bg-danger")
            $(index+' .modal-header').html( "Error" );
            $(index+' .modal-body').html( "<div class='alert alert-danger'>"+"There was an error loading resource: " + xhr.status + " " + xhr.statusText+"</div>" );
        }
        else {
            //hideSpinner ()
        }
    });
    // show
    $(index).modal('show');
    // dont reload
    return false;
});

/**
 * Reload popup window
 */
$(document).on("click", '.reload-window', function() {
    location.reload();
    return false;
})

/**
 * Tooltips
 * @type {Array}
 */
tippy('[data-bs-toggle="tooltip"]', {
    duration: 0,
    arrow: false,
    followCursor: false,
    allowHTML: true,
    offset: [0, 10],
    content: (reference) => reference.getAttribute('title'),
})

/**
 * Ignore open_popup for now
 */
$('.open_popup').click(function () {
	return false;
})


/**
 * Expired certs link
 * @method
 * @return void
 */
$('.circle-expire').click(function () {
    $('.bootstrap-table input[type=search]').val($(this).attr('data-dst-text')).focus();
})



// login
$('form#login').submit(function() {
    showSpinner();
    var logindata = $(this).serialize();

    $('div#loginCheck').hide();
    //post to check form
    $.post('/route/login/login_check.php', logindata, function(data) {
        $('div#loginCheck').html(data).fadeIn('fast');
        //reload after 2 seconds if succeeded!
        if(data.search("alert alert-success") != -1) {
            setTimeout(function (){window.location="/";}, 1000)
        }
        else {
            hideSpinner();
        }
    });
    return false;
})


// expand
$('.expand_hosts, .shrink_hosts').click(function(){
    // show
    if($(this).hasClass('expand_hosts')) {
        $(this).removeClass('expand_hosts').addClass('shrink_hosts')
        $('td.td-hosts, th.td-hosts').removeClass('visually-hidden');

        $(this).find('svg').remove()
        $(this).prepend('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-minimize"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 9l4 0l0 -4" /><path d="M3 3l6 6" /><path d="M5 15l4 0l0 4" /><path d="M3 21l6 -6" /><path d="M19 9l-4 0l0 -4" /><path d="M15 9l6 -6" /><path d="M19 15l-4 0l0 4" /><path d="M15 15l6 6" /></svg>');

        $(this).html($(this).html().replace("Expand", "Shrink"))
        createCookie("show_hosts","1",30)
    }
    // hide
    else {
        $(this).removeClass('shrink_hosts').addClass('expand_hosts')
        $('td.td-hosts, th.td-hosts').addClass('visually-hidden');

        $(this).find('svg').remove()
        $(this).prepend('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-maximize"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M16 4l4 0l0 4" /><path d="M14 10l6 -6" /><path d="M8 20l-4 0l0 -4" /><path d="M4 20l6 -6" /><path d="M16 20l4 0l0 -4" /><path d="M14 14l6 6" /><path d="M8 4l-4 0l0 4" /><path d="M4 4l6 6" /></svg>');

        $(this).html($(this).html().replace("Shrink", "Expand"))
        createCookie("show_hosts","0",30)
    }
    // dont reload
    return false;
})


// Mark all as read
$('a#read-all').click(function() {
    $.get('/route/modals/logs/read-all.php?id='+$(this).attr('data-id'), function(data) {
    })
    .done(function(data) {
        $('#dropdown_new_log_indicator').remove();
        $('#dropdown_new_logs .badge-blink').removeClass('badge-blink').removeClass('bg-red');
        $('.read-error').html("<div class='alert alert-danger' style='margin-top:10px;'>"+data+"</div>");
        $('a#read-all').addClass('disabled')
        $('.read-error').html("<div class='alert alert-success' style='margin-top:10px;'>"+data+"</div>").fadeIn('fast').delay(1000).fadeOut('fast', function() { $('#dropdown').removeClass('show') });
    })
    .fail(function(data) {
        $('.read-error').html("<div class='alert alert-danger' style='margin-top:10px;'>"+data+"</div>").fadeIn('fast').delay(3000).fadeOut('fast');
    })
    return false;
});



/* @cookies */
function createCookie(name,value,days) {
    var date;
    var expires;

    if (typeof days !== 'undefined') {
        date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires="+date.toGMTString();
    }
    else {
        var expires = "";
    }

    document.cookie = name+"="+value+expires+"; path=/";
}


// end jQuery
})





