$(document).ready(function () {
    $('[data-toggle="popover"]').popover();
    
    clickcounter = 0;
    $('#collapseMessreihenLabel').click(function() {
        clickcounter++;
        if (clickcounter == 2) {
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
                        o.options.error(uploaded.failed = "Prozessor Skript ungültig.");
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
        data.append('projektID', o.options.projektID);
        
        if (o.options.projektID !== 'undefined') {
            data.append('projektid', o.options.projektID);
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

function convertArray (possibleArray) {
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
                    werte[j].push([x, parseFloat(cols[j]), "25.02.2015", "11:10:00.12345"]);
                }
            }
            x += data.step;
        }
        
        return {
            serien: serien,
            werte: werte
        };
    };
    
    function buildRowForSeriespoint(point) {
        var result = "<tr>";
        var series = point.series;
        result += "<td><span style='color:" + series.fillStyle + "'>" + series.title + "</span>:</td>";
        result += "<td><b>" + point.y + "</b></td>";
        result += "<td>" + point.dataItem[2] + "</td>";
        result += "<td>" + point.dataItem[3] + "</td>\n";

        return result;
    };