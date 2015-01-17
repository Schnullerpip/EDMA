<?php
require_once 'header.php';
?>

    <p>Projekt: <?php echo escape($projekt->data()->projektname); ?></p>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel-group accordeon" role="tablist">
                <div class="panel panel-default">
                    <a class="btn btn-block panel-heading text-center collapsed" role="tab" href="#collapseListengruppe1" data-toggle="collapse" aria-expanded="true" aria-controls="collapseListengruppe1" id="collapseListengruppeÜberschrift1">
                        Letzte Messreihenimporte anzeigen<span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                    </a>
                    <div id="collapseListengruppe1" class="panel-collapse collapse out" role="tabpanel" aria-labelledby="collapseListengruppeÜberschrift1" aria-expanded="true">
                        <ul class="list-group list-unstyled">
                            <li>
                                <div class="row">
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            Messreihe 1
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            <!-- TODO: <data> -->
                                            01.01.1970
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
                                                <a href="#">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="row">
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            Messreihe 1
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            <!-- TODO: <data> -->
                                            01.01.1970
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
                                                <a href="#">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="row">
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            Messreihe 1
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="list-content">
                                            <!-- TODO: <data> -->
                                            01.01.1970
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
                                                <a href="#">
                                                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

	
	<?php //Vorbereitung für die Filter		

		//datenbank instanz erstellen
		$db = DB::getInstance();
		$projektid = $projekt->data()->id;

		//Select für messreihenname, metadatenname, datentyp
		$db->query("SELECT messreihe.messreihenname, metainfo.metaname, messreihe_metainfo.metawert, datentyp.typ
					FROM messreihe INNER JOIN projekt ON messreihe.projekt_id = $projektid
					INNER JOIN messreihe_metainfo ON messreihe.id = messreihe_metainfo.messreihe_id
					INNER JOIN metainfo ON metainfo.id = messreihe_metainfo.metainfo_id
					INNER JOIN datentyp ON metainfo.datentyp_id = datentyp.id
					");

		//store the select in a variable
		$select = $db->results();
		$jsonselect = json_encode($select);
	?>
	<script>
        //-----------------------oft benutzt ----- ----------------------
		var select = <?php echo $jsonselect; ?>;    //enthält den select
        var selectedValue = 0;	                    //Zeigt an welche Art von Metadatenfilter aus der Selectbox gewählt wurde
        //---------------------------------------------------------------
        //
        //
        //
        //----------------------Variablen zum Schutz der Selectbox und dem Auswahlbutton -> button zündet nur wenn etwas legales gewählt wurde----------
        var old_value = 0;
        var selectFlag = false; //nur falls eine Option aus dem select tag gewählt wurde darf der entsprechende button getriggert werden
        //----------------------------------------------------------------------------------------------------------------------------------------------
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
        //----------------------------------------------------------------------------------------------------------------------------------------------------------
        //
        //
        //
        //
	</script>				

		<div class="form-horizontal" id="addMetaDiv">

			<h2>Metadaten filtern</h1>
			<!-- Anzeigefelder für die ausgewählten Metadatenfilter -->
			<div class="form-group">
				<div id="meta_name_operator_div" class="col-xs-6"></div>
				<div id="meta_value_div" class="col-xs-6"></div>
			</div>



			<!-- Select element und Bestätigungsbutton -->
			<div class="form-group">
				<div class="col-sm-2 col-sm-offset-4">
					<select id="selectBox" class="dontbewhite" onchange="selectChanged(value);">
					<?php
						echo "<option></option>";
						foreach($select as $i => $value){
							echo '<option id="selectOption'.$i.'" class="dontbewhite" value="'.$i.'">'.$value->metaname.'</option>';
						}
					?>
					</select>
				</div>

				<div class="col-sm-4">
					<button id="meta_select_button" class="dontbewhite" onClick="addMeta();">new Metafilter</button>
				</div>
			</div>
		

			<!-- Rest -->
			<div class="form-group">
				<h2>Messreihe wählen</h1>
				<div class="col-sm-12 col-md-6 col-lg-4">
				</div>
			</div>
			<div class="form-group">
				<h2>Einstellungen</h1>
				<div class="col-sm-12 col-md-6 col-lg-4">
				</div>
			</div>
	</div>

<script>
	function selectChanged(val){
		selectedValue = val;
		selectFlag=true;
	}

	function addMeta(){
		if(selectFlag==false && old_value!=selectedValue){
            old_value = selectedValue;
			return;
		}
		var obj = select[selectedValue];
		$("#meta_name_operator_div").append("<div class='col-sm-8 text-right'> <textfield id='metaNameField"+uniqueId+"' class='meta-element-size'>" + obj.metaname + "</textfield> </div>");
		addOperatorMenu(obj.typ);
        addDefaultValueField();
		selectFlag = false;
        old_value = selectedValue;
	}

	function addOperatorMenu(type){
		var appendString;
		switch(type){
			case 'string':
				appendString = "<div class='col-sm-4' ><textfield id='metaOperatorField"+uniqueId+"' class='meta-element-size'>equals: </textfield> </div>";
				addValueField('==');
				break;

			case 'numerisch':
				appendString = "<div class='col-xs-4'><select id='operatorSelect"+uniqueId+"' onchange='addValueField(passMultipleArgsForSelect(this));' class='meta-element-size'><option></option><option value='==' selected>equals</option><option value='<'>less then</option><option value='>'>greater then</option><option value='<='>less/equals</option><option value='>='>greater/equals</option></select></div>";
					/*<select id='operatorSelect"+selectedValue+"' onchange='operatorSelectChanged(value);' class='meta-element-size'>
					<option></option>
					<option value='=='>equals</option>
					<option value='<'>less then</option>
					<option value='>'>greater then</option>
					<option value='<='>less/equals</option>
					<option value='>='>greater/equals</option></select>*/
				break;

			case 'datum':
				//TODO #######Datum########
				break;
		}
		$("#meta_name_operator_div").append(appendString);
	}

	function addValueField(param){
		var appendString;
        var singleFieldOperators = ["==", "<", ">", "<=", ">="];

		var argsId = $(param.elem).attr('id');
		argsId= argsId.slice(-1);
        var valueFieldExists = $(param.elem).parent().parent().next().children('#metaValueField'+argsId);

        //Das gefundene bereits vorhandene ValueField muss nun mit der neuen Auswahlersetzt
        //werden, falls es sich die Anzahl der angeforderten Inputfelder unterscheiden
        var isSingleValueFieldOperator = $.inArray(param.value, singleFieldOperators);
        if($(valueFieldExists).hasClass("singleValueField") && isSingleValueFieldOperator){
            //Feld muss nicht erneuert werden
			console.log("valueField already exists (single)");
            return;
        }else if($(valueFieldExists).hasClass("doubleValuefField") && !isSingleValueFieldOperator){
            //Feld muss nicht erneuert werden
			console.log("valueField already exists (double)");
            return;
        }

        
        //In deisem Fall muss das bestehende Feld gelöscht werden und mit einem neuen ersetzt werden!
        var previousMetaValueFieldId = $(valueFieldExists).attr('id');
		console.log("prev id: "+previousMetaValueFieldId);
		console.log("prev class: "+$(valueFieldExists).attr('class'));
		if($.inArray(param.value, singleFieldOperators) > -1){
				appendString = "<div id='metaValueField"+previousMetaValueFieldId+"' style='margin-right:10px' class='col-xs-12 singleValueField valueField'><input class='dontbewhite' type='text' placeholder='insert Value' name='stringInput"+previousMetaValueFieldId+"'></input></div>";
        }
        //TODO #######Datum####### /*vielleicht kommt noch weiterer Bedarf für andere Felder wie between in welchem Fall dann zwei Textfelder geadded werden müssen */
		$(valueFieldExists).replaceWith(appendString);
	}

    function addDefaultValueField(){
		var appendString;
		appendString = "<div id='metaValueField"+uniqueId+"' class='col-xs-12 singleValueField valueField'><input class='dontbewhite' type='text' placeholder='insert Value' name='stringInput"+uniqueId+"'></input></div>";
		$("#meta_value_div").append(appendString);
        ++uniqueId;
    }


    function passMultipleArgsForSelect(param){
        var obj = {elem:param, value:param.value};
        return obj;
    }
</script>

<?php
require_once 'footer.php';
