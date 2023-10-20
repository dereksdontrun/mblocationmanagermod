<?php
/**
 * Warehouse Location Manager powered by Modulebuddy™ (www.modulebuddy.com)
 *
 *  @author    Modulebuddy™ <info@modulebuddy.com>
 *  @copyright 2015-2016 Modulebuddy™
 *  @license   Check License.txt file in module root directory for End User License Agreement.
 */

if (!defined('_PS_VERSION_'))
    exit;

class AdminLocationManagerController extends ModuleAdminController {
    protected $limit = 1000; //límite de 1000 productos para mostrar
    
    public function __construct() {
        require_once (dirname(__FILE__) .'/../../mblocationmanagermod.php');

        $this->lang = false;
        $this->bootstrap = true;
        $this->module = new Mblocationmanagermod();
        $this->table = 'product';
        $this->identifier = 'id_product';
        $this->className = 'Product';
        $this->allow_export = false;
        $this->delete = false;
        $this->context = Context::getContext();
        $this->default_lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        parent::__construct();

        return true;
    }

    /**
     * AdminController::init() override
     * @see AdminController::init()
     */
    public function init() {
        $this->display = 'add';
        parent::init();
    }

    public function postProcess() {

        return parent::postProcess();
    }

    /**
     * AdminController::renderForm() override
     * @see AdminController::renderForm()
     */
    public function renderForm() {
        $this->toolbar_title = $this->module->i18n['product_location_manager'];
        //$this->displayWarning($_SERVER["REQUEST_URI"]);
        //get warehouses list
        $warehouses = Warehouse::getWarehouses(true); 

        //get lista categorías
        $categories = Category::getCategories((int)($cookie->id_lang), true, false);

        if (!$categories || empty($categories))
            $this->displayWarning($this->module->i18n['no_categories_msg']);

        // displays warning if there are no warehouses
        if (!$warehouses || empty($warehouses))
            $this->displayWarning($this->module->i18n['no_warehouse_msg']);

        asort($warehouses); //reordenar array de almacenes para que la opción por defecto en el select sea almacén online (por id_warehouse)
        
        //reordenar array de categorías para que aparezcan por orden alfabético, pero manteniendo Raíz como la primera
        //Primero con foreach creamos un array temporal donde se guarda en cada key suya la línea (row) de key='name' del array categories (este guarda no solo los ids de categoría sino toda la info de las categorías en la BBDD) , se puede ver con $this->displayWarning(print_r($categories));      
        foreach($categories as $key => $row){
            $tmp[$key] = $row['name'];
        }
        //Después, en base al array temporal y con orden ascendente SORT_ASC ordenamos el array $categories.
        array_multisort($tmp, SORT_ASC, $categories);
        //ahora volvemos a colocar como categoría primera la categoría Raíz
        //Primero sacamos la posición de Raíz en el array después de reordenar por orden alfabético
        $i = 0;
        $position_raiz = 0;
        foreach($categories as $key => $row){
            if ($row['name'] == 'Raíz'){
                $position_raiz = $i;
            }
            $i++;
        }
        // con array_slice 'sacamos' la parte de 'Raíz' del array $categories y después hacemos array_merge, que lo añadirá al principio del array
        $raiz = array_slice($categories, $position_raiz, 1);
        $categories = array_merge($raiz, $categories);

        //select clasificación ABC
        // $abc = array(0 => '--', 1 => 'A', 2 => 'B', 3 => 'C');
        $abc = array(
            array('id_abc' => 0, 'name' => '--'),
            array('id_abc' => 1, 'name' => 'A'),
            array('id_abc' => 2, 'name' => 'B'),
            array('id_abc' => 3, 'name' => 'C')
        );
        //         die(Tools::jsonEncode(array('error'=> true, 'message'=>$abc)));
        //Creamos el formulario del localizador
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->module->i18n['order_infos'],
                'icon' => 'icon-pencil'
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->module->i18n['warehouse'],
                    'name' => 'id_warehouse',
                    'required' => true,
                    'options' => array(
                        'query' => $warehouses,
                        'id' => 'id_warehouse',
                        'name' => 'name'
                    ),
                    'hint' => $this->module->i18n['warehouse_desc'],
                ),
                array(//16/07/2021 Mod para filtrar por ABC
                    'type' => 'select',
                    'label' => 'Clasificación ABC',
                    'name' => 'abc',
                    'required' => true,                    
                    'options' => array(
                        'query' => $abc,
                        'id' => 'id_abc',
                        'name' => 'name'
                    ),
                    'hint' => 'Mostrar solo productos de una clasificación',
                ),
                array(
                    'type' => 'select',
                    'label' => $this->module->i18n['categories'],
                    'name' => 'id_category',
                    'required' => false,
                    'options' => array(
                        'query' => $categories,
                        'id' => 'id_category',
                        'name' => 'name'
                    ),
                    'hint' => $this->module->i18n['category_desc'],
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->i18n['barcode'],
                    'name' => 'barrecode',
                    'required' => false, /* Modificado de true a false */
                    'hint' => $this->module->i18n['barcode_desc'],
                ),                
                //Añado en el formulario una entrada para el código de pedido a proveedores y abajo otra para la localización de producto
                array(
                    'type' => 'text',
                    'label' => $this->module->i18n['proveedores'],
                    'name' => 'pedido',
                    'required' => false,
                    'hint' => $this->module->i18n['proveedores_desc'],
                ),
                array(
                    'type' => 'text',
                    'label' => $this->module->i18n['localizacion'],
                    'name' => 'localizacion',
                    'required' => false,
                    'hint' => $this->module->i18n['localizacion_desc'],
                ),
                array(//16/07/2021 Mod para separar búsqueda de localización repo y normal para poder añadir búsqueda solo de localización y detectar abc mal colocados
                    'type' => 'text',
                    'label' => 'Reposición',
                    'name' => 'reposicion',
                    'required' => false,
                    'hint' => 'Localización de reposición',
                ),
                //17/06/2021 Añadir pedido cliente para pedidos para Tienda física, escondido con Jquery mientras no se seleccione Almacén Tienda
                array(
                    'type' => 'text',
                    'label' => 'Pedido para TIENDA',
                    'name' => 'pedido_tienda',
                    'required' => false,
                    'hint' => 'Introduce el ID de pedido',
                ),
                array('type' => 'select',
                    'label' => 'Orden de productos',
                    'name' => 'id_ordenacion',
                    'required' => false,                    
                    'options' => array(
                        'query' => $ordenacion = array(  //16/07/2020 reordeno para que salga primero ordenar de nuevo a viejo                                    
                                    array(
                                        'id_ordenacion' => 1,
                                        'name' => 'Nuevos Primero'
                                    ),
                                    array(
                                        'id_ordenacion' => 2,
                                        'name' => 'Antiguos Primero'
                                    ),
                                    array(
                                        'id_ordenacion' => 3,
                                        'name' => 'Cantidad Disponible Creciente'
                                    ),
                                    array(
                                        'id_ordenacion' => 4,
                                        'name' => 'Cantidad Disponible Decreciente'
                                    ),
                                    //16/07/2021 Mod para ABC
                                    array(
                                        'id_ordenacion' => 5,
                                        'name' => 'ABC Descendente'
                                    ),
                                    array(
                                        'id_ordenacion' => 6,
                                        'name' => 'ABC Ascendente'
                                    ),
                        ),
                        'id' => 'id_ordenacion',
                        'name' => 'name'
                    )                     
                ),            
            ),
            'reset' => array('title' => $this->module->i18n['reset'], 'icon' => 'process-icon-eraser icon-eraser'),/* Fin MOD */   
            'submit' => array('title' => $this->module->i18n['submit'], 'icon' => 'process-icon-search icon-search'),            
        );



        $this->displayInformation(
                $this->module->i18n['informations1']
                . '<br> '
        );
        return parent::renderForm();
    }

    /**
     * method call when ajax request is made for search product according to the search
     */
    public function ajaxProcessGetProducts() {
        // Get the search pattern
        $pattern_producto = trim(pSQL(Tools::getValue('barrecode', false)));

        $pattern_pedido = trim(pSQL(Tools::getValue('pedido', false)));

        //17/06/2021 añadimos pattern pedido Tienda y creamos una variable que indicará que es este tipo de búsqueda para utilizarla en form.tpl para mostrar bien el desplegable de gestión de stock
        $pattern_pedido_tienda = trim(pSQL(Tools::getValue('pedido_tienda', false)));
        $pedido_para_tienda = 0;

        $pattern_localizacion = trim(pSQL(Tools::getValue('localizacion', false)));

        //16/07/2021 mod para localización repo y sacar abc
        $pattern_reposicion = trim(pSQL(Tools::getValue('reposicion', false)));
        $clas_abc = (int) Tools::getValue('abc', false);
        // die(Tools::jsonEncode(array('error'=> true, 'message'=>$clas_abc)));

        $id_lang = (int) $this->context->language->id;

        // gets the warehouse id
        // $id_warehouse = (int) Tools::getValue('id_warehouse', false);
        $id_warehouse = '('.(int) Tools::getValue('id_warehouse', false).')';

        // gets id categoría
        $id_category = (int) Tools::getValue('id_category', false);

        // get ordenación de productos, según la opción elegida en el select, al hacer LEFT JOIN en la consulta con lafrips_stock_available, recogemos quantity disponible
        $id_ordenacion = (int) Tools::getValue('id_ordenacion', false);
        if ($id_ordenacion == 1){
            $ordenacion = 'p.id_product DESC';
        }else if ($id_ordenacion == 2){
            $ordenacion = 'p.id_product ASC';
        }else if ($id_ordenacion == 3){
            $ordenacion = 'sa.quantity ASC';
        }else if ($id_ordenacion == 4){
            $ordenacion = 'sa.quantity DESC';
        }else if ($id_ordenacion == 5){ //16/07/2021 ordenar por abc
            $ordenacion = 'con.abc ASC';
        }else if ($id_ordenacion == 6){
            $ordenacion = 'con.abc DESC';
        }



//CONDICIONES PARA LO INTRODUCIDO EN EL FORMULARIO
        //Si no se ha introducido nada en el formulario y además la categoría es Raíz (id 1) se muestra mensaje de formulario vacio
        if ((!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1) && ((int)$id_category == 1))
            //die('Empty');
            die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['campos_vacios'])));

        //Si no se ha introducido nada en el formulario pero SI hay categoría seleccionada (no raíz) se muestran todos los productos que haya en esa categoría
        if ((!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1) && ((int)$id_category !== 1)) 
        {

            $where_especifico = 'cp.id_category =' . (int)$id_category ;

        }
        // 17/06/2021 Si se introduce algo en el input de Pedido para Tienda, queremos que se busque un pedido normal de cliente y se muestren los productos que contiene. Como pueden no tener asignado el almacén tienda, añadimos a la query un where con el id de almacen online, de modo que para ese caso muestra todos
        //Si se ha introducido algo solo en la casilla de Pedido para Tiendas, y la categoría es raíz (si no dará error) muestra los productos en dicho pedido (forzando que admita los de cualquier almacén)
        else if (($pattern_pedido_tienda || $pattern_pedido_tienda !== '' || Tools::strlen($pattern_pedido_tienda) > 1) && ((int)$id_category == 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1) && (!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1)) 
        {

            $where_especifico = '(ode.id_order = ' . $pattern_pedido_tienda . ')'; 
            $id_warehouse = '(1, 4)';

            //activamos el indicador de que la búsqueda será de productos de un pedido para tienda:
            $pedido_para_tienda = 1;
                

        }
        //Si se ha introducido algo solo en la casilla de Pedido a proveedores, y la categoría es raíz (si no dará error) muestra los productos en dicho pedido
        else if (($pattern_pedido || $pattern_pedido !== '' || Tools::strlen($pattern_pedido) > 1) && ((int)$id_category == 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1)) 
        {

            $where_especifico = '(so.reference LIKE \'%' . $pattern_pedido . '%\')';

        }
        //Si se ha introducido algo en la casilla de Producto, manteniendo la categoría Raíz, se busca en los productos la coincidencia de lo buscado con nombre, referencia, referencia de proveedor y ean/upc. Añado AND id_category etc porque con esa busqueda reduce el tiempo a la mitad cuando no se selecciona ninguna categoría ¿? 
        else if (($pattern_producto || $pattern_producto !== '' || Tools::strlen($pattern_producto) > 1) && ((int)$id_category == 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1))
        {

            $where_especifico = '(pa.upc LIKE \'%' . $pattern_producto
                    . '%\' OR pa.reference LIKE \'%' . $pattern_producto
                    . '%\' OR ps.product_supplier_reference LIKE \'%' . $pattern_producto
                    . '%\' OR p.ean13 LIKE \'%' . $pattern_producto
                    . '%\' OR pa.ean13 LIKE \'%' . $pattern_producto
                    . '%\' OR p.upc LIKE \'%' . $pattern_producto
                    . '%\' OR p.reference LIKE \'%' . $pattern_producto
                    . '%\' OR pl.name LIKE \'%' . $pattern_producto
                    . '%\') AND cp.id_category IN (SELECT id_category FROM lafrips_category)';

        }
        //16/07/2021 Cambiamos, colocamos nuevo input y las búsquedas de localización estarán separadas, ahora hay un input para localización y itro para localización de reposición
        //Si se introduce algo en la casilla localización manteniendo categoría raíz, se busca lo introducido en la tabla de lafrips_warehouse_product_location para localización de almacén y en mi tabla lafrips_localizaciones para localización de reposición. 
        else if (($pattern_localizacion || $pattern_localizacion !== '' || Tools::strlen($pattern_localizacion) > 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1) && ((int)$id_category == 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1) && (!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1)) 
        {

            // $where_especifico = '(pw.location LIKE \'' . $pattern_localizacion
            //         . '%\') OR (fl.r_location LIKE \'' . $pattern_localizacion
            //         . '%\')';
            $where_especifico = 'pw.location LIKE \'' . $pattern_localizacion
                    . '%\'';

         }

         else if (($pattern_reposicion || $pattern_reposicion !== '' || Tools::strlen($pattern_reposicion) > 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && ((int)$id_category == 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1) && (!$pattern_producto || $pattern_producto == '' || Tools::strlen($pattern_producto) < 1)) 
        {

            $where_especifico = 'fl.r_location LIKE \'' . $pattern_reposicion
                    . '%\'';

         }

        //Si la casilla producto contiene algo y la categoría no es raíz, se busca el producto en la categoría seleccionada
        else if (($pattern_producto || $pattern_producto !== '' || Tools::strlen($pattern_producto) > 1) && ((int)$id_category !== 1) && (!$pattern_pedido_tienda || $pattern_pedido_tienda == '' || Tools::strlen($pattern_pedido_tienda) < 1) && (!$pattern_localizacion || $pattern_localizacion == '' || Tools::strlen($pattern_localizacion) < 1) && (!$pattern_reposicion || $pattern_reposicion == '' || Tools::strlen($pattern_reposicion) < 1) && (!$pattern_pedido || $pattern_pedido == '' || Tools::strlen($pattern_pedido) < 1))
        {

            $where_especifico = '(pa.upc LIKE \'%' . $pattern_producto
                    . '%\' OR pa.reference LIKE \'%' . $pattern_producto
                    . '%\' OR ps.product_supplier_reference LIKE \'%' . $pattern_producto
                    . '%\' OR p.ean13 LIKE \'%' . $pattern_producto
                    . '%\' OR pa.ean13 LIKE \'%' . $pattern_producto
                    . '%\' OR p.upc LIKE \'%' . $pattern_producto
                    . '%\' OR p.reference LIKE \'%' . $pattern_producto
                    . '%\' OR pl.name LIKE \'%' . $pattern_producto
                    . '%\') AND cp.id_category = ' . (int)$id_category;

        }
        else //Si hay cosas en varias casillas etc se pide limpiar el formulario
        {
            die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['limpia_consulta'])));
        }

        //16/07/2021 preparamos where para cuando se quiere filtrar abc con el nuevo select        
        if ($clas_abc == 1) {
            $where_especifico_abc = 'con.abc = "A"';
        } else if ($clas_abc == 2) {
            $where_especifico_abc = 'con.abc = "B"';
        } else if ($clas_abc == 3) {
            $where_especifico_abc = 'con.abc = "C"';
        } else {
            $where_especifico_abc = '';
        }

        //20/05/2021 obtenemos los días que un producto es considerado novedad (clasificación B) desde lafrips_configuration
        // 10/06/2021 ahora se guarda en lafrips_cosnumos campo novedad
        // $novedad = (int)Configuration::get('CLASIFICACIONABC_NOVEDAD', 0);

        //Consulta de busqueda de productos, no queremos ni packs antiguos (cache_is_pack=1) ni packs de módulo advanced packs
            $query = new DbQuery();
            $query->select('
                    CONCAT(p.id_product, \'_\', IFNULL(pa.id_product_attribute, \'0\')) as id,
                    p.id_product,
                    p.cache_is_pack as es_pack,
                    IFNULL(pa.id_product_attribute, 0) as id_product_attribute,
                    IFNULL(pai.id_image, IFNULL(i.id_image, 0)) as id_image,
                    IFNULL(pa.reference, IFNULL(p.reference, \'\')) as reference,
                    IFNULL(ps.product_supplier_reference,\'\') as supplier_reference,
                    IFNULL(pa.ean13, IFNULL(p.ean13, \'\')) as ean13,
                    IFNULL(pa.upc, IFNULL(p.upc, \'\')) as upc,
                    pw.id_warehouse as idwarehouse,
                    IFNULL(pw.location, \'\') as location,                    
                    IFNULL(fl.r_location, \'\') as r_location,                    
                    pl.link_rewrite,
                    sa.out_of_stock as out_of_stock,
                    con.consumo as consumo,
                    con.abc as clasificacion_abc,
                    con.novedad as novedad,
                    IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.name, \' - \', al.name order by agl.name SEPARATOR \', \')), pl.name) as name
            ');
            $query->from('product', 'p');
            $query->join(Shop::addSqlAssociation('product', 'p'));
            $query->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . $id_lang);
            $query->leftJoin('category_product', 'cp', 'cp.id_product = p.id_product');  
            $query->leftJoin('supply_order_detail', 'od', 'od.id_product = p.id_product');
            $query->leftJoin('supply_order', 'so', 'so.id_supply_order = od.id_supply_order'); 
            $query->leftJoin('image', 'i', 'i.id_product = p.id_product AND i.cover = 1');
            $query->leftJoin('product_attribute', 'pa', 'pa.id_product = p.id_product');
            $query->leftJoin('product_attribute_image', 'pai', 'pai.id_product_attribute = pa.id_product_attribute');
            $query->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
            $query->leftJoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
            $query->leftJoin('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = ' . $id_lang.Shop::addSqlRestrictionOnLang('pl'));
            $query->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = ' . $id_lang);
            $query->leftJoin('warehouse_product_location', 'pw', 'pw.id_product = p.id_product AND pw.id_product_attribute = IFNULL(pa.id_product_attribute, 0)');
            $query->leftJoin('localizaciones', 'fl', 'fl.id_product = p.id_product AND fl.id_product_attribute = IFNULL(pa.id_product_attribute, 0)');
            $query->leftJoin('product_supplier', 'ps', 'ps.id_product = p.id_product AND ps.id_product_attribute = IFNULL(pa.id_product_attribute, 0)'); 
            $query->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = IFNULL(pa.id_product_attribute, 0)');  
            $query->leftJoin('consumos', 'con', 'con.id_product = p.id_product AND con.id_product_attribute = sa.id_product_attribute');     
            $query->leftJoin('order_detail', 'ode', 'ode.product_id = p.id_product AND ode.product_attribute_id = sa.id_product_attribute');      
            $query->where('p.id_product NOT IN (SELECT pd.id_product FROM `' . _DB_PREFIX_ . 'product_download` pd WHERE (pd.id_product = p.id_product))');
            $query->where('p.id_product NOT IN (SELECT adp.id_pack FROM `' . _DB_PREFIX_ . 'pm_advancedpack` adp WHERE (adp.id_pack = p.id_product))');
            $query->where('p.is_virtual = 0 AND p.cache_is_pack = 0');
            //$query->where('p.is_virtual = 0');
            // $query->where('pw.id_warehouse = ' . (int)$id_warehouse);
            $query->where('pw.id_warehouse IN ' . $id_warehouse);
            //$query->where('cp.id_category = ' . (int)$id_category);
            $query->where($where_especifico);
            //16/07/2021 where para abc
            $query->where($where_especifico_abc);
            $query->groupBy('p.id_product, pa.id_product_attribute');
            $query->orderBy($ordenacion);
            $query->limit($this->limit);
            $items = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query); 

        
        $stock_manager = new StockManager();
        //crear una variable smarty para sacar en el tpl el número de productos
        /*//$num_productos = count($items);
        $smarty = new Smarty;
        $num_productos = 'pepe';
        $smarty->assign('num_productos',$num_productos);
        //$smarty->display('../../views/templates/admin/location_manager/helpers/form/form.tpl');    */   

        //sacamos los valores para clasificación abc de tabla configuration
        // 10/06/2021 innecesario, ya calculado todo en tabla consumos
        // $maxC = Configuration::get('CLASIFICACIONABC_MAX_C');
        // $minA = Configuration::get('CLASIFICACIONABC_MAX_B');


        foreach ($items as &$item) {
            // 17/06/2021 Añadimos a cada producto el indicador de si se trata de una búsqueda de productos en pedido para tienda
            $item['pedido_para_tienda'] = $pedido_para_tienda;
            //20/05/2021 En función del consumo almacenado (si tiene) calculamos su consumo redondeado y clasificación ABC
            $consumo = $item['consumo'];
            $abc = $item['clasificacion_abc'];
            if (!$consumo || !$abc) { //sino existe en la tabla de consumos
                $consumo = 0;
                $abc = 'C';
            }

            //en función de su clasificación enviamos ya el badge para el color que se asignará en la plantilla, como abc
            if ($abc == 'C') { //es producto C (o novedad si tiene menos de X días, pero aquí no afecta)
                $item['consumo'] = 1;
                $item['abc'] = 'success';
            } else if ($abc == 'A') { //es producto A
                $item['consumo'] = round($consumo , 0 , PHP_ROUND_HALF_DOWN); // 7.5 será 7, 7.51 será 8
                $item['abc'] = 'danger';
            } else { //es producto B
                $item['consumo'] = round($consumo , 0 , PHP_ROUND_HALF_DOWN);
                $item['abc'] = 'warning';
            }


            $ids = explode('_', $item['id']);
            if (empty($ids) || !count($ids))
                continue;
            $ids = array_map('intval', $ids);
            $item['ava_quantity'] = (int) StockAvailable::getQuantityAvailableByProduct($ids[0], $ids[1]); //stock disponible
            $item['phy_quantity_online'] = (int) $stock_manager->getProductPhysicalQuantities($ids[0], $ids[1], 1,true);  //stock físico online
            $item['phy_quantity_fisica'] = (int) $stock_manager->getProductPhysicalQuantities($ids[0], $ids[1], 4,true);  //stock físico tienda física
            $almacenes = Warehouse::getProductWarehouseList($ids[0], $ids[1]);  //almacenes activos del producto (si solo tiene uno, en tpl no daremos opción a transferir stock) Esto consulta en la tabla warehouse_product_location, por lo que no importa si tiene o ha tenido stock en los almacenes, se da por defecto id_shop ya que solo hay una de momento. Si no tiene ningún almacén asignado, no puede salir en el localizador.
            $i = 0;
            foreach ($almacenes as $almacen) { //si hay más de un almacén $i irá creciendo, almacenAsignado guardamos los almacenes que el producto tiene asignados, esto lo usaremos en form.tpl solo si solo tiene un almacén asignado, para ofrecer la opción de asignar el otro, por lo tanto, cuando solo tiene uno asignado, estará almacenado en $item['alamacenAsignado']                
                $item['almacenAsignado'][$i] = (int)$almacen['id_warehouse'];
                $i++;
            }
            //si hay más de un almacen mostraremos (hecho en form.tpl) la opción de transferir stock, si no no.
            if ($i > 1){
                $item['almacenes'] = 1;
            } else {
                $item['almacenes'] = 0;
            }
            //$this->displayWarning($item['almacenes']);

            //PROVISIONAL PARA TRANSFERIR A ALMACEN TIENDA FISICA 20/02/2019
                //Para facilitar el paso del stock de tienda fisica al almacén de tienda física y que no tengan que meterse al producto para marcar el almacén como activo, voy a añadir un check que, si se marca, al actualizar meta el producto y atributo en dicho almacén (pero solo el atributo en el que estemos, lógicamente). Para ello primero se comprueba que el producto + atributo no esté ya en el almacén, en este caso Tienda (id_warehouse = 3) Ignoraremos el parámetro location, ya que no vamos a usar localizaciones en tienda física. Estas funciones salen de AdminProductsController.php (línea aprox 3175)

                //esto obtiene el id_warehouse_product_location de esta combinación en la tabla warehouse_location, si no lo encuentra, es que este producto y atributo no están en el almacén tienda física
             /*   $wpl_id = (int)WarehouseProductLocation::getIdByProductAndWarehouse($ids[0], $ids[1], 3); 
                if (empty($wpl_id)) {   //si no lo ha encontrado, metemos el parámetro almacén tienda como 0, para en el tpl mostrar la opción de asignar almacén tienda
                    $item['almacentienda'] = 0;
                } else {
                    $item['almacentienda'] = 1;
                }*/
            //FIN PROVISIONAL

            if(!!$item['id_image'])
                $item['image'] = $this->context->link->getImageLink($item['link_rewrite'], (int)$item['id_image'], ImageType::getFormatedName('small'));
        }
        if ($items && !empty($items))
            die(Tools::jsonEncode(array('products'=> $items)));

        die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['no_product'])));
    }

    public function ajaxProcessUpdateProduct(){
        $product = Tools::getValue('id', 0);
        if(!$product)
            die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['product_error'])));

        $response = true;

        $location = pSQL(Tools::getValue('input_location_'.$product, false));
        $ean13 = pSQL(Tools::getValue('input_ean13_'.$product, false));
        //$weight = pSQL(Tools::getValue('input_weight_'.$product, false));
        //$p_cantidad = pSQL(Tools::getValue('input_p_cantidad_'.$product, false));
        $r_location = pSQL(Tools::getValue('input_r_location_'.$product, false));
        //$r_cantidad = pSQL(Tools::getValue('input_r_cantidad_'.$product, false));
        $tipo_gestion_stock =  pSQL(Tools::getValue('input_tipo_operacion_'.$product, false));  //almacena el id del select de gestionar stock
        $id_warehouse =  (int)pSQL(Tools::getValue('input_id_warehouse_'.$product, false)); //colocado en form.tpl en un input hidden, almacén sobre el que se busca
        $cantidad_a_gestionar = pSQL(Tools::getValue('input_cantidad_a_gestionar_'.$product, false));
        
        $product = explode('_', $product);
        $product = array_map('intval', $product);

        //si se ha seleccionado un tipo de gestión de stock (sumar o restar o transferir) y una cantidad a gestionar:
        if(($tipo_gestion_stock)&&($cantidad_a_gestionar !== '')){
            //dependiendo del valor de la gestión sumamos o restamos
            if($tipo_gestion_stock == 2){//RESTAR
                //creamos instancia de stockmanager para poder restar stock
                $stock_manager = new StockManager();               
                $id_stock_mvt_reason = 11; //movimiento creado en Existencias-configuracion Resta Modulo Localizacion
                $warehouse = new Warehouse($id_warehouse);
                //die(Tools::jsonEncode(array('error'=> true, 'message'=>'$id_warehouse = '.$id_warehouse.' $id_product = '.$product[0].' $id_product_att = '.$product[1].' $cantidad_a_gestionar = '.$cantidad_a_gestionar.' $id_stock_mvt_reason = '.$id_stock_mvt_reason.' $warehouse = '.$warehouse)));
                
                $posible_gestionar = $stock_manager->removeProduct($product[0], $product[1], $warehouse, $cantidad_a_gestionar, $id_stock_mvt_reason);

                //actualizar stock_available tras la operación
                StockAvailable::synchronize($product[0]);

                if(!$posible_gestionar)
                    die(Tools::jsonEncode(array('error'=> true, 'message'=>'Error de Stock')));                                

            }else if($tipo_gestion_stock == 1){//SUMAR
                //creamos instancia de stockmanager para poder sumar stock
                $stock_manager = new StockManager();                
                $id_stock_mvt_reason = 10; //movimiento creado en Existencias-configuracion Suma Modulo Localizacion
                $warehouse = new Warehouse($id_warehouse);
                //$price_te = str_replace(',', '.', Tools::getValue('price', 0)); //obtener price_te
                //die(Tools::jsonEncode(array('error'=> true, 'message'=>'$price_te = '.$price_te)));

                //obtener price_te, sacamos el price_te del último stock introducido ya que el almacén es FIFO
                $sql = "SELECT price_te AS price FROM lafrips_stock WHERE id_product = $product[0] AND id_product_attribute = $product[1] AND id_warehouse = $id_warehouse ORDER BY id_stock DESC LIMIT 1;";
                $result = Db::getInstance()->ExecuteS($sql);

                //En caso de ser un producto que no estaba en gestión avanzada o aún no tiene stock en ese almacén y por lo tanto nunca tuvo línea de stock en lafrips_stock, no se puede sacar price_te con esa consulta y dará error al intentar sumar, si es así, sacamos price_te buscando wholesale_price en la línea del producto en lafrips_product
                if ($result){
                    $prices = array();

                    foreach($result as $resultado) {
                        $prices[] = $resultado[price];
                    }
                    $price_te = $prices[0];
                    
                } else { //si no hay stock en lafrips_stock:
                    //obtener price_te, sacando el precio de proveedor desde la tabla lafrips_product
                    $sql = "SELECT wholesale_price AS price FROM lafrips_product WHERE id_product = $product[0];";
                    $result = Db::getInstance()->ExecuteS($sql);
                    $prices = array();

                    foreach($result as $resultado) {
                        $prices[] = $resultado[price];
                    }
                    $price_te = $prices[0];

                }
                //die(Tools::jsonEncode(array('error'=> true, 'message'=>'$price_te = '.$price_te)));
                $posible_gestionar = $stock_manager->addProduct($product[0], $product[1], $warehouse, $cantidad_a_gestionar, $id_stock_mvt_reason, $price_te);
                if(!$posible_gestionar)
                    die(Tools::jsonEncode(array('error'=> true, 'message'=>'Error de Stock')));

                //actualizar stock_available tras la operación
                StockAvailable::synchronize($product[0]);

            }else if(($tipo_gestion_stock == 3)||($tipo_gestion_stock == 4)){ //TRANSFERIR entre almacenes
                //si $tipo_gestion_stock es 3 se transfiere de almacén de tienda "online" ID 1 al almacén de tienda "física" ID 4, y viceversa
                //indicamos almacén origen y almacén destino con su id_warehouse
                if($tipo_gestion_stock == 3){
                    $almacen_desde = 1;
                    $almacen_hasta = 4;
                }else if($tipo_gestion_stock == 4){
                    $almacen_desde = 4;
                    $almacen_hasta = 1;
                } 

                //creamos instancia de stockmanager para poder transferir stock entre almacenes
                $stock_manager = new StockManager();

                $posible_transferir = $stock_manager->transferBetweenWarehouses($product[0], $product[1], $cantidad_a_gestionar,$almacen_desde, $almacen_hasta);
                
                //si no hay unidades suficientes en el almacen origen devuelve false, y sacamos error, si es posible ejecutará la transferencia
                if(!$posible_transferir)
                    die(Tools::jsonEncode(array('error'=> true, 'message'=>'Error, Comprueba la transferencia de stock')));
                    //die(Tools::jsonEncode(array('error'=> true, 'message'=>$posible_transferir )));
            }          

        }

        //ASIGNAR A ALMACÉN TIENDA FÍSICA o a TIENDA ONLINE
        $almacenasignadoconexito = 0;
        //Si en el select se ha seleccionado Asignar algún almacén:
        if (($tipo_gestion_stock == 5)||($tipo_gestion_stock == 6)){
            if ($cantidad_a_gestionar == '') {
                //Si tipo gestion es 5 en el select es Asignar almacén Tienda Física a producto id_warehouse Tienda Mugica es 4
                if($tipo_gestion_stock == 5){
                   $almacenAsignado = 4;
                }

                //Si tipo gestion es 6 en el select es Asignar almacén Tienda Online a producto id_warehouse Almacen Online es 1
                if($tipo_gestion_stock == 6){
                    $almacenAsignado = 1;
                }

                //crear nueva entrada en tabla lafrips_warehouse_product_location (asignar almacén)
                $warehouse_location_entity = new WarehouseProductLocation();
                $warehouse_location_entity->id_product = $product[0];   //id_product
                $warehouse_location_entity->id_product_attribute = $product[1]; //id_product_attribute
                $warehouse_location_entity->id_warehouse = $almacenAsignado;   //id_warehouse (4 es tienda física, 1 tienda online)
                $warehouse_location_entity->location = '';  //localización, vacia puesto que en tienda física no usamos y en la online habría que colocarlo
                $warehouse_location_entity->save();

                //actualizar stock_available tras la operación
                StockAvailable::synchronize($product[0]);

                //creamos una variable que indicará a efectos de la respuesta ajax más abajo que ha añadido un almacén
                $almacenasignadoconexito = 1;


            } else {//si  han metido una cantidad cuando seleccionan asignar almacén les muestra error
                die(Tools::jsonEncode(array('error'=> true, 'message'=>'¿Para qué introduces una cantidad?')));
            }
        }

        //Si se selecciona Separador para Nacho sacar error
        if ($tipo_gestion_stock == 10){
            die(Tools::jsonEncode(array('error'=> true, 'message'=>'¡¡Atent@ a lo que haces!!')));
        }

        //EAN13
        //Para que si borramos el ean13 lo interprete como borrar, de momento le obligo a que siempre actualice el ean13 a lo que haya en el formulario
        //if(($ean13)||($weight)){ Hemos quitado peso de la vista del módulo
        if($ean13 || !$ean13){
            if($product[1])
                $pa = new Combination($product[1]);
            else
                $pa = new Product($product[0]);

            $pa -> ean13 = $ean13;
            //$pa -> location = $location;
            //$pa -> weight = $weight;
            $response &= $pa ->save();
        }

        //LOCALIZACIONES
        //Si en el select arriba hemos seleccionado un almacen que no sea almacén online (id_warehouse =1) no queremos procesar ningún cambio en las localizaciones, ni de picking ni de reposición, ya que para la tienda física no utilizamos, de modo que ignoramos todo el proceso referente a las localizaciones. De todas formas, cuando estemos procesando en almacén tienda mugica, se ocultarán las casillas de localización

        if ($id_warehouse == 1 ){

            //comprobar si el producto está en la tabla localizaciones, si no está (el resultado de buscarlo en la tabla es empty()), hacer un insert, introduciendo en la tabla el id_product y el id_product_attribute. A partir de ahí, en las lineas posteriores se hacen updates sobre el producto en la tabla

            $sql = "SELECT id_product, id_product_attribute FROM lafrips_localizaciones WHERE id_product = ".$product[0]." AND id_product_attribute = ".$product[1]." ;";
            $respuesta = Db::getInstance()->ExecuteS($sql); 

            if(empty($respuesta)){
                    $sql = "INSERT INTO lafrips_localizaciones(id_product, id_product_attribute, date_add) VALUES (".$product[0].",".$product[1].",  NOW()) ;";
                    Db::getInstance()->ExecuteS($sql);
                }
            /*
            //Si $location está vacio (se toma como que se introduce el valor vacio) o si no está vacio pero encaja con el regex exigido, se mete a location el valor en $location. Si no, es decir, si no está vacio pero no encaja con regex, sacamos mensaje de error
            //Se pone un regex que deje pasar localizaciones de 8 caracteres de los cuales, solo puede empezar por 0 o 1, luego 0, 1 o 2 y el 3 y 4 solo pueden ser 00, 01 o 02 (00 son la parte de arriba de las estanterias, 01 y 02 los cuerpos 1 y 2) El resto son 4 números del 0 al 9. 
            if ((!$location)||(preg_match("/^[0-1][0-2][0][0-2][0-9]{4}$/", $location))){
                //$location tiene algo y encaja en regex, lo metemos en location
                $id_warehouse = Warehouse::getWarehousesByProductId ($product[0], $product[1]);
                if($id_warehouse){
                    $response &= WarehouseCore::setProductLocation($product[0], $product[1], $id_warehouse, $location);
                }
                //después de introducir la localización en lafrips_products, hacerlo en tabla auxiliar lafrips_localizaciones
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), p_location = '".$location."' WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";
                Db::getInstance()->ExecuteS($sql);
            }else {
                //$location tiene algo pero no encaja en regex, lanzamos error
                die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['localizacion__picking_incorrecta'])));
            }
            */

            //filtrar $location temporalmente, hasta que se implante un formato. Ahora solo impide locations de más de 9 caracteres:
            if ((!$location)||(preg_match("/^[0-9a-zA-Z]{0,9}$/", $location))){
                //$location tiene algo y encaja en regex, lo metemos en location, o está vacio y lo vaciamos
                /*$id_warehouse = Warehouse::getWarehousesByProductId ($product[0], $product[1]);
                if($id_warehouse){
                    $response &= WarehouseCore::setProductLocation($product[0], $product[1], $id_warehouse, $location);
                }*/

                WarehouseCore::setProductLocation($product[0], $product[1], $id_warehouse, $location);
                //después de introducir la localización en lafrips_products, hacerlo en tabla auxiliar lafrips_localizaciones
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), p_location = '".$location."' WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";
                Db::getInstance()->ExecuteS($sql);

                //03/01/2023 metemos log en lafrips_localizaciones_log. Utilizamos mensaje error para indicar que hacemos el insert desde el localizador
                $mensaje_error = 'UPDATE localización Localizador';
                $id_product = $product[0];
                $id_product_attribute = $product[1];
                $id_producto = $id_product.'_'.$id_product_attribute;
                $id_empleado = Context::getContext()->employee->id;
                $nombre_empleado = Context::getContext()->employee->firstname;

                $sql_insert_localizaciones_log = "INSERT INTO lafrips_localizaciones_log
                (id_producto,
                id_product,
                id_product_attribute,                
                localizacion,                             
                id_employee,
                nombre_employee,                
                mensaje_error,
                date_add)
                VALUES
                ('$id_producto',
                $id_product,
                $id_product_attribute,                
                '$location',                             
                $id_empleado,
                '$nombre_empleado',                    
                '$mensaje_error',
                NOW())";
            
                Db::getInstance()->Execute($sql_insert_localizaciones_log);
            }else {
                //$location tiene algo pero no encaja en regex, lanzamos error
                die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['localizacion_picking_incorrecta'])));
            }

           

            /*if($p_cantidad){
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), p_cantidad = ".$p_cantidad." WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";
                Db::getInstance()->ExecuteS($sql);
            }*/
    /*  FILTRO PARA LOCALIZACIONES DE REPOSICIÓN de momento lo dejamos libre 22/08/2018
            //filtrar $r_location temporalmente, hasta que se implante un formato. Ahora solo impide r_locations de más de 9 caracteres:
            if ((!$r_location)||(preg_match("/^[0-9a-zA-Z]{0,9}$/", $r_location))){
                //$r_location tiene algo y encaja en regex, lo metemos en r_location, o está vacio y lo vaciamos
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), r_location = '".$r_location."' WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";
                Db::getInstance()->ExecuteS($sql);
            }else {
                //$r_location tiene algo pero no encaja en regex, lanzamos error
                die(Tools::jsonEncode(array('error'=> true, 'message'=>$this->module->i18n['localizacion_repo_incorrecta'])));
            }
    */
            //de momento no filtramos la localización hasta que se aclare la forma de hacerlo
            //sacamos r_location de lafrips_localizaciones y comparamos con lo que haya en la casilla en el formulario del módulo, si es diferente lo cambiamos, así, si está vacio vacia también
            $sql = "SELECT r_location AS localizacion_repo FROM lafrips_localizaciones WHERE id_product = ".$product[0]." AND id_product_attribute = ".$product[1]." ;";
            $respuesta = Db::getInstance()->ExecuteS($sql); 
            foreach ($respuesta as $resultado) {
                    $localizacion_repo = $resultado['localizacion_repo'];
                }


            if ($r_location != $localizacion_repo){
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), r_location = '".$r_location."' WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";

                Db::getInstance()->ExecuteS($sql);

                //03/01/2023 metemos log en lafrips_localizaciones_log. Utilizamos mensaje error para indicar que hacemos el insert desde el localizador
                $mensaje_error = 'UPDATE reposición Localizador';
                $id_product = $product[0];
                $id_product_attribute = $product[1];
                $id_producto = $id_product.'_'.$id_product_attribute;
                $id_empleado = Context::getContext()->employee->id;
                $nombre_empleado = Context::getContext()->employee->firstname;

                $sql_insert_localizaciones_log = "INSERT INTO lafrips_localizaciones_log
                (id_producto,
                id_product,
                id_product_attribute,                
                reposicion,                             
                id_employee,
                nombre_employee,                
                mensaje_error,
                date_add)
                VALUES
                ('$id_producto',
                $id_product,
                $id_product_attribute,                
                '$r_location',                             
                $id_empleado,
                '$nombre_empleado',                    
                '$mensaje_error',
                NOW())";
            
                Db::getInstance()->Execute($sql_insert_localizaciones_log);
            }

            //Para cantidades de reposición (no usuado)
            /*if($r_cantidad){
                $sql = "UPDATE lafrips_localizaciones SET date_upd = NOW(), r_cantidad = '".$r_cantidad."' WHERE id_product = '".$product[0]."' AND id_product_attribute = '".$product[1]."' ;";
                Db::getInstance()->ExecuteS($sql);
            }*/
        } //fin if (id_warehouse == 1)

        //ACTUALIZAR STOCKS en cada producto al pulsar Actualizar
        //Creamos instancia de stockmanager para obtener los valores de stock actualizados
        $stock_manager = new StockManager();
        //creamos array para almacenar los stocks que quiero devolver via ajax para que se actualicen cada vez que se cambia en un producto
        $stock = array();
        //le vamos introduciendo por orden el stock available del producto, luego el físico almacén online (id 1) y el físico tienda física Múgica (id 4)
        $stock[0] = (int) StockAvailable::getQuantityAvailableByProduct($product[0], $product[1]);
        $stock[1] = (int) $stock_manager->getProductPhysicalQuantities($product[0], $product[1], 1,true);
        $stock[2] = (int) $stock_manager->getProductPhysicalQuantities($product[0], $product[1], 4,true);   
        //en $stock metemos tambien el nombre completo del producto, id_product más id_product_attribute
        $stock[3] = $product[0].'_'.$product[1];    
        //Enviamos abajo con ajax el almacén sobre el que se actua para actualizar bien el select


        //para actualizar si el producto ya tiene ambos almacenes y quitar el label y añadir las opciones de transferencia, miramos que almacenes tiene ahora asignado, si es más de uno es que los tiene y ponemos $todosalmacenes = 1, si no 0.
        //el select tiene que haber cogido valor 5 o 6 y el campo de gestión haber estado vacio:
        if ((($tipo_gestion_stock == 5)||($tipo_gestion_stock == 6))&&($cantidad_a_gestionar == '')){
            $almacenesasignados = Warehouse::getProductWarehouseList($product[0], $product[1]);

            $i = 0;
            foreach ($almacenesasignados as $almacen) { //si hay más de un almacén $i irá creciendo, si $i es mayor que 1 enviaremos al ajax que tiene ambos almacenes asignados            
                $i++;
            }
            //die(Tools::jsonEncode(array('error'=> true, 'message'=>'valor $i= '+$i)));
            if (($i > 1)&&($almacenasignadoconexito == 1)) {   
                $todosalmacenes = 1;
            } else {
                $todosalmacenes = 0; 
            }
        }
        
        //enviamos también de vuelta si el producto tiene asignado el almacén tienda física o no para quitar el check en el producto
       /* $wpl_id = (int)WarehouseProductLocation::getIdByProductAndWarehouse($product[0], $product[1], 3); 
                if (empty($wpl_id)) {   //si no lo ha encontrado, metemos el parámetro almacén tienda como 0, para que no esconda el check
                    $almacentienda = 0;
                } else {
                    $almacentienda = 1; //si lo tiene asignado, enviamos valor 1, que en el ajax en back.js implicará que esconda el check y ponga valor 0 al input hidden hasta que se vuelva a efectuar una busqueda
                }*/

        //enviamos de vuelta la respuesta de ajax con el mensaje de exito y los stocks e id de producto y si está en almacén tienda
        if($response)            
            die(Tools::jsonEncode(array('message'=>$this->module->i18n['update_success'],'stockavailable'=>$stock[0], 'stockfisicoonline'=>$stock[1], 'stockfisicotienda'=>$stock[2], 'productIDcompleto'=>$stock[3], 'todosalmacenes'=>(int)$todosalmacenes, 'almacenenuso'=>$id_warehouse)));
        
    }


    /**
     * AdminController::initContent() override
     * @see AdminController::initContent()
     */
    public function initContent() {
        $this->tpl_form_vars['currency'] = $this->context->currency;
        $this->tpl_form_vars['img_prod_dir'] = _THEME_PROD_DIR_;
        $this->tpl_form_vars['i18n'] = $this->module->i18n;
        parent::initContent();
    }

    /*
     *
     */
    public function setMedia(){
        parent::setMedia();
        $this->addJs($this->module->getPathUri().'views/js/back.js');
    }

}
