/**
 * Warehouse Location Manager powered by Modulebuddy™ (www.modulebuddy.com)
 *
 *  @author    Modulebuddy™ <info@modulebuddy.com>
 *  @copyright 2015-2016 Modulebuddy™
 *  @license   Check License.txt file in module root directory for End User License Agreement.
 */

$(function(){
   $('#product_form').on('submit',function(event){
       event.preventDefault();
       var dataObj = {};
       product_ids = []; //declarado el array aquí también
       $(this).find('input, select').each(function(index, elt){ //Este Jquery recoge los elementos input y select que hay en la página 
                                                                //con su contenido "elt.value" y lo guarda en un array dataObj
           dataObj[elt.name] = elt.value; 
       });

       $.ajax({
        url: 'index.php?controller=AdminLocationManager' + '&token=' + token + "&action=get_products" + '&ajax=1' + '&rand=' + new Date().getTime(),
        type: 'POST',
        data: dataObj,
        cache: false,
        dataType: 'json',
        success: function (data, textStatus, jqXHR)
        {
            $('#products_in_search > tbody').html('');
            if (typeof data.error === 'undefined')
            {
                $('#product_ids').val();
                product_ids = []; //vaciar el array para que el número de productos mostrado sea correcto
                $.each(data.products, function(index, line){
                    addProduct(line);
                });
                $('#products_in_search > tbody tr:eq(0) input:eq(0)').trigger('focus').select();
            }
            else
            {
                showErrorMessage(data.message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            showErrorMessage('ERRORS: ' + textStatus);
        }
    });
   });

   // bind enter key event on inputs location and ean
    $('#products_in_search> tbody').on('keypress', 'input[type="text"]',function(e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if(code == 13) { //Enter keycode
            e.stopPropagation();//Stop event propagation
            $(this).parents('tr:eq(0)').find('button.update').trigger('click');
            return false;
        }
    });

    $('#products_in_search> tbody').on('click', 'button.update', function(e){
        var line = $(this).parents('tr:eq(0)');
        var dataObj = {};
        line.find('input').each(function(index, elt){
            dataObj[elt.name] = elt.value;
        });
        var ids = $(this).attr('id').split('|');
        dataObj['id'] = ids[1];
        $('#product_form input[name=barrecode]').trigger('focus').select();
        jQuery('html, body').animate({scrollTop: $("#product_form").offset().top-100}, {duration: 500, specialEasing: {width: "linear", height: "easeOutBounce"}});
        $.ajax({
        url: 'index.php?controller=AdminLocationManager' + '&token=' + token + "&action=update_product" + '&ajax=1' + '&rand=' + new Date().getTime(),
        type: 'POST',
        data: dataObj,
        cache: false,
        dataType: 'json',
        success: function (data, textStatus, jqXHR)
        {
            if (typeof data.error === 'undefined')
            {
                //alert('available='+data.stockavailable+' online='+data.stockfisicoonline+' tienda='+data.stockfisicotienda);

                //Con los datos enviados desde AdminLocationManager con los stocks actualizados, se los metemos en su sitio al producto mediante Jquery
                //en el caso de la columna Disponible (Online), tenemos que actualizar tanto el disponible total como el disponible solo para 
                //almacén online, que es la resta de disponible total menos disponible en tienda física(múgica)
                var disponibleonline = data.stockavailable - data.stockfisicotienda;
                
                $('#disponible_'+data.productIDcompleto).text(data.stockavailable);
                $('#disponible_online_'+data.productIDcompleto).text(disponibleonline);
                $('#fisica_online_'+data.productIDcompleto).text(data.stockfisicoonline);
                $('#fisica_tienda_'+data.productIDcompleto).text(data.stockfisicotienda);
                //vaciar el input de gestionar stock
                $('input[name=input_cantidad_a_gestionar_'+data.productIDcompleto+']').val('');
                
                //si tenemos los dos almacenes asignados (todosalmacenes = 1) ocultaremos los label de 'Almacén no asignado' y actualizaremos las 
                //opciones del select para que se puedan hacer transferencias y no salga la opción de asignar almacén, limpiando el select
                if (data.todosalmacenes == 1) {
                    var escondido_operacion = '#escondido_operacion_'+data.productIDcompleto;
                    if (($(escondido_operacion).val() == 5)||($(escondido_operacion).val() == 6)){
                        var labeltiendafisica = '#label_tienda_fisica_'+data.productIDcompleto;
                        var labeltiendaonline = '#label_tienda_online_'+data.productIDcompleto;
                        var selectgestion = '#select_gestion_stock_'+data.productIDcompleto;
                        $(function() {
                                $(labeltiendafisica).hide();
                                $(labeltiendaonline).hide();  
                                $(escondido_operacion).val('');  
                                //Dependiendo de que almacén estamos gestionando mostramos un texto u otro en el select
                                if (data.almacenenuso == 1) {
                                    var sumastock = 'Sumar stock Almacén';
                                    var restastock = 'Restar stock Almacén';
                                } else if (data.almacenenuso == 4) {
                                    var sumastock = 'Sumar stock Tienda';
                                    var restastock = 'Restar stock Tienda';
                                }
                                var contenidoselect = '<option value="" disabled selected>Operación:</option><option value="1">'+sumastock+'</option>'+
                                        '<option value="2">'+restastock+'</option><option value="10" style="color:red;">SEPARADOR PARA NACHO</option>'+
                                        '<option value="3">Almacén a Tienda</option><option value="4">Tienda a Almacén</option>';
                                //vaciamos el select y añadimos de nuevo las opciones correctas
                                $(selectgestion).empty().append(contenidoselect);
                            });
                    }
                }

                //alert('todosalmacenes='+data.todosalmacenes);
               /* if (data.todosalmacenes == 1) {
                    
                    var escondido_agregar_almacen = '#escondido_agregar_almacen_'+data.productIDcompleto; 
                    if ($(escondido_agregar_almacen).val() == 1){
                        var labeltiendafisica = '#label_tienda_fisica_'+data.productIDcompleto;
                        var labeltiendaonline = '#label_tienda_online_'+data.productIDcompleto;
                        var selectgestion = '#select_gestion_stock_'+data.productIDcompleto;
                        $(function() {
                                $(labeltiendafisica).hide();
                                $(labeltiendaonline).hide();  
                                $(escondido_agregar_almacen).val(0);                          
                                $(selectgestion).append('<option value="3">Almacén a Tienda</option><option value="4">Tienda a Almacén</option>');
                            });
                    }
                }*/

                //si el producto ha pasado de no tener almacén tienda asignado a si tenerlo, escondido agregar tienda, el input hidden, tendrá valor 1, 
                //escondemos el span con el check de agregar tienda y al input hidden 
                //le ponemos valor 0, además, añadimos al select de gestión de stock las dos opciones de transferir entre almacenes
               /* if (data.almacentienda == 1) {
                    var escondido_agregar_tienda = '#escondido_agregar_tienda_'+data.productIDcompleto; 
                    if ($(escondido_agregar_tienda).val() == 1){
                        var spanagregartienda = '#spanagregartienda_'+data.productIDcompleto;                    
                        var selectgestion = '#select_gestion_stock_'+data.productIDcompleto;
                        $(function() {
                            $(spanagregartienda).hide();
                            $(escondido_agregar_tienda).val(0);
                            $(selectgestion).append('<option value="3">Almacén a Tienda</option><option value="4">Tienda a Almacén</option>');
                        });
                    }
                }*/



                //mostrar mensaje de exito
                showSuccessMessage(data.message);
                
            }
            else
            {
                showErrorMessage(data.message);
            }

        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            
            showErrorMessage('ERRORS: ' + 'La visualización del stock puede no haberse actualizado, RECARGA ' + textStatus);
        }
    });
    });

    //console.log('id warehouse select:'+$( "#id_warehouse" ).val());

    // 17/06/2021 Preparamos para cuando se seleccione el almacén Tienda id = 4 mostrar el input de Pedidos Tienda y ocultarlo si está otro almacén, y al reves con el de Pedidos de Materiales. .parent().parent() sube dos niveles hasta el div que contiene el form-group. Vaciamos el input que se esconde
    if ($( "#id_warehouse" ).val() != 4) {
        $("#pedido_tienda").parent().parent().hide();
        $("#pedido_tienda").val('');
        $("#pedido").parent().parent().show(); //input pedido de materiales
        console.log('id warehouse select if:'+$( "#id_warehouse" ).val());
    }

    //alcambiar el select de almacén mostramos lo que corresponda
    $("#id_warehouse").change(function(){
        if ($( "#id_warehouse" ).val() == 4) {
            $("#pedido_tienda").parent().parent().show();
            $("#pedido").parent().parent().hide(); //input pedido de materiales
            $("#pedido").val('');
            console.log('id warehouse select:'+$( "#id_warehouse" ).val());
        } else {
            $("#pedido_tienda").parent().parent().hide();
            $("#pedido_tienda").val('');
            $("#pedido").parent().parent().show(); //input pedido de materiales
            console.log('id warehouse select:'+$( "#id_warehouse" ).val());
        }
    });



});


