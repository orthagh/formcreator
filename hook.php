<?php
/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_formcreator_install()
{
   $version   = plugin_version_formcreator();
   $migration = new Migration($version['version']);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php$/", $filepath, $matches)) {
         $classname = 'PluginFormcreator' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }
   $migration->executeMigration();

   return true;
}

/**
 * Uninstall previously installed elements of the plugin
 *
 * @return boolean True if success
 */
function plugin_formcreator_uninstall()
{
   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginFormcreator' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall();
         }
      }
   }
   return true ;
}

/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_formcreator_getDropdown()
{
   return array(
       'PluginFormcreatorHeader'   => _n('Header', 'Headers', 2, 'formcreator'),
       'PluginFormcreatorCategory' => _n('Form category', 'Form categories', 2, 'formcreator'),
   );
}


function plugin_formcreator_addDefaultSelect($itemtype) {
   switch ($itemtype) {
      case "PluginFormcreatorIssue" :
         return "`glpi_plugin_formcreator_issues`.`sub_itemtype`, ";
   }
   return "";
}


function plugin_formcreator_addDefaultJoin($itemtype, $ref_table, &$already_link_tables) {
   $join = "";
   switch ($itemtype) {
      case "PluginFormcreatorIssue" :
         $join = Search::addDefaultJoin("Ticket", "glpi_tickets", $already_link_tables);
         $join = str_replace('`glpi_tickets`.`id`', '`glpi_plugin_formcreator_issues`.`original_id`', $join);
         $join = str_replace('`glpi_tickets`', '`glpi_plugin_formcreator_issues`', $join);
         $join = str_replace('`users_id_recipient`', '`requester_id`', $join);
   }
   return $join;
}


function plugin_formcreator_canValidate() {
   return Session::haveRight('config', UPDATE)
          || Session::haveRight('ticketvalidation', TicketValidation::VALIDATEINCIDENT)
          || Session::haveRight('ticketvalidation', TicketValidation::VALIDATEREQUEST);
}

function plugin_formcreator_getCondition($itemtype) {
   $table = getTableForItemType($itemtype);
   if ($itemtype == "PluginFormcreatorForm_Answer"
       && plugin_formcreator_canValidate()) {
      $condition = " 1=1 ";

   } else {
      $condition = " `$table`.`requester_id` = " . $_SESSION['glpiID'];
   }
   return $condition;
}

/**
 * Define specific search request
 *
 * @param  String $itemtype    Itemtype for the search engine
 * @return String          Specific search request
 */
function plugin_formcreator_addDefaultWhere($itemtype)
{
   $condition = "";
   $table = getTableForItemType($itemtype);
   switch ($itemtype) {
      case "PluginFormcreatorIssue" :
         $condition_fanwser = plugin_formcreator_getCondition("PluginFormcreatorForm_Answer");
         $condition = "`$table`.`sub_itemtype` = 'PluginFormcreatorForm_Answer'
                       AND ($condition_fanwser) OR ";
         $condition = Search::addDefaultWhere("Ticket");
         $condition = str_replace('`glpi_tickets`', '`glpi_plugin_formcreator_issues`', $condition);
         $condition = str_replace('`users_id_recipient`', '`requester_id`', $condition);
         break;

      case "PluginFormcreatorForm_Answer" :
         if (isset($_SESSION['formcreator']['form_search_answers'])
             && $_SESSION['formcreator']['form_search_answers']) {
            $condition = "`$table`.`".PluginFormcreatorForm_Answer::$items_id."` = ".
                         $_SESSION['formcreator']['form_search_answers'];
         } else {
            $condition = plugin_formcreator_getCondition($itemtype);
         }
         break;
   }
   return $condition;
}


function plugin_formcreator_addLeftJoin($itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
   $join = "";
   switch ($itemtype) {
      case 'PluginFormcreatorIssue':
            if ($new_table == 'glpi_ticketvalidations') {
               foreach ($already_link_tables as $table) {
                  if (strpos($table, $new_table) === 0) {
                     $AS = $table;
                  }
               }
               $join = " LEFT JOIN `$new_table` AS `$AS` ON (`$ref_table`.`tickets_id` = `$AS`.`tickets_id`) ";
            }
      break;
   }

   return $join;
}


function plugin_formcreator_addWhere($link, $nott, $itemtype, $ID, $val, $searchtype) {
   $searchopt = &Search::getOptions($itemtype);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($table.".".$field) {
      case "glpi_plugin_formcreator_issues.status" :
         $tocheck = array();
         if ($item = getItemForItemtype($itemtype)) {
            switch ($val) {
               case 'all':
                  $tocheck = array_merge($item->getNewStatusArray(),
                                         $item->getProcessStatusArray(),
                                         $item->getSolvedStatusArray(),
                                         $item->getClosedStatusArray());
                  break;

               case Ticket::INCOMING:
                  $tocheck = $item->getNewStatusArray();
                  break;

               case 'process' :
                  $tocheck = $item->getProcessStatusArray();
                  break;

               case 'notclosed' :
                  $tocheck = $item->getAllStatusArray();
                  foreach ($item->getClosedStatusArray() as $status) {
                     unset($tocheck[$status]);
                  }
                  $tocheck = array_keys($tocheck);
                  break;

               case 'old' :
                  $tocheck = array_merge($item->getSolvedStatusArray(),
                                         $item->getClosedStatusArray());
                  break;

               case 'notold' :
                  $tocheck = $item->getAllStatusArray();
                  foreach ($item->getSolvedStatusArray() as $status) {
                     unset($tocheck[$status]);
                  }
                  foreach ($item->getClosedStatusArray() as $status) {
                     unset($tocheck[$status]);
                  }
                  unset($tocheck['refused']);

                  $tocheck = array_keys($tocheck);
                  break;
            }
         }

         if (count($tocheck) == 0) {
            $statuses = $item->getAllStatusArray();
            if (isset($statuses[$val])) {
               $tocheck = array($val);
            }
         }

         if (count($tocheck)) {
            if ($nott) {
               return $link." `$table`.`$field` NOT IN ('".implode("','",$tocheck)."')";
            }
            return $link." `$table`.`$field` IN ('".implode("','",$tocheck)."')";
         }
         break;
   }
}


function plugin_formcreator_AssignToTicket($types)
{
   $types['PluginFormcreatorForm_Answer'] = PluginFormcreatorForm_Answer::getTypeName();

   return $types;
}


function plugin_formcreator_MassiveActions($itemtype) {

   switch ($itemtype) {
      case 'PluginFormcreatorForm' :
         return array(
            'PluginFormcreatorForm' . MassiveAction::CLASS_ACTION_SEPARATOR . 'Duplicate' => _x('button', 'Duplicate'),
            'PluginFormcreatorForm' . MassiveAction::CLASS_ACTION_SEPARATOR . 'Transfert' => __('Transfer'),
            'PluginFormcreatorForm' . MassiveAction::CLASS_ACTION_SEPARATOR . 'Export' => _sx('button', 'Export'),
         );
   }
   return array();
}


function plugin_formcreator_giveItem($itemtype, $ID, $data, $num) {
   $searchopt=&Search::getOptions($itemtype);
   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($itemtype) {
      case "PluginFormcreatorIssue":
         return PluginFormcreatorIssue::giveItem($itemtype, $ID, $data, $num);
         break;
   }

   return "";
}


function plugin_formcreator_dynamicReport($params) {
   switch ($params['item_type']) {
      case "PluginFormcreatorForm_Answer";
         if ($url = parse_url($_SERVER['HTTP_REFERER'])) {
            if (strpos($url['path'],
                       Toolbox::getItemTypeFormURL("PluginFormcreatorForm")) !== false) {
               parse_str($url['query'], $query);
               if (isset($query['id'])) {
                  $item = new PluginFormcreatorForm;
                  $item->getFromDB($query['id']);
                  PluginFormcreatorForm_Answer::showForForm($item, $params);
                  return true;
               }
            }
         }
         break;
   }

   return false;
}