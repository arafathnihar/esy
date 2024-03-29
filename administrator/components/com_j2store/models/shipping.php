<?php

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.'/components/com_j2store/models/_base.php');
class J2StoreModelShipping extends J2StoreModelBase
{
    protected function _buildQueryWhere($query)
    {
       	$filter     = $this->getState('filter');
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
        $filter_name    = $this->getState('filter_name');
        $filter_enabled    = $this->getState('filter_enabled');

       	if ($filter)
       	{
       	    $key	= $this->_db->Quote('%'.$this->_db->escape( trim( strtolower( $filter ) ) ).'%');
       	    $where = array();
       	    $where[] = 'LOWER(tbl.id) LIKE '.$key;
       	    $where[] = 'LOWER(tbl.name) LIKE '.$key;
       	    $query->where('('.implode(' OR ', $where).')');
       	}
        if (strlen($filter_id_from))
        {
            if (strlen($filter_id_to))
            {
                $query->where('tbl.id >= '.(int) $filter_id_from);
            }
            else
            {
                $query->where('tbl.id = '.(int) $filter_id_from);
            }
        }
        if (strlen($filter_id_to))
        {
            $query->where('tbl.id <= '.(int) $filter_id_to);
        }
        if (strlen($filter_enabled))
        {

          $query->where('tbl.enabled = 1');
        }
        if ($filter_name)
        {
            $key    = $this->_db->q('%'.$this->_db->escape( trim( strtolower( $filter_name ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.name) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }

        // force returned records to only be j2store shipping
        $query->where("tbl.folder = 'j2store'");
        $query->where("tbl.element LIKE 'shipping_%'");

    }

    public function getList($refresh = false)
    {
        $list = parent::getList($refresh);
        foreach(@$list as $item)
        {
            $item->id = $item->extension_id;

            $item->link = 'index.php?option=com_j2store&view=shipping&task=view&id='.$item->id;
            $item->link_edit = 'index.php?option=com_j2store&view=shipping&task=edit&id='.$item->id;
        }
        return $list;
    }

    public function getItem($pk=null, $refresh=false, $emptyState=true)
    {
        if ($item = parent::getItem($pk, $refresh, $emptyState))
        {
                $formdata = new JRegistry;
                $formdata -> loadString($item -> params);
                $item -> data = $formdata -> toArray('data');

        }
        return $item;
    }

}
