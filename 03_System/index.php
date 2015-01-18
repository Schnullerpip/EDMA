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
		//
		//
		//
		//
        //-----------------------Variablen zur Auswahl aus dem Select----
		var select = <?php echo $jsonselect; ?>;    //enthält den select
		var selectedMetafeld;
		var select_copy = [];		


		//filter alle Metas heraus, die es doppelt gibt
		var i;
		for(i = 0; i < select.length; i++){
			var o;
			for(o = 0; o < select_copy.length; o++){
				var already_exists = false;
				if((select[i].metaname == select_copy[o].metaname)){
					//metafield already exists!!!
					already_exists = true;
					break;
				}
			}
			if(!already_exists){
				console.log("adding new meta to select: "+select[i].metaname);
				select_copy.push(select[i]);
			}
		}
		select = select_copy;

		//Durch den folgenden Code ist nun eine array verfügbar, welche ausschließlich die verschiedenen Messreihen aufzeigt
		var messreihen = [];
		var messreihennamen = [];
		for(i = 0; i < select_copy.length; i++){
			if($.inArray(select_copy[i].messreihenname, messreihennamen) < 0){
				messreihennamen.push(select_copy[i].messreihenname);
				var tmp_array = [select_copy[i].messreihenname];
				messreihen.push(tmp_array);
				var o;
				for(o = i; o < select_copy.length; o++){
					var mname = select_copy[o].metaname;
					if((select_copy[o].messreihenname == select_copy[i].messreihenname) && ($.inArray(mname, messreihen[messreihen.length-1]) < 0)){
						messreihen[messreihen.length-1].push({name:mname, typ:select_copy[o].typ});
					}					
				}
				console.log("adding new 'messreihe' -->"+select_copy[i].messreihenname+"<-- to array 'messreihen'");
			}
		}


		var messreihen_copy = $.extend(true, [], messreihen);

		//only for debug
		/*for(i = 0; i < messreihen.length; i++){
			console.log(messreihen[i][0]);
		}*/
        //---------------------------------------------------------------
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

		<div class="form-horizontal" id="addMetaDiv">
			<div style="border:1px solid white; border-radius:5px;">
				<!-- Anzeigefelder für die ausgewählten Metadatenfilter -->
				<div class="form-group">
					<h2 style="text-align:center">Metadaten filtern</h1>
					<div class="row">
						<div id="meta_name_operator_div" class="col-xs-6"></div>
						<div id="meta_value_div" class="col-xs-6"></div>
					</div>
				</div>



				<!-- Select element und Bestätigungsbutton -->
				<div class="form-group">
					<div class="col-sm-2 col-sm-offset-4">
						<select id="selectBox" class="dontbewhite" onchange="selectChanged(value);">
						</select>
					</div>

					<div class="col-sm-4">
						<button id="meta_select_button" class="btn btn-default" onClick="addMeta();">new Metafilter</button>
					</div>
				</div>
			</div>

			<!-- Rest -->
			<div class="form-group">
				<h2 style="text-align:center">Messreihe wählen</h1>
				<div class="col-sm-12 col-md-6 col-lg-4">
				</div>
			</div>
			<div class="form-group">
				<h2 style="text-align:center">Einstellungen</h1>
				<div class="col-sm-12 col-md-6 col-lg-4">
				</div>
			</div>
	</div>

<script>
	function selectChanged(val){
		var io = val.split("");
		selectedMetafeld = messreihen_copy[io[0]][io[1]];
		selectFlag=true;
		selectChangedCount++;
	}

	function addMeta(){
		if((selectFlag==false && old_value!=selectedMetafeld) || (selectChangedCount == 0)){
            old_value = selectedMetafeld;
			return;
		}

		$("#meta_name_operator_div").append("<div id='metaNameField"+uniqueId+"' class='col-sm-8 text-right'> <textfield class='meta-element-size'>" + selectedMetafeld["name"] + "</textfield> </div>");
		
		addOperatorMenu(selectedMetafeld.typ);
		addDefaultValueField();
		selectFlag = false;
        old_value = selectedMetafeld;

		//Falls eine andere Messreihe das gewählte Metafeld nicht hat sollte diese (ihre eigenen, die wiederum kein anderer hat)aus der auswahl entfernt werden
						var to_delete = [];
						for(i = 0; i < messreihen_copy.length; i++){
							var exists_in_messreihe = false;
							for(o = 0; o < messreihen_copy[i].length; o++){
								if(messreihen_copy[i][o]["name"] == selectedMetafeld["name"]){
									exists_in_messreihe = true;
								}
							}
							if(!exists_in_messreihe){
								to_delete.push(messreihen[i]);
							}
						}
						//Jetzt wissen wir (in to_delete) welche messreihen von messreihen_copy (der Arbeitskopie)
						//gelöscht werden müssen -> anschließend muss das MetafilterSelect neu generiert werden
						var tmp_new_array = [];
						for(i = 0; i < messreihen.length; i++){
							if($.inArray(messreihen[i], to_delete) < 0){
								tmp_new_array.push(messreihen[i]);
							}
						}
						messreihen_copy = tmp_new_array;
						//Nun das SelectFeld neu generieren
						regenerateMetaSelect();
						for(i = 0; i < to_delete.length; i++){
							look_up_unique_id.push(to_delete[i]);//Für delMeta(argid) Funktion
						}
	}







	function addOperatorMenu(type){
		var appendString;
		switch(type){
			case 'string':
				appendString = "<div id='metaOperatorField"+uniqueId+"' class='col-sm-4'> <textfield class='meta-element-size'>equals: </textfield> </div>";
				break;

			case 'numerisch':
				appendString = "<div id='metaOperatorField"+uniqueId+"' class='col-xs-4'><select id='operatorSelect"+uniqueId+"' onchange='addValueField(passMultipleArgsForSelect(this));' class='meta-element-size'><option></option><option value='==' selected>equals</option><option value='<'>less than</option><option value='>'>greater than</option><option value='<='>less/equals</option><option value='>='>greater/equals</option></select></div>";
					/*<select id='operatorSelect"+selectedValue+"' onchange='operatorSelectChanged(value);' class='meta-element-size'>
					<option></option>
					<option value='=='>equals</option>
					<option value='<'>less than</option>
					<option value='>'>greater than</option>
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
        if($(valueFieldExists).hasClass("singleValueFieldERRORDELETETHIS") && isSingleValueFieldOperator){
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
		if($.inArray(param.value, singleFieldOperators) > -1){
				appendString = "<div id='metaValueField"+previousMetaValueFieldId+"' class='col-xs-12 singleValueField valueField'><input class='dontbewhite' type='text' placeholder='insert Value' name='stringInput"+previousMetaValueFieldId+"'></input> <a onclick='delMeta("+previousMetaValueFieldId+");'><span class='glyphicon glyphicon-remove-circle'></span></a></div>";
        }
        //TODO #######Datum####### /*vielleicht kommt noch weiterer Bedarf für andere Felder wie between in welchem Fall dann zwei Textfelder geadded werden müssen */
		console.log("valueFieldExists: ");
		console.log(valueFieldExists);
		$('metaValueField'+previousMetaValueFieldId).replaceWith(appendString);
	}










    function addDefaultValueField(){
		var appendString;
		appendString = "<div id='metaValueField"+uniqueId+"' class='col-xs-12 singleValueField valueField'><input class='dontbewhite' type='text' placeholder='insert Value' name='stringInput"+uniqueId+"'></input><a class='btn' onclick='delMeta("+uniqueId+");'><span class='glyphicon glyphicon-remove-circle'></span></a></div>";
		$("#meta_value_div").append(appendString);
        ++uniqueId;
    }







	function delMeta(argid){	
		var tmp_str = "metaNameField"+argid;
		var element_to_delete = document.getElementById(tmp_str);	
		console.log("########## deleted:");
		console.log(element_to_delete);
		element_to_delete.parentNode.removeChild(element_to_delete);
		
		tmp_str = "metaOperatorField"+argid;
		element_to_delete = document.getElementById(tmp_str);
		console.log(element_to_delete);
		element_to_delete.parentNode.removeChild(element_to_delete);
		
		tmp_str = "metaValueField"+argid;
		element_to_delete = document.getElementById(tmp_str);
		console.log(element_to_delete);
		console.log("##########");
		element_to_delete.parentNode.removeChild(element_to_delete);

		//TODO regenerate #selectBox da nun vorherig weggefallene messreihen wieder erlaubt sein können	
		var to_remove_from_look_up= [];
		for(i = 0; i < look_up_unique_id; i++){
			messreihen_copy.push(look_up_unique_id[i]);
			to_remove_from_look_up.push(i);
		}
		//look_up_unique_id aufräumen
		look_up_unique_id = [];		
		regenerateMetaSelect();
	}	






    function passMultipleArgsForSelect(param){
        var obj = {elem:param, value:param.value};
        return obj;
    }





	function regenerateMetaSelect(){
		var replace_string = "<select id='selectBox' class='dontbewhite' onchange='selectChanged(value);'><option></option>";
		var tmp_array = [];
		var i;
		for(i = 0; i < messreihen_copy.length; i++){
			var o;
			for(o = 1; o < messreihen_copy[i].length; o++){
				if($.inArray(messreihen_copy[i][o]) < 0){
					tmp_array.push(messreihen_copy[i][o]);
					replace_string += "<option id='selectOption"+(uniquei++)+" class='dontbewhite' value='"+i+""+o+"'>"+messreihen_copy[i][o]["name"]+"</option>";
				}
			}
		}
		replace_string += "</select>";

		$("#selectBox").replaceWith(replace_string);
	}







	$(function(){
		regenerateMetaSelect();
	});

</script>

<?php
require_once 'footer.php';
