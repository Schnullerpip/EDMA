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
});
