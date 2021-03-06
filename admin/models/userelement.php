<?php
/**
 * @package JEM
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

/**
 * Model: Userelement
 */
class JemModelUserelement extends JModelLegacy
{
	/**
	 * data array
	 *
	 * @var array
	 */
	public $_data = null;

	/**
	 * total
	 *
	 * @var integer
	 */
	public $_total = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	public $_pagination = null;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$app =  JFactory::getApplication();

		$limit		= $app->getUserStateFromRequest( 'com_jem.limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart = $app->getUserStateFromRequest( 'com_jem.limitstart', 'limitstart', 0, 'int' );
		$limitstart = $limit ? (int)(floor($limitstart / $limit) * $limit) : 0;

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);
	}

	/**
	 * Method to get data
	 *
	 * @access public
	 * @return array
	 */
	function getData()
	{
		$query 		= $this->buildQuery();
		$pagination = $this->getPagination();

		$rows 		= $this->_getList($query, $pagination->limitstart, $pagination->limit);

		return $rows;
	}

	/**
	 * Query
	 */

	function buildQuery() {

		$app 				= JFactory::getApplication();
		$jemsettings 		= JemHelper::config();

		$filter_order		= $app->getUserStateFromRequest( 'com_jem.userelement.filter_order', 'filter_order', 'u.name', 'cmd' );
		$filter_order_Dir	= $app->getUserStateFromRequest( 'com_jem.userelement.filter_order_Dir', 'filter_order_Dir', '', 'word' );

		$filter_order		= JFilterInput::getInstance()->clean($filter_order, 'cmd');
		$filter_order_Dir	= JFilterInput::getInstance()->clean($filter_order_Dir, 'word');

		$search 			= $app->getUserStateFromRequest('com_jem.userelement.filter_search', 'filter_search', '', 'string' );
		$search 			= $this->_db->escape( trim(JString::strtolower( $search ) ) );

		// start query
		$db 	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select(array('u.id','u.name','u.username','u.email'));
		$query->from('#__users as u');

		// where
		$where = array();
		$where[] = 'u.block = 0';

		/*
		 * Search name
		**/
		if ($search) {
			$where[] = ' LOWER(u.name) LIKE \'%'.$search.'%\' ';
		}

		$query->where($where);

		// ordering
		$orderby 	= '';
		$orderby 	= $filter_order.' '.$filter_order_Dir;

		$query->order($orderby);

		return $query;
	}

	/**
	 * Method to get a pagination object
	 *
	 * @access public
	 * @return integer
	 */
	function getPagination()
	{
		$app 				= JFactory::getApplication();
		$jinput 			= $app->input;
		$jemsettings 		= JemHelper::config();

		$limit 				= $app->getUserStateFromRequest('com_jem.userelement.limit', 'limit', $jemsettings->display_num, 'int');
		$limitstart 		= $jinput->getInt('limitstart');

		$query = $this->buildQuery();
		$total = $this->_getListCount($query);

		// Create the pagination object
		jimport('joomla.html.pagination');
		$pagination = new JPagination($total, $limitstart, $limit);

		return $pagination;
	}
}
