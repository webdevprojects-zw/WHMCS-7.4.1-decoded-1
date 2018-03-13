var SMALL_SCREEN_SIZE = 825;

function searchclose() {
    $("#searchresults").slideUp();
}

function sidebarOpen() {
    $("#sidebaropen").fadeOut();
    $("#contentarea").animate({"margin-left":"209px"},1000,function() {
        $("#sidebar").fadeIn("slow");
    });
    $.post("search.php","a=maxsidebar");
}
function sidebarClose() {
    $("#sidebar").fadeOut("slow",function(){
        $("#contentarea").animate({"margin-left":"10px"});
        $("#sidebaropen").fadeIn();
    });
    $.post("search.php","a=minsidebar");
}
function notesclose(save) {
    $('#myNotes').modal('hide');
    if (save) {
        $.post(
            WHMCS.adminUtils.getAdminRouteUrl('/profile/notes'),
            $("#frmmynotes").serialize()
        );
    }
}

$(document).ready(function(){
    $(window).on("load resize", function() {
        if ($("#sidebar").is(':visible') && $(window).width() <= SMALL_SCREEN_SIZE) {
            $("#sidebar").hide();
            $("#contentarea").css('margin-left', '10px');
            $("#sidebaropen").show();
        }
    });
    $("#frmintellisearch").submit(function(e) {
        e.preventDefault();
        $("#intellisearchval").css("background-image","url('images/loading.gif')");
        $.post("search.php", $("#frmintellisearch").serialize(),
        function(data){
            $("#searchresultsscroller").html(data);
            $("#searchresults").slideDown("slow",function(){
                    $("#intellisearchval").css("background-image","url('images/icons/search.png')");
                });
        });
    });
    $(".datepick, .date-picker").datepicker({
        dateFormat: datepickerformat,
        showOn: "button",
        buttonImage: "images/showcalendar.gif",
        buttonImageOnly: true,
        showButtonPanel: true,
        showOtherMonths: true,
        selectOtherMonths: true
    });

    $('div.modal').on('shown.bs.modal', function() {
        var inputs = $(this).find('input,button.btn-primary');

        if (inputs.length > 0) {
            $(inputs).first().focus();
        }
    });

    $('#btnClientLimitNotificationDismiss').click(function(e) {
        $('#clientLimitNotification').fadeOut();
        $.post(window.location, 'clientlimitdismiss=1&name=' + $('#clientLimitNotification').find('.panel-title span').html());
    });

    $('#btnClientLimitNotificationDontShowAgain').click(function(e) {
        $('#clientLimitNotification').fadeOut();
        $.post(window.location, 'clientlimitdontshowagain=1&name=' + $('#clientLimitNotification').find('.panel-title span').html());
    });

    $('.client-limit-notification-form form').submit(function(e) {
        e.preventDefault();
        var $this = $(this);
        var $fetchUrl = $this.data('fetchUrl');
        var $submit = $this.find('button[type="submit"]');
        var $submitLabel = $submit.html();
        $submit.css('width', $submit.css('width')).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.post($fetchUrl, $this.serialize(),
            function(data) {
                $this.find('.input-license-key').val(data.license_key);
                $this.find('.input-member-data').val(data.member_data);
                $this.off('submit').submit();
                $submit.html($submitLabel).removeProp('disabled');
            }, 'json');
    });
});
