$(function () {
    // Messreihe löschen
    $('.delete').click(function (e) {
        e.preventDefault();
        var messreihenid = $(this).data('messreihenid');

        $.ajax({
            type: "POST",
            url: "ajaxHandler.php",
            data: {function: "delete", element: "messreihe"}
        }).done(function (msg) {
            alert("Data Saved: " + msg);
        });
    });
});
