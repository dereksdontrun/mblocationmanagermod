<?php
/**
 * Warehouse Location Manager powered by Modulebuddy™ (www.modulebuddy.com)
 *
 *  @author    Modulebuddy™ <info@modulebuddy.com>
 *  @copyright 2015-2016 Modulebuddy™
 *  @license   Check License.txt file in module root directory for End User License Agreement.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mblocationmanagermod extends Module
{
    protected $config_form = false;
    protected $admin_tab = array();
    public $i18n = array();

    public function __construct()
    {
        $this->name = 'mblocationmanagermod';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Sergio (bueno, más o menos)';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
		//$this->module_key = 'ad4eeb387531f2ff577507a41b3775ec';

        parent::__construct();

        $this->initTranslations();

        $this->admin_tab[] = array('classname' => 'AdminLocationManager', 'parent' => 'AdminStock', 'displayname' => $this->i18n['location_manager']);

        $this->displayName = $this->l('Localización de productos en almacén');
        $this->description = $this->l('Administrar localización de productos.');

        $this->confirmUninstall = $this->l('Are you sure you want to delete this module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }


    public function install()
    {
        if(!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
        {
            $this->displayError('Advanced stock management should be anable to use this module');
            return false;
        }
        include(dirname(__FILE__).'/sql/install.php');

        foreach ($this->admin_tab as $tab)
            $this->installTab($tab['classname'], $tab['parent'], $this->name, $tab['displayname']);

        return parent::install();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        foreach ($this->admin_tab as $tab)
            $this->unInstallTab($tab['classname']);

        return parent::uninstall();
    }

    /*
     * create menu tab
     */
    protected function installTab($classname = false, $parent = false, $module = false, $displayname = false) {
        if (!$classname)
            return true;

        $tab = new Tab();
        $tab->class_name = $classname;
        if ($parent)
            if (!is_int($parent))
                $tab->id_parent = (int) Tab::getIdFromClassName($parent);
            else
                $tab->id_parent = (int) $parent;
        if (!$module)
            $module = $this->name;
        $tab->module = $module;
        $tab->active = true;
        if (!$displayname)
            $displayname = $this->displayName;
        $tab->name[(int) (Configuration::get('PS_LANG_DEFAULT'))] = $displayname;

        if (!$tab->add())
            return false;

        return true;
    }

    protected function unInstallTab($classname = false) {
        if (!$classname)
            return true;

        $idTab = Tab::getIdFromClassName($classname);
        if ($idTab) {
            $tab = new Tab($idTab);
            return $tab->delete();
            ;
        }
        return true;
    }

    protected function initTranslations() {
        $source = basename(__FILE__, '.php');
        $this->i18n = array(
            'location_manager' =>           $this->l('Localización de Productos Frikis', $source),
            'submit' =>                     $this->l('Buscar', $source),
            'warehouse' =>                  $this->l('Almacén', $source),
            'location' =>                   $this->l('Localización Picking', $source),
            'reference' =>                  $this->l('Referencia', $source),
            'product' =>                    $this->l('Producto', $source),
            'name' =>                       $this->l('Nombre', $source),
            'ean' =>                        $this->l('EAN', $source),
            'update' =>                     $this->l('Actualizar', $source),
            'product_error' =>              $this->l('No lo encuentro', $source),
            'update_success' =>             $this->l('Producto actualizado', $source),
            'ava_quantity' =>               $this->l('Disponible', $source),
            'phy_quantity' =>               $this->l('Cantidad física', $source),
            'supplier_reference' =>         $this->l('Referencia Proveedor', $source),
            'barcode' =>                    $this->l('Producto', $source),
            'order_infos' =>                $this->l('Organizar Localización de Productos en Almacén', $source),
            'barcode_desc' =>               $this->l('Escribe la referencia, el ean o la referencia de proveedor del producto', $source),
            'warehouse_desc' =>             $this->l('Selecciona el almacén', $source),
            'product_location_manager' =>   $this->l('Organizar Localización de Productos', $source),
            'no_product' =>                 $this->l('No lo encuentro', $source),
            'no_warehouse_msg' =>           $this->l('No hay almacenes disponibles', $source),
            'informations1' =>              $this->l('Administrar localizaciones de productos por producto, por pedido a proveedores o por localización...', $source),
            /* MOD Sergio */
            'proveedores' =>               $this->l('Pedido de materiales', $source),
            'proveedores_desc' =>               $this->l('Escribe código de pedido a proveedores completo', $source),
            'localizacion' =>               $this->l('Localización', $source),
            'localizacion_desc' =>               $this->l('Escribe código de localización en el almacén', $source), 
            'reset' =>               $this->l('Limpiar', $source),    
            'no_categories_msg' =>           $this->l('No hay categorías disponibles', $source),   
            'category_desc' =>             $this->l('Selecciona una categoría', $source),    
            'categories' =>                  $this->l('Categorías', $source),
            'limpia_consulta' =>                  $this->l('¡Por favor, despeja el formulario, que me lías!', $source),
            'campos_vacios' =>                  $this->l('¿Qué buscas? Introduce algo.', $source),
            'weight' =>                  $this->l('Peso (kg)', $source),
            'location_repo' =>                   $this->l('Localización Reposición', $source),
            'location_cantidad' =>                   $this->l('Cantidad Picking', $source),
            'location_repo_cantidad' =>                   $this->l('Cantidad Reposición', $source),
            'localizacion_picking_incorrecta' =>               $this->l('El formato de localización de picking es incorrecto.', $source),
            'localizacion_repo_incorrecta' =>               $this->l('El formato de localización de reposición es incorrecto.', $source),
            'gestion_stock' =>               $this->l('Gestionar stock:', $source),
            'unidades_insuficientes' =>                   $this->l('¡No hay unidades suficientes para transferir!', $source),
            'phy_quantity_online' =>               $this->l('Almacén', $source),
            'phy_quantity_fisica' =>               $this->l('Tienda', $source),
            /* Fin MOD */
        );
    }
}
