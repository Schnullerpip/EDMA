$(function () {
    // Messreihe löschen
    $('.delete').click(function (e) {
        e.preventDefault();
        var messreihenid = $(this).data('messreihenid');

        $.ajax({
            type: "POST",
            url: "ajaxHandler.php",
            data: {function: "delete", element: "messreihe", id: messreihenid, ajax: true}
        }).done(function (msg) {
            msg = JSON.parse(msg);
            if (msg.failed.length === 0) {
                // Erfolg:
                $(e.target).closest('tr').fadeOut("slow");
            } else {
                // Fehler:
                $('#infoModal').modal();
                modalTextError("<strong>" + msg.failed[0].name + "</strong>: " + msg.failed[0].error);
            }
        });
    });
});
