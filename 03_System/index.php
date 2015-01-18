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
//Vorbereitung für die Filter		
//datenbank instanz erstellen
$projektid = $projekt->data()->id;

//Select für messreihenname, metadatenname, datentyp
$db->query("SELECT messreihe.messreihenname, messreihe.datum, metainfo.metaname, messreihe_metainfo.metawert, datentyp.typ
					FROM messreihe INNER JOIN projekt ON messreihe.projekt_id = projekt.id = $projektid
					INNER JOIN messreihe_metainfo ON messreihe.id = messreihe_metainfo.messreihe_id
					INNER JOIN metainfo ON metainfo.id = messreihe_metainfo.metainfo_id
					INNER JOIN datentyp ON metainfo.datentyp_id = datentyp.id");

//speichere den Select mit den Metafeldern in einer Variable
$select = $db->results();
$jsonselectmeta = json_encode($select);

//select für sensoren
$db->query("SELECT messreihe.messreihenname, messreihe.id, messreihe_sensor.anzeigename, sensor.id
					FROM messreihe INNER JOIN projekt ON messreihe.projekt_id = projekt.id = $projektid
					INNER JOIN messreihe_sensor ON messreihe.id = messreihe_sensor.messreihe_id
					INNER JOIN sensor ON messreihe_sensor.sensor_id = sensor.id");

$selectsensor = $db->results();
$jsonselectsensor = json_encode($selectsensor);
?>
<script>
    //
    //
    //
    //
    //-----------------------Variablen zur Auswahl aus dem Select----
    var select = <?php echo $jsonselectmeta; ?>;    //enthält den select
    var selectedMetafeld;
    var select_copy = [];

    //filter alle Metas heraus, die es doppelt gibt -----------------------------------
    var i;
    for (i = 0; i < select.length; i++) {
        var o;
        for (o = 0; o < select_copy.length; o++) {
            var already_exists = false;
            if ((select[i].metaname == select_copy[o].metaname)) {
                //metafield already exists!!!
                already_exists = true;
                break;
            }
        }
        if (!already_exists) {
            console.log("adding new meta to select meta: " + select[i].metaname);
            select_copy.push(select[i]);
        }
    }
    select = select_copy;
    //jetzt befinden sich in select alle messreihen so oft, wie sie metafelder haben

    //Durch den folgenden Code ist nun eine array verfügbar, welche ausschließlich die verschiedenen Messreihen (jede genau ein mal) mit allen metafeldern aufzeigt
    var messreihen = [];
    var messreihennamen = [];
    for (i = 0; i < select_copy.length; i++) {
        if ($.inArray(select_copy[i].messreihenname, messreihennamen) < 0) {
            messreihennamen.push(select_copy[i].messreihenname);
            var tmp_messreihe = {messreihenname: select_copy[i].messreihenname};
            tmp_messreihe.datum = select_copy[i].datum;
            tmp_messreihe.metafields = [];
            messreihen.push(tmp_messreihe);
            var o;
            for (o = i; o < select_copy.length; o++) {
                var mname = select_copy[o].metaname;
                if ((select_copy[o].messreihenname == select_copy[i].messreihenname) && ($.inArray(mname, messreihen[messreihen.length - 1]) < 0)) {
                }
                messreihen[messreihen.length - 1].metafields.push({metaname: select_copy[o].metaname, typ: select_copy[o].typ, wert: select_copy[o].metawert});
            }
            console.log("adding new 'messreihe' -->" + select_copy[i].messreihenname + "<-- to array 'messreihen'");
        }
    }

    //redundantes Datum zu messreihen metafields hinzufügen damit datum wie metafield gehandelt werden kann
    for (i = 0; i < messreihen.length; i++) {
        var o;
        var tmp_array = [];
        for (o = 0; o < select_copy.length; o++) {
            if ((messreihen[i]["messreihenname"] == select_copy[o]["messreihenname"]) && ($.inArray(messreihen[i], tmp_array) < 0)) {
                tmp_array.push(messreihen[i]);
                messreihen[i].metafields.push({metaname: "datum", typ: "datum", wert: select_copy[o]["datum"]});
            }
        }
    }

    //Arbeitskopie von messreihen erstellen
    var messreihen_copy = $.extend(true, [], messreihen);

    //only for debug
    /*for(i = 0; i < messreihen.length; i++){
     console.log(messreihen[i].metafields.length);
     var o;
     for(o = 0; o < messreihen[i].metafields.length; o++){
     console.log(messreihen[i].metafields[o].metaname);	
     }
     }*/
    //----------------------------------------------------------------------------------






    //Variablen für den Sensorzugriff
    var select_sensor = <?php echo $jsonselectsensor; ?>;
    var sensors = [];

    //keine doppelten sensoren zulassen-------------------------------
    for (i = 0; i < select_sensor.length; i++) {
        var o;
        for (o = 0; o < sensors.length; o++) {
            var already_exists = false;
            if ((select_sensor[i].anzeigename == sensors[o].anzeigename)) {
                //metafield already exists!!!
                already_exists = true;
                break;
            }
        }
        if (!already_exists) {
            console.log("adding new sensor to sensors[]: " + select_sensor[i].anzeigename);
            sensors.push(select_sensor[i]);
        }
    }

    /* only for debug
     console.log("##########");
     console.log(sensors);
     for(i = 0; i < sensors.length; i++){
     console.log(sensors[i]);
     }
     console.log("##########");*/
    //-------------------------------------------------------------------------------------------
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //----------------------Variablen zum Schutz der Selectbox und dem Auswahlbutton -> button zündet nur wenn etwas legales gewählt wurde----------
    var old_value = 0;
    var selectFlag = false; //nur falls eine Option aus dem select tag gewählt wurde darf der entsprechende button getriggert werden
    var selectChangedCount = 0;
    //----------------------------------------------------------------------------------------------------------------------------------------------
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //------------------------------------Variablen, mit deren Hilfe unique-Ids erstellt werden können----------------------------------------------------------
    var uniqueId = 0; //Diese Variable sollte nach erstellen eines neuen Metafilters inkrementiert werden	
    var look_up_unique_id = []; //Mit dieser Array kann die delMeta Funktion anhand der uniqueId zurückverfolgen
    //welches Metafeld in die Arbeitskopie messreihen_copy zurückgeführt werden muss
    var uniquei = 0; //für die <option> tagsim metafilterselect "#selectBox"
    //----------------------------------------------------------------------------------------------------------------------------------------------------------
    //
    //
    //
    //
    //
    //
    //
    //
    //
    //------------------------------------Die Strings, aus denen zuletzt der Select gebildet wird-------------
    var QUERY_SELECT = " SELECT";
    var QUERY_FROM = " FROM";
    var QUERY_WHERE = " WHERE";
    //--------------------------------------------------------------------------------------------------------
    //
    //
    //
    //
    //
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
                    Metafeld auswählen<span class="caret"></span>
                </button>
                <ul  id="selectBox" class="dropdown-menu" role="xmenu"></ul>
            </div>
        </div>

        <div class="col-sm-4">
            <button id="meta_select_button" class="btn btn-default"><span class="glyphicon glyphicon-plus"></span>Metafilter hinzufügen</button>
        </div>
    </div>
</div>




<!-- Filterung der Messreihen/Sensoren -->
<h2>Messreihe wählen</h2>
<div id="messreihenSensorenFilterDiv">
    <div id="messreihenDiv" class="col-xs-12 col-xs-6">
        <div id="messreihenListe" class="btn-group-vertical" role="group"></div>
    </div>
    <div id="sensorenDiv" class="col-xs-12 col-xs-6">
        <div id="sensorenListe" class="btn-group-vertical" role="group"></div>
    </div>
</div>


<br>

<!-- Weitere Einstellungen -->
<h2>Einstellungen</h2>
<div class="form-group">
    <div class="col-sm-12 col-md-6 col-lg-4"></div>
</div>

<script>
    function selectChanged(val) {
        var io = val.split("");
        selectedMetafeld = messreihen_copy[io[0]].metafields[io[1]];
        selectFlag = true;
        selectChangedCount++;
        $("#meta_select_button").html("<span class='glyphicon glyphicon-plus'></span>" + selectedMetafeld["metaname"] + "filter hinzufügen");
    }









    function addMeta() {
        if ((selectFlag == false && old_value != selectedMetafeld) || (selectChangedCount == 0)) {
            old_value = selectedMetafeld;
            return;
        }

        var tmp_str = "<div id='' class='form-group'><label id='metaNameField" + uniqueId + "' class='control-label col-sm-8 text-right'>" + selectedMetafeld["metaname"] + "</label>";

        addOperatorMenu(selectedMetafeld.typ, tmp_str);
        addDefaultValueField();
        selectFlag = false;
        old_value = selectedMetafeld;

        //Falls eine andere Messreihe das gewählte Metafeld nicht hat sollte diese (ihre eigenen, die wiederum kein anderer hat)aus der auswahl entfernt werden
        var to_delete = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            var exists_in_messreihe = false;
            for (o = 0; o < messreihen_copy[i].metafields.length; o++) {
                if (messreihen_copy[i].metafields[o]["metaname"] == selectedMetafeld["metaname"]) {
                    exists_in_messreihe = true;
                }
            }
            if (!exists_in_messreihe) {
                to_delete.push(messreihen_copy[i]);
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
        messreihen_copy = tmp_new_array;
        for (i = 0; i < to_delete.length; i++) {
            look_up_unique_id.push(to_delete[i]);//Für delMeta(argid) Funktion, so kann rückverfolgt werden was wieso gelöscht wurde
        }

        //Nun das SelectFeld neu generieren
        regenerateDocument();
    }







    function addOperatorMenu(type, append) {
        var appendString = "<div id='metaOperatorField" + uniqueId + "' class='col-sm-4'><div class='btn-group'>";
        if (type == 'string') {
            appendString = appendString.concat("<button id='operatorButton" + uniqueId + "' class='btn btn-default'>");
            appendString = appendString.concat("Ist</button></div>");
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
		        console.log("valueField already exists (single)");
		        return;
        	}
            appendString = "<div id='metaValueField"+argsId+"' class='form-group'><div class='col-xs-8'><input id='metaValueInput"+argsId+"' class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput"+argsId+"'></input></div><a class='btn' onclick='delMeta("+argsId+");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        }else{
			//kann momentan nur "between sein"
			if ($(valueFieldExists).hasClass("doubleValueField") && !(isSingleValueFieldOperator)) {
		        //Feld muss nicht erneuert werden
		        console.log("valueField already exists (double)");
            	return;
        	}
			appendString = "<div id='metaValueField" + argsId + "' class='form-group'><div class='col-xs-4'><input class='form-control' type='text' placeholder='von' name='stringInput" + argsId + "'></input></div><div class='col-xs-4'><input id='metaValueInput"+argsId+"' class='doubleValueField form-control valueField' type='text' placeholder='bis' name='stringInput"+argsId+"'></input></div><a class='btn' onclick='delMeta(" + argsId + ");'><span class='glyphicon glyphicon-remove'></span></a></div>";
		}
        $('#metaValueField'+argsId).replaceWith(appendString);
    }










    function addDefaultValueField() {
        var appendString;
        appendString = "<div id='metaValueField"+uniqueId+"' class='form-group'><div class='col-xs-8'><input id='metaValueInput"+uniqueId+"'class='singleValueField form-control valueField' type='text' placeholder='insert Value' name='stringInput"+uniqueId+"'></input></div><a class='btn' onclick='delMeta("+uniqueId+");'><span class='glyphicon glyphicon-remove'></span></a></div>";
        $("#meta_value_div").append(appendString);
        ++uniqueId;
    }







    function delMeta(argid) {
        $("#metaNameField" + argid).parent().remove();
        $("#metaValueField" + argid).remove();

        //TODO regenerate #selectBox da nun vorherig weggefallene messreihen wieder erlaubt sein können	
        var to_remove_from_look_up = [];
        for (i = 0; i < look_up_unique_id; i++) {
            messreihen_copy.push(look_up_unique_id[i]);
            to_remove_from_look_up.push(i);
        }
        //look_up_unique_id aufräumen
        look_up_unique_id = [];
        regenerateDocument();
    }








    function regenerateMetaSelect() {
        var replace_string = "";

        var tmp_array = [];
        for (i = 0; i < messreihen_copy.length; i++) {
            var o;
            for (o = 0; o < messreihen_copy[i].metafields.length; o++) {
                if ($.inArray(messreihen_copy[i].metafields[o]) < 0) {
                    tmp_array.push(messreihen_copy[i].metafields[o], tmp_array);
                    replace_string = replace_string.concat("<li id='selectOption" + (uniquei++) + "'>");
                    var tmp_str = "" + i;
                    tmp_str = tmp_str.concat("" + o);
                    replace_string = replace_string.concat("<a onclick='selectChanged(\"" + tmp_str + "\");'>" + messreihen_copy[i].metafields[o]["metaname"] + "</a></li>");
                }
            }
        }
        $("#selectBox").html(replace_string);
    }







	function filterMessreihen(target, div_id){
		var val2 = target.value;
		var val1 = $("#"+div_id).parent().prev().children().attr("value");
		console.log(val2);
		if(val1 != undefined){
			console.log(val1);
		}
	}



	function filterMessreihenOfSingleValueField(target){
		console.log("[filterSingle]:"+target);
	}

	function filterMessreihenOfDoubleValueField(target){
		console.log("[filterDouble]:"+target);
	}






//----------------------------------Funktionen zum Bearbeiten der "Messreihen/Sensoren-Filtern" Felder ------------------------------
    /*<div class="btn-group-vertical" role="group" aria-label="...">*/

    function regenerateMessreihenList() {
        var replace_string = "<div id='messreihenListe'>";
        for (i = 0; i < messreihen_copy.length; i++) {
            replace_string += "<button class='btn btn-default' data-messreihe='"+messreihen_copy[i]["messreihenname"]+"'>" + messreihen_copy[i]["messreihenname"] + "</button>";
        }
        replace_string += "</div>";
        $("#messreihenListe").replaceWith(replace_string);
    }



    function showSensorsOf(arg) {
        console.log(arg);
        var replace_string = "<div id='sensorListe'>";
        for (i = 0; i < sensors.length; i++) {
            if (arg == sensors[i]["messreihenname"]) {
                replace_string += "<button>" + sensors[i]["anzeigename"] + "</button>";
            }
            replace_string += "</div>";
            $("#sensorListe").replaceWith(replace_string);
        }
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

		 $('#meta_value_div').on("blur", ".valueField", function (e) {
			console.log("[eventHandler]#####");
			console.log(e.target);
			console.log($(this).attr("id"));
			console.log("###################");
            filterMessreihen(e.target, $(this).attr("id"));
        });
        
        $('#messreihenDiv').on("click", ".btn", function (e) {
            showSensorsOf($(e.target).data('messreihe'));
        });
    });

</script>

<?php
require_once 'footer.php';
