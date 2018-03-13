$(document).ready(function(){
     $("#shownotes").click(function () {
        $("#mynotes").toggle("slow");
        return false;
    });
    $("#savenotes").click(function () {
        $("#mynotes").toggle("slow");
        $.post(
            "index.php?rp=" + adminBaseRoutePath + '/profile/notes',
            $("#frmmynotes").serialize()
        );
    });
    $("#frmintellisearch").submit(function(e) {
        e.preventDefault();
        $.post("search.php", $("#frmintellisearch").serialize(),
        function(data){
            if (data) {
                $("#searchresults").html(data);
                $("#btnIntelliSearch").hide();
                $("#btnIntelliSearchCancel").removeClass('hidden').show();
                $("#searchresults").hide().removeClass('hidden').slideDown();
            }
        });
    });
    $(".datepick, .date-picker").datepicker({
        dateFormat: datepickerformat,
        showOn: "button",
        buttonImage: "images/showcalendar.gif",
        buttonImageOnly: true,
        showButtonPanel: true
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
function intellisearchcancel() {
    $("#intellisearchval").val("");
    $("#btnIntelliSearchCancel").hide();
    $("#btnIntelliSearch").show();
    $("#searchresults").slideUp();
}
