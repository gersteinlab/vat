<?php defined('VAT_SRC') or die('No direct script access.');

class Model_Dataset extends Model {
    
    protected $_table = 'datasets';
    
    protected $_primary_key = 'id';
    
    protected $_fields = array(
        'id',
        'title',
        'description',
        'annotation_file',
        'variant_type',
        'raw_filename',
        'status'
    );

    
}