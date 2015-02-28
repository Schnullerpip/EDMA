<?php
require_once 'header.php';
?>
<link rel="stylesheet" type="text/css" href="./css/vendor/jquery.jqChart.css" />
<link rel="stylesheet" type="text/css" href="./css/vendor/jquery.jqRangeSlider.css" />
<link rel="stylesheet" type="text/css" media="screen" 
      href="http://ajax.aspnetcdn.com/ajax/jquery.ui/1.8.21/themes/smoothness/jquery-ui.css" />
<script src="./js/vendor/jqChart/jquery.jqChart.min.js" type="text/javascript"></script>
<script src="./js/vendor/jqChart/jquery.jqRangeSlider.min.js" type="text/javascript"></script>
<script src="./js/vendor/jqChart/jquery.mousewheel.js" type="text/javascript"></script>
<!--[if IE]><script lang="javascript" type="text/javascript" src="./js/vendor/jqChart/excanvas.js"></script><![endif]-->

<script>
    
    
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

    $(document).ready(function () {
        //var t1, t2, zu, ab, wab, wsl;
        var skala = {
            "Trocknungslauf kont. Förderung - Temperaturen Trichter 5": '1', 
            "Trocknungslauf kont. Förderung - Temperaturen Abluft": '2',
            "Trocknungslauf kont. Förderung - Abluft Temperatur bei Geschwindigkeitsmessung": '2',
            "Trocknungslauf kont. Förderung UTF-8 - Temperaturen Trichter 5": '1',
            "Trocknungslauf kont. Förderung UTF-8 - Temperaturen Abluft": '2',
            "Trocknungslauf kont. Förderung UTF-8 - Abluft Temperatur bei Geschwindigkeitsmessung": '3'
        };
        
        var dataaa = {
                from: 5000,
                to: 109712,
                step: 100,
                //pair: [[40,11],[41,11],[40,12],[41,12]]
                pair: [[25, 3], [30, 3], [26, 3], [25, 7], [30, 7], [26, 7]]
            };
        
//        console.log("hole die csv datei");
        var seriesData = [];
        $.ajax({
            url: "./chartData.php",
            dataType: 'text',
            data: dataaa,
            async: false,
            cache: false
        }).done(function (csvAsString) {
            var csvAsObj = parseCSV(csvAsString, dataaa);
            var i;
            for (i = 0; i < csvAsObj.serien.length; ++i) {
                // suche achse in sensors array
                seriesData.push( {
                    title: csvAsObj.serien[i],
                    markers: null,
                    data: csvAsObj.werte[i],
                    axisY: skala[csvAsObj.serien[i]],
                    type: 'line',
                });
            }

            console.log("fertig");
        });

        console.log("jq ab gehts");
        
        $('#selector').jqChart({
            title: {
                text: 'Trocknungslauf vom 25.02.2015',
                fillStyle: '#FFFFFF'
            },
            background: '#36a7eb',
            chartAreaBackground: '#FFFFFF',
            border: {
                visible: false
            },
            legend: {
                location: 'bottom',
                textFillStyle: '#FFFFFF',
                border: {
                    visible: false
                },
                margin: 10,
            },
            axes: [
                {
                    name: '1',
                    location: 'left',
                    strokeStyle: '#FFFFFF',
                    majorGridLines: {
                        visible: false,
                    },
                    majorTickMarks: {
                        strokeStyle: '#FFFFFF',  
                    },
                    title: {
                        text: 'Temperatur in °C',
                        fillStyle: '#FFFFFF'
                    },
                    labels: {
                        stringFormat: '%d °C',
                        fillStyle: '#FFFFFF'
                    },
                    zoomEnabled: true
                },
                {
                    name: '2',
                    location: 'right',
                    strokeStyle: '#FFFFFF',
                    majorGridLines: {
                        visible: false
                    },
                    majorTickMarks: {
                        strokeStyle: '#FFFFFF',  
                    },
                    title: {
                        text: 'Taupunkt Zuluft in °C',
                        fillStyle: '#FFFFFF'
                    },
                    labels: {
                        stringFormat: '%d °C',
                        fillStyle: '#FFFFFF'
                    },
                },
                {
                    name: '3',
                    location: 'left',
                    strokeStyle: '#FFFFFF',
                    majorGridLines: {
                        visible: false
                    },
                    majorTickMarks: {
                        strokeStyle: '#FFFFFF',  
                    },
                    title: {
                        text: 'Wärme in °C',
                        fillStyle: '#FFFFFF'
                    },
                    labels: {
                        stringFormat: '%.2f °C',
                        fillStyle: '#FFFFFF'
                    },
                },
//                {
//                    name: '4',
//                    location: 'right',
//                    strokeStyle: '#FFFFFF',
//                    majorGridLines: {
//                        visible: false
//                    },
//                    majorTickMarks: {
//                        strokeStyle: '#FFFFFF',  
//                    },
//                    title: {
//                        text: 'Taupunkt Abluft in °C',
//                        fillStyle: '#FFFFFF'
//                    },
//                    labels: {
//                        stringFormat: '%.1f °C',
//                        fillStyle: '#FFFFFF'
//                    },
//                },
                {
                    name: 'x',
                    location: 'bottom',
                    zoomEnabled: true,
                    strokeStyle: '#FFFFFF',
                    labels: {
                        fillStyle: '#FFFFFF'
                    },
                    majorTickMarks: {
                        strokeStyle: '#FFFFFF',  
                    },
                }
            ],
            series: seriesData,
//                    [
//                {
//                    title: 'Trichter 1',
//                    type: 'line',
//                    axisY: '1',
//                    data: t1.slice(0,maxVal),
//                    markers: null,
//                },
//                {
//                    title: 'Trichter 2',
//                    type: 'line',
//                    axisY: '1',
//                    data: t2.slice(0,maxVal),
//                    markers: null
//                },
//                {
//                    title: 'TP Zuluft',
//                    type: 'line',
//                    axisY: '2',
//                    data: zu.slice(0,maxVal),
//                    markers: null
//                },
//                {
//                    title: 'TP Abluft',
//                    type: 'line',
//                    axisY: '4',
//                    data: ab.slice(0,maxVal),
//                    markers: null
//                },
//                {
//                    title: 'Waerme Abluft',
//                    type: 'line',
//                    axisY: '3',
//                    data: wab.slice(0,maxVal),
//                    markers: null
//                },
//                {
//                    title: 'Waerme spez. Luftmenge',
//                    type: 'line',
//                    axisY: '3',
//                    data: wsl.slice(0,maxVal),
//                    markers: null
//                },
//            ],
            tooltips: {
                type: 'shared'
            },
        });

        $('#selector').bind('tooltipFormat', function (e, data) {
            var result = "<b>Zeitpunkt: ";
            if (data.constructor === Array) {
                result += data[0].x + "</b><br>\n" +
                        "<table id='tooltipTable'>\n" +
                        "<tr><th>Serie</th><th>Wert</th><th>Datum</th><th>Uhrzeit</th></tr>\n";

                var i;
                for (i = 0; i < data.length; ++i) {
                    result += buildRowForSeriespoint(data[i]);
                }

                result += "</table>";
            } else {
                console.log("kein Array");
                result += data.x + "</b><br>\n";
                result += "<table id='tooltipTable'>\n" +
                        "<tr><th>Serie</th><th>Wert</th><th>Datum</th><th>Uhrzeit</th></tr>\n";
                result += buildRowForSeriespoint(data);
            }
            return result;
        });

//        $('#exportImage').click(function () {
//            $("#selector").find("canvas").each(function(index) {
//                console.log($(this)[0]);
//                console.log($(this)[0].toDataURL());
//                
//                var image = $(this)[0].toDataURL("image/png").replace("image/png", "image/octet-stream");  // here is the most important part because if you dont replace you will get a DOM 18 exception.
//                window.location.href=image; // it will save locally
//                return false;
//            });
//            return;
//            var config = {
//                fileName: 'Chart.png',
//                type: 'image/png' // 'image/png' or 'image/jpeg'
//            };
//
//            $('#selector').jqChart('exportToImage');
//        });
        
        $('#saveImg').click(function() {
            var image;
            $("#selector").find("canvas").each(function(index) {
                
                image = $(this)[0].toDataURL("image/png");//.replace("image/png", "image/octet-stream");  // here is the most important part because if you dont replace you will get a DOM 18 exception.
                return false;
            });
            $(this).attr("href", image);
        });

    });

    function buildRowForSeriespoint(point) {
        var result = "<tr>";
        var series = point.series;
        result += "<td><span style='color:" + series.fillStyle + "'>" + series.title + "</span>:</td>";
        result += "<td><b>" + point.y + "</b></td>";
        result += "<td>" + point.dataItem[2] + "</td>";
        result += "<td>" + point.dataItem[3] + "</td>\n";

        return result;
    };
</script>

<div id="selector" style="width: 100%; height: 800px;"></div>
<a id="saveImg" class="btn btn-default" href="#" download="Chart.png">Speichern als Bild</a>
<a id="saveCSV" class="btn btn-default" href="../datagross.csv" download="Daten.csv">Speichern als CSV</a>

<?php
require_once 'footer.php';
?>