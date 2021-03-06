$(document).ready(function () {
    // Für das Delete-modal
    var button, target, id, element;
    
    $('[data-toggle="popover"]').popover();

    clickcounter = 0;
    $('#collapseMessreihenLabel').click(function () {
        clickcounter++;
        if (clickcounter === 2) {
            clickcounter = 0;
            $(this).blur();
        }
    });

    $('.input-group.date').datepicker({
        format: "dd.mm.yyyy",
        language: "de",
        autoclose: true,
        clearBtn: true
    });

    // Resette Modal Texte nach ausblenden
    $('#infoModal').on('hidden.bs.modal', function (e) {
        $('#infoModal section').each(function () {
            $(this).find('content').empty();
            $(this).hide();
        });
    });

    // Delete Modal confirm-Funktionalität
    $('#delete-modal').on('show.bs.modal', function (event) {
        button = $(event.relatedTarget);
        id = button.data('id');
        element = button.data('element');
        target = button.data('redirect');
        
        var text = (element === 'messreihe' ? 'die Messreihe' : 'das Projekt');
        $('.type').text(text);
    });
    $('#confirm-delete').on('click', function () {
        var errorbox = $('#delete-modal .error');
        errorbox.hide();
        errorbox.siblings('p').hide();
        errorbox.siblings('.loading-div').show();
        // Element löschen
        $.ajax({
            type: "POST",
            url: "ajaxHandler.php",
            data: {function: "delete", element: element, id: id, ajax: true}
        }).done(function (msg) {
            msg = JSON.parse(msg);
            if (msg.failed.length === 0) {
                // Erfolg:
                // Modal ausblenden und weiterleiten oder sonstiges
                errorbox.siblings('.loading-div').hide();
                $('#delete-modal').modal('hide');
                $('#delete-modal').on('hidden.bs.modal', function () {
                    if (target === '') {
                        button.closest('tr').fadeOut("slow");
                    } else {
                        window.location = target + '.php';
                    } 
                });
            } else {
                // Fehler:
                // Fehler-Alert im Modal anzeigen
                $('#delete-modal section.error .content').html("<strong>" + msg.failed.name + "</strong>: " + msg.failed.message);
                errorbox.siblings('p').hide();
                errorbox.siblings('.loading-div').hide();
                errorbox.toggle();
            }
        });
    });
    $('#delete-modal').on('hidden.bs.modal', function () {
        $(this).find('p').show();
        $('#delete-modal .error').hide();
    });
    
    // Scrollbar für Messreihen + Sensoren
    $('.scrollbar-inner').scrollbar();
    
    // Dynatable
    if ($('#messreihen-tabelle' !== undefined)) {
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
                        sort: true
                    },
                    inputs: {
                        queries: $('#suche-messreihen-name, #suche-messreihen-datum'),
                        processingText: ''
                    }
                });
    }
    
    // Modal Footer Jump Bugfix
    $('.modal').on('show.bs.modal', function () {
        $('footer').css('padding-right', '28px');
    });
    $('.modal').on('hidden.bs.modal', function () {
        $('footer').css('padding-right', '');
    });
});

// Append Modal Methods
function modalTextSuccess(msg) {
    $('#infoModal').find('.modal-body .success').show().find('.content').html(msg);
}
function modalTextError(msg) {
    $('#infoModal').find('.modal-body .error').show().find('.content').html(msg);
}
function modalTextWarning(msg) {
    $('#infoModal').find('.modal-body .warning').show().find('.content').html(msg);
}

// Upload-Funktionalitaet
var app = app || {};

(function (o) {
    "use strict";

    // private methods
    var ajax, getFormData, setProgress;

    ajax = function (data) {
        var xmlhttp = new XMLHttpRequest(), uploaded;

        xmlhttp.addEventListener('readystatechange', function () {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        uploaded = JSON.parse(this.response);
                    } catch (error) {
                        // wenn response kein JSON Objekt ist, ist ein unerwarterter Fehler passiert.
                        // this. response ist plain html => convert to JSON
                        uploaded = {
                            succeeded: "",
                            warned: "",
                            failed: this.response,
                        };
                    }

                    if (uploaded.failed.length != 0) {
                        if (typeof o.options.error === 'function') {
                            o.options.error(uploaded.failed);
                        }
                    }
                    if (uploaded.warned.length != 0) {
                        if (typeof o.options.warning === 'function') {
                            o.options.warning(uploaded.warned);
                        }
                    }
                    if (uploaded.succeeded.length != 0) {
                        if (typeof o.options.finished === 'function') {

                            o.options.finished(uploaded.succeeded);
                        }
                    }
                } else {
                    if (typeof o.options.error === 'function') {
                        console.log("status:" + this.status);
                        console.log(this.repsonse);
                        o.options.error("Prozessor Skript ungültig.");
                    }
                }
            }
        });

        xmlhttp.upload.onprogress = function (e) {
            var percentComplete;

            percentComplete = Math.round((e.loaded / e.total) * 100);
            setProgress(percentComplete);
        };

        xmlhttp.open('post', o.options.processor);
        xmlhttp.send(data);
    };

    getFormData = function (source) {
        var data = new FormData(), i;

        for (i = 0; i < source.length; i++) {
            data.append('file[]', source[i]);
        }

        data.append('function', o.options.function);
        data.append('element', o.options.element.name);
        data.append('ajax', true);
        data.append('maxsize', o.options.maxsize);

        if (o.options.projektID !== 'undefined') {
            data.append('projektID', o.options.projektID);
        }

        return data;
    };

    setProgress = function (percent) {
        o.options.progress.width(percent);
    };

    o.uploader = function (options) {
        o.options = options;

        if (o.options.files !== undefined) {
            ajax(getFormData(o.options.files.files));
        }
    };
}(app));

function checkMaxsize(size, files) {
    var sizeTmp = 0;
    files = files.files;

    for (var i = 0; i < files.length; i++) {
        file = files[i];

        sizeTmp += file.size;
    }

    if (sizeTmp > size) {
        return 'Die ausgewählten Dateien sind zu groß!';
    } else if (sizeTmp === 0) {
        return 'Bitte eine Datei auswählen!';
    }
    return '';
}

function convertArray(possibleArray) {
    var result = possibleArray;
    if (Array.isArray(possibleArray) || typeof possibleArray === 'object') {
        result = "";
        for (var prop in possibleArray) {
            if (possibleArray.hasOwnProperty(prop)) {
                if (typeof possibleArray[prop] === 'object') {
                    result += convertArray(possibleArray[prop]);
                } else {
                    result += (prop + ": " + possibleArray[prop] + "<br>");
                }
            }
        }
    }
    return result;
}

function parseCSV(csvAsString, data) {
    var rows = csvAsString.split("\n");
    var i, j, x, serien = [], werte = [];
    x = data.from;
    for (i = 0; i < rows.length; ++i) {
        var cols = rows[i].split(",");
        if (cols.length === 0 || cols[0].length === 0) {
            continue;
        }
        for (j = 0; j < cols.length; ++j) {
            if (i === 0) {
                //header
                serien.push(cols[j].trim());
                werte[j] = []; //new Array(rows.length - 2);
            } else {
                if(cols[j] != ""){
                    var cell = cols[j].split(";");
                    var datum_uhrzeit = cell[1].split(" ");
                    werte[j].push([x, parseFloat(cell[0]), datum_uhrzeit[0], datum_uhrzeit[1]]);
                }
            }
        }
        if (i > 0) {
            // erst ab 2. Zeile X-Werte hochzaehlen (1 === 0 ist header)
            x += data.step;
        }
    }

    return {
        serien: serien,
        werte: werte
    };
};
    
function buildRowForSeriespoint(point, assertedX) {
    var result = "<tr>";
    var series = point.series;
    result += "<td><span style='color:" + series.fillStyle + "'>" + series.title + "</span>:</td>";
    
    var y, date, time;
    if ((point.x !== assertedX) && (assertedX != undefined)) {
        // falls x Werte ableichen muessen die Daten "korrigiert" werden
        var rightData = series.arrData[assertedX];
        y = rightData[1];
        date = rightData[2];
        time = rightData[3];
    } else {
        y = point.y;
        date = point.dataItem[2];
        time = point.dataItem[3];
    }
    result += "<td><b>" + y + "</b></td>";
    result += "<td>" + date + "</td>";
    result += "<td>" + time + "</td>\n";
    return result;
};
