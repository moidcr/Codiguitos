<?php
/* Copyright (C) 2024 Doli Admin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    multidivisionssales/class/actions_multidivisionssales.class.php
 * \ingroup multidivisionssales
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * Class ActionsMultiDivisionsSales
 */
class ActionsMultiDivisionsSales
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    public function beforeBodyClose(&$parameters){
		
		global $conf, $user, $langs, $db, $categories, $MAXCATEG, $MAXPRODUCT, $place, $id;
		
        $langs->load("multidivisionssales@multidivisionssales");
        
		$currurl = $_SERVER['PHP_SELF'];
        
        //$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Bar or Restaurant
			$placeid = 0; // $placeid is ID of invoice
			$invoiceid = GETPOST('invoiceid', 'int');
			
			$invoice = new Facture($db);
			if ($invoiceid > 0) {
				$ret = $invoice->fetch($invoiceid);
			} else {
				$ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
			}
			if ($ret > 0) {
				$placeid = $invoice->id;
			}
		 
		
		if(strpos($currurl,"takepos/index.php")>=1){
		$script='
		<script>
			$(function() {
            
            
            
                setTimeout(() => {
                    if($("#tablelines").length > 0){
				        $("#search").before("<input type=\"text\" id=\"ncomensales\" name=\"ncomensales\" class=\"input-search-takepos\" onchange=\"SaveCommensales();\" placeholder=\"'.$langs->trans('ncomensales').'\" value=\"'.$invoice->array_options["options_ncomensales"].'\">");
                    }
                }, "500");
									
				//setInterval(function () {SetCarPlaque()}, 1000);
                
				//setTimeout(() => {
 
				if($(".div3 button").length>0){
					var bots = 0;
					for(var btn_ of $(".div3 button")){
						
						';
						$script.= '
                        console.log(btn_);
						if($(btn_).attr("onclick") == "Split();"){
							$(btn_).attr("onclick", "MultiSplit();");
						}
						';

					$script.= '}
				}
			 console.log("Retrasado por 1 segundo.");
             //}, "2000");
			});
            
            function MultiSplit(){
	           filter_lugar();
                ShowWinDivisions();
            }
            
            function GoMultiSplit(placem, invoiceidm) {
            $(".close_mdiv").click();
            //invoiceid = $("#invoiceid").val();
            console.log("Open popup to split on invoiceid="+invoiceidm);
            $.colorbox({href:"'.DOL_URL_ROOT.'/custom/multidivisionssales/split.php?place="+placem+"&invoiceid="+invoiceidm, width:"80%", height:"90%", transition:"none", iframe:"true", title:""});
            
            
        }
        
        function SaveCommensales(){
            
                var datos = {
                    token:  "'.currentToken().'",
                    action: "SaveCommensales",
                    terminal: "'.$_SESSION["takeposterminal"].'",
                    ncomensales: $("#ncomensales").val(),
                    place: place
                    
                };
                
                $.ajax({
                    type: "POST",
                    url: "'.DOL_URL_ROOT.'/custom/multidivisionssales/ajax/multidivisionssales.php",
                    data: datos,
                    success: function(data, status, xhr) {
                        var obj = JSON.parse(data); //if the dataType is not specified as json uncomment this

                        if(obj.success == "OK" )
                        {
                            //$("#multi_divisions_bills").html(obj.list);
                            
                        }
                        else{
                            //alert(obj.message);
                        }


                    },
                    error: function() {
                        alert("error handling here");
                    }
                });
            }
            
           
            
		</script>';
            
        echo $this->WinDivisions();
		
		echo $script;
        
        if(GETPOSTISSET('ht') && !empty(GETPOST('ht')) && isset($_SESSION['MULTTERM'.GETPOST('ht')]) && $_SESSION['MULTTERM'.GETPOST('ht')] == 'Y'){
            
            
            $script2 = '
            
            <script>
			$(function() {
            $("#poslines").load("invoice.php?action=history&placeid='.GETPOST('ht').'&fromSpl=Y", function() {parent.$.colorbox.close();});
            })
            </script>
            ';
            
            echo $script2;
            unset($_SESSION['MULTTERM'.GETPOST('ht')]);
        }
            
            
            
            
	   return 0;
	   }
        
        //==============CASH CONTROL===========
        if (in_array($parameters['currentcontext'], array('cashcontrolcard')) || in_array('cashcontrolcard', explode(':', $parameters['context']))) {
            
            $langs->loadLangs(array("install", "cashdesk", "admin", "banks"));
            
            $script = '
            <script>
            $(function() {
                var linka = $("a").filter(function(index) { return $(this).text() === "'.$langs->trans('PrintTicket').'"; });
                console.log(linka);
                if(linka != undefined && linka != "undefined"){
                    linka.attr("href", "'.DOL_URL_ROOT.'/custom/multidivisionssales/report.php?id='.$id.'");
                }
            })
            </script>
            ';
            echo $script;
        }
        //==============CASH CONTROL===========
    }
    
    public function get_floors(){
        global $db, $conf;
        $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."takepos_floor_tables WHERE entity = " . ((INT) $conf->entity) . " order by rowid ";
        $resql = $db->query($sql);
        $floors = array(0);
        while ($row = $db->fetch_array($resql)) {
            $floors[$row['rowid']] = $row['label'];
        }

        return $floors;
    }
    
    public function WinDivisions(){
        global $conf, $user, $langs, $place;
        
        $langs->load("multidivisionssales@multidivisionssales");
        
        $colorbackhmenu1 = '60,70,100'; // topmenu
        
        $colorbackhmenu1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);
        
        $places = $this->get_floors();
        
        
        $pos = strpos($place, 'SPLIT');
        $place_curr = $place;       
        //echo $pos."===".$place;
        if($pos !== false){
            $invoice = new Facture($this->db);
            $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"]."-".$place.')');
            $place_curr = $invoice->array_options['options_place_nx'];
        }
        
        
        /*if(isset($params['invoiceid']) && !empty($params['invoiceid'])){
            $sql .= " AND f.rowid <> " . ((INT) $params['invoiceid']);
        }*/
        
        $acc = 2;
        $bills = array();
        $result = $this->db->query($sql);
        $objk = $this->db->fetch_object($result);
        
        
        
        //echo "========".$place."=="; 
        $opts_places = '<option value="-1">'.$langs->trans("All").'</option>';
        foreach($places as $pla => $place_){
            $opts_places .= '<option value="'.$pla.'" '.($place_curr == $pla ? "selected" : "").'>'.$place_.'</option>';
        }
        
        $html = '
        
        <div class="sidebar_mdiv">
            
            <h2 class="title_mdiv">
            <span id="place_tit_mdiv" style="color:#fff">'.$langs->trans("Accounts_mult").'</span>
            <button class="close_mdiv"><i class="fa fa-times-circle" aria-hidden="true"></i></button>
            </h2>
            
            <div class="menu-bar_mdiv">
            <div class="search_lugar_mdiv">
            <table width="100%">
                <tr>
                    <td>'.$langs->trans("Place").': 
                    </td>
                    <td>
                    <select id="lugar_mdiv" onclick="filter_lugar()">
                        '.$opts_places.'
                    </select>
                    </td>
                    <td>
                    <input type="text" id="search_acc" name="search_acc" class="input-search-takepos"  placeholder="Buscar" autofocus=""  onkeyup="doSearchMdiv()">
                    </td>
                </tr>
            </table>
            </div>
            <br>
            <br>
            <br>
                <span id="multi_divisions_bills">
                
                </span>

                
            </div>
        </div>

        <div class="overlay_mdiv"></div>
        
        
        ';
        
        $css = '
        <style>
        .title_mdiv{
            background-color: rgba('.$colorbackhmenu1.');
            height: 52px;
            position: fixed;
            width: calc(100% - 65%);
        }
        
        .search_lugar_mdiv{
             position: fixed;
             width: calc(100% - 65%);
             background-color: rgba(255,255,255) !important;
             height: 50px;
        }
        
        .sidebar_mdiv {
            position: fixed;
            top: 0;
            left: -85%;
            text-align: center;
            width: calc(100% - 65%);
            height: 100vh;
            background: #EEE;
            box-shadow: 0 0 8px rgba(2, 4, 3, 0.5);
            transition: 0.5s ease;
            z-index: 9;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar_mdiv.active_mdiv {
            left: 0;
        }

        .sidebar_mdiv .title_mdiv {
            margin-top: 0%;
            
        }

        .sidebar_mdiv .menu-bar_mdiv {
            margin: 10% auto;
        }

        .menu-bar_mdiv .bar_mdiv {
            position: relative;
            font-size: 14px;
            text-transform: capitalize;
            width: calc(100% - 30%);
            margin: 5% auto;
            padding: 6px 0;
            color: black;
            background: white;
            border-radius: 15px;
        }

        .bar_mdiv:hover {
            background: rgb(0, 128, 0, 0.5);
        }

        .bar_mdiv .icon {
            position: absolute;
            left: 8%;
        }

        .overlay_mdiv {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 4, 3, 0.5);
        }

        .overlay_mdiv.active_mdiv {
            display: block;
        }

        .close_mdiv {
            display: none;
            background: transparent;
            border-color: transparent;
            position: absolute;
            top: 10%;
            right: 3%;
            color: white;
            font-size: 25px;
            cursor: pointer;
            z-index: 9;
        }

        .close_mdiv.active_mdiv {
            display: block;
        }
        
        #multi_divisions_bills table {
          border-collapse: collapse;
          width: 100%;
        }

        #multi_divisions_bills th, #multi_divisions_bills td {
          text-align: left;
          padding: 8px;
        }

        #multi_divisions_bills tr:nth-child(even) {background-color: #fff;}
        
        .manito{
            cursor:pointer;
        }
        </style>
        ';
        
        $js_int = '
        <script>
            var token_var = "'.currentToken().'";
            var session_var = "'.$_SESSION["takeposterminal"].'";
            var DOL_root = "'.DOL_URL_ROOT.'";
        </script>
        ';
        
        $js = '
        <script>
            const sidemenu = document.querySelector(".sidebar_mdiv");
            const closeX = document.querySelector(".close_mdiv");
            const overlay = document.querySelector(".overlay_mdiv");

            function ShowWinDivisions(){
              sidemenu.classList.toggle("active_mdiv");
              closeX.classList.toggle("active_mdiv");
              overlay.classList.toggle("active_mdiv");
            };

            closeX.addEventListener("click", () => {
              sidemenu.classList.remove("active_mdiv");
              closeX.classList.remove("active_mdiv");
              overlay.classList.remove("active_mdiv");
            });
            
            function doSearchMdiv()
            {
                const tableReg = document.getElementById("datos_mdiv");
                const searchText = document.getElementById("search_acc").value.toLowerCase();
                let total = 0;
                
                // Recorremos todas las filas con contenido de la tabla
                for (let i = 1; i < tableReg.rows.length; i++) {
                
                    // Si el td tiene la clase "noSearch" no se busca en su cntenido
                    if (tableReg.rows[i].classList.contains("noSearch")) {
                        continue;
                    }

                    let found = false;
                    const cellsOfRow = tableReg.rows[i].getElementsByTagName("td");
                    
                    // Recorremos todas las celdas
                    for (let j = 0; j < cellsOfRow.length && !found; j++) {
                        const compareWith = cellsOfRow[j].innerHTML.toLowerCase();
                        
                        // Buscamos el texto en el contenido de la celda
                        if (searchText.length == 0 || compareWith.indexOf(searchText) > -1) {
                            found = true;
                            total++;
                        }
                    }

                    if (found) {
                        tableReg.rows[i].style.display = "";
                    } else {
                        // si no ha encontrado ninguna coincidencia, esconde la
                        // fila de la tabla
                        tableReg.rows[i].style.display = "none";
                    }
                }
                
                // mostramos las coincidencias
                /*const lastTR=tableReg.rows[tableReg.rows.length-1];
                const td=lastTR.querySelector("td");
                lastTR.classList.remove("hide", "red");

                if (searchText == "") {
                    lastTR.classList.add("hide");
                } else if (total) {
                    td.innerHTML="Se ha encontrado "+total+" coincidencia"+((total>1)?"s":"");
                } else {
                    lastTR.classList.add("red");
                    td.innerHTML="No se han encontrado coincidencias";
                }*/
            }
            
            function filter_lugar(){
            
                var datos = {
                    token:  token_var,
                    action: "getAccountsList",
                    terminal: session_var,
                    lugar: $("#lugar_mdiv").val(),
                    
                };
                
                $.ajax({
                    type: "POST",
                    url: DOL_root+"/custom/multidivisionssales/ajax/multidivisionssales.php",
                    data: datos,
                    success: function(data, status, xhr) {
                        var obj = JSON.parse(data); //if the dataType is not specified as json uncomment this

                        if(obj.success == "OK" )
                        {
                            $("#multi_divisions_bills").html(obj.list);
                            
                        }
                        else{
                            alert(obj.message);
                        }


                    },
                    error: function() {
                        alert("error handling here");
                    }
                });
            }
            
             function setAccountFacture(){
            
                var datos = {
                    token:  token_var,
                    action: "setAccountFacture",
                    terminal: session_var,
                    
                };
                
                $.ajax({
                    type: "POST",
                    url: DOL_root+"/custom/multidivisionssales/ajax/multidivisionssales.php",
                    data: datos,
                    success: function(data, status, xhr) {
                        var obj = JSON.parse(data); //if the dataType is not specified as json uncomment this

                        if(obj.success == "OK" )
                        {
                            //$("#multi_divisions_bills").html(obj.list);
                            
                        }
                        else{
                            //alert(obj.message);
                        }


                    },
                    error: function() {
                        alert("error handling here");
                    }
                });
            }
        </script>
        ';
        
        return $html.$css.$js_int.$js;
    }
    
    public function get_min_max_bills_terminal($terminal){
        
        global $conf;
        
        $sql = "SELECT MIN(rowid) as min, MAX(rowid) as max";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as t";
        $sql .= " WHERE t.pos_source = ".((int) $terminal);
        $sql .= " AND ifnull(t.date_valid,'') = '' ";
        $sql .= " AND entity = " . ((INT) $conf->entity);

        $last = null;
        $first = null;

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $first = $obj->min;
                $last = $obj->max;
            }
        }
        
        $min_max = array(
            'last' => $last,
            'first' => $first
        );
        
        
        return $min_max;
    }
    
    public function get_all_bills_terminal($params){
        global $conf;
        
        /*$sql = 'SELECT f.rowid, f.ref, x.account_nx ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as x ON f.rowid = x.fk_object';
        $sql .= " WHERE f.pos_source = ".((int) $params['terminal']);
        $sql .= " AND ifnull(f.date_valid,'') = '' ";
        $sql .= " AND ifnull(f.ref,'') like '%(PROV-POS%' ";
        $sql .= " AND entity = " . ((INT) $conf->entity);
        if(isset($params['invoiceid']) && !empty($params['invoiceid'])){
            $sql .= " AND f.rowid <> " . ((INT) $params['invoiceid']);
        }*/
        $filterinv = "";
        
        
        if(isset($params['invoiceid']) && !empty($params['invoiceid'])){
            $filterinv = " AND f.rowid <> '" . ((INT) $params['invoiceid']) ."' ";
        }
        
        $sql = "
            select *, (SELECT label FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid = tr1.place_org limit 1) as placel from (
            
            SELECT f.rowid, f.ref, f.fk_user_author as author, 'org' as type, SUBSTRING_INDEX(SUBSTRING_INDEX(f.ref, '(PROV-POS".$params['terminal']."-', -1), ')', 1) as place_org, 1 as account_nx, SUBSTRING_INDEX(SUBSTRING_INDEX(f.ref, '(PROV-POS".$params['terminal']."-', -1), ')', 1) as place_bill FROM ".MAIN_DB_PREFIX."facture f LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as x ON f.rowid = x.fk_object  where (f.ref like'%(PROV-POS".$params['terminal']."%') AND ifnull(x.account_nx,'') = '' AND ifnull(x.place_nx,'') = ''  AND entity = " . ((INT) $conf->entity) . "  
            
            $filterinv
            
            UNION ALL
            
            SELECT f.rowid, f.ref, f.fk_user_author as author, 'split' as type, x.place_nx as place_org, x.account_nx as account_nx, concat('SPLIT',x.account_nx) as place_bill FROM ".MAIN_DB_PREFIX."facture f JOIN ".MAIN_DB_PREFIX."facture_extrafields as x ON f.rowid = x.fk_object where (f.ref like '%(PROV-POS".$params['terminal']."-SPLIT%') AND ifnull(x.account_nx,'') <> '' AND ifnull(x.place_nx,'') <> ''  AND f.entity = " . ((INT) $conf->entity) . " 
            
            $filterinv
            
            ) tr1 order by place_org, account_nx
            ";
        
        //echo $sql;
        
        $bills = array();
        $result = $this->db->query($sql);
        if ($result)
        {
            $i = 0;
            $num = $this->db->num_rows($result);		
            while ($i < $num)
            {   
                
                $objp = $this->db->fetch_object($result);
                $objp->place = $this->get_ref_parts($params['terminal'], $objp->rowid);
                $bills[$objp->rowid] = $objp;
                $i++;
            }
        }

        
        return $bills;
    }
    
    public function get_tables_floors(){
        global $db, $conf;
        $sql = "SELECT rowid, label, floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables  where entity = " . ((INT) $conf->entity);
        $resql = $db->query($sql);
        $floors_tbl = array(0);
        while ($row = $db->fetch_array($resql)) {
            
            if(!isset($floors_tbl[$row['floor']])){
                $floors_tbl[$row['floor']] = array();
            }
            $floors_tbl[$row['rowid']] = $row['label'];
        }

        return $floors_tbl;
    }
    
    public function get_ref_parts($terminal, $invoiceid){
        
        $place = null;
        $invoice = new Facture($this->db);
		$ret = $invoice->fetch($invoiceid);
		if ($ret > 0) {
            $ind =  "(PROV-POS".$terminal."-";
            $part = explode($ind, $invoice->ref);
            
            $place = str_replace(")", "", $part[1]);
        }
        
        return $place;
        
    }
    
    public function get_number_account($terminal, $place){
        global $conf;
        
        $sql = 'SELECT f.rowid, f.ref, x.account_nx ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as x ON f.rowid = x.fk_object';
        $sql .= " WHERE f.pos_source = ".((int) $terminal);
        $sql .= " AND ifnull(f.date_valid,'') = '' ";
        $sql .= " AND ifnull(f.ref,'') like '%(PROV-POS".$terminal."-SPLIT%' ";
        $sql .= " AND entity = " . ((INT) $conf->entity);
        $sql .= " ORDER BY x.account_nx ASC ";
        
        /*if(isset($params['invoiceid']) && !empty($params['invoiceid'])){
            $sql .= " AND f.rowid <> " . ((INT) $params['invoiceid']);
        }*/
        
        $acc = 2;
        $bills = array();
        $result = $this->db->query($sql);
        if ($result)
        {
            $i = 0;
            $num = $this->db->num_rows($result);		
            while ($i < $num)
            {   
                
                $objp = $this->db->fetch_object($result);
                $bills[$objp->rowid] = $objp->account_nx;
                $i++;
            }
        }
        
        for($i=2; $i<100; $i++){
            if(!in_array($i,$bills))
            {
                return $i;
            }
        }
    }
    
    public function doActions($parameters, $invoice, $action){
        
        global $action, $langs, $user, $place;
        
        
        //print_r($parameters);
        
        //echo $action."==========";
        
        //Cuando pagan una factura split automáticamente redirigo a el lugar de origen para evitar que carguen nuevas ventas en ese place que es un SPLIT
        if ((in_array($parameters['currentcontext'], array('takeposinvoice')) || in_array('takeposinvoice', explode(':', $parameters['context']))) && !empty($invoice->array_options['options_place_nx'])) {
            
            $_SESSION['MULTTERM_PLACEBK'] = $invoice->array_options['options_place_nx'];
            
        }
    }
    
    public function completeTakePosInvoiceHeader($parameters, $invoice, $action){
        
        global $action, $langs, $user, $place;
        
        
        //print_r($parameters);
        
        //echo $action."==========";
        
        //Cuando pagan una factura split automáticamente redirigo a el lugar de origen para evitar que carguen nuevas ventas en ese place que es un SPLIT
        if ((in_array($parameters['currentcontext'], array('takeposinvoice')) || in_array('takeposinvoice', explode(':', $parameters['context']))) && !empty($invoice->array_options['options_place_nx']) && $action == 'valid') {
            
            $_SESSION['MULTTERM'.$invoice->id] = 'Y';
            
            $script = '
            <script>
                parent.location.href="'.DOL_URL_ROOT.'/takepos/index.php?place='.$invoice->array_options['options_place_nx'].'&ht='.$invoice->id.'";
            </script>
            ';
            
            echo $script;
            exit;
        }
        
        if ((in_array($parameters['currentcontext'], array('takeposinvoice')) || in_array('takeposinvoice', explode(':', $parameters['context']))) && !empty($_SESSION['MULTTERM_PLACEBK']) && count($invoice->lines) == 0) {
            
            $script = '
            <script>
                parent.location.href="'.DOL_URL_ROOT.'/takepos/index.php?place='.$_SESSION['MULTTERM_PLACEBK'].'";
            </script>
            ';
            unset($_SESSION['MULTTERM_PLACEBK']);
            echo $script;
            exit;
        }
        
        //Si es una factura SPLIT, dividida entonces busco cual es su lugar de origen para indicarlo en la pantalla
        $post = strpos($place, 'SPLIT');
        
              if($post !== false){
            
                   
            $descrip = $this->get_description_place();
            
                 // echo $place ."======".$descrip; exit; 
                  
            $script3 = '
                <script>
                    $("#tablelines .linecoldescription").html("'.$descrip.'");

                </script>
                ';

                echo $script3;
        }
        
        //Activo la impresión automática si es una redirección luego de pagar una factura split, viene de beforeBodyClose
        if ((in_array($parameters['currentcontext'], array('takeposinvoice')) || in_array('takeposinvoice', explode(':', $parameters['context']))) && $action == 'history' && GETPOSTISSET('fromSpl') && GETPOST('fromSpl') == 'Y') {
            
            $remaintopay = $invoice->getRemainToPay();
            
            if ($remaintopay <= 0 && getDolGlobalString('TAKEPOS_AUTO_PRINT_TICKETS') ) {
			 echo  '<script type="text/javascript">setTimeout(() => {$("#buttonprint").click();}, "100");</script>';
		      }
            
        }
         
        if(1){
            
            
            
                $script = '
                <script>
                    setTimeout(() => {
                    SaveCommensales();
                    setAccountFacture();
                    }, "1 segundo");

                </script>
                ';

                echo $script;

        }
        
        
        
    }
    
    function get_description_place(){
        global $langs, $place, $conf, $mobilepage, $invoice, $mysoc;
        
        $descrip = '';
        // In phone version only show when it is invoice page
        if (empty($mobilepage) || $mobilepage == "invoice") {
            $descrip.= '<input type=\"hidden\" name=\"invoiceid\" id=\"invoiceid\" value=\"'.$invoice->id.'\">';
        }
        if (getDolGlobalString('TAKEPOS_BAR_RESTAURANT')) {
            $sql = "SELECT place_nx, account_number FROM ".MAIN_DB_PREFIX."facture_extrafields where fk_object=".((int) $invoice->id);
            $resql = $this->db->query($sql);
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $label = $obj->account_number;
                $floor = $obj->place_nx;
            }
            if ($mobilepage == "invoice" || $mobilepage == "") {
                // If not on smartphone version or if it is the invoice page
                //print 'mobilepage='.$mobilepage;
                $descrip.=  '<span class=\"opacitymedium\">'.$langs->trans('Place')."</span> <b>".(empty($label) ? '?' : $label)."</b><br>";
                $descrip.=  '<span class=\"opacitymedium\">'.$langs->trans('Floor')."</span> <b>".(empty($floor) ? '?' : $floor)."</b>";
            } elseif (defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
                $descrip.=  $mysoc->name;
            } elseif ($mobilepage == "cats") {
                $descrip.=  $langs->trans('Category');
            } elseif ($mobilepage == "products") {
                $descrip.=  $langs->trans('Label');
            }
        } else {
            $descrip.=  $langs->trans("Products");
        }
        
        
        return $descrip;
    }
    
}
