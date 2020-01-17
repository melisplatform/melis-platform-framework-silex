return array(
    'table' => array(
        'ajaxUrl' => '/melis/silex-get-table-data',
        'dataFunction' => '',
        'ajaxCallback' => '',
        'attributes' => [
            'id' => '[tcf-name]Table',
            'class' => 'table table-stripes table-primary dt-responsive nowrap',
            'cellspacing' => '0',
            'width' => '100%',
        ],
        'filters' => array(
            'left' => array(
                'show' => "l",
            ),
            'center' => array(
                'search' => "f"
            ),
            'right' => array(
                'refresh' => '<a class="btn btn-default silex[tcf-name]TableRefreshBtn" data-toggle="tab" aria-expanded="true" title="refresh"><i class="fa fa-refresh"></i></a>'
            ),
        ),
        'columns' => array(
            [tcf-table-col-conf]
        ),
        'searchables' => array(
            '[tcf-db-table-cols]'
        ),
        'actionButtons' => array(
            'edit' => '<button class="btn btn-success btn_[tcf-name-trans]_edit"><i class="fa fa-pencil"></i></button>',
            'delete' => '<button class="btn btn-danger btn_[tcf-name-trans]_delete"><i class="fa fa-times"></i></button>'
        ),
    ),
);