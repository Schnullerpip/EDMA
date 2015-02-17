$(function () {
    // Dynatable
    $('#messreihen-tabelle')
            .bind('dynatable:init', function (e, dynatable) {
                dynatable.queries.functions['suche-messreihen-name'] = function (record, queryValue) {
                    return record.name.score(queryValue) > 0;
                };
                dynatable.queries.functions['suche-messreihen-datum'] = function (record, queryValue) {
                    console.log("record: " + record.datum);
                    console.log(queryValue);
                    return (record.datum == queryValue) ? 1 : 0;
                };
            })
            .dynatable({
                features: {
                    paginate: false,
                    search: false,
                    pushState: false,
                    recordCount: false,
                    perPageSelect: false,
                    sort: false
                },
                inputs: {
                    queries: $('#suche-messreihen-name, #suche-messreihen-datum'),
                    processingText: ''
                }
            });

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
