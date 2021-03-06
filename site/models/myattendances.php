<?php
/**
 * @package JEM
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;
require_once dirname(__FILE__) . '/eventslist.php';

/**
 * Model-MyAttendances
 */
class JemModelMyattendances extends JemModelEventslist
{
	/**
	 * Constructor
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{

		$app 				= JFactory::getApplication();
		$jemsettings		= JemHelper::config();
		$jinput				= JFactory::getApplication()->input;
		$itemid 			= $jinput->getInt('id', 0) . ':' . $jinput->getInt('Itemid', 0);

		# limit/start
		$limit		= $app->getUserStateFromRequest('com_jem.myattendances.'.$itemid.'.limit', 'limit', $jemsettings->display_num, 'uint');
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $limitstart);

		# Search
		$search = $app->getUserStateFromRequest('com_jem.myattendances.'.$itemid.'.filter_search', 'filter_search', '', 'string');
		$this->setState('filter.filter_search', $search);

		# FilterType
		$filtertype = $app->getUserStateFromRequest('com_jem.myattendances.'.$itemid.'.filter_type', 'filter_type', '', 'int');
		$this->setState('filter.filter_type', $filtertype);

		###########
		## ORDER ##
		###########
		$filter_order 		= $app->getUserStateFromRequest('com_jem.myattendances.'.$itemid.'.filter_order', 'filter_order', 'a.dates', 'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest('com_jem.myattendances.'.$itemid.'.filter_order_Dir', 'filter_order_Dir', 'ASC', 'string');
		$filter_order		= JFilterInput::getInstance()->clean($filter_order, 'string');
		$filter_order_Dir	= JFilterInput::getInstance()->clean($filter_order_Dir, 'string');

		if ($filter_order == 'a.dates') {
			$orderby = array('a.dates '.$filter_order_Dir,'a.times '.$filter_order_Dir);
		} else {
			$orderby = $filter_order . ' ' . $filter_order_Dir;
		}

		$this->setState('filter.orderby',$orderby);

		# params
		$params = $app->getParams();
		$this->setState('params', $params);

		# groupby
		$this->setState('filter.groupby',array('a.id'));

	}

	function getListQuery() {

		$params  = $this->state->params;
		$jinput  = JFactory::getApplication()->input;
		$task    = $jinput->getCmd('task');
		$user	 = JFactory::getUser();

		// Create a new query object.
		$query = parent::getListQuery();

		// limit output so only future events the user attends will be shown
		if ($params->get('filtermyregs')) {
			$query->where(' DATE_SUB(NOW(), INTERVAL '.(int)$params->get('myregspast').' DAY) < (IF (a.enddates IS NOT NULL, a.enddates, a.dates)) ');
		}

		// then if the user is attending the event
		$query->join('LEFT', '#__jem_register AS r ON r.event = a.id');
		$query->where('r.uid = '.$user->id);

		return $query;
	}
}
