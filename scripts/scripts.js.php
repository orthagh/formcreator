<?php
include ('../../../inc/includes.php');
header('Content-Type: text/javascript');
?>

var modalWindow;
var rootDoc          = "<?php echo $GLOBALS['CFG_GLPI']['root_doc']; ?>";

// === MENU ===
var link = '';
link += '<li id="menu7">';
link += '<a href="' + rootDoc + '/plugins/formcreator/front/formlist.php" class="itemP">';
link += "<?php echo _n('Form','Forms', 2, 'formcreator'); ?>";
link += '</a>';
link += '</li>';

jQuery(document).ready(function($) {
   modalWindow = $("<div></div>").dialog({
      width: 980,
      autoOpen: false,
      height: "auto",
      modal: true
   });

   <?php
      if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk')
         echo "$('#c_menu #menu1').after(link);";
   ?>

   var NomDuFichier = document.location.href.substring(document.location.href.lastIndexOf("/") + 1);

   if (NomDuFichier == "central.php" || NomDuFichier == "helpdesk.public.php") {
      $.ajax({
         url: rootDoc + '/plugins/formcreator/ajax/homepage_forms.php',
         type: "GET"
      }).done(function(response){
         setTimeout(function() {
            $('.central td').first().prepend(response);
         }, 200);
      });
   }
});

// === QUESTIONS ===
var urlQuestion      = rootDoc + "/plugins/formcreator/ajax/question.php";
var urlFrontQuestion = rootDoc + "/plugins/formcreator/front/question.form.php";

function addQuestion(items_id, token, section) {
   modalWindow.load(urlQuestion, {
      section_id: section,
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function editQuestion(items_id, token, question, section) {
   modalWindow.load(urlQuestion, {
      question_id: question,
      section_id: section,
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function setRequired(token, question_id, val) {
   jQuery.ajax({
     url: urlFrontQuestion,
     type: "POST",
     data: {
         set_required: 1,
         id: question_id,
         value: val,
         _glpi_csrf_token: token
      }
   }).done(reloadTab);
}

function moveQuestion(token, question_id, action) {
   jQuery.ajax({
     url: urlFrontQuestion,
     type: "POST",
     data: {
         move: 1,
         id: question_id,
         way: action,
         _glpi_csrf_token: token
      }
   }).done(reloadTab);
}

function deleteQuestion(items_id, token, question_id, question_name) {
   if(confirm("<?php echo __('Are you sure you want to delete this question:', 'formcreator'); ?> " + question_name)) {
      jQuery.ajax({
        url: urlFrontQuestion,
        type: "POST",
        data: {
            id: question_id,
            delete_question: 1,
            plugin_formcreator_forms_id: items_id,
            _glpi_csrf_token: token
         }
      }).done(reloadTab);
   }
}


// === SECTIONS ===
var urlSection      = rootDoc + "/plugins/formcreator/ajax/section.php";
var urlFrontSection = rootDoc + "/plugins/formcreator/front/section.form.php";

function addSection(items_id, token) {
   modalWindow.load(urlSection, {
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function editSection(items_id, token ,section) {
   modalWindow.load(urlSection, {
      section_id: section,
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function deleteSection(items_id, token, section_id, section_name) {
   if(confirm("<?php echo __('Are you sure you want to delete this section:', 'formcreator'); ?> " + section_name)) {
      jQuery.ajax({
        url: urlFrontSection,
        type: "POST",
        data: {
            delete_section: 1,
            id: section_id,
            plugin_formcreator_forms_id: items_id,
            _glpi_csrf_token: token
         }
      }).done(reloadTab);
   }
}

function moveSection(token, section_id, action) {
   jQuery.ajax({
     url: urlFrontSection,
     type: "POST",
     data: {
         move: 1,
         id: section_id,
         way: action,
         _glpi_csrf_token: token
      }
   }).done(reloadTab);
}


// === TARGETS ===
function addTarget(items_id, token) {
   modalWindow.load(rootDoc + '/plugins/formcreator/ajax/target.php', {
      form_id: items_id,
      _glpi_csrf_token: token
   }).dialog("open");
}

function deleteTarget(items_id, token, target_id, target_name) {
   if(confirm("<?php echo __('Are you sure you want to delete this destination:', 'formcreator'); ?> " + target_name)) {
      jQuery.ajax({
        url: rootDoc + '/plugins/formcreator/front/target.form.php',
        type: "POST",
        data: {
            delete_target: 1,
            id: target_id,
            plugin_formcreator_forms_id: items_id,
            _glpi_csrf_token: token
         }
      }).done(reloadTab);

   }
}

// SHOW OR HIDE FORM FIELDS
var formcreatorQuestions = new Object();

function formcreatorChangeValueOf(field_id, value) {
   formcreatorQuestions[field_id] = value;
   formcreatorShowFields();
}
function formcreatorAddValueOf(field_id, value) {
   formcreatorQuestions[field_id] = value;
}

function formcreatorShowFields() {
   $.ajax({
      url: '../ajax/showfields.php',
      type: "POST",
      data: {
         values: JSON.stringify(formcreatorQuestions)
      }
   }).done(function(response){
      var questionToShow = JSON.parse(response);
      var i = 0;
      for (question in formcreatorQuestions) {
         if (questionToShow[question]) {
            $('#form-group-field' + question).show();
            i++;
            $('#form-group-field' + question).removeClass('line' + (i+1) % 2);
            $('#form-group-field' + question).addClass('line' + i%2);
         } else {
            $('#form-group-field' + question).hide();
            $('#form-group-field' + question).removeClass('line0');
            $('#form-group-field' + question).removeClass('line1');
         }
      }
   });
}
