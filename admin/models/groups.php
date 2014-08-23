<?php
/**
 * @version 3.0.2
 * @package JEM
 * @copyright (C) 2013-2014 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;


/**
 * Model: Groups
 */
class JEMModelGroups extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param	array	An optional associative array of configuration settings.
	 * @see		JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
					'name', 'a.name',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter.search');
		$this->setState('filter.search', $search);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_jem');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.name', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 *
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db		= $this->getDbo();
		$query	= $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select',
						'a.*'
				)
		);
		$query->from($db->quoteName('#__jem_groups').' AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id = a.checked_out');

		$search = $this->getState('filter.search');

		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = '.(int) substr($search, 3));
			} else {
				$search = $db->Quote('%'.$db->escape($search, true).'%');

				if ($search) {
					$query->where('a.name LIKE '.$search);
				}
			}
		}
	

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering');
		$orderDirn	= $this->state->get('list.direction');
	
		$query->order($db->escape($orderCol.' '.$orderDirn));
		
		return $query;
	}


	/**
	 * Method to get the userinformation of edited/submitted venues
	 *
	 * @access private
	 * @return object
	 *
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}


	/**
	 * Method to remove a group
	 *
	 * @access	public
	 * @return	boolean	True on success
	 *
	 */
	function delete($cid = array())
	{
		if (count($cid) > 0)
		{
			$cids = implode(',', $cid);
			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->delete('#__jem_groups');
			$query->where(array('id IN ('. $cids .')'));
			$db->setQuery($query);
			
			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
				return false;
			}
			
			$query = $db->getQuery(true);
			$query->delete('#__jem_groupmembers');
			$query->where(array('group_id IN ('. $cids .')'));
			$db->setQuery($query);
				
			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				JError::raiseWarning(500, $e->getMessage());
				return false;
			}
		}

		return true;
	}
}
