namespace [tcf-name]\Controllers;

use MelisCore\Service\MelisCoreFlashMessengerService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class IndexController implements ControllerProviderInterface {

    public function connect(Application $app) {
        $factory=$app['controllers_factory'];
        $factory->get('/melis/[tcf-name-trans]/tool','[tcf-name]\Controllers\IndexController::renderIndex');
        $factory->get('/melis/silex-form','[tcf-name]\Controllers\IndexController::renderForm');
        $factory->post('/melis/silex-get-table-data','[tcf-name]\Controllers\IndexController::getTableData');
        $factory->post('/melis/silex-save','[tcf-name]\Controllers\IndexController::save');
        $factory->post('/melis/silex-edit','[tcf-name]\Controllers\IndexController::getData');
        $factory->post('/melis/silex-delete','[tcf-name]\Controllers\IndexController::delete');
        $factory->post('/melis/silex-translation','[tcf-name]\Controllers\IndexController::getTranslations');

        return $factory;
    }

    /**
     *
     * @param Application $app
     * @return mixed
     *
     * Renders the content view.
     */

    public function renderIndex(Application $app) {
        //getting data from melis db using MELIS PLATFORM SERVICES;
        $langSvc = $app['melis.services']->getService("MelisEngineLang");
        $langs = $langSvc->getAvailableLanguages();

        //This block of code below is the configuration of the data table that is same as the melis platform

        //getting config
        $config = include_once __DIR__."/../../config/[tcf-name]MelisPlatformTable.config.php";

        //Translate column names
        foreach ($config['table']['columns'] as $key => $column){
            $config['table']['columns'][$key]['text'] = $app['translator']->trans($column["text"]);
        }

        return $app['twig']->render('index.template.html.twig',array("langs" => $langs, "silexTableConfig" => $config['table']));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse fetch the list of albums from melis DB for the data table.
     *
     * fetch the list of data from melis DB for the data table.
     * @throws \Exception
     */
    public function getTableData(Application $app, Request $request){

        $tableData = array();

        //Data table config
        $config = include_once __DIR__."/../../config/[tcf-name]MelisPlatformTable.config.php";

        $params = $request->request->all();

        // sorting ASC or DESC
        $sortOrder = $params['order'][0]['dir'] ?? null;
        // column to sort
        $selCol    = $params['order'] ?? null;
        $colId     = array_keys($config['table']['columns']);
        $selCol    = $colId[$selCol[0]['column']] ?? null;
        // number of displayed item per page
        $draw      = $params['draw'] ?? null;
        // pagination start
        $start     = $params['start'] ?? null;
        // drop down limit
        $length    = $params['length'] ?? null;
        // search value from the table
        $search    = $params['search']['value'] ?? null;
        // get all searchable columns from the config
        $searchableCols = $config['table']['searchables'] ?? [];
        // get data from the service

        try {
            // fetching albums depending on the filters applied to the table
            $qb = new \Doctrine\DBAL\Query\QueryBuilder($app['dbs']['melis']);
            $qb->select("*");
            $qb->from("[tcf-db-table]");
            if (! empty($searchableCols) && !empty($search)){
                foreach ($searchableCols as $idx => $col) {
                    $expr = $qb->expr();
                    $qb->orWhere($expr->like($col, "'%" . $search . "%'"));
                }
            }
            $qb->setFirstResult($start)
                ->setMaxResults($length)
                ->orderBy($selCol,$sortOrder);

            $data = $qb->execute()->fetchAll();

        }catch (\Exception $err) {
            // return error
            throw new \Exception($err->getMessage());
        }

        if (! empty($searchableCols) && !empty($search)) {
            $tmpDataCount = count($data);
        }else{
            $sql = "SELECT * FROM [tcf-db-table]";
            $tmpDataCount = count($app['dbs']['melis']->fetchAll($sql));
        }
        $data = [
            'data' => $data,
            'dataCount' => $tmpDataCount
        ];

        // get total count of the data in the db
        $dataCount = $data['dataCount'];
        $albumData = $data['data'];
        // organized data
        $c = 0;

        foreach($albumData as $data){

            $tableData[$c]['DT_RowId'] = $data["[column_id]"];
            foreach($data as $key => $datum){
                $tableData[$c][$key] = $datum;
            }
            $c++;
        }

        return new JsonResponse(array(
            'draw' => $draw,
            'recordsTotal' => $dataCount,
            'recordsFiltered' => $dataCount,
            'data' => $tableData
        ));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     *
     * render the form modal for creating/editing
     */
    public function renderForm(Application $app, Request $request) {

        $params = !empty($request->query->get("parameters")) ? $request->query->get("parameters") : [];

        return $app['twig']->render('form.template.html.twig',array('alb' => $params));
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse save the album either creating or editing
     *
     * saving or editing content
     * @throws \Exception
     */
    public function save(Application $app, Request $request) {

        $message = $app['translator']->trans("tr_[tcf-name-trans]_save_album_ko");
        $errors = [];
        $icon = MelisCoreFlashMessengerService::WARNING;

        // post data
        $postData = $request->request->all();


        // validation
        $requiredFields = [tcf-db-table-col-required];
        $assertFields = array();

        foreach($requiredFields as $requiredField)
            $assertFields[$requiredField] = new Assert\NotBlank();

        $constraint = new Assert\Collection($assertFields);
        $validatorResults = $app['validator']->validate($postData, $constraint);

        foreach ($validatorResults as $validatorResult){
            $errors[str_replace(['[',']'],"",$validatorResult->getPropertyPath())] = [$app['translator']->trans($validatorResult->getMessage()),"label" => $app['translator']->trans(str_replace(['[',']'],"",$validatorResult->getPropertyPath()))];
        }

        if(!empty($request->get("[primary-key]"))){

            // updating album
            $title = $app['translator']->trans("tr_[tcf-name-trans]_edit_album");
            if(empty($errors))
            {
                try {
                    $id = $postData["[primary-key]"];
                    unset($postData["[primary-key]"]);

                    $qb = new \Doctrine\DBAL\Query\QueryBuilder($app['dbs']['melis']);
                    $qb->update("[tcf-db-table]");

                    foreach($postData as $key => $alb)
                        $qb->set($key,"'".$alb."'");

                    $qb->where("[primary-key] =".$id);
                    $qb->execute();

                    $success = 1;
                }catch (\Exception $err) {
                    // return error
                    throw new \Exception($err->getMessage());
                }
            }else{
                $success = 0;
            }

            if($success > 0){
                $message = $app['translator']->trans("tr_[tcf-name-trans]_save_album_ok");
                $icon = MelisCoreFlashMessengerService::INFO;
                $success = 1;
            }

            $id = $request->get("[primary-key]");
            $this->melisLog($app,$title,$message,$success,"SILEX_ALBUM_EDIT",$id);
            $this->melisNotification($app,$title,$message,$icon);

        }else {
            // creating album
            $title = $app['translator']->trans("tr_[tcf-name-trans]_new_album");
            if(!$errors) {
                try {
                    unset($postData['[primary-key]']);
                    $success = $app['dbs']['melis']->insert("[tcf-db-table]", $postData);
                } catch (\Exception $err) {
                    // return error
                    throw new \Exception($err->getMessage());
                }
            }else{
                $success = 0;
            }

            if($success > 0){
                $message = $app['translator']->trans("tr_[tcf-name-trans]_save_album_ok");
                $icon = MelisCoreFlashMessengerService::INFO;
                $success = 1;
            }

            $id = $app['dbs']['melis']->lastInsertId();
            $this->melisLog($app,$title,$message,$success,"SILEX_ALBUM_CREATE",$id);
            $this->melisNotification($app,$title,$message,$icon);

        }

        return new JsonResponse(array(
            "success" => $success,
            "title" => $title,
            "message" => $message,
            "errors" => $errors
        ));

    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse fetching data of the album to be edited
     *
     * fetching data of the album to be edited
     * @throws \Exception
     */
    public function getData(Application $app, Request $request) {

        try{
            // fetching data of album to be deleted
            $sql = 'SELECT * FROM [tcf-db-table] WHERE [primary-key] = :id';
            $album = $app['dbs']['melis']->fetchAssoc($sql, array(
                'id' => $request->get('id'),
            ));
        }catch (\Exception $err) {
            // return error
            throw new \Exception($err->getMessage());
        }

        $success = count($album) > 1 ? 1 : 0;

        return new JsonResponse(array(
            "success" => $success,
            "album" => $album,
        ));

    }

    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse fetching the album data to be deleted
     *
     * fetching the album data to be deleted
     * @throws \Exception
     */
    public function delete(Application $app, Request $request) {

        $message = $app['translator']->trans("tr_[tcf-name-trans]_delete_album_ko");
        $title = $app['translator']->trans("tr_[tcf-name-trans]_album_delete");
        $icon = MelisCoreFlashMessengerService::WARNING;

        try{
            $success = $app['dbs']['melis']->delete("[tcf-db-table]",array(
                "[primary-key]" => $request->get("id")
            ));
        }catch (\Exception $err) {
            // return error
            throw new \Exception($err->getMessage());
        }

        if($success > 0){

            $icon = MelisCoreFlashMessengerService::INFO;
            $message = $app['translator']->trans("tr_[tcf-name-trans]_delete_album_ok");
            $success = 1;

        }

        $id = $request->get('id');
        $this->melisLog($app,$title,$message,$success,"SILEX_ALBUM_CREATE",$id);
        $this->melisNotification($app,$title,$message,$icon);

        return new JsonResponse(array(
            "success" => $success,
            "title" => $title,
            "message" => $message
        ));

    }

    /**
     * @param Application $app
     * @return JsonResponse
     *
     * fetch all the translation using silex
     */
    public function getTranslations(Application $app) {

        $locale = empty( $app['locale']) ? "en" :  $app['locale'];
        $translation = $app['translator.domains']['messages'][$locale];

        return new JsonResponse(array(
            "success" => 1,
            "translation" => $translation
        ));

    }

    /**
     * @param Application $app silex application
     * @param string $title log title
     * @param string $message string log message
     * @param string $success action status
     * @param string $typeCode action log code
     * @param int $itemId id of the modified or created data
     *
     * logs action made in silex demo tool album in melis log module.
     */
    private function melisLog($app,$title,$message,$success,$typeCode,$itemId){
        $logSrv = $app['melis.services']->getService("MelisCoreLogService");
        $logSrv->saveLog($title, $message, $success, $typeCode, $itemId);
    }

    /**
     * @param Application $app
     * @param string $title
     * @param string $message
     * @param string $icon
     *
     *
     * add action made to the melis notification.
     */
    private function melisNotification($app, $title,$message,$icon = MelisCoreFlashMessengerService::INFO){

        $flashMessenger =  $app['melis.services']->getService('MelisCoreFlashMessenger');
        $flashMessenger->addToFlashMessenger($title, $message, $icon);
    }
}
