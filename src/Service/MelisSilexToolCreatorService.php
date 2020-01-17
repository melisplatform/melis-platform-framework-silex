<?php
/**
 * Created by PhpStorm.
 * User: melis
 * Date: 12/16/2019
 * Time: 3:44 PM
 */

namespace MelisPlatformFrameworkSilex\Service;


use Zend\Form\Form as zendForm;

class MelisSilexToolCreatorService
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function createTool(){
        $this->createToolDirectories();
        $this->createTableConfig();
        $this->createController();
        $this->createTranslations();
        $this->createTemplates();
        $this->createProvider();
    }

    public  function createController(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];
        $pathToCreate = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name']."/src/Controllers";
        if (!file_exists($pathToCreate)) {
            mkdir($pathToCreate,077);
        }

        $tableName = $toolCreatorData['step3']['tcf-db-table'];
        $toolName = $toolCreatorData['step1']['tcf-name'];
        $sql = "SHOW KEYS FROM `" . $tableName . "` WHERE Key_name = 'PRIMARY'";
        $primaryKey = $this->app['dbs']['melis']->fetchAssoc($sql)['Column_name'];

        $requiredFields = $toolCreatorData['step5']['tcf-db-table-col-required'] ?? array();
        $tcfRequireFields = "[";
        $ctr = 0;
        foreach($requiredFields as $requiredField){
            $ctr++;
            if($ctr == count($requiredFields)){
                $tcfRequireFields = $tcfRequireFields."\"".$requiredField."\"]";
            }else{
                $tcfRequireFields = $tcfRequireFields."\"".$requiredField."\",";
            }
        }

        $template = __DIR__."/../../install/moduleTemplate/src/Controllers/IndexController.php";
        $tmpData = file_get_contents($template);
        $tmpData = str_replace('[primary_key]',$primaryKey,$tmpData);
        $tmpData = str_replace('[tcf-db-table]',$tableName,$tmpData);
        $tmpData = str_replace('[tcf-db-table-col-required]',$tcfRequireFields,$tmpData);
        $tmpData = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpData);
        $data =  "<?php \n" . str_replace('[tcf-name]',$toolName,$tmpData);

        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR  ."IndexController.php",$data);
    }

    public  function createProvider(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];
        $pathToCreate = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name']."/src/Provider";
        if (!file_exists($pathToCreate)) {
            mkdir($pathToCreate,077);
        }

        $toolName = $toolCreatorData['step1']['tcf-name'];

        $template = __DIR__."/../../install/moduleTemplate/src/Provider/ToolLogicServiceProvider.php";
        $tmpData = file_get_contents($template);
        $data =  "<?php \n" . str_replace('[tcf-name]',$toolName,$tmpData);

        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR . $toolName."LogicServiceProvider.php",$data);
    }

    public  function createTemplates(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];
        $pathToCreate = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name']."/src/Templates";
        if (!file_exists($pathToCreate)) {
            mkdir($pathToCreate,077);
        }

        $toolName = $toolCreatorData['step1']['tcf-name'];

        $template = __DIR__."/../../install/moduleTemplate/src/Templates/index.template.html.twig";
        $tmpData = file_get_contents($template);
        $tmpData = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpData);
        $data =  str_replace('[tcf-name]',$toolName,$tmpData);
        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR . "index.template.html.twig",$data);

        $formInputs = $toolCreatorData['step5']['tcf-db-table-col-editable'];
        $formInputTypes = $toolCreatorData['step5']['tcf-db-table-col-type'];
        $formInputsRendered = '';
        $form = new zendForm();
        echo "<pre>";
        print_r($formInputTypes);
        echo "</pre>";
        die;
        foreach($formInputTypes as $key => $formInputType){
            $element = $this->app['melis.services']->getService("FormElementManager")->get($formInputType)->setName($formInputs[$key]);
            $form->add($formInputType);
        }

        $formRow = $this->app['melis.services']->getService("viewhelpermanager")->get('melisFieldCollection');
        $formInputsRendered = $formRow($form);

        $template = __DIR__."/../../install/moduleTemplate/src/Templates/form.template.html.twig";
        $tmpData = file_get_contents($template);
        $tmpData = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpData);
        $tmpData = str_replace('[tcf-form-inputs]',$formInputsRendered,$tmpData);
        $data = str_replace('[tcf-name]',$toolName,$tmpData);
        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR . "form.template.html.twig",$data);
    }

    public  function createTranslations(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];
        $pathToCreate = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name']."/src/Translations";
        if (!file_exists($pathToCreate)) {
            mkdir($pathToCreate,077);
        }

        $toolName = $toolCreatorData['step1']['tcf-name'];

        $toolEnTransArr = $toolCreatorData['step6']['en_EN']['pri_tbl'];
        $toolEnTransArr = array_merge($toolEnTransArr,$toolCreatorData['step2']['en_EN']);
        $toolEnTransStr = "";

        $toolFrTransArr = $toolCreatorData['step6']['fr_FR']['pri_tbl'];
        $toolFrTransArr = array_merge($toolFrTransArr,$toolCreatorData['step2']['fr_FR']);
        $toolFrTransStr = "";

        unset($toolEnTransArr["tcf-lang-local"]);
        unset($toolEnTransArr["tcf-tbl-type"]);

        foreach($toolEnTransArr as $key => $val)
            $toolEnTransStr = $toolEnTransStr . "\n\t\t". "'tr_".strtolower($toolName)."_".$key. "' => '" . $val . "',";
        foreach($toolFrTransArr as $key => $val)
            $toolFrTransStr = $toolFrTransStr . "\n\t\t". "'tr_".strtolower($toolName)."_".$key. "' => '" . $val . "',";

        $templateEn = __DIR__."/../../install/moduleTemplate/src/Translations/en_EN.interface.php";
        $tmpDataEN = file_get_contents($templateEn);
        $tmpDataEN = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpDataEN);
        $tmpDataEN = str_replace('[tcf-tool-trans]',$toolEnTransStr,$tmpDataEN);
        $tmpDataEN =  "<?php \n".$tmpDataEN;
        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR ."en_EN.interface.php",$tmpDataEN);

        $templateFr = __DIR__."/../../install/moduleTemplate/src/Translations/fr_FR.interface.php";
        $tmpDataFr = file_get_contents($templateFr);
        $tmpDataFr = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpDataFr);
        $tmpDataFr = str_replace('[tcf-tool-trans]',$toolEnTransStr,$tmpDataFr);
        $tmpDataFr =  "<?php \n".$tmpDataFr;
        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR ."fr_FR.interface.php",$tmpDataFr);
    }

    public  function createTableConfig(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];
        $pathToCreate = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name']."/config";
        if (!file_exists($pathToCreate)) {
            mkdir($pathToCreate,077);
        }

        $toolName = $toolCreatorData['step1']['tcf-name'];
        $tableCols = $toolCreatorData['step4']['tcf-db-table-cols'];
        $tableColsConfig = [];
        $ctr = 0;
        foreach ($tableCols as $tableCol){
            if($ctr == 0){
                $tableColsConfig = "'$tableCol' => array(".PHP_EOL.
                    "\t\t\t\t'text' => 'tr_" . strtolower($toolName) . "_".$tableCol."',".PHP_EOL.
                    "\t\t\t\t'css' => array('width' => '10%', 'padding-right' => '0'),".PHP_EOL.
                    "\t\t\t\t'sortable' => true,".
                "\n\t\t\t),";
            }else{
                $tableColsConfig = $tableColsConfig .PHP_EOL.
                    "\t\t\t'$tableCol' => array(".PHP_EOL.
                    "\t\t\t\t'text' => 'tr_" . strtolower($toolName) . "_".$tableCol."',".PHP_EOL.
                    "\t\t\t\t'css' => array('width' => '30%', 'padding-right' => '0'),".PHP_EOL.
                    "\t\t\t\t'sortable' => true,".
                    "\n\t\t\t),";
            }
            $ctr++;
        }

        $template = __DIR__."/../../install/moduleTemplate/config/MelisPlatformTable.config.php";
        $tmpData = file_get_contents($template);
        $tmpData = str_replace('[tcf-name-trans]',strtolower($toolName),$tmpData);
        $tmpData = str_replace('[tcf-db-table-cols]',implode("','",$tableCols),$tmpData);
        $tmpData = str_replace('[tcf-table-col-conf]',$tableColsConfig,$tmpData);
        $data =  "<?php \n" . str_replace('[tcf-name]',$toolName,$tmpData);

        $this->createFile($pathToCreate . DIRECTORY_SEPARATOR . $toolName."MelisPlatformTable.config.php",$data);
    }

    public function createToolDirectories(){
        $container = $_SESSION['melistoolcreator'];
        $toolCreatorData = $container["melis-toolcreator"];

        $moduleRoot = $_SERVER['DOCUMENT_ROOT']. "/../thirdparty/Silex/module/".$toolCreatorData['step1']['tcf-name'];
        mkdir("$moduleRoot");
        chmod("$moduleRoot", 0777);

        $srcFolder = $moduleRoot."/config";
        mkdir("$srcFolder");
        chmod("$srcFolder", 0777);

        $srcFolder = $moduleRoot."/src";
        mkdir("$srcFolder");
        chmod("$srcFolder", 0777);

        $srcSubFolder = $srcFolder."/Controllers";
        mkdir("$srcSubFolder");
        chmod("$srcSubFolder", 0777);

        $srcSubFolder = $srcFolder."/Provider";
        mkdir("$srcSubFolder");
        chmod("$srcSubFolder", 0777);

        $srcSubFolder = $srcFolder."/Templates";
        mkdir("$srcSubFolder");
        chmod("$srcSubFolder", 0777);

        $srcSubFolder = $srcFolder."/Translations";
        mkdir("$srcSubFolder");
        chmod("$srcSubFolder", 0777);
    }

    private function createFile($filePath,$contents)
    {
        // open a file or create
        $file = fopen($filePath, "w");
        // write file
        fwrite($file,$contents);
        // close file stream
        fclose($file);
    }

    private function renderFormElement($element){
        $formRow = $this->app['melis.services']->getService("viewhelpermanager")->get('formrow');
        return $formRow($element);
    }
    private function renderForm($form){
        $formRow = $this->app['melis.services']->getService("viewhelpermanager")->get('melisFieldCollection');
        return $formRow($form);
    }

}