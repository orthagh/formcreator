<?php
include ('../../../inc/includes.php');

if (!isset($_SESSION['glpiactiveprofile']['id'])) {
   // Session is not valid then exit
   exit;
}

if ($_REQUEST['wizard'] == 'categories') {
   plugin_formcreator_showWizardCategories();
} else if ($_REQUEST['wizard'] == 'forms') {
   plugin_formcreator_showWizardForms($_REQUEST['categoriesId']);
}

function plugin_formcreator_showWizardCategories() {
   PluginFormcreatorCategory::slinkyView();
}

/**
 * Builds a category tree with UL / LI HTML  tags
 * @param array $categoryTree nested arrays of category IDs
 */
function plugin_formcreator_buildCategorySlinky(array $categoryTree) {
   $html = '<a href="#" data-parent-category-id="' . $parentId . '" data-category-id="' . $rootId . '" onclick="updateWizardFormsView(' . $rootId . ')">' . $formCategory->getField('name') . '</a>';
   foreach($categoryTree as $categoryId => $categorySubTree) {
   }
}

function plugin_formcreator_showWizardForms($rootCategory = 0) {
   $cat_table  = getTableForItemType('PluginFormcreatorCategory');
   $form_table = getTableForItemType('PluginFormcreatorForm');
   $table_fp   = getTableForItemType('PluginFormcreatorFormprofiles');
   $where      = getEntitiesRestrictRequest( "", $form_table, "", "", true, false);
   
   if ($rootCategory == 0) {
      $category = new PluginFormcreatorCategory();
      $selectedCategories = $category->find('1');
      // Add forms without category
      $selectedCategories[0] = '0';
   } else {
      $selectedCategories = getSonsOf($cat_table, $rootCategory);
   }
   $selectedCategories = implode(', ', array_keys($selectedCategories));
   
   // Fond forms without category and accessible by the current user
   $query_forms = "SELECT $form_table.id, $form_table.name, $form_table.description
                   FROM $form_table
                   WHERE $form_table.`plugin_formcreator_categories_id` IN ($selectedCategories)
                   AND $form_table.`is_active` = 1
                   AND $form_table.`is_deleted` = 0
                   AND $form_table.`helpdesk_home` = 1
                   AND ($form_table.`language` = '{$_SESSION['glpilanguage']}' OR $form_table.`language` = '')
                   AND $where
                   AND (`access_rights` != " . PluginFormcreatorForm::ACCESS_RESTRICTED . " OR $form_table.`id` IN (
                      SELECT plugin_formcreator_forms_id
                      FROM $table_fp
                      WHERE plugin_formcreator_profiles_id = " . (int) $_SESSION['glpiactiveprofile']['id'] . "))
                   ORDER BY $form_table.name ASC";
   $result_forms = $GLOBALS['DB']->query($query_forms);
   
   echo '<table class="tab_cadrehov">';
   echo '<tr class="noHover">';
   echo '<th><a href="../plugins/formcreator/front/formlist.php">' . _n('Form', 'Forms', 2, 'formcreator') . '</a></th>';
   echo '</tr>';

   if ($GLOBALS['DB']->numrows($result_forms) == 0) {
      echo '<tr><td>' . __('No form yet in this category', 'formcreator') . '</td></tr>';
   } else {
      $i = 0;
      while ($form = $GLOBALS['DB']->fetch_array($result_forms)) {
         $i++;
         echo '<tr class="line' . ($i % 2) . ' tab_bg_' . ($i % 2 +1) . '">';
         echo '<td>';
         echo '<img src="' . $GLOBALS['CFG_GLPI']['root_doc'] . '/pics/plus.png" alt="+" title=""
                onclick="showDescription(' . $form['id'] . ', this)" align="absmiddle" style="cursor: pointer">';
         echo '&nbsp;';
         echo '<a href="' . $GLOBALS['CFG_GLPI']['root_doc']
         . '/plugins/formcreator/front/formdisplay.php?id=' . $form['id'] . '"
               title="' . plugin_formcreator_encode($form['description']) . '">'
                           . $form['name']
                           . '</a></td>';
                           echo '</tr>';
                           echo '<tr id="desc' . $form['id'] . '" class="line' . ($i % 2) . ' form_description">';
                           echo '<td><div>' . $form['description'] . '&nbsp;</div></td>';
                           echo '</tr>';
      }
   
      }   
   echo '</table>';
   echo '<script type="text/javascript">
            function showDescription(id, img){
               if(img.alt == "+") {
                 img.alt = "-";
                 img.src = "' . $GLOBALS['CFG_GLPI']['root_doc'] . '/pics/moins.png";
                 document.getElementById("desc" + id).style.display = "table-row";
               } else {
                 img.alt = "+";
                 img.src = "' . $GLOBALS['CFG_GLPI']['root_doc'] . '/pics/plus.png";
                 document.getElementById("desc" + id).style.display = "none";
               }
            }
         </script>';
}