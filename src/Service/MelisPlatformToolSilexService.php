<?php
namespace MelisPlatformFrameworkSilex\Service;

class MelisPlatformToolSilexService
{

    private $app;

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     *  This service will output a script that will render a data table with the same feature as a native melis platform modules.
     *
     * @param $tableConfig
     * @param null $targetTable
     * @param bool $allowReInit
     * @param bool $selectCheckbox
     * @param array $tableOption
     * @param string $type
     * @return string
     * @throws \Throwable
     */
    public function getDataTableConfiguration(
        $tableConfig,
        $targetTable = null,
        $allowReInit = false,
        $selectCheckbox = false,
        $tableOption = [],
        $type = '')
    {
        // start logic
        $table = $tableConfig;
        $dtJScript = '';
        if ($table) {
            $tableId = is_null($targetTable) ? $table['target'] : $targetTable;
            $ajaxUrl = $table['ajaxUrl'];
            $dataFunction = !empty($table['dataFunction']) ? 'data: ' . $table['dataFunction'] : '';
            $ajaxCallBack = !empty($table['ajaxCallback']) ? $table['ajaxCallback'] . ';' : '';
            $initComplete = !empty($table['initComplete']) ? $table['initComplete'] . ';' : '';
            $filters = $table['filters'];
            $columns = $table['columns'];
            $actionContainer = $table['actionButtons'];
            $jsSdomContentInit = '';
            $tableTop = '<"filter-bar"<"row"';
            $left = $filters['left'];
            $center = $filters['center'];
            $right = $filters['right'];
            $leftDom = '<"fb-dt-left col-xs-12 col-md-4"';
            $centerDom = '<"fb-dt-center col-xs-12 col-md-4"';
            $rightDom = '<"fb-dt-right col-xs-12 col-md-4"';
            // datatables predefined filter plugins
            $preDefDTFilter = ['l', 'f'];
            $searchInputClass = '';
            // render the buttons in the left section of the filter bar
            foreach ($left as $leftKey => $leftValue) {
                $htmlContent = $this->getViewContent($leftValue);
                if (!in_array($htmlContent, $preDefDTFilter)) {
                    $leftDom .= '<"' . $leftKey . '">';
                    $jsSdomContentInit .= '$(".' . $leftKey . '").html(\'' . $this->replaceQuotes($htmlContent) . '\');';
                } else {
                    $leftDom .= '<"' . $leftKey . '"' . $htmlContent . '>';
                    if ($htmlContent == 'f') {
                        $searchInputClass = $leftKey;
                    }
                }
            }
            // render the buttons in the center section of the filter bar
            foreach ($center as $centerKey => $centerValue) {
                $htmlContent = $this->getViewContent($centerValue);
                if (!in_array($htmlContent, $preDefDTFilter)) {
                    $centerDom .= '<"' . $centerKey . '">';
                    $jsSdomContentInit .= '$(".' . $centerKey . '").html(\'' . $htmlContent . '\');';
                } else {
                    $centerDom .= '<"' . $centerKey . '"' . $htmlContent . '>';
                    if ($htmlContent == 'f') {
                        $searchInputClass = $centerKey;
                    }
                }
            }
            // render the buttons in the right sectuib if the filter bar
            foreach ($right as $rightKey => $rightValue) {
                $htmlContent = $this->getViewContent($rightValue);
                if (!in_array($htmlContent, $preDefDTFilter)) {
                    $rightDom .= '<"' . $rightKey . '">';
                    $jsSdomContentInit .= '$(".' . $rightKey . '").html(\'' . $htmlContent . '\');';
                } else {
                    $rightDom .= '<"' . $rightKey . '"' . $htmlContent . '>';
                    if ($htmlContent == 'f') {
                        $searchInputClass = $rightKey;
                    }
                }
            }
            $tableSearchPlugin = '';
            if (!empty($searchInputClass)) {
                $tableSearchPlugin = '$(\'.' . $searchInputClass . ' input[type="search"]\').unbind();
                    	               $(\'.' . $searchInputClass . ' input[type="search"]\').typeWatch({
                            				captureLength: 2,
                            				callback: function(value) {
                        	                ' . str_replace("#", "$", $tableId) . '.search(value).draw();   
                            				}
                            			});';
            }
            $tableTop .= $leftDom . '>' . $centerDom . '>' . $rightDom . '>>>';
            $tableBottom = '<"bottom" t<"pagination-cont clearfix"rip>>';
            // check if the filter array configuration is empty
            if (empty($left) && empty($center) && empty($right)) {
                $sDomStructure = '';
            } else {
                // if not filters found, filter-bar class content should not be displayed
                $sDomStructure = $tableTop . $tableBottom;
            }
            // Action Buttons
            $actionButtons = '';
            $action = '';
            $actionCount = 0;
            foreach ($actionContainer as $actionKey => $actionContent) {
                $actionButtons .= $this->getViewContent($actionContent);
            }
            // remove unnecessary new lines and text paragraphs (not <p> tags)
            $actionButtons = trim(preg_replace('/\s+/', ' ', $actionButtons));
            // retrieve the css configuration inside each columns
            $colCtr = 1; // starts with index 1 since this will be used in JS configuration for jquery nth-child
            $colKeyId = array_keys($columns);
            // Action Column
            $actionColumn = null;
            // convert columns in Javascript JSON
            $jsonColumns = '[';
            foreach ($colKeyId as $colId) {
                $jsonColumns .= '{"data":"' . $colId . '"},';
            }
            if (!empty($actionButtons)) {
                $jsonColumns .= '{"data":"actions"}';
                // Preparing the Table Action column Buttons
                $actionColumn = '{
                                    "targets": -1,
                                    "data": null,
                                    "mRender": function (data, type, full) {
                                        return \'<div>' . $actionButtons . '</div>\';
        						    },
        						    "bSortable" : false,
        						    "sClass" : \'dtActionCls\',
        					    }';
            }
            $jsonColumns .= ']';
            $fnName = 'fn' . $tableId . 'init';
            $reInitTable = '';
            if ($allowReInit) {
                $reInitTable = '     
                var dTable = $("' . $tableId . '").DataTable();
                if(dTable !== undefined) {
                       dTable.destroy();    
                }';
            }
            // select checkbox extension
            $select = '';
            $selectColDef = '';
            if ($selectCheckbox) {
                $selectColDef = '{
                                "targets": 0,                                   
                                 "bSortable":false,                                 
                                 "mRender": function (data, type, full, meta){
                                     return `<div class="checkbox checkbox-single margin-none">
                									<label class="checkbox-custom">
                										<i class="fa fa-fw fa-square-o checked"></i>
                										<input type="checkbox" checked="checked" name="id[]" value="` + $("<div/>").text(data).html() + `">
                									</label>
                								</div>  
                                            `;
                                 }
                            },';
            }
            /**
             * DataTable default is every Column are sortable
             * This process will get not sortable column from tool config and prepare string for datatable configuration
             **/
            $unSortableColumns = [];
            $columnCtr = 0;
            foreach ($columns as $colKey => $colArrValue) {
                if (isset($colArrValue['sortable'])) {
                    // Getting unsortable columns
                    $isSortable = $colArrValue['sortable'] == false ? array_push($unSortableColumns, $columnCtr) : '';
                }
                $columnCtr++;
            }
            $unSortableColumnsStr = '';
            if (!empty($unSortableColumns)) {
                // Creating config string for Unsortable Columns
                $unSortableColumnsStr = '{ targets: [' . implode(',', $unSortableColumns) . '], bSortable: false},';
            }
            // Column Unsortable End
            // Preparing Table Column Styles
            $columnsStyles = [];
            $columnCtr = 0;
            foreach ($columns as $colKey => $colArrValue) {
                if (isset($colArrValue['css'])) {
                    // Getting Style of the columns
                    $columnStyles = $colArrValue['css'];
                }
                // Adding the Ctr/index/number of the column
                $columnStyles['targets'] = $columnCtr;
                array_push($columnsStyles, $columnStyles);
                $columnCtr++;
            }
            $columnsStylesStr = '';
            if (!empty($columnsStyles)) {
                // Creating Column config string
                foreach ($columnsStyles As $sVal) {
                    $columnStyle = [];
                    foreach ($sVal As $cKey => $cVal) {
                        if (in_array($cKey, ['width', 'targets', 'visible'])) {
                            $cVal = (is_numeric($cVal)) ? $cVal : '"' . $cVal . '"';
                            array_push($columnStyle, '"' . $cKey . '": ' . $cVal);
                        }
                    }
                    $columnsStylesStr .= '{ ' . implode(', ', $columnStyle) . ' },' . PHP_EOL;
                }
            }
            // Columns Styles End
            // Default Melis Table Configuration
            // This can be override from Param
            $defaultTblOptions = [
                'paging' => 'true',
                'ordering' => 'true',
                'serverSide' => 'true',
                'searching' => 'true',
            ];
            // Merging Default Configuration and Param Configuration
            // This process will override default config if index exist on param config
            $finalTblOption = array_merge($defaultTblOptions, $tableOption);
            // Table Option
            $finalTblOptionStr = '';
            foreach ($finalTblOption As $key => $val) {
                if (is_array($val)) {
                    // If Option has multiple options
                    $val = json_encode($val);
                }
                $finalTblOptionStr .= $key . ': ' . $val . ',' . PHP_EOL;
            }
            $language = '"/melis/MelisCore/Language/getDataTableTranslations"';
            if ($type) {
                $language = '"/melis/MelisCommerce/MelisComOrderCheckout/getDataTableTranslations"';
            }
            //remove special characters in function name
            $fnName = preg_replace('/\W/', '', $fnName);
            // simulate javascript code function here
            $dtJScript = 'window.' . $fnName . ' = function() {
                ' . $reInitTable . '
                var ' . str_replace("#", "$", $tableId) . ' = $("' . $tableId . '").DataTable({
                    ' . $select . '
                    ' . $finalTblOptionStr . '
                    responsive:true,
                    processing: true,
                    lengthMenu: [ [5, 10, 25, 50], [5, 10, 25, 50] ],
                    pageLength: 10,
                    ajax: {
                        url: "' . $ajaxUrl . '",
                        type: "POST",
                        ' . $dataFunction . '
                    },
                    initComplete : function(oSettings, json) {
                        ' . $initComplete . '  
                    },
                    fnDrawCallback: function(oSettings) {
                        ' . $ajaxCallBack . '
                    },
                    columns: ' . $jsonColumns . ',
				    language: {
                        url : ' . $language . ',
                    },
                    sDom : \'' . $sDomStructure . '\',
                    bSort: true,
                    searchDelay: 1500,
			        columnDefs: [
                        ' . $columnsStylesStr . '  
                        ' . $unSortableColumnsStr . '
					    ' . $selectColDef . '
					    { responsivePriority: 1, targets: 0 },';
            if ($actionColumn != "") {
                $dtJScript .= '{responsivePriority:2, targets: -1 },'; // make sure action column stays whenever the window is resized
            }
            $dtJScript .= $actionColumn . '
				    ],
                }).columns.adjust().responsive.recalc();
                return ' . str_replace("#", "$", $tableId) . ';
            };
            var ' . str_replace("#", "$", $tableId) . ' = ' . $fnName . '();
	        $("' . $tableId . '").on("init.dt", function(e, settings) {
			    ' . $jsSdomContentInit . '
		        ' . $tableSearchPlugin . '   
	        });';
        }
        return $dtJScript;
    }

    /**
     * Get the view of certain url
     * @param $config
     * @return array|string
     * @throws \Throwable
     */
    public function getViewContent($config)
    {
        $view = $config['view'] ?? null;
        if (empty($view)) {
            return "Not set \t";
        }
        return $this->app['twig']->render($view);
    }

    /**
     * Quote correction for better execution in queries
     *
     * @param $text
     *
     * @return string
     */
    private function replaceQuotes($text)
    {
        return str_replace(["'", "’"], chr(92) . "'", $text);
    }

}