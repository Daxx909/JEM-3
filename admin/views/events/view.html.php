<?php
/**
 * @version 3.0.1
 * @package JEM
 * @copyright (C) 2013-2014 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;


/**
 * View: Events
 */
 class JEMViewEvents extends JViewLegacy {

	protected $items;
	protected $pagination;
	protected $state;

	public function display($tpl = null)
	{
		$user 		= JFactory::getUser();
		$document	= JFactory::getDocument();
		$settings 	= JEMHelper::globalattribs();

		$jemsettings = JEMAdmin::config();
		$url 		= JURI::root();

		// Initialise variables.
		$this->items			= $this->get('Items');
		$this->pagination		= $this->get('Pagination');
		$this->state			= $this->get('State');
		$this->filterForm		= $this->get('FilterForm');
		$this->activeFilters	= $this->get('ActiveFilters');

		// Retrieving params
		$params = $this->state->get('params');

		// highlighter
		$highlighter = $settings->get('highlight','0');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		// Load css
		JHtml::_('stylesheet', 'com_jem/backend.css', array(), true);

		// Load Scripts
		//JHtml::_('bootstrap.framework');
		
		
		$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');

		if ($highlighter) {
			$document->addScript($url.'media/com_jem/js/highlighter.js');
			$style = '
			    .red a:link, .red a:visited, .red a:active {
			    color:red;}
			    ';
			$document->addStyleDeclaration($style);
		}

		//assign data to template
		$this->user			= $user;
		$this->jemsettings  = $jemsettings;
		$this->settings		= $settings;

		// add toolbar
		$this->addToolbar();

		parent::display($tpl);
		}


	/**
	 * Add Toolbar
	 */
	protected function addToolbar()
	{
		JToolBarHelper::title(JText::_('COM_JEM_EVENTS'), 'events');

		/* retrieving the allowed actions for the user */
		$canDo = JEMHelperBackend::getActions(0);

		/* create */
		if (($canDo->get('core.create'))) {
			JToolBarHelper::addNew('event.add');
		}

		/* edit */
		if (($canDo->get('core.edit'))) {
			JToolBarHelper::editList('event.edit');
			JToolBarHelper::divider();
		}

		/* state */
		if ($canDo->get('core.edit.state')) {
			if ($this->state->get('filter.published') != 2) {
				JToolBarHelper::publishList('events.publish', 'JTOOLBAR_PUBLISH', true);
				JToolBarHelper::unpublishList('events.unpublish', 'JTOOLBAR_UNPUBLISH', true);
				JToolBarHelper::custom('events.featured', 'featured.png', 'featured_f2.png', 'JFEATURED', true);
			}

			if ($this->state->get('filter.published') != -1) {
				JToolBarHelper::divider();
				if ($this->state->get('filter_state') != 2) {
					JToolBarHelper::archiveList('events.archive');
				} elseif ($this->state->get('filter_state') == 2) {
					JToolBarHelper::unarchiveList('events.publish');
				}
			}
		}

		if ($canDo->get('core.edit.state')) {
			JToolBarHelper::checkin('events.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
			JToolBarHelper::deleteList('COM_JEM_CONFIRM_DELETE', 'events.delete', 'JTOOLBAR_EMPTY_TRASH');
		} elseif ($canDo->get('core.edit.state')) {
			JToolBarHelper::trash('events.trash');
		}

		JToolBarHelper::divider();
		JToolBarHelper::help('listevents', true);
	}
}
?>