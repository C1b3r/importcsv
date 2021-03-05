<?php 
/**
 * PrestaShop module created by Arturo
 *
 * @author    arturo https://artulance.com
 * @copyright 2020-2021 arturo
 * @license   This program is not free software and you can't resell and redistribute it
 *
 * CONTACT WITH DEVELOPER
 * artudevweb@gmail.com
 */
class importcsv extends Module{
    public function __construct()
    {
        $this->name          = 'importcsv';
        $this->tab           = 'Blocks';
        $this->author        = 'artulance.com';
        $this->version       = '1.0.0';
        $this->bootstrap     = true;
        //le indicamos que lo construya
        /*El logo no hace falta definirlo, lo coge automáticamente de la carpeta si lo llamas logo.png */
        parent::__construct();
        $this->displayName = $this->l('Importa un csv predeterminado');
        $this->description = $this->l('Importa un csv específico');

    }

    public function install()
    {
        if(!parent::install() )
        {
            /* Como comprobación si no está instalado o si esta registrado en el hook de la home o en el hook del footer,devolverá false */
            return false;
        }else{
            //si esta bien instalado nos dirá que es true
            return true;
        }
    }

    public function unistall()
    {
        if(!parent::unistall() )
        {
                /* Como comprobación si no está desinstalado o si esta registrado en el hook de la home o en el hook del footer,devolverá false */
            return false;
        }else{
            return true;
        }
    }

    /* Función que solo es necesaria si hay algo que configurar del módulo, en este caso si */
    public function getContent()
    {
        return $this->postProcess() . $this->getForm();
    }

    public function getForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->title = $this->displayName;

        /*Puedo configurar cual va a ser mi campo de submit */
        $helper->submit_action = 'importar';
        $helper->fields_value['texto_header'] = Configuration::get('HOLI_MODULO_TEXTO_HOME');
        /*Asocio que cada campo tenga su valor correspondiente */
        $helper->fields_value['texto_footer'] = Configuration::get('HOLI_MODULO_TEXTO_FOOTER');
        


    $this->form[0] = array(
            'form' => array(
                'legend' => array(
                   /* 'title' => $this->displayName*/
                    'title' => $this->l('Texto en la principal ')
                 ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Fichero csv'),
                        'desc' => $this->l('Formato: Nombre,Referencia,EAN13,Precio de coste,Precio de venta,IVA,Cantidad,Categorias,Marca'),
                        'hint' => $this->l('Fichero csv '),
                        'name' => 'archivo',
                        'accept'=>'.csv',
                        'lang' => false,
                     ),
                 ),
                'submit' => array(
                    'title' => $this->l('Save')
                 )
             )
         );
       
        return $helper->generateForm( $this->form );
    }

    public function postProcess()
    {
        /* El submit con lo que hayamos configurado el campo en el getform */
        if (Tools::isSubmit('importar')) {
                if($this->comprobarextension($_FILES['archivo']['name'])){
                      /* Devuelvo un mensaje de confirmación si se actualiza adecuadamente */
                      $this->parsearCsv($_FILES['archivo']);
                      return $this->displayConfirmation($this->l('Updated Successfully'));
                    // return $this->displayConfirmation($_FILES['archivo']['name']);
                }else{
                   // error_log(print_r($_FILES, 1));
                    return $this->displayError($this->l('Wrong format'));
                   /* return $this->displayError(error_log(print_r($_FILES['archivo']['name'])));*/
                    
                }

          
        }
    }

public function comprobarextension($file)
{

   $allowed='csv,xls';  //which file types are allowed seperated by comma

   $extension_allowed=  explode(',', $allowed);
    $file_extension=  pathinfo($file, PATHINFO_EXTENSION);
    if(in_array($file_extension, $extension_allowed))
    {
       return true;
    }
    else
    {
        //error_log(print_r($_FILES, 1));
       return false;
    }
}

public function readCSV($csvFile){
    $file_handle = fopen($csvFile, 'r');
    $line_of_text = array();
    while (!feof($file_handle) ) {
        $line_of_text[] = fgetcsv($file_handle, 0, ',');
    }
    fclose($file_handle);
    return $line_of_text;
}

public function parsearCsv($adjunto)
{
    $csv = $this->readCSV($adjunto['tmp_name']);
    $totRows = count($csv);

    if ($totRows<2) {
        $this->controller->errors[] = $this->l('Formato de excel erroneo, comprueba las filas.');
        return false;
    }
    //$csvTitles = array();
    $csvContent = array();
    $obligatorias = array('nombre', 'referencia', 'ean13', 'precio de coste', 'precio de venta', 'iva', 'cantidad', 'categorias' , 'marca');

    $separado_por_comas = strtolower(implode(",", $csv[0]));
    //echo $separado_por_comas;
    $csvTitles=explode(",", $separado_por_comas);
   /*print_r($junto_de_nuevo);*/


    $intersect = count(array_intersect($obligatorias, $csvTitles));

    if ($intersect != count($obligatorias)) {
        $this->controller->errors[] = $this->l('Faltan columnas obligatorias');
        return false;
    }
    //Create associative array with titles for each row
    array_shift($csv);
    $idxRow = 0;
    foreach ($csv as $row) {
        $idxCol = 0;
        if (!empty($row)) {
            foreach ($row as $col) {
                $this->l('Faltan columnas obligatorias');
                $csvContent[$idxRow][$csvTitles[$idxCol]] = $col;
                $idxCol++;
            }
        }
        $idxRow++;
    }

    $this->desmigarCSV($csvContent);
    

}   

public function desmigarCSV($csv){
    $limite=count($csv);
    for ($i=0; $i <$limite ; $i++) {  
        //lo declaro aquí porque necesito limpiarlo
           $id_categoria=[];
       // $id_categoria[]=$this->crearCategoria($csv[$i]['categorias']);
        $id_categoria[]=$this->crearCategoria($csv[$i]['categorias']);
        $id_fabricante=$this->crearFabricante($csv[$i]['marca']);
        $nombre=$csv[$i]['nombre'];
        $referencia=$csv[$i]['referencia'];
        $ean13=$csv[$i]['ean13'];
        $preciocoste=$csv[$i]['precio de coste'];
        $precioventa=$csv[$i]['precio de venta'];
        $iva=$csv[$i]['iva'];
        $cantidad=$csv[$i]['cantidad'];

        $this->crearproducto($id_categoria,$id_fabricante,$nombre,$referencia,$ean13,$preciocoste,$precioventa,$iva,$cantidad);

     
       }
       return $this->displayConfirmation($this->l('Updated Successfully'));
}

public function crearproducto($id_categoria,$id_fabricante,$nombre,$referencia,$ean13,$preciocoste,$precioventa,$iva,$cantidad){
    //$this->debug($iva);
    $product = new Product();
    $product->reference = $referencia;
    $product->name =  array((int)(Configuration::get('PS_LANG_DEFAULT')) => $nombre);  // Unicode ěščřžý , éůú
    $product->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  Tools::str2url($nombre));
    $product->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => " ");  //  unicode
    $product->description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => " "); // unicode
  /*  echo "eee";
var_dump($id_categoria);
echo "ooo";*/
    $product->id_category_default = $id_categoria[0][0];  
  //  $product->category = $id_categoria[0][0]; 
  
    $product->redirect_type = '404';
    $product->minimal_quantity = 1;
   // $product->quantity = $cantidad;
   //$product ->id_tax_rules_group = 0;
   $product ->id_tax_rules_group =  $this->seartax($iva);
 
    $product->show_price = 1;
    $product->on_sale = 0;
    $product->online_only = 1;                        
    //$price = round(("0.25" / '1.25'),6); // '1.25' is VAT
   // $product->ecotax = $iva; 
   $product->ecotax = '0.000000';
    $product->price =  Validate::isPrice($precioventa);
    $product->wholesale_price = $preciocoste;// '0.000000';
    /*$product->ean13 = Validate::isEan13($ean13);*/
    $product->ean13 = $ean13;
    //$product->quantity = '30';  // 30
    $product->id_manufacturer=$id_fabricante;
    //$product->Add();
   $product->Add();
   //una vez lo he metido, actualizo las categorías del producto, tenía que seleccionar que posición del array meter(en este caso hace un array de 0 y ahí mete los elementos)
   $product->updateCategories($id_categoria[0]);
   StockAvailable::setQuantity ($product->id ,null, $cantidad ); // Si no tiene un id_product_attribute, déjelo como "nulo". 
  //$product->updateCategories(array('74','75','76'));

  /* $product->update();*/
  
}

public function crearCategoria($datoscat){
    $home = (int)Configuration::get('PS_HOME_CATEGORY');
   //Cambio el string de ; a , 
   $nombres=str_replace(";",",",$datoscat);
   //Después lo paso a un array xd
   $nombrearray=explode(",",$nombres);
/*var_dump($nombrearray);
 $this->debug($nombrearray);*/

	$arraydecategorias=array();
    $limite=count( $nombrearray);
//echo $limite;
    for ($i=0; $i <$limite ; $i++) { 
        $category = new Category();
        $category->name= array((int)(Configuration::get('PS_LANG_DEFAULT')) =>  $nombrearray[$i]); 
                //$category->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  Tools::str2url('aaaaa'));
        $category->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  Tools::str2url($nombrearray[$i]));
        $category->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => "");  //  unicode
        $category->description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => ""); // unicode
        $category->id_parent = $home; // Para que las puedas visualizar,primero tienen que ser hijas del primer elemento raiz tiene que ser un 2
        $category->active = 1;
        
               if(!$id_category=Db::getInstance()->getValue('SELECT id_category FROM '._DB_PREFIX_.'category_lang WHERE name in("'.pSQL($nombrearray[$i]).'")')){
               
            $category->add();
          //  return $id_category_insert =Db::getInstance()->Insert_ID();
         // return $category->id; // prestashop me dará el ultimo id insertado.
         array_push($arraydecategorias, $category->id);
                }
        else{
            //return false;
            array_push($arraydecategorias, $id_category);
           // return $id_category;
            //sea falso o no, retorna el ultimo id que ha conseguido
        }  
    //var_dump($arraydecategorias);
        
    }//fin for


return $arraydecategorias;
}

public function crearFabricante($nombre){
    $manucfacturer = new Manufacturer();


    $manucfacturer->name= Validate::isCatalogName($nombre); 
        $manucfacturer->name = $nombre;
        $manucfacturer->active = 1;
       /* $manucfacturer->date_add = $fechahoy;
        $manucfacturer->date_upd = $fechahoy;*/
        $manucfacturer->description = '';
        $manucfacturer->short_description = '';
        if(!$id_manufacturer=Db::getInstance()->getValue('SELECT id_manufacturer FROM '._DB_PREFIX_.'manufacturer WHERE name="'.pSQL($nombre).'"')){
            $manucfacturer->add();
          //  return $id_manufacturer_insert =Db::getInstance()->Insert_ID();
          return $manucfacturer->id; // prestashop me dará el ultimo id insertado.
                }
        else{
            //return false;
            return $id_manufacturer;
            //sea falso o no, retorna el ultimo id que ha conseguido
        }   
        
}
public function debug($texto){
    $logfilename = dirname(__FILE__).'/log.log';
/*$texto=var_dump($texto);*/
    file_put_contents($logfilename, date('M d Y G:i:s') . ' -- ' . $texto . "\r\n", is_file($logfilename)?FILE_APPEND:0);
}

public function seartax($iva){
    //busco por nombre que impuesto tiene para encontrar a que grupo pertenece
    $result = Db::getInstance()->getRow('
    SELECT `id_tax_rules_group`
    FROM `'._DB_PREFIX_.'tax_rules_group`
    WHERE `name` LIKE "%'.$iva.'%"'
);
/*$this->debug($result['id_tax_rules_group']);*/

return $result['id_tax_rules_group'];
}


}



?>