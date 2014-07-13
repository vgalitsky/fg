<?php

$this->startSetup();
$installer = $this;



$this->addAttribute('catalog_category', 'smiffys_code', array(
    'group'                    => 'Smiffys Attributes',
    'type'                     => 'text',
    'label'                    => 'Smiffys Code',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required'                 => false,
    'visible_on_front'         => false,
    'is_html_allowed_on_front' => false,
    'is_configurable'          => false,
    'searchable'               => false,
    'filterable'               => false,
    'comparable'               => false,
    'unique'                   => true,
    'user_defined'             => true,
    'is_user_defined'          => true,
));

$this->endSetup();
