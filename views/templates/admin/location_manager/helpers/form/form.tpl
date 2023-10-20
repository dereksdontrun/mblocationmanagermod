{*
 * Warehouse Location Manager powered by Modulebuddy™ (www.modulebuddy.com)
 *
 *  @author    Modulebuddy™ <info@modulebuddy.com>
 *  @copyright 2015-2016 Modulebuddy™
 *  @license   Check License.txt file in module root directory for End User License Agreement.
 *}
{extends file="helpers/form/form.tpl"}
{block name="other_fieldsets"}

    <input type="hidden" id="product_ids" name="product_ids" value="" />
    <input type="hidden" id="product_ids_to_delete" name="product_ids_to_delete" value="0" />
    <input type="hidden" name="supply_order" value="1" />

    <div class="panel">
        <table id="products_in_search" class="table">
            <caption style="text-align:left"><h2><span id='num_prods'></span></h2></caption> 
            <thead>                
                <tr class="nodrag nodrop">
                    <th class="fixed-width-xs"><span class="title">ID</span></th>
                    <th class="fixed-width-sm"><span class="title">{$i18n.product|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-lg text-center"><span class="title">{$i18n.reference|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-lg text-center"><span class="title">{$i18n.name|escape:'htmlall':'UTF-8'}</span></th>                    
                    <th class="fixed-width-sm"><span class="title">{$i18n.location|escape:'htmlall':'UTF-8'}</span></th> 
                    <!--<th><span class="title_box">{$i18n.location_cantidad|escape:'htmlall':'UTF-8'}</span></th>--> 
                    <th class="fixed-width-sm text-center"><span class="title">{$i18n.ean|escape:'htmlall':'UTF-8'}</span></th>     
                    <th class="fixed-width-sm"><span class="title">{$i18n.supplier_reference|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-sm"><span class="title">{$i18n.location_repo|escape:'htmlall':'UTF-8'}</span></th> 
                    <!--<th><span class="title_box">{$i18n.location_repo_cantidad|escape:'htmlall':'UTF-8'}</span></th>--> 
                    <!--<th><span class="title">{$i18n.weight|escape:'htmlall':'UTF-8'}</span></th>-->
                    <th class="fixed-width-xs text-center"><span class="title">ABC</span></th>
                    <th class="fixed-width-xs"><span class="title_box">{$i18n.ava_quantity|escape:'htmlall':'UTF-8'}<br>( Online )</span></th>
                    <th class="fixed-width-xs"><span class="title_box">{$i18n.phy_quantity_online|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-xs"><span class="title_box">{$i18n.phy_quantity_fisica|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-xs"><span class="title_box">Permite<br>pedidos</span></th>
                    <th class="text-center"><span class="title_box">{$i18n.gestion_stock|escape:'htmlall':'UTF-8'}</span></th>
                    <th class="fixed-width-xs text-center"><span class="title">Imprimir Etiquetas</span></th>
                    <th class="fixed-width-sm">&nbsp;</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

    </div>

    <script type="text/javascript">
        product_infos = null;
        debug = null;
    
        if ($('#product_ids').val() == '')
            product_ids = [];
        else
            product_ids = $('#product_ids').val().split('|');

        function addProduct(product_infos)
        {
            //limitamos el tamaño de la imagen para que salgan bien los GIF con width y height (No sé si aquí es válido...)
            var img = '<img itemprop="image" src="{$img_prod_dir|escape:'html':'UTF-8'}{$lang_iso|escape:'html':'UTF-8'}-default-small_default.jpg"   width="98" height="98"/>';

            var packs = parseInt(product_infos.es_pack); //Si el producto es pack, no se podrá gestionar su stock, abajo al pintar las lineas de producto se meterá el select de gestión de stock o un mensaje que diga PACK. En actualización 28/03/2019 he quitado los packs antiguos y los del módulo de advanced packs para que no puedan salir en la select, de modo que esto es inocuo.

            if (packs == 0){    
                //17/06/2021 Hemos añadido la posibiidad de ponerse en almacén Múgica y buscar los productos de un pedido de tienda creado para reponer en tienda. Cuando se busque en ese input, los productos vendrán con una variable product_infos.pedido_para_tienda = 1, que nos indica que el gestionador de stock debe ser diferente, ya que al mostrar todos los productos independientemente de si tienen o no el almacén tienda, puede permitr sumar y restar a online, y solo queremos que permita a Tienda en ese caso.
                if (product_infos.pedido_para_tienda) {
                    //tenemos que forzar que solo se pueda sumar o restar a almacén Tienda, y si no lo tiene, que haya que asignarlo primero. Si el producto tiene ambos almacenes, haremos que opere sobre id_warehouse = 4, si solo tiene tienda Múgica también. Si solo tiene online, solo daremos la opción de Asignar Tienda Múgica.
                    //variable para almacenar las operaciones en el select, sumar y restar, serán ambas si tiene elalmacén tienda, y ninguna si no lo tiene
                    var operaciones = '';

                    //comprobamos los almacenes asignados, si solo tiene uno no puede hacer transferencia con lo que no mostramos la opción en el select. Si tiene tienda mugica permitirá añadir almacén online y viceversa
                    if (product_infos.almacenes) {                         
                        var almacenes = '<option value="10" style="color:red;">SEPARADOR PARA NACHO</option><option value="3">Almacén a Tienda</option><option value="4">Tienda a Almacén</option>';
                        var noAsignadoOnline = '';
                        var noAsignadoTienda = '';
                        var operaciones = '<option value="1">Sumar stock Tienda</option>'+
                                '<option value="2">Restar stock Tienda</option>';
                    } else {                               
                        //Comprobamos cual es el almacén que SI tiene asignado y según el caso mostramos una opción de select u otra, y además mostraremos junto al stock del almacén que no está asignado un mensaje avisando que no lo tiene asignado. Al solo haber un almacén se habrá almacenado en la primera posición del array almacenAsignado[]
                        if (product_infos.almacenAsignado[0] == 1){ //tiene asignado almacén tienda online
                            var operaciones = '';
                            var almacenes = '<option value="5">Asignar almacén Tienda Múgica</option>';
                            var noAsignadoTienda = '<label id="label_tienda_fisica_'+product_infos.id+'" for="fisica_tienda_'+product_infos.id+'">Almacén NO asignado</label>';
                            var noAsignadoOnline = '';
                        } else if (product_infos.almacenAsignado[0] == 4){  //tiene asignado almacén tienda física Múgica
                            var operaciones = '<option value="1">Sumar stock Tienda</option>'+
                                '<option value="2">Restar stock Tienda</option>';
                            var almacenes = '<option value="6">Asignar almacén Tienda Online</option>';
                            var noAsignadoTienda = '';
                            var noAsignadoOnline = '<label id="label_tienda_online_'+product_infos.id+'" for="fisica_online_'+product_infos.id+'">Almacén NO asignado</label>';                        
                        }                    
                    }

                    //aqui forzamos los datos para que opere sobre el almacén Tienda, id_warehouse 4
                    var gestionStock = 
                    '<td>'+             
                        '<div class="form-group fixed-width-md" style="margin: 0 auto; float: none;">'+
                            '<select id="select_gestion_stock_'+product_infos.id+'" class="mdb-select" name="select_gestion_stock_'+product_infos.id+'">'+
                                '<option value="" disabled selected>Operación:</option>'+
                                operaciones+
                                almacenes+
                            '</select>'+
                            '<input type="text" name="input_cantidad_a_gestionar_'+product_infos.id+'" value=""  placeholder="Cantidad"/>'+
                            '<input id="escondido_operacion_'+product_infos.id+'" type="hidden" name="input_tipo_operacion_'+product_infos.id+'" value=""/>'+
                            '<input id="escondido_warehouse" type="hidden" name="input_id_warehouse_'+product_infos.id+'" value="4"/>';
                        '</div>'+
                    '</td>';



                } else { //si la busqueda no era sobre produtos de un pedido para tienda, procesamos los datos normalmente
                    //si no es pack comprobamos los almacenes asignados, si solo tiene uno no puede hacer transferencia con lo que no mostramos la opción en el select. En product_infos.almacenes hemos guardado 1 si hay más de un almacén y 0 si uno o ninguno. Si el valor es 0, mostraremos en el selct otra opción que servirá para añadir el otro almacén al producto. Si tiene tienda mugica permitirá añadir almacén online y viceversa
                    if (product_infos.almacenes) { 
                        //alert('product_infos.almacenAsignado: '+product_infos.almacenAsignado[0]+' '+product_infos.almacenAsignado[1]);
                        var almacenes = '<option value="10" style="color:red;">SEPARADOR PARA NACHO</option><option value="3">Almacén a Tienda</option><option value="4">Tienda a Almacén</option>';
                        var noAsignadoOnline = '';
                        var noAsignadoTienda = '';
                        
                    } else {  
                        //alert('product_infos.almacenAsignado: '+product_infos.almacenAsignado[0]);           
                        //Comprobamos cual es el almacén que SI tiene asignado y según el caso mostramos una opción de select u otra, y además mostraremos junto al stock del almacén que no está asignado un mensaje avisando que no lo tiene asignado
                        if (product_infos.almacenAsignado[0] == 1){ //tiene asignado almacén tienda online
                            var almacenes = '<option value="5">Asignar almacén Tienda Múgica</option>';
                            var noAsignadoTienda = '<label id="label_tienda_fisica_'+product_infos.id+'" for="fisica_tienda_'+product_infos.id+'">Almacén NO asignado</label>';
                            var noAsignadoOnline = '';
                        } else if (product_infos.almacenAsignado[0] == 4){  //tiene asignado almacén tienda física Múgica
                            var almacenes = '<option value="6">Asignar almacén Tienda Online</option>';
                            var noAsignadoTienda = '';
                            var noAsignadoOnline = '<label id="label_tienda_online_'+product_infos.id+'" for="fisica_online_'+product_infos.id+'">Almacén NO asignado</label>';                        
                        }                    
                    }

                    //Para evitar confusión, al select de añadir o quitar stock le añadimos a que almacén va dirigido el stock a añadir o quitar. Si el almacén seleccionado en el select principal arriba es almacén tienda física múgica, el stock que se manipula es el de tienda física, si es online será ese.
                    if (product_infos.idwarehouse == 1) {
                        var sumastock = 'Sumar stock Almacén';
                        var restastock = 'Restar stock Almacén';
                    } else if (product_infos.idwarehouse == 4) {
                        var sumastock = 'Sumar stock Tienda';
                        var restastock = 'Restar stock Tienda';
                    }
                    
                    var gestionStock = 
                    '<td>'+             //añadimos a cada select e input hidden el product_id para que sean únicos product_infos.id
                        '<div class="form-group fixed-width-md" style="margin: 0 auto; float: none;">'+
                            '<select id="select_gestion_stock_'+product_infos.id+'" class="mdb-select" name="select_gestion_stock_'+product_infos.id+'">'+
                                '<option value="" disabled selected>Operación:</option>'+
                                '<option value="1">'+sumastock+'</option>'+
                                '<option value="2">'+restastock+'</option>'+
                                almacenes+
                            '</select>'+
                            '<input type="text" name="input_cantidad_a_gestionar_'+product_infos.id+'" value=""  placeholder="Cantidad"/>'+
                            '<input id="escondido_operacion_'+product_infos.id+'" type="hidden" name="input_tipo_operacion_'+product_infos.id+'" value=""/>'+
                            '<input id="escondido_warehouse" type="hidden" name="input_id_warehouse_'+product_infos.id+'" value="'+product_infos.idwarehouse+'"/>';
                        '</div>'+
                    '</td>';

                }               


            }else{ //si es pack solo muestra ES PACK en gestión de stock y tampoco añade el check
                var gestionStock = '<td><div class="form-group fixed-width-md" style="margin: 0 auto; float: none;">ES PACK</div></td>';
                /*var agregartienda = '';
                var checktiendahidden = '';*/
            }

            //Si hacemos la busqueda en el almacén tienda física múgica (id=4) no queremos que nos muestre la localización de picking ni reposición ya que no la usamos, mostraremos TIENDA en lugar de la casilla de formulario
            if (product_infos.idwarehouse == 1) {
                var local_pic = '<td><div class="input-group fixed-width-md"><input type="text" name="input_location_'+product_infos.id+'" value="'+product_infos.location+'" /></div></td>';
                var local_rep = '<td><div class="input-group fixed-width-md"><input type="text" name="input_r_location_'+product_infos.id+'" value="'+product_infos.r_location+'" /></div></td>';
            } else {
                var local_pic = local_rep = '<td><div class="form-group fixed-width-md" style="margin: 0 auto; float: none;">TIENDA</div></td>';
            }

            //En la columna Disponible, con el stock total disponible, queremos poner entre paréntesis el stock online disponible, que es el resultado de restar al disponible total el stock de tienda física. Será diferente de la suma de ambos stocks físicos si el producto está en algún pedido
            var disponibleonline = product_infos.ava_quantity - product_infos.phy_quantity_fisica;

            //01/12/2020 Sacamos out_of_stock para indicar si el producto está disponible para compra online
            var out_of_stock = product_infos.out_of_stock;
            if (out_of_stock != 1) {
                //no permite pedidos
                var permite_pedido = '<span class="badge badge-pill badge-danger">NO</span>';
            } else {
                //permite pedidos
                var permite_pedido = '<span class="badge badge-pill badge-success">SI</span>';
            }

            //20/05/2021 Recibimos el dato de si el producto es novedad, el consumo redondeado, y la clasificación ABC como abc, siendo success si es C, y ponemos badge success, warning si es B y ponemos badge warning y danger si es A, poniendo badge danger.. Si es novedad, se pone consumo N y warning, como clasificación B. 
            //18/01/2023 Queremos poner el consumo si el producto es Novedad también, hacemos salto de línea y metemso el consumo 
            if (product_infos.novedad == 1) {
                // producto novedad, ponemos Tipo B y una N en lugar de cifra de stock
                var consumo = 'N<br>'+product_infos.consumo;
                var badge = 'warning';
            } else {
                var consumo = product_infos.consumo;
                var badge = product_infos.abc;                
            }           

            var abc = '<span class="badge badge-pill badge-'+badge+'" style="font-size: 130%;">'+consumo+'</span>';


            //limitamos el tamaño de la imagen para que salgan bien los GIF con width y height
            if(!!product_infos.image && product_infos.image!='')
                img = '<img itemprop="image" src = "'+product_infos.image+'"  width="98" height="98"  title="'+product_infos.id+'" />';

            // add a new line in the products table
            $('#products_in_search > tbody:last').append(                
                '<tr>'+
                '<td><div class="fixed-width-xs"  title="'+product_infos.id+'">'+product_infos.id_product+'</div></td>'+
                '<td><div class="fixed-width-sm">'+img+'</div></td>'+
                '<td class="text-center"><div class="fixed-width-lg">'+product_infos.reference+'</div></td>'+
                '<td class="text-center"><div class="fixed-width-lg">'+product_infos.name+'</div></td>'+                
                local_pic+ 
                //'<td><div class="input-group fixed-width-md"><input type="text" name="input_p_cantidad_'+product_infos.id+'" value="'+product_infos.p_cantidad+'" /></div></td>'+ 
                '<td class="text-center"><div class="input-group fixed-width-md"><input type="text" name="input_ean13_'+product_infos.id+'" value="'+product_infos.ean13+'" /></div></td>'+              
                '<td class="text-center">'+product_infos.supplier_reference+'</td>'+
                local_rep+
                //'<td><div class="input-group fixed-width-md"><input type="text" name="input_r_cantidad_'+product_infos.id+'" value="'+product_infos.r_cantidad+'" /></div></td>'+
                //'<td><div class="input-group fixed-width-md"><input type="text" name="input_weight_'+product_infos.id+'" value="'+product_infos.weight+'" /></div></td>'+
                '<td class="text-center">'+abc+'</td>'+
                '<td class="text-center"><span id="disponible_'+product_infos.id+'">'+product_infos.ava_quantity+'</span><br>( <span id="disponible_online_'+product_infos.id+'">'+disponibleonline+'</span> )</td>'+
                '<td class="text-center"><span id="fisica_online_'+product_infos.id+'">'+product_infos.phy_quantity_online+'</span>'+noAsignadoOnline+'</td>'+
                '<td class="text-center"><span id="fisica_tienda_'+product_infos.id+'">'+product_infos.phy_quantity_fisica+'</span>'+noAsignadoTienda+'</td>'+
                '<td class="text-center">'+permite_pedido+'</td>'+
                gestionStock+
                //Añadir botón de impresora etiquetas                
                '<td><a class="btn" href="#" onclick="printProductLabelOf('+product_infos.id_product+','+product_infos.id_product_attribute+');"><img src="https://lafrikileria.com/modules/directlabelprintproduct/views/img/icon-print.png" style="height:25px"></a></td>'+
                //'<td><button type="button" id="update|'+product_infos.id+'" class="btn btn-default update"><i class="icon-save"></i> {$i18n.update|escape:'htmlall':'UTF-8'}'+
                '<td><button type="button" id="update|'+product_infos.id+'" class="btn btn-default update" title="'+product_infos.id+'"><i class="icon-save"></i> {$i18n.update|escape:'htmlall':'UTF-8'}'+
                '</button></td></tr>'
            );
            //He credo un input hidden con id escondido_warehouse donde almacenar como valor el id del almacen sobre el que operamos para poder sacarlo luego desde Adminlocationmanager.php con Tools::getValue a la hora de hacer gestión de stock, por eso en el select sacamos también el id_warehouse como idwarehouse.

            // add the current product id to the product_id array - used for not show another time the product in the list
            product_ids.push(product_infos.id);

            // update the product_ids hidden field
            $('#product_ids').val(product_ids.join('|'));

            //como el valor seleccionado en el select de gestión de stock no se puede recoger en el php con Tools::getValue lo que hago es mediante jquery, cuando cambie el select, poner la opción elegida en el valor de un input hidden con id='escondido_operacion', que luego si se podrá recoger con Tools::getValue desde Adminlocationmanager.php. 
            //Para que los ids etc de la select sean únicos hay que añadir el id de producto mas atributo, y para que la función change de jquery funcione, el nombre hay que prepararlo antes
            var id_select = '#select_gestion_stock_'+product_infos.id;
            var escondido_operacion = '#escondido_operacion_'+product_infos.id;
            var stock_gestion = '#select_gestion_stock_'+product_infos.id;

            $(id_select).change(function(){
                    $(escondido_operacion).val($(stock_gestion).val());                    
               });            
            
            
            //contar el número de productos (ids) en el array para mostrar en la lista, saldrá en pantalla la última cantidad, se vacia desde back.js
            if(product_ids.length > 1){
                $('#num_prods').text(product_ids.length + ' productos');
            }else{
                $('#num_prods').text(product_ids.length + ' producto');
            }
            
            // clear the product_infos var
            product_infos = null;
        }
        
        //Ajax para actualizar los valores del stock de un producto cada vez que se pulse en su botón actualizar
        /*function actualizaStock(idproduct, idpattribute){
            var productIDcompleto = idproduct+'_'+idpattribute;
            $('#num_prods').text(idproduct + '¡¡CHORPRECHA!!' + idpattribute + '  ' + productIDcompleto);
            //$('#num_prods').text(productIDS);
            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", 'https://lafrikileria.com/test/modules/mblocationmanagermod/actualizastock.php?idprod='+idproduct+'&idpattribute='+idpattribute, true);
            xhttp.send();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    //alert(this.responseText);
                    //convertir el json que llega de actualizastock.php de nuevo en array
                    var stock = JSON.parse(this.responseText);

                    if (!stock[0]){
                        var stockavailable = 0;
                    }else{
                        var stockavailable = stock[0];
                    }

                    if (!stock[1]){
                        var stockfisicoonline = 0;
                    }else{
                        var stockfisicoonline = stock[1];
                    }
                    
                    if (!stock[2]){
                        var stockfisicotienda = 0;
                    }else{
                        var stockfisicotienda = stock[2];
                    }
                    //ACTUALIZA MAL lo hace después
                    $('#disponible_'+productIDcompleto).text(stockavailable);
                    $('#fisica_online_'+productIDcompleto).text(stockfisicoonline);
                    $('#fisica_tienda_'+productIDcompleto).text(stockfisicotienda);

                    //    alert(stockfisicotienda);
                }
            };
            
        }*/

    </script>

{/block}
