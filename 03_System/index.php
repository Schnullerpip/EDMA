<?php
require_once 'header.php';
$db = DB::getInstance();
?>

<h1 class="heading"><?php echo escape($projekt->data()->projektname); ?></h1>

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
                    Letzte Messreihenimporte<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                </a>
                <div id="collapseMessreihen" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="collapseMessreihenLabel" aria-expanded="true">
                    <ul class="list-group list-unstyled">
                        <?php foreach ($messreihen as $key => $messreihe) : ?>
                            <li>
                                <div class="row">
                                    <div class="col-sm-9">
                                        <div class="list-content">
                                            <?php echo escape($messreihe->messreihenname); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-2 text-right">
                                        <div class="list-content">
                                            <?php echo escape($messreihe->datum); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-1 pull-right controls">
                                        <ul class="list-unstyled list-inline pull-right">
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
//Vorbereitung für die Filter		
//datenbank instanz erstellen
$projektid = $projekt->data()->id;

//Select für messreihenname, messreihen id, metadatenname, datentyp
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
    //datum ist eigentlich ein eigenes Feld in der Datenbank und kein metafeld aber da nach datum gefiltert werden soll muss es auch wie ein metadatum behandelt werden
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

    //Arbeitskopie von messreihen erstellen, dadurch kann bei wiederherstellen der aktuellen Auswahl einfach auf das Original zugegriffen werden
    var messreihen_copy = $.extend(true, [], messreihen); //Tiefe Kopie


    //Variablen für den Sensorzugriff
    var select_sensor = <?php echo $jsonselectsensor; ?>;
    var sensors = [];           //wird der standardanlaufpunkt um Sensoren zu bearbeiten, hält alle sensoren
    var number_sensors = 0;     //enthält die aktuelle Anzahl an gewählten sensoren 
    const max_number_sensors = 6; //Es sollen höchstens 6 Sensoren ausgewählt werden dürfen, dies ist die Vergleichskonstante
    var selected_sensors = []; //speichert die bereits ausgewählten Sensoren

    //Sensoren müssen einer Skala zugeordnet werden, entsprechende ZUweisung wurd in folgender Datenstruktur gespeichert
    var scalas = [];       //enthält alle skalen
    var scalas_copy = []; //Wird später der jqChart übergeben, da die 'anzeige' anweisung noch eine x-achse pusht
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
    //--------------------------------------------------------------------------------------------------------------------------------------------



    //------------------------------------Variablen, mit deren Hilfe unique-Ids erstellt werden können----------------------------------------------------------
    var uniqueId = 0; //Diese Variable sollte nach erstellen eines neuen Metafilters inkrementiert werden	
    //welches Metafeld in die Arbeitskopie messreihen_copy zurückgeführt werden muss
    var uniquei = 0; //für die <option> tags im metafilterselect "#selectBox"
    //--------------------------------------------------------------------------------------------------------------------------------------------


    //------------------------------------Die Strings, aus denen zuletzt der Select gebildet wird-------------
    var QUERY_SELECT = "SELECT ";
    var QUERY_FROM = " FROM";
    var QUERY_WHERE = " WHERE";
    //--------------------------------------------------------------------------------------------------------
</script>				

<h2>Metadaten filtern</h2>
<div class="form-horizontal mb-15" id="addMetaDiv">
    <!-- Anzeigefelder für die ausgewählten Metadatenfilter -->
    <div class="form-group" style="display:none">
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

    <div class="col-sm-6"><small class="col-xs-12">Messreihen</small></div>
    <div class="col-sm-5"><small id="smallSensoren" class="col-xs-12">Sensoren</small></div>
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
<div class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-4 control-label" for="stepInput">Schrittweite</label>
        <div class="col-sm-4">
            <input id="stepInput" class="form-control einstellungenInput" type="text" name="IntervallInput" placeholder="z.B. 100(er Schritte)">
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label" for="intervallInput1">Intervall</label>
        <div class="einstellungenInputDiv">
            <div class="col-sm-2">
                <input id="intervallInput1" class="form-control einstellungenInput" type="text" name="IntervallInput" placeholder="Von">
            </div>
            <div class="col-sm-2">
                <input id="intervallInput2" class="form-control einstellungenInput" type="text" name="IntervallInput" placeholder="Bis">
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-4 col-sm-offset-4 anzeigeButtonDiv">
            <button id="anzeigeButton"  type="button" class="btn btn-default btn-block" >Anzeigen</button>
        </div>
    </div>
</div>

<!-- Spinner waehrend Chart laedt -->
<div class="loading-div" style="display: none">
    <div class="loading-spinner"></div>
    <h4 class="text-center">Chart wird geladen, bitte warten...</h4>
</div>


<div class="row">
    <div class="col-xs-12">
        <div id="jqChart-wrapper" data-title="<?php echo escape($projekt->data()->projektname); ?>"></div>
    </div>
</div>

<a id="saveImg" style="display:none" class="btn btn-default" href="#">Speichern als Bild</a>
<a id="saveCSV" style="display:none" class="btn btn-default" target=_blank href="../datagross.csv">Speichern als CSV</a>

<!--Skala Modal -->
<div id="scalaModal" class="modal fade" tabindex="-1" aria-hidden="true">
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
                    <label class="col-sm-4 control-label" for="scalaTitelInput">Titel<sup>*</sup></label>
                    <div class="col-sm-6">
                        <input id="scalaTitelInput" class="form-control scalaModalInput" type="text" name="scalaTitleInput" placeholder="z.B. Temperatur"></input>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-4 control-label" for="scalaEinheitInput">Einheit</label>
                    <div class="col-sm-6">
                        <input id="scalaEinheitInput" class="form-control scalaModalInput" type="text" name="scalaEinheitInput" placeholder="z.B. °C"></input>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-2 einheit-datentyp">
                        <div class="radio">
                            <label>
                                <input type="radio" id="radioINT" name="Zahlengruppe" value="int" checked="checked">
                                Ganzzahl
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" id="radioFLOAT" name="Zahlengruppe" value="float">
                                Gleitkommazahl
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group nachkommastellen-wrapper" style="display: none;">
                    <label class="col-sm-4 control-label" for="scalaEinheitInput">Nachkommastellen</label>
                    <div class="col-sm-2">
                        <select id="sel1" class="form-control">
                            <option>1</option>
                            <option>2</option>
                            <option>3</option>
                            <option>4</option>
                            <option>5</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="rightSideScala" name="Position der Skala" value="left"> Skala rechts vom Graphen anzeigen
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-offset-4 col-sm-3">
                        <button id="modalContentMenuButtonNewScala" class="btn btn-primary">Skala erstellen</button>
                    </div>
                </div>

                <hr>

                <h4 id="scalaModalh4">Skalen auswählen</h4>

                <div class="form-group">
                    <div class="col-xs-12">
                        <div class="table-responsive">
                            <table id="scalaModalContent" class="table table-striped"></table>
                        </div><!-- table-responsive end -->
                    </div>
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
        addDefaultValueField(selectedMetafield.typ);
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
        var valueFieldExists = $('#metaValueInput' + argsId);
        former_input_string = $("#metaValueInput"+argsId).val();

        //Den Beschriftung des OperatorenMenüs des Metafilters auf den ausgewählten Operator setzen
        $("#operatorButton" + argsId).html(name + "<span class='caret'></span>");

        //Das gefundene bereits vorhandene ValueField muss nun mit der neuen Auswahlersetzt
        //werden, falls es sich die Anzahl der angeforderten Inputfelder unterscheiden
        var isSingleValueFieldOperator = $.inArray(value, singleFieldOperators);

        //Unterscheide ob es sich um einen ein-Feld-/oder mehr-feld-operator handelt
        if (isSingleValueFieldOperator > -1) {

            appendString = "<div id='metaValueField" + argsId + "' class='form-group'><div class='col-xs-8'><input id='metaValueInput" + argsId + "' class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput" + argsId + "'></input></div><a class='btn' onclick='delMeta(" + argsId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";

        } else {
            //kann momentan nur "between sein"
            appendString = "<div id='metaValueField" + argsId + "' class='form-group'><div class='col-xs-4'><input class='form-control' type='text' placeholder='von' name='stringInput" + argsId + "'></input></div><div class='col-xs-4'><input id='metaValueInput" + argsId + "' class='doubleValueField form-control valueField' type='text' placeholder='bis' name='stringInput" + argsId + "'></input></div><a class='btn' onclick='delMeta(" + argsId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        }
        $('#metaValueField' + argsId).replaceWith(appendString);
        $("#metaValueInput"+argsId).val(former_input_string);
        $("#metaValueInput"+argsId).fadeOut(100).fadeIn(100);//kleiner Effekt um dem veränderten ValueInput Aufmerksamkeit zu schenken

        //mit dem neuen Operator gleich einen neuen Filterdurchlaif starten - nur wenn auch ein vorheriger input vorhanden war
        if((former_input_string != "") && (isSingleValueFieldOperator > -1)){
            evaluateAllFilters();
        }
    }










    function addDefaultValueField(type) {
        var appendString;
        appendString = "<div id='metaValueField" + uniqueId + "' class='form-group'><div class='col-xs-8'><input id='metaValueInput" + uniqueId + "'class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput" + uniqueId + "'></input></div><a class='btn' onclick='delMeta(" + uniqueId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        $("#meta_value_div").append(appendString);
        if(type != 'string'){
            $("#metaValueInput"+uniqueId).attr("disabled", "disabled");
            $("#metaValueInput"+uniqueId).attr("title", "Bitte erst Operator wählen"); //Damit Inputfelder erst beschreibbar sind, wenn ein passender operator gewählt wurde -> bei strings egal da strings immer mit gleich verglichen werden sollen
        }
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
            modalTextWarning("Ein Datum sollte von der Form yyyy-mm-dd sein!");
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


    function reorganizeMessreihenCopyByDate(){
        var tmp;
        var iter_index;
        var d1, d2;
        if(messreihen_copy.length < 2){//in dem fall würde die überprüfung gehler werfen, außerdem ist in diesem Fall eine Sortierung nicht nötig
            return;
        }
        for(iter_index=1;iter_index<messreihen_copy.length;iter_index++){
        d2 = new Date(messreihen_copy[iter_index].datum).getTime();
        d1= new Date(messreihen_copy[iter_index-1].datum).getTime();
            if((d2 < d1) || ((d2 == d1) &&(messreihen_copy[iter_indexi].messreihenname < messreihen_copy[iter_indexi-1].messreihenname))){
                tmp = tmp = d1;
                messreihen_copy[iter_indexi-1] = messreihen_copy[iter_indexi];
                messreihen_copy[iter_indexi] = tmp;
            }
        }
    }

    //diese Funktion kann zum debuggen benutzt werden
    function showMessreihenCopy(){
        console.log("A--------A");
        for(i=0;i<messreihen_copy.length;i++){
            console.log(messreihen_copy[i].messreihennamei+" - "+messreihen_copy[i].datum);
        }
        console.log("V--------V");
    }





    //----------------------------------Funktionen zum Bearbeiten der "Messreihen/Sensoren-Filtern" Felder ------------------------------


    //Liste der angezeigten Messreihen regenerieren 
    var lookup_selected_messreihe = null;
    function regenerateMessreihenList() {
        //falls es noch keine messreihen gibt - eine Meldung ausgeben welche auf die Situation hinweist, anstatt überschriften ohne eigentliche werte anzuzeigen
        checkForExistingMessreihen();
        reorganizeMessreihenCopyByDate();
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

        if (lookup_selected_messreihe != null) {
            for(i=0;i<messreihen_copy.length;i++){
                if(messreihen_copy[i].messreihenname == lookup_selected_messreihe){
                    showSensorsOf(lookup_selected_messreihe);
                    return;
                }
            }
        }
        showSensorsOf(messreihen_copy[0].messreihenname);
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
                if (sensors[i]["scala"] != null) {
                    scalas_string.push("<b>"+sensors[i]["scala"]["name"]+"</b>");
                }else{
                    scalas_string.push("<span class='glyphicon glyphicon-stats'></span>  ");
                }
                scalas_string.push("</button>");
            }
        }
        $("#sensorenListe").html(sensors_string.join(""));
        $("#skalenListe").html(scalas_string.join(""));
        $("#smallSensoren").html('Sensoren für "' + arg + '"');

        $("#messreihenListe button").css({"color": "white", "background-color": "#36A7EB"});
        $("#messreihenListe button[data-messreihe='"+arg+"']").css({"background-color": "white", "color": "#224565"});
    }



    function selectSensor(target) {
        var selected_id = target.getAttribute("data-sensorID");
        var zugehörige_messreihe = target.getAttribute("data-messreihe");

        for (i = 0; i < sensors.length; i++) {
            if ((sensors[i].id == selected_id) && (sensors[i].messreihenname == zugehörige_messreihe)) {
                if(sensors[i].scala == null){//initial die default-skala für den sensor auswählen
                    sensors[i].scala = scalas[0]; 
                }
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
        replace_string.push("<tr><th>Skala</th><th>Titel</th><th>Einheit</th><th>Int/Float</th><th>Position</th><th></th><th></th></tr>");
        for (i = 0; i < scalas.length; i++) {
            replace_string.push("<tr>");
            replace_string.push("<td>" + scalas[i].name + "</td>");
            replace_string.push("<td>" + scalas[i].title.text + "</td>");

            if (/.*\%\.[0-9]1*/.test(scalas[i].labels.stringFormat)) { //handelt sich um float
                replace_string.push("<td>" + scalas[i].labels.stringFormat.slice(5, 100) + "</td>");
                replace_string.push("<td>Gleitkommazahl</td>");
            } else {
                replace_string.push("<td>" + scalas[i].labels.stringFormat.slice(3, 100) + "</td>");
                replace_string.push("<td>Ganzzahl</td>");
            }
            replace_string.push("<td>" + scalas[i].location + "</td>");
            replace_string.push("<td><button class='btn btn-primary choose-scala-btn btn-sm pull-right' data-scalaID='" + scalas[i].name + "'>Ausw&auml;hlen</button></td><td><button class='btn btn-primary delete-scala-btn btn-sm pull-right' data-scalaID='"+scalas[i].name+"'>Löschen</button></td>");
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
        var chosen_location = 'links';
        var chosen_int_float = '%d ';

        if (rightSideScala) {
            chosen_location = 'rechts';
        }
        if (radioFloatBool) {
            chosen_int_float = "%." + $("#sel1").val() + "f ";
        }

        if ((chosen_title != "") && (titleDoesntExists(chosen_title))) {
            var new_scala = {
                name: "" + (unique_scala_id++),
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
            $("#scalaTitelInput").val("");
            $("#scalaEinheitInput").val("");
            regenerateScalaModal();
        }
    }

    function deleteScala(e){
        for(i=0;i<scalas.length;i++){
            if(scalas[i].name === e){
                for(o=0;o<sensors.length;o++){
                    if(sensors[o].scala === scalas[i]){
                        sensors[o].scala = null;
                    }
                }
                for(o=0;o<selected_sensors.length;o++){
                    if(selected_sensors[o].scala === scalas[i]){
                        selected_sensors[o].scala = null;
                    }
                }
                scalas.splice(i, 1);
                break;
            }
        }
        regenerateMessreihenList();
        regenerateScalaModal();
    }

    function titleDoesntExists(titl) {
        for (i = 0; i < scalas.length; i++) {
            if (scalas[i].title.text == titl) {
                return false;
            }
        }
        return true;
    }
    
    function isInt(value) {
        return /^\d+$/.test(value);
   }

    function checkForExistingMessreihen(){
        if(messreihen.length == 0){
            modalTextWarning("Momentan sind noch keine Messreihen importiert, dies kann auf <a href='messreihen'>Messreihenverwaltung</a> erledigt werden.");
            $('#infoModal').modal();
            //Die Überschriften Messreihen, Sensoren, Skala entfernen -> sieht nur verwirrend aus wenn es noch keine Daten gibt..
            $("#messreihenSensorenFilterDiv").html("<p style='text-align:center'>Keine Messreihen vorhanden</p>");
        }
    }
    //-----------------------------------------------------------------------------------------------------------------------------------



    function regenerateDocument() {
        regenerateMetaSelect();
        regenerateMessreihenList();
    }


    $(function () {


        //Defaultskala erstellen
        scalas.push({
                name: "D",
                strokeStyle: '#FFFFFF',
                location: "links",
                majorGridLines: {
                    visible: false,
                },
                majorTickMarks: {
                    strokeSTyle: '#FFFFFF',
                },
                title: {
                    text: "Default",
                    fillStyle: '#FFFFFF',
                },
                labels: {
                    stringFormat: "%.2f",
                    fillStyle: '#FFFFFF',
                },
        });

        //for(i=0;i<sensors.length;i++){
        //    sensors[i].scala = scalas[0];
        //}

        regenerateDocument();


        //den Downloadbuttons den richtigen Titel usw. geben
        var projekt_name = "<?php echo escape($projekt->data()->projektname); ?>";
        var curr_date = new Date();
        var curr_time = curr_date.getDate()+"."+(curr_date.getMonth()+1)+"."+curr_date.getFullYear()+" um "+curr_date.getHours()+":"+curr_date.getMinutes()+":"+curr_date.getSeconds()+"Uhr";
        $("#saveImg").attr("download", "Chart - "+projekt_name+" on "+curr_time+".png"); 
        $("#saveCSV").attr("download", "Data - "+projekt_name+" on "+curr_time+".csv"); 

        $('#meta_select_button').click(function () {
            $("#meta_name_operator_div").parent().show();
            addMeta();
            $(this).blur();
        });

        $('#meta_value_div').keypress(function (e) {
            if(e.keyCode == 13){
                evaluateAllFilters();
            }
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

        //in Modal on click in modalContent -> delete a scala
        $("#scalaModalContent").on("click", ".delete-scala-btn", function (e) {
            deleteScala(e.target.getAttribute("data-scalaID"));
        });

        //in Modal click on "neue skala"
        $('#modalContentMenuButtonNewScala').click(function () {
            createNewScala();
            $('#scalaModal').modal('handleUpdate');
        });

        //in Modal on change in modals inputs -> radiobuttons 
        $('#radioINT, #radioFLOAT').change(function () {
            radioFloatBool = !radioFloatBool;
            if (radioFloatBool) {
                $(".nachkommastellen-wrapper").show();
            } else {
                $(".nachkommastellen-wrapper").hide();
            }
        });

        //in Modal on click in modal inputs -> checkbox rightSideScala
        $("#rightSideScala").click(function () {
            rightSideScala = !rightSideScala;
        });


        //stepInput on change
        $("#stepInput").change(function (e) {
            step = $(e.target).val();
            if (!step) {
                // feld ist leer => default
                step = 1;
            } else {
                // string to int
                step = parseInt(step);
            }
            if(!isInt(step)){
                step = 1;
                $("#stepInput").val("");
                modalTextWarning("'Schrittweite' muss als positive ganze Zahl eingegeben werden!");
                $('#infoModal').modal();
            }
        });

        //intervallInput1 on change
        $("#intervallInput1").change(function (e) {
            intervall1 = $(e.target).val();
            if (!intervall1) {
                // feld ist leer => default
                intervall1 = 0;
            } else {
                // string to int
                intervall1 = parseInt(intervall1);
            }
            if(!isInt(intervall1)){
                intervall1 = 0;
                $("#intervallInput1").val("");
                modalTextWarning("'Von' muss als positive ganze Zahl eingegeben werden!");
                $('#infoModal').modal();
            }
        });

        //intervallInput2 on change
        $("#intervallInput2").change(function (e) {
            intervall2 = $(e.target).val();
            if (!intervall2) {
                // feld ist leer => default
                intervall2 = 0;
            } else {
                // string to int
                intervall2 = parseInt(intervall2);
            }
            if(!isInt(intervall2)){
                intervall2 = 0;
                $("#intervallInput2").val("");
                modalTextWarning("'Bis' muss als positive ganze Zahl eingegeben werden!");
                $('#infoModal').modal();
            }
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
                    modalTextWarning("Vorsicht! 'Von' ist negativ und wurde automatisch auf 0 gesetzt");
                    $('#infoModal').modal();
                }
            }

            if (b != "") {
                if (intervall2 < 0) {
                    intervall2 = 0;
                    $("#intervallInput2").val(0);
                    modalTextWarning("Vorsicht! 'Bis' ist negativ und wurde automatisch auf 0 gesetzt");
                    $('#infoModal').modal();
                }
            }

            if (s != "") {
                if (step <= 0) {
                    step = 1;
                    $("#stepInput").val("1");
                    modalTextWarning("Vorsicht! 'Schrittweite' ist kleiner/gleich 0 -> Wert wurde automatisch auf 1 gesetzt");
                    $('#infoModal').modal();
                }
                if (b != "") {
                    if (intervall2 < step) {
                        intervall2 = intervall1 + step;
                        $("#intervallInput2").val(intervall1 + step);
                        modalTextWarning("Vorsicht! 'Schrittweite' ist höher als Intervall! Intervall wurde automatisch auf kleinstmöglichen Wert gesetzt");
                        $('#infoModal').modal();
                    }
                }
            }

            if ((v != "") && (b != "")) {
                if (intervall2 < intervall1) {
                    intervall2 = intervall1 + step;
                    $("#intervallInput2").val(intervall1 + step);
                    modalTextWarning("Vorsicht! 'Bis' ist kleiner als 'Von' -> Werte wurden automatisch logisch neu verteilt");
                    $('#infoModal').modal();
                }
            }


        });

        //Anzeigen! button on click
        $("#anzeigeButton").click(function () {
            //Es wird eine Map benötigt in der schnell ausgelesen werden kann welch messreihen-sensor kmbination auf welche skala abgebildet werden soll
            var skalaMap = {};
            scalas_copy = [];



            if (selected_sensors.length == 0) {
                modalTextError("Vorsicht! Es wurden noch keine Sensoren ausgewählt. Bitte erst berichtigen!");
                $('#infoModal').modal();
                return;
            }

            var data = {
                from: intervall1,
                to: intervall2,
                step: step,
            };

            data.pair = [];
            for (i = 0; i < selected_sensors.length; i++) {
                if (selected_sensors[i].scala == null) {
                    modalTextError("Vorsicht! '" + selected_sensors[i].anzeigename + "' aus der Messreihe: '" + selected_sensors[i].messreihenname + "' wurde noch keiner Skala zugewiesen! Bitte erst berichtigen!");
                    $('#infoModal').modal();
                    return;
                }

                var tmp_array = [];
                tmp_array.push(selected_sensors[i].id);
                tmp_array.push(selected_sensors[i].messreihenid);

                data.pair.push(tmp_array);
                if ($.inArray(selected_sensors[i].scala, scalas_copy) < 0) {
                    scalas_copy.push(selected_sensors[i].scala);
                }

                skalaMap[selected_sensors[i].messreihenname + " - " + selected_sensors[i].anzeigename] = selected_sensors[i].scala.name;
            }

            //nun ist sichergestellt dass Sensoren ausgewählt wurden und jeder einer Skala zugewiesen wurde, deshalb kann nun das loading-div (spinner) getoggelt werden
            $('.loading-div, #anzeigeButton').toggle();
            //Außerdem kann nun das jqWrapper div angezeigt werden, sowie die Buttons zum Speichern des Graphen als img/csv
            $("#jqChart-wrapper").show();
            $("#saveImg").show();
            $("#saveCSV").show();
            
            // ausblenden bei erneutem drücken von 'Anzeigen', solange Spinner eingeblendet ist
            if ($("#jqChart-wrapper").is(":visible")){
                $("#jqChart-wrapper").toggle();
                $("#saveImg").toggle();
                $("#saveCSV").toggle();
            }

            //die erste y-Achse (auf der linken Seite des Graphen) sollte zoom-enabled haben
            for (i = 0; i < scalas_copy.length; i++) {
                if (scalas_copy[i].location == "left") {
                    scalas_copy[i].zoomenabled = true;
                    break;
                }
            }

            data.mode = "chart";
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
                        axisY: skalaMap[csvAsObj.serien[i]],
                        type: 'line'
                    });
                }
            });

            scalas_copy.push({
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
                toolbar: {
                  visibility: 'auto', // auto, visible, hidden
                  resetZoomTooltipText: 'Zoom zurücksetzen (100%)',
                  zoomingTooltipText: 'Zoombereich aufspannen',
                  panningTooltipText: 'Zoombereich verschieben'
                },
                background: '#36a7eb',
                chartAreaBackground: '#FFFFFF',
                border: {
                    visible: false
                },
                legend: {
                    location: 'bottom',
                    textFillStyle: '#224565',
                    border: {
                        visible: true,
                        lineWidth: 0,
                        padding: 6
                    },
                    margin: 10,
                    inactiveTextFillStyle: '#83afd7',
                    background: '#FFFFFF',
                    cornerRadius: 3
                },
                axes: scalas_copy,
                series: seriesData,
                tooltips: {
                    type: 'shared'
                },
                noDataMessage: {
                    text: "Keine Daten vorhanden",
                    fillStyle: "#FFFFFF"
                }
            });
            
            // Spinner ausblenden, da Chart fertig geladen
            $('.loading-div, #anzeigeButton').toggle();
            
            // anzeigen, da Chart fertig geladen
            $("#jqChart-wrapper").toggle();
            $("#saveImg").toggle();
            $("#saveCSV").toggle();

            $('#jqChart-wrapper').bind('tooltipFormat', function (e, data) {
                var result = "<b>Zeitpunkt: ";
                if (data.constructor === Array) {
                    var xValue = data[0].x
                    result += xValue + "</b><br>\n" +
                            "<table id='tooltipTable'>\n" +
                            "<tr><th>Serie</th><th>Wert</th><th>Datum</th><th>Uhrzeit</th></tr>\n";

                    var i;
                    for (i = 0; i < data.length; ++i) {
                        result += buildRowForSeriespoint(data[i], xValue);
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
            
            $('#saveImg').click(function() {
                var image;
                $("#jqChart-wrapper").find("canvas").each(function(index) {
                    image = $(this)[0].toDataURL("image/png");
                    // return false innerhalb each() ist wie continue
                    return false;
                });
                $(this).attr("href", image);
            });
            
            $('#saveCSV').click(function() {                
                var url = "chartData.php?";
                data.mode = "CSV";
                for (key in data) {
                    url += key;
                    url += "=";
                    
                    // wenn value von key ein Array ist muss es erst in JSON umgewandelt werden
                    if (data[key].constructor === Array) {
                        url += encodeURIComponent(JSON.stringify(data[key]))
                    } else {
                        url +=encodeURIComponent(data[key]);
                    }
                    url += "&";
                }
                $(this).attr("href",url);
            });
        });
    });
</script>

<?php
require_once 'footer.php';
