<?php
/**
 * @package		MiwoVideos
 * @copyright	Copyright  ( C ) 2009-2014 Miwisoft, LLC. All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
# No Permission
defined('MIWI') or die ;

class MiwovideosViewCategory extends MiwovideosView {
	
	public function display($tpl = null) {
        $user = MFactory::getUser();
		$nullDate = MFactory::getDBO()->getNullDate();
		$pathway = $this->_mainframe->getPathway();

		$category_id = MiwoVideos::getInput()->getInt('category_id', 0);
		$category = Miwovideos::get('utility')->getCategory($category_id);

        if (is_object($category) and !$this->acl->canAccess($category->access)) {
            $this->_mainframe->redirect(MRoute::_('index.php?option=com_miwovideos&view=category'), MText::_('JERROR_ALERTNOAUTHOR'), 'error');
        }

        $Itemid = MiwoVideos::get('router')->getItemid(array('view' => 'category', 'category_id' => $category_id), null, true);

        $videos 			= $this->get('Videos');
        $categories 		= $this->get('Categories');

        if (!empty($category_id)) {
            $page_title = MText::_('COM_MIWOVIDEOS_CATEGORY_PAGE_TITLE');
            $page_title = str_replace('[CATEGORY_NAME]', $category->title, $page_title);

            if ($this->_mainframe->getCfg('sitename_pagetitles', 0) == 1) {
                $page_title = MText::sprintf('MPAGETITLE', $this->_mainframe->getCfg('sitename'), $page_title);
            }
            elseif ($this->_mainframe->getCfg('sitename_pagetitles', 0) == 2) {
                $page_title = MText::sprintf('MPAGETITLE', $page_title, $this->_mainframe->getCfg('sitename'));
            }

            $this->document->setTitle($page_title);
            $this->document->setMetadata('description', $category->meta_desc);
            $this->document->setMetadata('keywords', 	$category->meta_key);
            $this->document->setMetadata('author', 		$category->meta_author);
        }
        else {
            $this->document->setTitle(MText::_('COM_MIWOVIDEOS_CATEGORIES_PAGE_TITLE'));
        }

		if ($this->config->get('load_plugins')) {
            $n = count($videos);
			
			for ($i = 0; $i < $n; $i++) {
				$item = &$videos[$i];
				
				$item->introtext = MHtml::_('content.prepare', $item->introtext);
			}
			
			if ($category) {	
				$category->description = MHtml::_('content.prepare', $category->introtext.$category->fulltext);
			}
		}
		
		# BreadCrumbs
        $active_menu = $this->_mainframe->getMenu()->getActive();
        if (!isset($active_menu->query['category_id']) or ($active_menu->query['category_id'] != $category_id)) {
            $cats = Miwovideos::get('utility')->getCategories($category_id);

            if (!empty($cats)) {
                asort($cats);

                foreach ($cats as $cat) {
                    if($cat->id != $category_id) {
                        $Itemid = MiwoVideos::get('router')->getItemid(array('view' => 'category', 'category_id' => $cat->id), null, true);

                        $path_url = MRoute::_('index.php?option=com_miwovideos&view=category&category_id='.$cat->id.$Itemid);
                        $pathway->addItem($cat->title, $path_url);
                    }
                }

                $pathway->addItem($category->title);
            }
        }

		$userId = $user->get('id');
		$_SESSION['last_category_id'] = $category_id;

        MHtml::_('behavior.modal');

		$this->userId			= $userId;
		$this->items			= $videos;
		$this->categories		= $categories;									
		$this->pagination		= $this->get('Pagination');
		$this->Itemid			= $Itemid;
		$this->category			= $category;
		$this->nullDate			= $nullDate;
        $this->params       	= $this->_mainframe->getParams();
        $this->viewLevels		= $user->getAuthorisedViewLevels();
        $this->view_levels      = $user->getAuthorisedViewLevels();
		
		parent::display($tpl);
	}
}