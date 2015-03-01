<?php
require_once 'header.php';
$db = DB::getInstance();
?>

<p>Projekt: <?php echo escape($projekt->data()->projektname); ?></p>

<?php
$db->get('messreihe', array('projekt_id', '=', $projekt->data()->id));
?>

<?php if ($db->error()) : ?>
    <p>Fehler beim holen der Messreihen!</p>
<?php else: ?>

    <?php $messreihen = $db->results(); ?>

    <?php if (!empty($messreihen)) : ?>
        <div class="panel-group accordeon" role="tablist">
            <div class="panel panel-default">
                <a class="btn btn-block panel-heading text-center collapsed" role="tab" href="#collapseMessreihen" data-toggle="collapse" aria-expanded="true" aria-controls="collapseMessreihen" id="collapseMessreihenLabel">
                    Letzte Messreihenimporte anzeigen<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                </a>
                <div id="collapseMessreihen" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="collapseMessreihenLabel" aria-expanded="true">
                    <ul class="list-group list-unstyled">
                        <?php foreach ($messreihen as $key => $messreihe) : ?>
                            <li>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <div class="list-content">
                                            <?php echo escape($messreihe->messreihenname); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-2 text-right">
                                        <div class="list-content">
                                            <?php echo escape($messreihe->datum); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-2 pull-right controls">
                                        <ul class="list-unstyled list-inline pull-right">
                                            <li>
                                                <a href="#graph">
                                                    <span class="glyphicon glyphicon-circle-arrow-right" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="messreihen.php?id=<?php echo escape($messreihe->id); ?>" title="Messreihe bearbeiten">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>



<?php
// TODO:
// 
// Name einer Messreihe als Filter?
//Vorbereitung für die Filter		
//datenbank instanz erstellen
$projektid = $projekt->data()->id;

//Select für messreihenname, metadatenname, datentyp
//TODO messreihen_id
$db->query("SELECT messreihe.messreihenname, messreihe.id, messreihe.datum, metainfo.metaname, messreihe_metainfo.metawert, datentyp.typ
					FROM messreihe INNER JOIN projekt ON messreihe.projekt_id = projekt.id
					INNER JOIN messreihe_metainfo ON messreihe.id = messreihe_metainfo.messreihe_id
					INNER JOIN metainfo ON metainfo.id = messreihe_metainfo.metainfo_id
                    INNER JOIN datentyp ON metainfo.datentyp_id = datentyp.id
                    WHERE projekt.id = $projektid");

//speichere den Select mit den Metafeldern in einer Variable
$select = $db->results();
$jsonselectmeta = json_encode($select);

//select für sensoren
$db->query("SELECT messreihe.messreihenname, messreihe.id, messreihe_sensor.anzeigename, sensor.id
					FROM messreihe INNER JOIN projekt ON messreihe.projekt_id = projekt.id
					INNER JOIN messreihe_sensor ON messreihe.id = messreihe_sensor.messreihe_id
					INNER JOIN sensor ON messreihe_sensor.sensor_id = sensor.id
                    WHERE projekt.id = $projektid");

$selectsensor = $db->results();
$jsonselectsensor = json_encode($selectsensor);
?>


<script>
    //-----------------------Variablen zur Auswahl aus dem Select----
    var select = <?php echo $jsonselectmeta; ?>;    //enthält den select
    var selectedMetafield;

    var i;

    //Durch den folgenden Code ist nun eine array verfügbar, welche ausschließlich die verschiedenen Messreihen (jede genau ein mal) mit allen metafeldern aufzeigt
    var messreihen = [];
    var messreihennamen = [];
    for (i = 0; i < select.length; i++) {
        if ($.inArray(select[i].messreihenname, messreihennamen) < 0) {
            var metanamen = [];
            messreihennamen.push(select[i].messreihenname);
            var tmp_messreihe = {messreihenname: select[i].messreihenname};
            tmp_messreihe.datum = select[i].datum;
            tmp_messreihe.metafields = [];
            tmp_messreihe.id = select[i].id;
            messreihen.push(tmp_messreihe);
            var o;
            for (o = i; o < select.length; o++) {
                var mname = select[o].metaname;
                if ((select[o].messreihenname == select[i].messreihenname) && ($.inArray(mname, metanamen) < 0)) {
                    metanamen.push(mname);
                    messreihen[messreihen.length - 1].metafields.push({metaname: select[o].metaname, typ: select[o].typ, wert: select[o].metawert});
                }
            }
            /*ONLY FOR DEBUGGING -> console.log("adding new 'messreihe' -->" + select[i].messreihenname + "<-- to array 'messreihen'");*/
        }
    }

    //redundantes Datum zu messreihen metafields hinzufügen damit datum wie metafield behandelt werden kann
    for (i = 0; i < messreihen.length; i++) {
        var o;
        var tmp_array = [];
        for (o = 0; o < select.length; o++) {
            if ((messreihen[i]["messreihenname"] == select[o]["messreihenname"]) && ($.inArray(messreihen[i], tmp_array) < 0)) {
                tmp_array.push(messreihen[i]);
                messreihen[i].metafields.push({metaname: "datum", typ: "datum", wert: select[o]["datum"]});
            }
        }
    }

    //Arbeitskopie von messreihen erstellen
    var messreihen_copy = $.extend(true, [], messreihen); //Tiefe Kopie


    //Variablen für den Sensorzugriff
    var select_sensor = <?php echo $jsonselectsensor; ?>;
    var sensors = [];
    var number_sensors = 0;
    const max_number_sensors = 6; //Es sollen höchstens 6 Sensoren ausgewählt werden dürfen, dies ist die Vergleichskonstante
    var selected_sensors = []; //speichert die bereits ausgewählten Sensoren

    //Sensnoren müssen einer Skala zugeordnet werden, entsprechende ZUweisung wurd in folgender Datenstruktur gespeichert
    var scalas = [];
    var scala_unique_id; //gibt jeder Skala eine eigene id - sollte nach erstellung einer skala inkrementiert werden -> anhand dieser ID werden auch variable Faktoren wie Graphen bzw y-Achsen Farbe und Erscheinungsbild bestimmt sodass einzelne Graphen voneinander unterschieden werden können und einer y-Achse zugewiesen werden können
    var patient_sensor = null; //diese referenz wird den sensor speichern, dem über das auswahlmodal eine skala zugewiesen werden soll



    for (i = 0; i < select_sensor.length; i++) {
        /*zunächst werden noch die elemente selected und scala beigefügt -> selected sagt aus ob der sensor gewählt wurde,
         * scala auf welcher y-achse er dann angezeugt werden soll*/
        select_sensor[i].selected = false;
        select_sensor[i].scala = null;
        for (o = 0; o < messreihen_copy.length; o++) { /*Da messreihe.id aus dem sensors select leder von der 
         *sensor id überschrieben wird, muss ich sie hier manuell zuweisen*/
            if (messreihen_copy[o].messreihenname == select_sensor[i].messreihenname) {
                select_sensor[i].messreihenid = messreihen_copy[o].id;
                break;
            }
        }
        sensors.push(select_sensor[i]);
    }

    //----------------------Variablen zum Schutz der Selectbox und dem Auswahlbutton -> button zündet nur wenn etwas legales gewählt wurde----------
    var old_value = 0;
    var selectFlag = false; //nur falls eine Option aus dem select tag gewählt wurde darf der entsprechende button getriggert werden
    var selectChangedCount = 0;
    //----------------------------------------------------------------------------------------------------------------------------------------------



    //------------------------------------Variablen, mit deren Hilfe unique-Ids erstellt werden können----------------------------------------------------------
    var uniqueId = 0; //Diese Variable sollte nach erstellen eines neuen Metafilters inkrementiert werden	
    //welches Metafeld in die Arbeitskopie messreihen_copy zurückgeführt werden muss
    var uniquei = 0; //für die <option> tags im metafilterselect "#selectBox"
    //----------------------------------------------------------------------------------------------------------------------------------------------------------


    //------------------------------------Die Strings, aus denen zuletzt der Select gebildet wird-------------
    var QUERY_SELECT = "SELECT ";
    var QUERY_FROM = " FROM";
    var QUERY_WHERE = " WHERE";
    //--------------------------------------------------------------------------------------------------------
</script>				

<h2>Metadaten filtern</h2>
<div class="form-horizontal mb-15" id="addMetaDiv">
    <!-- Anzeigefelder für die ausgewählten Metadatenfilter -->
    <div class="form-group">
        <div class="col-sm-6" id="meta_name_operator_div"></div>
        <div id="meta_value_div" class="col-sm-6"></div>
    </div>

    <!-- Select element und Bestätigungsbutton -->
    <div class="form-group">
        <div class="col-sm-2 col-sm-offset-4">
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    Metafeld ausw&auml;hlen<span class="caret"></span>
                </button>
                <ul id="selectBox" class="dropdown-menu" role="xmenu"></ul>
            </div>
        </div>

        <div class="col-sm-4">
            <button id="meta_select_button" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span>Metafilter hinzuf&uuml;gen</button>
        </div>
    </div>
</div>

<!-- Filterung der Messreihen/Sensoren -->
<h2 id="h2MessreihenWählen">Messreihen/Sensoren wählen</h2>
<div id="messreihenSensorenFilterDiv" class="row">

    <div class="col-sm-6"> <small>Messreihen</small></div>
    <div id="smallSensoren" class="col-sm-5"><small id="smallSensoren">Sensoren</small></div>
    <div id="smallSkala" class="col-sm-1" style="padding-left:0px"><small>Skala</small></div>

    <div id="messreihenDiv" class="col-xs-12 col-sm-6">
        <div class="scrollbar-inner">
            <div id="messreihenListe" class="btn-group-vertical" style="width:100%" role="group"></div>
        </div>
    </div>

    <div id="sensorsAndSkalas" class="col-xs-6">
        <div class="row scrollbar-inner">
            <div id="sensorenDiv" class="col-xs-10">
                <div id="sensorenListe" class="btn-group-vertical" style="width:100%" role="group"></div>
            </div>
            <div id="scalaDiv" class="col-xs-2">
                <div id="skalenListe" class="btn-group-vertical" role="group"></div>
            </div>
        </div>
    </div>
</div>
<br>

<!-- Weitere Einstellungen -->
<script>
    var step = 1;
    var intervall1 = 0;
    var intervall2 = 0;
</script>

<h2>Einstellungen</h2>
<div class="row form-group">
    <div class="col-sm-1 col-sm-offset-3">
        <div><label class="control-label">Schrittweite</label></div>
        <br>
        <div><label class="control-label">Intrevall</label></div>
    </div>

    <div class="col-sm-3 einstellungenInputDiv">
        <div>
            <input id="stepInput" class="col-sm-6 form-control einstellungenInput" type="text" name="IntervallInput" placeholder="z.B. 100 (er Schritte)"></input>
        </div>
        <br>
        <br>
        <div class="row">
            <div class="col-sm-6">
                <input id="intervallInput1" class="form-control einstellungenInput" type="text" name="IntervallInput" placeholder="Von"></input>
            </div>
            <div class="col-sm-6">
                <input id="intervallInput2" class="form-control einstellungenInput" type="text" name="IntervallInput" placeholder="Bis"></input>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-sm-offset-4 anzeigeButtonDiv">
        <button id="anzeigeButton"  type="button" class="btn btn-default" >Anzeigen!</button>
    </div>
</div>

<div id="jqChart-wrapper" style="width: 100%; height: 800px;" data-title="<?php echo escape($projekt->data()->projektname); ?>"></div>
<a id="saveImg" class="btn btn-default" href="#" download="Chart.png">Speichern als Bild</a>
<a id="saveCSV" class="btn btn-default" href="../datagross.csv" download="Daten.csv">Speichern als CSV</a>

<!--Skala Modal -->
<div id="scalaModal" class="modal fade" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Skalen Erstellen</h4>
            </div><!-- modal-header end-->

            <div class="modal-body form-horizontal">
                <script> //Diese Variablen speichern die Zustände der Optionalen radio/check buttons/boxes
                    var radioFloatBool = false;
                    var rightSideScala = false;
                </script>
                
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="scalaTitelInput">Titel</label>
                    <div class="col-sm-6">
                        <input id="scalaTitelInput" class="form-control scalaModalInput" type="text" name="scalaTitleInput" placeholder="z.B. Temperatur"></input>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-4 control-label" for="scalaEinheitInput">Einheit</label>
                    <div class="col-sm-6">
                        <input id="scalaEinheitInput" class="form-control scalaModalInput" type="text" name="scalaEinheitInput" placeholder="z.B. in °C"></input>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-6">
                        <fieldset>
                            <input type="radio" id="radioINT" name="Zahlengruppe" value="int" checked="checked"><label for="radioINT"> Int</label><br>
                            <input type="radio" id="radioFLOAT" name="Zahlengruppe" value="float"><span id="radioFloatSpan"><label for="radioFLOAT">Float</label></span>
                        </fieldset>
                    </div>

                    <div class="col-sm-6">
                        <fieldset>
                            <input type="checkbox" id="rightSideScala" name="Position der Skala" value="left"><label for="rightSideScala">Skala rechts vom Graphen anzeigen</label>
                        </fieldset>
                    </div>
                </div>
                <button id="modalContentMenuButtonNewScala" class="btn btn-primary">Neue Skala</button>

                <hr>

                <h4 id="scalaModalh4">Skalen auswählen</h4>

                <div class="form-group">
                    <div class="table-responsive">
                        <table id="scalaModalContent" class="table"></table>
                    </div><!-- table-responsive end -->
                </div>
            </div><!-- modal-body end -->       

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Schließen</button>
            </div><!-- modal-footer end -->
        </div><!-- model-content end -->
    </div><!-- model-dialog end-->
</div><!-- modal end-->







<script>
    function selectChanged(val) { //val ist von der Form val[i, o] über i wird die MEssreihe erfasst und über o das Metafeld
        var io = val.split("");
        selectedMetafield = messreihen_copy[io[0]].metafields[io[1]];
        selectFlag = true;
        selectChangedCount++;
        $("#meta_select_button").html("<span class='glyphicon glyphicon-plus'></span>" + selectedMetafield["metaname"] + " filter hinzufügen");
    }





    function addMeta() {
        if ((selectFlag == false && old_value != selectedMetafield) || (selectChangedCount == 0)) {
            old_value = selectedMetafield;
            return;
        }
        old_value = selectedMetafield;

        var tmp_str = "<div id='' class='form-group'><label id='metaNameField" + uniqueId + "' class='control-label col-sm-8 text-right'>" + selectedMetafield["metaname"] + "</label>";

        addOperatorMenu(selectedMetafield.typ, tmp_str);
        addDefaultValueField();
        selectFlag = false;


        //Falls eine andere Messreihe das gewählte Metafeld nicht hat sollte diese aus der auswahl entfernt werden
        var to_delete = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            var exists_in_messreihe = false;
            for (o = 0; o < messreihen_copy[i].metafields.length; o++) {
                if (messreihen_copy[i].metafields[o]["metaname"] == selectedMetafield["metaname"]) {
                    exists_in_messreihe = true;
                }
            }
            if (!exists_in_messreihe) {
                to_delete.push(messreihen_copy[i]);
                excludeIrrelevantSensors(messreihen_copy[i]);
            }
        }
        //Jetzt wissen wir (in to_delete) welche messreihen von messreihen_copy (der Arbeitskopie)
        //gelöscht werden müssen -> anschließend muss das MetafilterSelect neu generiert werden
        var tmp_new_array = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            if ($.inArray(messreihen_copy[i], to_delete) < 0) {
                tmp_new_array.push(messreihen_copy[i]);
            }
        }
        messreihen_copy = $.extend(true, [], tmp_new_array);

        //Nun das SelectFeld neu generieren
        regenerateDocument();
    }







    function addOperatorMenu(type, append) {
        var datatype = type;
        var appendString = "<div id='metaOperatorField" + uniqueId + "' class='col-sm-4 datatype_" + type + "'><div class='btn-group'>";
        if (datatype == 'string') {
            appendString = appendString.concat("<button id='operatorButton" + uniqueId + "' class='btn btn-default'>");
            appendString = appendString.concat("ist </button></div>");
        } else {
            appendString = appendString.concat("<button id='operatorButton" + uniqueId + "' type='button' class='btn btn-default dropdown-toggle' data-toggle='dropdown' aria-expanded='false'>");
            appendString = appendString.concat("Operator <span class='caret'></span></button>");
            appendString = appendString.concat("<ul class='dropdown-menu' role='xmenu'>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \"==\", \"gleich\");'>gleich</a></li>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \"<\", \"kleiner\");'>kleiner</a></li>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \">\", \"größer\");'>größer</a></li>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \"<=\", \"kleiner gleich\");'>kleiner gleich</a></li>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \">=\", \"größer gleich\");'>größer gleich</a></li>");
            appendString = appendString.concat("<li><a onclick='addValueField(" + uniqueId + ", \"><\", \"zwischen\");'>zwischen</a></li></ul></div></div>");

        }
        $("#meta_name_operator_div").append(append + appendString);
    }








    function addValueField(id, value, name) {
        var appendString;
        var singleFieldOperators = ["==", "<", ">", "<=", ">="];

        var argsId = id;
        var valueFieldExists = $('#metaValueField' + argsId);

        //Den Beschriftung des OperatorenMenüs des Metafilters auf den ausgewählten Operator setzen
        $("#operatorButton" + argsId).html(name + "<span class='caret'></span>");

        //Das gefundene bereits vorhandene ValueField muss nun mit der neuen Auswahlersetzt
        //werden, falls es sich die Anzahl der angeforderten Inputfelder unterscheiden
        var isSingleValueFieldOperator = $.inArray(value, singleFieldOperators);

        //Unterscheide ob es sich um einen ein-Feld-/oder mehr-feld-operator handelt
        if ($.inArray(value, singleFieldOperators) > -1) {

            if ($(valueFieldExists).hasClass("singleValueField") && isSingleValueFieldOperator) {
                //Feld muss nicht erneuert werden
                return;
            }
            appendString = "<div id='metaValueField" + argsId + "' class='form-group'><div class='col-xs-8'><input id='metaValueInput" + argsId + "' class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput" + argsId + "'></input></div><a class='btn' onclick='delMeta(" + argsId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        } else {
            //kann momentan nur "between sein"
            if ($(valueFieldExists).hasClass("doubleValueField") && !(isSingleValueFieldOperator)) {
                //Feld muss nicht erneuert werden
                return;
            }
            appendString = "<div id='metaValueField" + argsId + "' class='form-group'><div class='col-xs-4'><input class='form-control' type='text' placeholder='von' name='stringInput" + argsId + "'></input></div><div class='col-xs-4'><input id='metaValueInput" + argsId + "' class='doubleValueField form-control valueField' type='text' placeholder='bis' name='stringInput" + argsId + "'></input></div><a class='btn' onclick='delMeta(" + argsId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        }
        $('#metaValueField' + argsId).replaceWith(appendString);
    }










    function addDefaultValueField() {
        var appendString;
        appendString = "<div id='metaValueField" + uniqueId + "' class='form-group'><div class='col-xs-8'><input id='metaValueInput" + uniqueId + "'class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput" + uniqueId + "'></input></div><a class='btn' onclick='delMeta(" + uniqueId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        $("#meta_value_div").append(appendString);
        ++uniqueId;
    }






    //löscht einen bestehenden metadatenfilter und macht die vorfilterung der meetaliste rückgängig
    function delMeta(argid) {
        $("#metaNameField" + argid).parent().remove();
        $("#metaValueField" + argid).remove();

        evaluateAllFilters();
    }








    function regenerateMetaSelect() {
        var replace_string = [];
        var tmp_metanamen = [];
        var tmp_array = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            var o;
            for (o = 0; o < messreihen_copy[i].metafields.length; o++) {
                if ($.inArray(messreihen_copy[i].metafields[o].metaname, tmp_metanamen) < 0) {
                    tmp_metanamen.push(messreihen_copy[i].metafields[o].metaname);
                    replace_string.push("<li id='selectOption" + (uniquei++) + "'>");
                    var tmp_str = "" + i;
                    tmp_str = tmp_str.concat("" + o);
                    replace_string.push("<a onclick='selectChanged(\"" + tmp_str + "\");'>" + messreihen_copy[i].metafields[o]["metaname"] + "</a></li>");
                }
            }
        }
        $("#selectBox").html(replace_string.join(""));
    }




    function evaluateAllFilters() {
        /*erstelle eine neue messreihen_copy von dem original anhand des filters auf alle verbleibenden filter*/
        messreihen_copy = $.extend(true, [], messreihen); //Tiefe Kopie vom Original

        var to_filter_number = $("#meta_value_div").children().length; //auf wie viele elemente muss dir filter funktion angewendet werden
        var to_filter; //wird in der for schleife das momentane element enthalten mit dem als nächstes gefiltert werden muss
        var target; //das target, welches filterMessreihen() als arument erwartet (das valuefield)
        var to_filter_id; //wird entsprechend der iteration die id des zu filternden elements ehntahlten
        //durch alle metadatenfilter durchiterieren und für jeden Filter die FIlterfunktion anwenden
        for (i = 0; i < to_filter_number; i++) {
            to_filter = $("#meta_value_div").children().eq(i);
            if ($(to_filter).children("div").length > 1) { //zwischen Operator zweites valuefield nehmen, da filtermessreihen das erwartet
                target = $(to_filter).children("div").eq(1).children()["0"];
            } else {
                target = $(to_filter).children("div").children()["0"];
            }

            to_filter_id = target.getAttribute("id");
            filterMessreihen(target, to_filter_id);
        }

        regenerateDocument();
    }



    function filterMessreihen(target, div_id) {
        //diese variablen dienen der ermittlung der relevanten faktoren zum filtern wie operator und datentyp
        var target_id = div_id.match(/[0-9]+/);
        var data_type;
        var operator;
        var metaname;

        //diese beiden input felder enthalten die entsprechenden inputs anhand denen gefiltert werden kann
        var target1 = target;
        var target2 = $("#" + div_id).parent().prev().children();

        //einfangen der möglicherweise zwei input felder - anhand dieser werte wird gefiltert
        var filterstring = target1.value;
        var untergrenze = target2.val();

        //welches metafeld? (name... zb. Material, Druck etc. ..)
        metaname = $("#metaNameField" + target_id).html();

        //welcher datentyp?
        operator = $("#metaOperatorField" + target_id);
        if (operator.hasClass('datatype_numerisch')) {
            data_type = 'numerisch';
        } else if (operator.hasClass('datatype_datum')) {
            data_type = 'datum';
        } else if (operator.hasClass('datatype_string')) {
            data_type = 'string';
        }


        //welcher operator?
        operator = operator.children().children().html();
        var cutoff_index = operator.search('<');
        operator = operator.slice(0, cutoff_index);


        if ((untergrenze != undefined) && (operator == "zwischen")) { // in diesem fall muss speziell behandelt werden, da es sich hier um zwei filterwerte handelt
            filterWith(metaname, data_type, "größer gleich", untergrenze, target_id);
            filterWith(metaname, data_type, "kleiner gleich", filterstring, target_id);
        } else {
            filterWith(metaname, data_type, operator, filterstring, target_id);
        }
    }





    function filterWith(metaname, datatype, operator, value, target_id) {
        //zunächst sollte der input bereinigt werden, zb falls der datatype numerisch ist sollte der input kein A-Z usw enthalten...
        if (!checkInput(datatype, value))
            return;


        /*jetzt sollte durch alle messreihen durchiteriert werden und geguckt werden ob wegen der eingegebenen werte eventuell
         *manche messreihen nicht mehr in die auswahl passen*/
        var tmp_array = [];//speichert die entstehend liste und wird am ende die arbeitskopie von messreihen übernommen
        var to_delete = [];
        var iterate_i;
        var iterate_o;
        for (iterate_i = 0; iterate_i < messreihen_copy.length; iterate_i++) {
            var messreihe_fits = false;
            for (iterate_o = 0; iterate_o < messreihen_copy[iterate_i].metafields.length; iterate_o++) {
                if (messreihen_copy[iterate_i].metafields[iterate_o].metaname == metaname) {//match gefunden nun werte vergleichen
                    if (elementFitsTheFilter(datatype, operator, value, messreihen_copy[iterate_i].metafields[iterate_o].wert)) {
                        messreihe_fits = true;
                    }
                    break;
                }
            }
            if (messreihe_fits) {
                tmp_array.push(messreihen_copy[iterate_i]);
            } else {
                //evtl wurden bereits sensoren von einer nun zu entfernenden Messreihe ausgewählt, diese müssen nun natürlich abgewählt werden
                excludeIrrelevantSensors(messreihen_copy[iterate_i]);
            }
        }
        messreihen_copy = $.extend(true, [], tmp_array);
    }


    function excludeIrrelevantSensors(messreihe) {
        var iterate_s;
        var selSenLen = selected_sensors.length;
        var to_delete = [];
        for (iterate_s = 0; iterate_s < selSenLen; iterate_s++) { //ermittle alle Sensoren, welche zur ausgeschlossnen Messreihe gehören und merke sie dir in to_delete
            var cond1, cond2;
            cond1 = selected_sensors[iterate_s].messreihenname;
            cond2 = messreihe.messreihenname;
            if (cond1 == cond2) {
                to_delete.push(selected_sensors[iterate_s]);
            }
        }

        for (iterate_s = 0; iterate_s < to_delete.length; iterate_s++) {
            if (to_delete[iterate_s].selected) {
                to_delete[iterate_s].selected = false;
                selected_sensors.splice($.inArray(to_delete[iterate_s], selected_sensors), 1);
            }
        }


        number_sensors = selected_sensors.length;
        $("#sensorenListe").html("");
        $("#skalenListe").html("");
        $("#h2MessreihenWählen").html("Messreihen/Sensoren <small>(" + number_sensors + ")</small> wählen");
    }





    function checkInput(datatype, value) {
        if ((datatype == "numerisch") && (isNaN(parseInt(value)))) {
            modalTextWarning("Ein numerischer input sollte eine Zahl sein! 120k geht zum Beispiel auch, jedoch wird dann eben das k ignoriert.");
            $('#infoModal').modal();
            return false;
        } else if ((datatype == "datum") && !(/[0-9]{4}-[0-9]{2}-[0-9]{2}$/).test(value)) {
            modalTextWarning("Ein Datum sollte von der Form yyy-mm-dd sein!");
            $('#infoModal').modal();
            return false;
        }
        return true;
    }



    function elementFitsTheFilter(datatype, operand, value, fit) {
        if (datatype == "string") {
            if (value == fit) {
                return true;
            }
            return false;
        }

        else if (datatype == "numerisch") {
            var val_numeric = parseInt(value);
            var fit_numeric = parseInt(fit);
            switch (operand) {
                case "kleiner gleich":
                    if (fit_numeric <= val_numeric) {
                        return true;
                    }
                    return false;
                case "größer gleich":
                    if (fit_numeric >= val_numeric) {
                        return true;
                    }
                    return false;
                case "gleich":
                    if (val_numeric == fit_numeric) {
                        return true;
                    }
                    return false;
                case "kleiner":
                    if (fit_numeric < val_numeric) {
                        return true;
                    }
                    return false;
                case "größer":
                    if (fit_numeric > val_numeric) {
                        return true;
                    }
                    return false;
            }
        }
        else if (datatype == "datum") {
            var date_value = new Date(value);
            var date_fit = new Date(fit);
            date_value = date_value.getTime();
            date_fit = date_fit.getTime();

            switch (operand) {
                case "kleiner gleich":
                    if (date_fit <= date_value)
                        return true;
                    return false;
                case "größer gleich":
                    if (date_fit >= date_value)
                        return true;
                    return false;
                case "gleich":
                    if (date_value == date_fit)
                        return true;
                    return false;
                case "kleiner":
                    if (date_fit < date_value)
                        return true;
                    return false;
                case "größer":
                    if (date_fit > date_value)
                        return true;
                    return false;
            }
        }
    }
//----------------------------------Funktionen zum Bearbeiten der "Messreihen/Sensoren-Filtern" Felder ------------------------------


    //Liste der angezeigten Messreihen regenerieren 
    var lookup_selected_mesreihe = null;
    function regenerateMessreihenList() {
        var replace_string = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            var hms = anySensorsSelectedFrom(messreihen_copy[i]["messreihenname"]);
            replace_string.push("<button class='btn btn-default' data-messreihe='" + messreihen_copy[i]["messreihenname"] + "'>");
            if (hms > 0) {
                replace_string.push(messreihen_copy[i]["messreihenname"] + " " + messreihen_copy[i].datum);
                replace_string.push(" <span class='glyphicon glyphicon-ok'></span>  <small>" + hms + "</small></button>");
            } else {
                replace_string.push(messreihen_copy[i]["messreihenname"] + " " + messreihen_copy[i].datum + "</button>");
            }
        }
        $("#messreihenListe").html(replace_string.join(""));

        if (selected_sensors.length > 0) {
            showSensorsOf(lookup_selected_messreihe);
        } else {
            showSensorsOf(messreihen_copy[0].messreihenname);
        }
    }




    function anySensorsSelectedFrom(messreihe) {
        var how_much_sensors = 0;
        for (o = 0; o < selected_sensors.length; o++) {
            if ((selected_sensors[o]["messreihenname"] == messreihe) && (selected_sensors[o]["selected"])) {
                ++how_much_sensors;
            }
        }
        return how_much_sensors;
    }


    function showSensorsOf(arg) {
        lookup_selected_messreihe = arg;
        var sensors_string = [];
        var scalas_string = [];

        for (i = 0; i < sensors.length; i++) {
            if (arg == sensors[i]["messreihenname"]) {
                sensors_string.push("<button class='btn btn-default sensor-btn' style='width:100%' data-messreihe='" + arg + "' data-sensorID='" + sensors[i]["id"] + "'>");
                sensors_string.push(sensors[i]["anzeigename"]);
                if (sensors[i].selected == true) {
                    sensors_string.push("<span class='glyphicon glyphicon-ok'></span>");
                }
                sensors_string.push("</button>");

                scalas_string.push("<button class='btn btn-default scala-btn' style='width:100%' data-messreihe='" + arg + "' data-sensorID='" + sensors[i]["id"] + "'>");
                scalas_string.push("<span class='glyphicon glyphicon-stats'></span>  ");
                if (sensors[i]["scala"] != null) {
                    scalas_string.push(sensors[i]["scala"]["name"]);
                }
                scalas_string.push("</button>");
            }
        }
        $("#sensorenListe").html(sensors_string.join(""));
        $("#skalenListe").html(scalas_string.join(""));
        $("#smallSensoren").html("Sensoren -> " + arg);
    }



    function selectSensor(target) {
        var selected_id = target.getAttribute("data-sensorID");
        var zugehörige_messreihe = target.getAttribute("data-messreihe");
        for (i = 0; i < sensors.length; i++) {
            if ((sensors[i].id == selected_id) && (sensors[i].messreihenname == zugehörige_messreihe)) {
                if (sensors[i].selected == false) {
                    $(target).append(" <span class='glyphicon glyphicon-ok'></span>");
                    sensors[i].selected = true;
                    selected_sensors.push(sensors[i]);
                    if (++number_sensors == max_number_sensors + 1) {
                        modalTextWarning("Achtung! Gute Performance ist nur mit " + max_number_sensors + " oder weniger Sensoren gewährleistet");
                        $('#infoModal').modal();
                    }
                    break;
                } else {
                    sensors[i].selected = false;
                    for (o = 0; o < selected_sensors.length; o++) {
                        if ((selected_sensors[o]["id"] == selected_id) && (selected_sensors[o].messreihenname == zugehörige_messreihe)) {
                            $(target).html(sensors[i]["anzeigename"]);
                            selected_sensors.splice(o, 1);
                            if (--number_sensors < 0) {
                                modalTextWarning("number_sensors is negative... this is strange... thanks obama");
                                $('#infoModal').modal();
                            }
                            break;
                        }
                    }
                }
            }
        }
        $("#h2MessreihenWählen").html("Messreihen/Sensoren <small>(" + number_sensors + ")</small> wählen");
        regenerateMessreihenList();
    }







    function selectScala(target) {
        var target_sensor_id = target.getAttribute("data-sensorID");
        var zugehörige_messreihe = target.getAttribute("data-messreihe");
        for (i = 0; i < sensors.length; i++) {
            if ((sensors[i].id == target_sensor_id) && (sensors[i].messreihenname == zugehörige_messreihe)) {
                patient_sensor = sensors[i]; //wenn anschließend eine skala aus se modal gewählt wurde wird sie dem sensor zugewiesen auf den patient_sensor zeigt
                break;
            }
        }
        if (patient_sensor == null) {
            modalTextWarning("patient_sensor ist 'null' da kann was nicht stimmen - function selsectScala.... thanks obama");
            $('#infoModal').modal();
        }
        regenerateScalaModal();
        $("#scalaModal").modal('show');
    }





    function regenerateScalaModal() {
        var replace_string = [];
        replace_string.push("<tr><th>Skala</th><th>Titel</th><th>Einheit</th><th>Int/Float</th><th>Position</th><th>choose</th></tr>");
        for (i = 0; i < scalas.length; i++) {
            replace_string.push("<tr>");
            replace_string.push("<td>" + scalas[i].name + "</td>");
            replace_string.push("<td>" + scalas[i].title.text + "</td>");

            if (/.*\%\.[0-9]1*/.test(scalas[i].labels.stringFormat)) { //handelt sich um float
                console.log("float");
                replace_string.push("<td>" + scalas[i].labels.stringFormat.slice(5, 100) + "</td>");
                replace_string.push("<td>FLOAT</td>");
            } else {
                console.log(scalas[i].labels.stringFormat.slice(3, 100));
                replace_string.push("<td>" + scalas[i].labels.stringFormat.slice(3, 100) + "</td>");
                replace_string.push("<td>INT</td>");
            }
            replace_string.push("<td>" + scalas[i].location + "</td>");
            replace_string.push("<td><button class='btn choose-scala-btn btn-xs' data-scalaID='" + scalas[i].name + "'>Auswaehlen</button></td>");
            replace_string.push("</tr>");
        }
        $("#scalaModalContent").html(replace_string.join(""));
        $("#scalaModalh4").html("Skala wählen für Sensor : <br>" + patient_sensor.anzeigename);
    }





    function chooseScala(target) {
        var chosen_scala;
        for (i = 0; i < scalas.length; i++) {
            if (scalas[i].name == target) {
                patient_sensor.scala = scalas[i];
                break;
            }
        }
        patient_scala = null; //Referenz löschen
        $("#scalaModal").modal('hide');
        regenerateMessreihenList();
    }



    var unique_scala_id = 0;

    function createNewScala() {
        var chosen_title = $("#scalaTitelInput").val();
        var chosen_unit = $("#scalaEinheitInput").val();
        var chosen_location = 'left';
        var chosen_int_float = '%d ';

        if (rightSideScala) {
            chosen_location = 'right';
        }
        if (radioFloatBool) {
            chosen_int_float = "%." + $("#sel1").val() + "f ";
        }

        if ((chosen_title != "") && (titleDoesntExists(chosen_title))) {
            var new_scala = {
                name: "Skala: " + (unique_scala_id++),
                strokeStyle: '#FFFFFF',
                location: chosen_location,
                majorGridLines: {
                    visible: false,
                },
                majorTickMarks: {
                    strokeSTyle: '#FFFFFF',
                },
                title: {
                    text: chosen_title,
                    fillStyle: '#FFFFFF',
                },
                labels: {
                    stringFormat: chosen_int_float.concat(chosen_unit),
                    fillStyle: '#FFFFFF',
                },
            }


            scalas.push(new_scala);
            regenerateScalaModal();
        }
    }


    function titleDoesntExists(titl) {
        for (i = 0; i < scalas.length; i++) {
            if (scalas[i].title.text == titl) {
                return false;
            }
        }
        return true;
    }
//-----------------------------------------------------------------------------------------------------------------------------------



    function regenerateDocument() {
        regenerateMetaSelect();
        regenerateMessreihenList();
    }


    $(function () {

        regenerateDocument();
        $('#meta_select_button').click(function () {
            addMeta();
            $(this).blur();
        });

        $('#meta_value_div').on("change", ".valueField", function (e) {
            evaluateAllFilters();
        });

        //CLICK ON MESSREIHE
        $('#messreihenListe').on("click", ".btn", function (e) {
            showSensorsOf($(e.target).data('messreihe'));
        });

        //CLICK ON SENSOR
        $('#sensorenListe').on("click", ".sensor-btn", function (e) {
            selectSensor(e.target);
        });

        //CLICK ON SCALA
        $('#skalenListe').on("click", ".scala-btn", function (e) {
            selectScala(e.target);
            /*generiere den Modal inhalt für die skalenanzeige*/
        });

        //in Modal on click in modalContent -> click on a scala
        $("#scalaModalContent").on("click", ".choose-scala-btn", function (e) {
            chooseScala(e.target.getAttribute("data-scalaID")); //wird der funktion eine scala id geben
        });

        //in Modal click on "neue skala"
        $('#modalContentMenuButtonNewScala').click(function () {
            createNewScala();
        });

        //in Modal on change in modals inputs -> radiobuttons 
        $('#radioINT, #radioFLOAT').change(function () {
            radioFloatBool = !radioFloatBool;
            if (radioFloatBool) {
                $("#radioFloatSpan").append("<div class='form-group'><label for='sel'>Anzahl Nachkommastellen:</label><select class='form-control' id='sel1'><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option></select></div>");
            } else {
                $("#radioFloatSpan").html("<label for='radioFLOAT'> Float</label>");
            }
        });

        //in Modal on click in modal inputs -> checkbox rightSideScala
        $("#rightSideScala").click(function () {
            rightSideScala = !rightSideScala;
            console.log(rightSideScala);
        });


        //stepInput on change
        $("#stepInput").change(function (e) {
            step = parseInt($(e.target).val());
        });

        //intervallInput1 on change
        $("#intervallInput1").change(function (e) {
            intervall1 = parseInt($(e.target).val());
        });

        //intervallInput2 on change
        $("#intervallInput2").change(function (e) {
            intervall2 = parseInt($(e.target).val());
        });

        //einstellungenInputDiv loses focus
        $(".einstellungenInput").blur(function (e) {
            var s, v, b; //step, von, bis
            s = $("#stepInput").val();
            v = $("#intervallInput1").val();
            b = $("#intervallInput2").val();
            //Verschiedene Fälle beachten - nur wenn manche Eingaben geleistet wurden können sie auch überprüft werden...

            if (v != "") {
                if (intervall1 < 0) {
                    intervall1 = 0;
                    $("#intervallInput1").val(0);
                    modalTextWarning("Vorsicht! -> 'Von' is negativ und wurde automatisch auf 0 gesetzt");
                    $('#infoModal').modal();
                }
            }

            if (b != "") {
                if (intervall2 < 0) {
                    intervall2 = 0;
                    $("#intervallInput2").val(0);
                    modalTextWarning("Vorsicht! -> 'Bis' is negativ und wurde automatisch auf 0 gesetzt");
                    $('#infoModal').modal();
                }
            }

            if (s != "") {
                if (step <= 0) {
                    step = 1;
                    $("#stepInput").val("1");
                    modalTextWarning("Vorsicht! -> 'Schrittweite' ist kleiner/gleich 0 -> Wert wurde automatisch auf 1 gesetzt");
                    $('#infoModal').modal();
                }
                if (b != "") {
                    if (intervall2 < step) {
                        intervall2 = intervall1 + step;
                        $("#intervallInput2").val(intervall1 + step);
                        modalTextWarning("Vorsicht! -> die Schrittweite ist höher als der Intervall!? Der Intervall wurde automatisch auf den kleinstmöglichen Wert gesetzt");
                        $('#infoModal').modal();
                    }
                }
            }

            if ((v != "") && (b != "")) {
                if (intervall2 < intervall1) {
                    intervall2 = intervall1 + step;
                    $("#intervallInput2").val(intervall1 + step);
                    modalTextWarning("Vorsicht! -> 'Bis' ist kleiner als 'Von' -> Werte wurden automatisch logisch neu verteilt");
                    $('#infoModal').modal();
                }
            }


        });

        //Anzeigen! button on click
        $("#anzeigeButton").click(function () {
            //die erste y-Achse (auf der linken Seite des Graphen) sollte zoom-enabled haben
            for (i = 0; i < scalas.length; i++) {
                if (scalas[i].location == "left") {
                    scalas[i].zoomenabled = true;
                    break;
                }
            }
            //Nun sollten alle benötigte Daten gesammelt sein - also triggern wir jqCharts
            var data = {
                from: intervall1,
                to: intervall2,
                step: step,
            };

            data.pair = [];
            for (i = 0; i < selected_sensors.length; i++) {
                tmp_array = [];
                tmp_array.push(selected_sensors[i].id);
                tmp_array.push(selected_sensors[i].messreihenid);

                data.pair.push(tmp_array);
            }
            //Ab jetzt ist data fertig
            //Nun wird noch eine Map benötigt in der schnell ausgelesen werden kann welch messreihen-sensor kmbination auf welche skala abgebildet werden soll
            var skalaMap = {};
            if (selected_sensors.length == 0) {
                modalTextError("Vorsicht! -> Es wurden keine Sensoren ausgewählt, deren Messwerte anzuzeigen wären... Bitte erst berichtigen");
                $('#infoModal').modal();
                return;
            }
            for (i = 0; i < selected_sensors.length; i++) {
                if (selected_sensors[i].scala == null) {
                    modalTextError("Vorsicht! -> " + selected_sensors[i].anzeigename + " aus der Messreihe: '" + selected_sensors[i].messreihenname + "', wurde noch keiner Skala zugewiesen! Bitte erst berichtigen... ");
                    $('#infoModal').modal();
                    return;
                }
                skalaMap[selected_sensors[i].messreihenname + " - " + selected_sensors[i].anzeigename] = selected_sensors[i].scala.name;
            }
            console.log(data);
            console.log(skalaMap);

            var seriesData = [];
            $.ajax({
                url: "./chartData.php",
                dataType: 'text',
                data: data,
                async: false,
                cache: false
            }).done(function (csvAsString) {
                var csvAsObj = parseCSV(csvAsString, data);
                var i;
                for (i = 0; i < csvAsObj.serien.length; ++i) {
                    // suche achse in sensors array
                    seriesData.push({
                        title: csvAsObj.serien[i],
                        markers: null,
                        data: csvAsObj.werte[i],
                        axisY: scalas[csvAsObj.serien[i]],
                        type: 'line'
                    });
                }

                console.log("CSV Daten sind fertig");
            });

            scalas.push({
                name: 'x',
                location: 'bottom',
                zoomEnabled: true,
                strokeStyle: '#FFFFFF',
                labels: {
                    fillStyle: '#FFFFFF'
                },
                majorTickMarks: {
                    strokeStyle: '#FFFFFF'
                }
            });

            $('#jqChart-wrapper').jqChart({
                title: {
                    text: $(this).data('title'),
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
                    margin: 10
                },
                axes: scalas,
                series: seriesData,
                tooltips: {
                    type: 'shared'
                }
            });

            $('#jqChart-wrapper').bind('tooltipFormat', function (e, data) {
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
                    result += data.x + "</b><br>\n";
                    result += "<table id='tooltipTable'>\n" +
                            "<tr><th>Serie</th><th>Wert</th><th>Datum</th><th>Uhrzeit</th></tr>\n";
                    result += buildRowForSeriespoint(data);
                }
                return result;
            });
        });
    });
</script>

<?php
require_once 'footer.php';
