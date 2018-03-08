<?php

namespace gorriecoe\Menu\Models;

// use GridFieldOrderableRows;

use gorriecoe\Menu\Models\MenuLink;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * MenuSet
 *
 * @package silverstripe-menu
 */
class MenuSet extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'MenuSet';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Menu';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Menus';

    /**
     * Database fields
     * @var array
     */
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Slug' => 'Varchar(255)',
        'Nested' => 'Boolean'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Links' => MenuLink::class,
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'Links.Count' => 'Links'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        // Requirements::javascript('gorriecoe/silverstripe-menu:client/js/menutree.js');

        $fields = FieldList::create(
            TabSet::create(
                'Root',
                Tab::create('Main')
            )
            ->setTitle(_t(__CLASS__ . '.TABMAIN', 'Main'))
        );

        $fields->addFieldToTab(
            'Root.Main',
            GridField::create(
                'Links',
                _t(__CLASS__ . '.FIELDLINKS', 'Links'),
                $this->Links(),
                GridFieldConfig_RelationEditor::create()
                    ->addComponent(new GridFieldOrderableRows('Sort'))
            )
        );

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Creating Permissions.
     * This module is not intended to allow creating menus via CMS.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * Deleting Permissions
     * This module is not intended to allow deleting menus via CMS
     * @param mixed $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * Editing Permissions
     * @param mixed $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return Permission::check('SITEMENUEDIT', 'any', $member);
    }

    /**
     * Viewing Permissions
     * @param mixed $member
     * @return boolean
     */
    public function canView($member = null)
    {
        return Permission::check('SITEMENUEDIT', 'any', $member);
    }

    /**
     * Set up default records based on the yaml config
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $default_menu_sets = $this->config()->get('sets') ?: array();
        foreach ($default_menu_sets as $slug => $options) {
            if (is_array($options)) {
                $title = $options['title'];
                $nested = isset($options['nested']) ? $options['nested'] : true;
            } else {
                $title = $options;
                $nested = true;
            }
            $slug = Convert::raw2htmlid($slug);
            $existingRecord = MenuSet::get()->find('Slug', $slug);
            if (!$existingRecord) {
                $set = MenuSet::create();
                $set->Slug = $slug;
                $set->Title = $title;
                $set->Nested = $nested;
                $set->write();
                DB::alteration_message("Menu '$title' created", 'created');
            }
        }
    }

    /**
     * Generates a link to edit this page in the CMS.
     *
     * @return string
     */
    public function CMSEditLink() {
        return Controller::join_links(
            Controller::curr()->Link(),
            'EditForm',
            'field',
            $this->ClassName,
            'item',
            $this->ID
        );
    }

    /**
     * Relationship accessor for Graphql
     * @return ManyManyList MenuLink
     */
    public function getLinks()
    {
        return $this->Links();
    }
}