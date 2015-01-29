<?php
/**
 * @version 3.0.6
 * @package JEM
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;


class JEMCategories
{

	/**
	 * Array to hold the object instances
	 *
	 * @var    array
	 * @since  11.1
	 */
	public static $instances = array();


	/**
	 * Array of category nodes
	 *
	 * @var    mixed
	 * @since  11.1
	 */
	protected $_nodes;

	/**
	 * Array of checked categories -- used to save values when _nodes are null
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $_checkedCategories;

	/**
	 * id
	 *
	 * @var int
	 */
	var $id = null;

	/**
	 * Parent Categories (name, slug), with top category first
	 *
	 * @var array
	 */
	var $parentcats = array();

	/**
	 * Category data
	 *
	 * @var array
	 */
	var $category = array();

	/**
	 * Constructor
	 *
	 * @param int category id
	 */
	public function __construct($cid,$options=false)
	{
		$this->id = $cid;
	}

	/**
	 * Instance
	 */
	public static function getInstance($cid,$options=false)
	{

		self::$instances = new JEMCategories($cid,$options);

		return self::$instances;

	}

	/**
	 * Loads a specific category and all its children in a CategoryNode object
	 *
	 * @param   mixed    $id         an optional id integer or equal to 'root'
	 * @param   boolean  $forceload  True to force  the _load method to execute
	 *
	 * @return  mixed    JCategoryNode object or null if $id is not valid
	 *
	 * @since   11.1
	 */
	public function get($id = 'root', $forceload = false)
	{
		if ($id !== 'root')
		{
			$id = (int) $id;

			if ($id == 0)
			{
				$id = 'root';
			}
		}

		// If this $id has not been processed yet, execute the _load method
		if ((!isset($this->_nodes[$id]) && !isset($this->_checkedCategories[$id])) || $forceload)
		{
			$this->_load($id);
		}

		// If we already have a value in _nodes for this $id, then use it.
		if (isset($this->_nodes[$id]))
		{
			return $this->_nodes[$id];
		}
		// If we processed this $id already and it was not valid, then return null.
		elseif (isset($this->_checkedCategories[$id]))
		{
			return null;
		}

		return false;
	}

	/**
	 * Load method
	 *
	 * @param   integer  $id  Id of category to load
	 */
	protected function _load($id)
	{
		$db = JFactory::getDbo();
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$this->_checkedCategories[$id] = true;

		$query = $db->getQuery(true);

		$case_when_c = ' CASE WHEN ';
		$case_when_c .= $query->charLength('c.alias');
		$case_when_c .= ' THEN ';
		$id_c = $query->castAsChar('c.id');
		$case_when_c .= $query->concatenate(array($id_c, 'c.alias'), ':');
		$case_when_c .= ' ELSE ';
		$case_when_c .= $id_c.' END as slug';

		$query->select(array('c.*',$case_when_c));
		$query->from('#__jem_categories as c');
		$query->join('LEFT', '#__jem_cats_event_relations AS rel ON rel.catid = c.id');

		$query->where('c.published = 1');

		###################################
		## FILTER - MAINTAINER/JEM GROUP ##
		###################################

		# as maintainter someone who is registered can see a category that has special rights
		# let's see if the user has access to this category.

		$query3	= $db->getQuery(true);
		$query3 = 'SELECT gr.id'
				. ' FROM #__jem_groups AS gr'
				. ' LEFT JOIN #__jem_groupmembers AS g ON g.group_id = gr.id'
				. ' WHERE g.member = ' . (int) $user->get('id')
				//. ' AND ' .$db->quoteName('gr.addevent') . ' = 1 '
				. ' AND g.member NOT LIKE 0';
		$db->setQuery($query3);
		$groupnumber = $db->loadColumn();

		$groups = implode(',', $user->getAuthorisedViewLevels());
		$jemgroups = implode(',',$groupnumber);

		if ($jemgroups) {
			$query->where('(c.access IN ('.$groups.') OR c.groupid IN ('.$jemgroups.'))');
		} else {
			$query->where('(c.access IN ('.$groups.'))');
		}

		#####################
		### FILTER - BYCAT ##
		#####################

		//$cats = $this->getCategories('all');
		//$query->where('c.id  IN (' . implode(',', $cats) . ')');

		$query->order('c.lft');

		// s for selected id
		if ($id != 'root')
		{
			// Get the selected category
			$query->where('s.id=' . (int) $id);
			{
				$query->leftJoin('#__jem_categories AS s ON (s.lft <= c.lft AND s.rgt >= c.rgt) OR (s.lft > c.lft AND s.rgt < c.rgt)');
			}
		}

		$subQuery = ' (SELECT cat.id as id FROM #__jem_categories AS cat JOIN #__jem_categories AS parent ' .
				'ON cat.lft BETWEEN parent.lft AND parent.rgt WHERE parent.published != 1 GROUP BY cat.id) ';
		$query->leftJoin($subQuery . 'AS badcats ON badcats.id = c.id');
		$query->where('badcats.id is null');

		// i for item
		// @todo: alter
		if (isset($this->_options['countItems']) && $this->_options['countItems'] == 1)
		{
			if ($this->_options['published'] == 1)
			{
				$query->leftJoin(
						$db->quoteName($this->_table) . ' AS i ON i.' . $db->quoteName($this->_field) . ' = c.id AND i.' . $this->_statefield . ' = 1'
				);
			}
			else
			{
				$query->leftJoin($db->quoteName($this->_table) . ' AS i ON i.' . $db->quoteName($this->_field) . ' = c.id');
			}

			$query->select('COUNT(i.' . $db->quoteName($this->_key) . ') AS numitems');
		}

		#############
		## GROUPBY ##
		#############

		$query->group('c.id, c.access, c.alias, c.level,
		 	c.lft, c.parent_id, c.path, c.published, c.rgt, c.catname');

		// Get the results
		$db->setQuery($query);
		$results = $db->loadObjectList('id');

		$childrenLoaded = false;

		if (count($results))
		{
			// Foreach categories
			foreach ($results as $result)
			{
				// Deal with root category
				if ($result->id == 1)
				{
					$result->id = 'root';
				}

				// Deal with parent_id
				if ($result->parent_id == 1)
				{
					$result->parent_id = 'root';
				}

				// Create the node
				if (!isset($this->_nodes[$result->id]))
				{
					// Create the JCategoryNode and add to _nodes
					$this->_nodes[$result->id] = new JEMCategoryNode($result, $this);

					// If this is not root and if the current node's parent is in the list or the current node parent is 0
					if ($result->id != 'root' && (isset($this->_nodes[$result->parent_id]) || $result->parent_id == 1))
					{
						// Compute relationship between node and its parent - set the parent in the _nodes field
						$this->_nodes[$result->id]->setParent($this->_nodes[$result->parent_id]);
					}

					// If the node's parent id is not in the _nodes list and the node is not root (doesn't have parent_id == 0),
					// then remove the node from the list
					if (!(isset($this->_nodes[$result->parent_id]) || $result->parent_id == 0))
					{
						# @todo: change
						# the unset has been disabled as it was giving errors when pointing to a subcategory of a category
						# with special rights

						//unset($this->_nodes[$result->id]);
						//continue;
					}

					if ($result->id == $id || $childrenLoaded)
					{
						$this->_nodes[$result->id]->setAllLoaded();
						$childrenLoaded = true;
					}
				}
				elseif ($result->id == $id || $childrenLoaded)
				{
					// Create the CategoryNode
					$this->_nodes[$result->id] = new JEMCategoryNode($result, $this);

					if ($result->id != 'root' && (isset($this->_nodes[$result->parent_id]) || $result->parent_id))
					{
						// Compute relationship between node and its parent
						$this->_nodes[$result->id]->setParent($this->_nodes[$result->parent_id]);
					}

					if (!isset($this->_nodes[$result->parent_id]))
					{
						unset($this->_nodes[$result->id]);
						continue;
					}

					if ($result->id == $id || $childrenLoaded)
					{
						$this->_nodes[$result->id]->setAllLoaded();
						$childrenLoaded = true;
					}

				}
			}
		}
		else
		{
			$this->_nodes[$id] = null;
		}
	}

	/**
	 * Retrieve Categories
	 *
	 * Due to multi-cat this function is needed
	 * filter-index (4) is pointing to the cats
	 */

	function getCategories($id = 0)
	{

		$id = (!empty($id)) ? $id : (int) $this->getState('event.id');

		$user 			= JFactory::getUser();
		$userid			= (int) $user->get('id');
		$levels 		= $user->getAuthorisedViewLevels();
		$app 			= JFactory::getApplication();
		$params 		= $app->getParams();
		$catswitch 		= $params->get('categoryswitch', '0');
		$settings 		= JemHelper::globalattribs();

		// Query
		$db 	= JFactory::getDBO();
		$query = $db->getQuery(true);

		$case_when_c = ' CASE WHEN ';
		$case_when_c .= $query->charLength('c.alias');
		$case_when_c .= ' THEN ';
		$id_c = $query->castAsChar('c.id');
		$case_when_c .= $query->concatenate(array($id_c, 'c.alias'), ':');
		$case_when_c .= ' ELSE ';
		$case_when_c .= $id_c.' END as catslug';

		$query->select(array('DISTINCT c.id','c.catname','c.access','c.checked_out AS cchecked_out','c.color',$case_when_c));
		$query->from('#__jem_categories as c');
		$query->join('LEFT', '#__jem_cats_event_relations AS rel ON rel.catid = c.id');

		$query->select(array('a.id AS multi'));
		$query->join('LEFT','#__jem_events AS a ON a.id = rel.itemid');

		if ($id != 'all'){
			$query->where('rel.itemid ='.(int)$id);
		}

		$query->where('c.published = 1');

		###################################
		## FILTER - MAINTAINER/JEM GROUP ##
		###################################

		# as maintainter someone who is registered can see a category that has special rights
		# let's see if the user has access to this category.

		$query3	= $db->getQuery(true);
		$query3 = 'SELECT gr.id'
				. ' FROM #__jem_groups AS gr'
				. ' LEFT JOIN #__jem_groupmembers AS g ON g.group_id = gr.id'
				. ' WHERE g.member = ' . (int) $user->get('id')
				//. ' AND ' .$db->quoteName('gr.addevent') . ' = 1 '
				. ' AND g.member NOT LIKE 0';
		$db->setQuery($query3);
		$groupnumber = $db->loadColumn();


		$groups = implode(',', $user->getAuthorisedViewLevels());
		$jemgroups = implode(',',$groupnumber);

		if ($jemgroups) {
			$query->where('(c.access IN ('.$groups.') OR c.groupid IN ('.$jemgroups.'))');
		} else {
			$query->where('(c.access IN ('.$groups.'))');
		}

		$db->setQuery($query);

		if ($id == 'all'){
		$cats = $db->loadColumn(0);
			$cats = array_unique($cats);
			return ($cats);
		} else {
			$cats = $db->loadObjectList();
			}
			return $cats;
	}

	function getPath()
	{
		$db = JFactory::getDBO();
		$parentcats = array();
		$cid = $this->id;

		do {
			$sql = $db->getQuery(true);

			$sql->select('id, parent_id, catname');

			// Handle the alias CASE WHEN portion of the query
			$case_when_cat_alias = ' CASE WHEN ';
			$case_when_cat_alias .= $sql->charLength('alias');
			$case_when_cat_alias .= ' THEN ';
			$cat_id = $sql->castAsChar('id');
			$case_when_cat_alias .= $sql->concatenate(array($cat_id, 'alias'), ':');
			$case_when_cat_alias .= ' ELSE ';
			$case_when_cat_alias .= $cat_id.' END as slug';
			$sql->select($case_when_cat_alias);

			$sql->from('#__jem_categories');
			$sql->where('id = ' . (int) $cid);
			$sql->where('published = 1');

			$db->setQuery($sql);
			$row = $db->loadObject();

			if($row) {
				$parentcats[] = $row->slug;
				$parent_id = $row->parent_id;
			} else {
				$parent_id = 0;
			}

			$cid = $parent_id;
		} while($parent_id > 0);

		$parentcats = array_reverse($parentcats);
		return $parentcats;
	}

	/**
	 * set the array (parentcats) of ascending parents categories, with initial category first.
	 *
	 * @param int category ids
	 */
	static function buildParentCats($cid)
	{
		$db = JFactory::getDBO();
		$parentcats = array();
		$user 			= JFactory::getUser();
		$userid			= (int) $user->get('id');
		$levels 		= $user->getAuthorisedViewLevels();
		$app 			= JFactory::getApplication();

		// start with parent
		$query = 'SELECT parent_id FROM #__jem_categories WHERE id = ' . (int) $cid;
		$db->setQuery($query);
		$cid = (int)$db->loadResult();

		while ($cid > 1) { // 'root' has id 1
			$query = $db->getQuery(true);

			$query->select('c.id, c.parent_id, c.catname');

			// Handle the alias CASE WHEN portion of the query
			$case_when_cat_alias = ' CASE WHEN ';
			$case_when_cat_alias .= $query->charLength('c.alias');
			$case_when_cat_alias .= ' THEN ';
			$cat_id = $query->castAsChar('c.id');
			$case_when_cat_alias .= $query->concatenate(array($cat_id, 'c.alias'), ':');
			$case_when_cat_alias .= ' ELSE ';
			$case_when_cat_alias .= $cat_id.' END AS slug';
			$query->select($case_when_cat_alias);

			$query->from('#__jem_categories as c');
			$query->join('LEFT', '#__jem_cats_event_relations AS rel ON rel.catid = c.id');
			$query->where('c.id = ' . (int) $cid);
			$query->where('c.published = 1');

			###################
			## special right ##
			###################

			$query3	= $db->getQuery(true);
			$query3 = 'SELECT gr.id'
					. ' FROM #__jem_groups AS gr'
					. ' LEFT JOIN #__jem_groupmembers AS g ON g.group_id = gr.id'
					. ' WHERE g.member = ' . (int) $user->get('id')
					//. ' AND ' .$db->quoteName('gr.addevent') . ' = 1 '
					. ' AND g.member NOT LIKE 0';
			$db->setQuery($query3);
			$groupnumber = $db->loadColumn();

			$groups = implode(',', $user->getAuthorisedViewLevels());
			$jemgroups = implode(',',$groupnumber);

			if ($jemgroups) {
				$query->where('(c.access IN ('.$groups.') OR c.groupid IN ('.$jemgroups.'))');
			} else {
				$query->where('(c.access IN ('.$groups.'))');
			}

			$db->setQuery($query);
			$row = $db->loadObject();

			if($row && $row->id > 1) {
				$parentcats[] = $row;
				$cid = $row->parent_id;
			} else {
				$cid = 0;
			}
		}

		$parentcats = array_reverse($parentcats);
		return $parentcats;
	}

	/**
	 * returns parent Categories (name, slug), with top category first
	 *
	 * @return array
	 */
	function getParentlist()
	{
		$categories = array();

		if ($this->id) {
			$categories = self::buildParentCats($this->id);
		}

		return $categories;
	}

	/**
	 * Get the categorie tree
	 * Based on Joomla/html/menu.php
	 *
	 * @todo alter this function as the published value is set to false
	 *
	 * @return array
	 */
	static function getCategoriesTree($published=false)
	{
		$db = JFactory::getDBO();
		$state = array(0,1);

		if ($published) {
			$where = ' WHERE published = '.$published;
		} else {
			$where = ' WHERE published IN (' . implode(',', $state) . ')';
			$where .= ' AND alias NOT LIKE "root"';
		}

		$query = 'SELECT *, id AS value, catname AS text' . ' FROM #__jem_categories' . $where . ' ORDER BY parent_id, ordering';
		$db->setQuery($query);
		$mitems = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseNotice(500, $db->getErrorMsg());
		}

		if (!$mitems)
		{
			$mitems = array();
			$children = array();

			$parentid = $mitems;
		}
		else
		{
			$mitems_temp = $mitems;

			$children = array();
			// First pass - collect children
			foreach ($mitems as $v)
			{
				$pt = $v->parent_id;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push($list, $v);
				$children[$pt] = $list;
			}

			$parentid = intval($mitems[0]->parent_id);
		}

		//get list of the items
		$list = JEMCategories::treerecurse($parentid, '', array(), $children, 9999, 0, 0);

		return $list;
	}

	/**
	 * Get the categorie tree
	 * based on the joomla 1.0 treerecurse
	 *
	 * @access public
	 * @return array
	 */
	static function treerecurse($id, $indent, $list, &$children, $maxlevel=9999, $level=0, $type=1)
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $v)
			{
				$id = $v->id;

				if ($type) {
						$pre	= '<sup>|_</sup>&nbsp;';
						$spacer = '.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				} else {
						$pre	= '- ';
						$spacer = '&nbsp;&nbsp;';
				}

				if ($v->parent_id == 0) {
						$txt = $v->catname;
				} else {
						$txt = $pre . $v->catname;
				}
				$pt = $v->parent_id;
				$list[$id] = $v;
				$list[$id]->treename = "$indent$txt";
				$list[$id]->children = count(@$children[$id]);

				$list = JEMCategories::treerecurse($id, $indent . $spacer, $list, $children, $maxlevel, $level+1, $type);
			}
		}
		return $list;
	}

	/**
	 * Build Categories select list
	 *
	 * @param array $list
	 * @param string $name
	 * @param array $selected
	 * @param bool $top
	 * @param string $class
	 * @return void
	 */
	static function buildcatselect($list, $name, $selected, $top, $class = array('class'=>'inputbox'))
	{
		$catlist = array();

		if ($top) {
			$catlist[] = JHtml::_('select.option', '0', JText::_('COM_JEM_TOPLEVEL'));
		}

		$catlist = array_merge($catlist, JEMCategories::getcatselectoptions($list));

		return JHtml::_('select.genericlist', $catlist, $name, $class, 'value', 'text', $selected);
	}

	/**
	 * Build Categories select list
	 *
	 * @param array $list
	 * @param string $name
	 * @param array $selected
	 * @param bool $top
	 * @param string $class
	 * @return void
	 */
	static function getcatselectoptions($list)
	{
		$catlist = array();

		foreach ($list as $item) {
			$catlist[] = JHtml::_('select.option', $item->id, $item->treename);
		}

		return $catlist;
	}

	/**
	 * returns all descendants of a category
	 *
	 * @param int category id
	 * @return array int categories id
	 */
	static function getChilds($id)
	{
		$db = JFactory::getDBO();
		$query = ' SELECT id, parent_id ' . ' FROM #__jem_categories ' . ' WHERE published = 1 ';
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		//get array children
		$children = array();
		foreach ($rows as $child) {
			$parent = $child->parent_id;
			$list = @$children[$parent] ? $children[$parent] : array();
			array_push($list, $child);
			$children[$parent] = $list;
		}
		return JEMCategories::_getChildsRecurse($id, $children);
	}

	/**
	 * recursive function to build the familly tree
	 *
	 * @param int category id
	 * @param array children indexed by parent id
	 * @return array of category descendants
	 */
	protected static function _getChildsRecurse($id, $childs)
	{
		$result = array(
			$id
		);
		if (@$childs[$id]) {
			foreach ($childs[$id] AS $c) {
				$result = array_merge($result, JEMCategories::_getChildsRecurse($c->id, $childs));
			}
		}
		return $result;
	}
}

/**
 * Helper class to load Categorytree
 */
class JEMCategoryNode extends JObject
{

	/**
	 * Primary key
	 */
	public $id = null;

	/**
	 * The id of the category in the asset table
	 */
	public $asset_id = null;

	/**
	 * The id of the parent of category in the asset table, 0 for category root
	 */
	public $parent_id = null;

	/**
	 * The lft value for this category in the category tree
	 */
	public $lft = null;

	/**
	 * The rgt value for this category in the category tree
	 */
	public $rgt = null;

	/**
	 * The depth of this category's position in the category tree
	 */
	public $level = null;

	/**
	 * The extension this category is associated with
	 */
	public $extension = null;

	/**
	 * The menu title for the category (a short name)
	 */
	public $title = null;

	/**
	 * The the alias for the category
	 */
	public $alias = null;

	/**
	 * Description of the category.
	 */
	public $description = null;

	/**
	 * The publication status of the category
	 */
	public $published = null;

	/**
	 * Whether the category is or is not checked out
	 */
	public $checked_out = 0;

	/**
	 * The time at which the category was checked out
	 */
	public $checked_out_time = 0;

	/**
	 * Access level for the category
	 */
	public $access = null;

	/**
	 * JSON string of parameters
	 */
	public $params = null;

	/**
	 * Metadata description
	 */
	public $metadesc = null;

	/**
	 * Key words for meta data
	 */
	public $metakey = null;

	/**
	 * JSON string of other meta data
	 */
	public $metadata = null;

	public $created_user_id = null;

	/**
	 * The time at which the category was created
	 */
	public $created_time = null;

	public $modified_user_id = null;

	/**
	 * The time at which the category was modified
	 */
	public $modified_time = null;

	/**
	 * Nmber of times the category has been viewed
	 */
	public $hits = null;

	/**
	 * The language for the category in xx-XX format
	 */
	public $language = null;

	/**
	 * Number of items in this category or descendants of this category
	 */
	public $numitems = null;

	/**
	 * Number of children items
	 */
	public $childrennumitems = null;

	/**
	 * Slug fo the category (used in URL)
	 */
	public $slug = null;

	/**
	 * Array of  assets
	 */
	public $assets = null;

	/**
	 * Parent Category object
	 */
	protected $_parent = null;

	/**
	 * @var Array of Children
	 */
	protected $_children = array();

	/**
	 * Path from root to this category
	*/
	protected $_path = array();

	/**
	 * Category left of this one
	*/
	protected $_leftSibling = null;

	/**
	 * Category right of this one
	 */
	protected $_rightSibling = null;

	/**
	 * true if all children have been loaded
	 */
	protected $_allChildrenloaded = false;

	/**
	 * Constructor of this tree
	 */
	protected $_constructor = null;

	/**
	 * Class constructor
	 *
	 * @param   array          $category      The category data.
	 * @param   CategoryNode  &$constructor  The tree constructor.
	 */
	public function __construct($category = null, &$constructor = null)
	{
		if ($category)
		{
			$this->setProperties($category);
			if ($constructor)
			{
				$this->_constructor = &$constructor;
			}

			return true;
		}

		return false;
	}

	/**
	 * Set the parent of this category
	 * If the category already has a parent, the link is unset
	 *
	 * @param   mixed  &$parent  JCategoryNode for the parent to be set or null
	 * @return  void
	 */
	public function setParent(&$parent)
	{
		if ($parent instanceof JemCategoryNode || is_null($parent))
		{
			if (!is_null($this->_parent))
			{
				$key = array_search($this, $this->_parent->_children);
				unset($this->_parent->_children[$key]);
			}

			if (!is_null($parent))
			{
				$parent->_children[] = & $this;
			}

			$this->_parent = & $parent;

			if ($this->id != 'root')
			{
				if ($this->parent_id != 1)
				{
					$this->_path = $parent->getPath();
				}
				$this->_path[] = $this->id . ':' . $this->alias;
			}

			if (count($parent->_children) > 1)
			{
				end($parent->_children);
				$this->_leftSibling = prev($parent->_children);
				$this->_leftSibling->_rightsibling = &$this;
			}
		}
	}

	/**
	 * Add child to this node
	 * If the child already has a parent, the link is unset
	 *
	 * @param   JNode  &$child  The child to be added.
	 * @return  void
	 */
	public function addChild(&$child)
	{
		if ($child instanceof JemCategoryNode)
		{
			$child->setParent($this);
		}
	}

	/**
	 * Remove a specific child
	 *
	 * @param   integer  $id  ID of a category
	 * @return  void
	 */
	public function removeChild($id)
	{
		$key = array_search($this, $this->_parent->_children);
		unset($this->_parent->_children[$key]);
	}

	/**
	 * Get the children of this node
	 *
	 * @param   boolean  $recursive  False by default
	 * @return  array  The children
	 */
	public function &getChildren($recursive = false)
	{
		if (!$this->_allChildrenloaded)
		{
			$temp = $this->_constructor->get($this->id, true);
			if ($temp)
			{
				$this->_children = $temp->getChildren();
				$this->_leftSibling = $temp->getSibling(false);
				$this->_rightSibling = $temp->getSibling(true);
				$this->setAllLoaded();
			}
		}

		if ($recursive)
		{
			$items = array();
			foreach ($this->_children as $child)
			{
				$items[] = $child;
				$items = array_merge($items, $child->getChildren(true));
			}
			return $items;
		}

		return $this->_children;
	}

	/**
	 * Get the parent of this node
	 * @return  mixed  JNode or null
	 */
	public function &getParent()
	{
		return $this->_parent;
	}

	/**
	 * Test if this node has children
	 * @return  boolean  True if there is a child
	 */
	public function hasChildren()
	{
		return count($this->_children);
	}

	/**
	 * Test if this node has a parent
	 *
	 * @return  boolean    True if there is a parent
	 */
	public function hasParent()
	{
		return $this->getParent() != null;
	}

	/**
	 * Function to set the left or right sibling of a category
	 *
	 * @param   object   $sibling  JCategoryNode object for the sibling
	 * @param   boolean  $right    If set to false, the sibling is the left one
	 *
	 * @return  void
	 * @since   11.1
	 */
	public function setSibling($sibling, $right = true)
	{
		if ($right)
		{
			$this->_rightSibling = $sibling;
		}
		else
		{
			$this->_leftSibling = $sibling;
		}
	}

	/**
	 * Returns the right or left sibling of a category
	 *
	 * @param   boolean  $right  If set to false, returns the left sibling
	 *
	 * @return  mixed  JCategoryNode object with the sibling information or
	 *                 NULL if there is no sibling on that side.
	 */
	public function getSibling($right = true)
	{
		if (!$this->_allChildrenloaded)
		{
			$temp = $this->_constructor->get($this->id, true);
			$this->_children = $temp->getChildren();
			$this->_leftSibling = $temp->getSibling(false);
			$this->_rightSibling = $temp->getSibling(true);
			$this->setAllLoaded();
		}

		if ($right)
		{
			return $this->_rightSibling;
		}
		else
		{
			return $this->_leftSibling;
		}
	}

	/**
	 * Returns the category parameters
	 *
	 * @return  JRegistry
	 */
	public function getParams()
	{
		if (!($this->params instanceof JRegistry))
		{
			$temp = new JRegistry;
			$temp->loadString($this->params);
			$this->params = $temp;
		}

		return $this->params;
	}

	/**
	 * Returns the category metadata
	 *
	 * @return  JRegistry  A JRegistry object containing the metadata
	 */
	public function getMetadata()
	{
		if (!($this->metadata instanceof JRegistry))
		{
			$temp = new JRegistry;
			$temp->loadString($this->metadata);
			$this->metadata = $temp;
		}

		return $this->metadata;
	}

	/**
	 * Returns the category path to the root category
	 *
	 * @return  array
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Returns the user that created the category
	 *
	 * @param   boolean  $modified_user  Returns the modified_user when set to true
	 *
	 * @return  JUser  A JUser object containing a userid
	 */
	public function getAuthor($modified_user = false)
	{
		if ($modified_user)
		{
			return JFactory::getUser($this->modified_user_id);
		}

		return JFactory::getUser($this->created_user_id);
	}

	/**
	 * Set to load all children
	 *
	 * @return  void
	 */
	public function setAllLoaded()
	{
		$this->_allChildrenloaded = true;
		foreach ($this->_children as $child)
		{
			$child->setAllLoaded();
		}
	}

	/**
	 * Returns the number of items.
	 *
	 * @param   boolean  $recursive  If false number of children, if true number of descendants
	 *
	 * @return  integer  Number of children or descendants
	 */
	public function getNumItems($recursive = false)
	{
		if ($recursive)
		{
			$count = $this->numitems;

			foreach ($this->getChildren() as $child)
			{
				$count = $count + $child->getNumItems(true);
			}

			return $count;
		}

		return $this->numitems;
	}
}
