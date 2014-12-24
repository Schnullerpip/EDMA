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
    var ajax, getFormData, setProgress;

    ajax = function (data) {
        var xmlhttp = new XMLHttpRequest(), uploaded;

        xmlhttp.addEventListener('readystatechange', function () {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    uploaded = JSON.parse(this.response);

                    if (typeof o.options.finished === 'function') {
                        o.options.finished(uploaded);
                    }
                } else {
                    if (typeof o.options.error === 'function') {
                        o.options.error(uploaded);
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
