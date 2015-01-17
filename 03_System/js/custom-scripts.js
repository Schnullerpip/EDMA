$(document).ready(function () {
    $(function () {
        $('[data-toggle="popover"]').popover();
    });
    
    $('.datepicker-init').datepicker({
    });
});

// Upload-Funktionalitaet
var app = app || {};

(function (o) {
    "use strict";

    // private methods
    var ajax, getFormData, setProgress, convertArray;

    ajax = function (data) {
        var xmlhttp = new XMLHttpRequest(), uploaded;

        xmlhttp.addEventListener('readystatechange', function () {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        uploaded = JSON.parse(this.response);
                    } catch (error) {
                        // wenn response kein JASON Objekt ist, ist ein unerwarterter Fehler passiert.
                        // this. response ist plain html => convert to JSON
                        uploaded = {
                            succeeded: "",
                            failed: this.response,
                        };
                    }
                    
                    if (uploaded.failed.length != 0) {
                        if (typeof o.options.error === 'function') {
                            o.options.error(convertArray(uploaded.failed), -1);
                        }
                    } else {
                        if (typeof o.options.finished === 'function') {
                            
                            o.options.finished(convertArray(uploaded.succeeded));
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
        
        if (o.options.projektID !== 'undefined') {
            data.append('projektid', o.options.projektID);
        }

        return data;
    };

    setProgress = function (percent) {
        o.options.progress.width(percent);
    };
    
    
    convertArray = function (possibleArray, depth) {
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
