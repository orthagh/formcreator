<?php

class PluginFormcreatorNotificationTargetFormanswer extends NotificationTarget
{
   const AUTHOR   = 101;
   const APPROVER = 102;


   public function getEvents()
   {
      $events = array (
         'plugin_formcreator_need_validation' => __('A form need to be validate', 'formcreator'),
         'plugin_formcreator_refused'         => __('The form is refused', 'formcreator'),
         'plugin_formcreator_accepted'        => __('The form is accepted', 'formcreator'),
         'plugin_formcreator_deleted'         => __('The form is deleted', 'formcreator'),
      );
      return $events;
   }

   public function getDatasForTemplate($event, $options = array())
   {
      $form = new PluginFormcreatorForm();
      $form->getFromDB($this->obj->fields['plugin_formcreator_forms_id']);
      $link = 'http://' . $_SERVER['SERVER_NAME'] . $GLOBALS['CFG_GLPI']['root_doc'];
      $link .= '/plugins/formcreator/front/formanswer.form.php?id=' . $this->obj->getID();

      $requester = new User();
      $requester->getFromDB($this->obj->fields['requester_id']);
      $validator = new User();
      $validator->getFromDB($this->obj->fields['validator_id']);

      $this->datas['##formcreator.form_id##']            = $form->getID();
      $this->datas['##formcreator.form_name##']          = $form->fields['name'];
      $this->datas['##formcreator.form_requester##']     = $requester->getName();
      $this->datas['##formcreator.form_validator##']     = $validator->getName();
      $this->datas['##formcreator.form_creation_date##'] = Html::convDateTime($this->obj->fields['request_date']);
      $this->datas['##formcreator.form_full_answers##']  = $this->obj->getFullForm();
      $this->datas['##formcreator.validation_comment##'] = $this->obj->fields['comment'];
      $this->datas['##formcreator.validation_link##']    = $link;
   }

   public function getTags()
   {
      $tags = array(
         'formcreator.form_id'            => __('Form #', 'formcreator'),
         'formcreator.form_name'          => __('Form name', 'formcreator'),
         'formcreator.form_requester'     => __('Requester', 'formcreator'),
         'formcreator.form_validator'     => __('Validator', 'formcreator'),
         'formcreator.form_creation_date' => __('Creation date'),
         'formcreator.form_full_answers'  => __('Full form answers', 'formcreator'),
         'formcreator.validation_comment' => __('Refused comment', 'formcreator'),
         'formcreator.validation_link'    => __('Validation link', 'formcreator'),
      );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
               'label'  => $label,
               'value'  => true,
               'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }
   }

   public function getAdditionalTargets($event='')
   {
      $this->addTarget(self::AUTHOR, __('Author'));
      $this->addTarget(self::APPROVER, __('Approver'));
   }

   public function getSpecificTargets($data, $options)
   {
      switch ($data['items_id']) {
         case self::AUTHOR :
            $this->getUserByField('requester_id', true);
            break;
         case self::APPROVER :
            $this->getUserByField('validator_id', true);
            break;
      }
   }

   public static function install()
   {
      $notifications = array(
         'plugin_formcreator_need_validation' => array(
               'name'     => __('A form need to be validate', 'formcreator'),
               'subject'  => __('A form from GLPI need to be validate', 'formcreator'),
               'content'  => __('Hi,\nA form from GLPI need to be validate and you have been choosen as the validator.\nYou can access it by clicking onto this link:\n<a href="##formcreator.validation_link##">##formcreator.validation_link##</a>', 'formcreator'),
               'notified' => self::APPROVER,
            ),
         'plugin_formcreator_refused'         => array(
               'name'     => __('The form is refused', 'formcreator'),
               'subject'  => __('Your form have been refused by the validator', 'formcreator'),
               'content'  => __('Hi,\nWe are sorry to inform you that your form have been refused by the validator for the reason below:\n##formcreator.validation_comment##\n\nYou can still modify and resubmit it by clicking onto this link:\n<a href="##formcreator.validation_link##">##formcreator.validation_link##</a>', 'formcreator'),
               'notified' => self::AUTHOR,
            ),
         'plugin_formcreator_accepted'        => array(
               'name'     => __('The form is accepted', 'formcreator'),
               'subject'  => __('Your form have been accepted by the validator', 'formcreator'),
               'content'  => __('Hi,\nWe are pleased to inform you that your form have been accepted by the validator.\nYour request will be considered soon.', 'formcreator'),
               'notified' => self::AUTHOR,
            ),
         'plugin_formcreator_deleted'         => array(
               'name'     => __('The form is deleted', 'formcreator'),
               'subject'  => __('Your form have been deleted by an administrator', 'formcreator'),
               'content'  => __('Hi,\nWe are sorry to inform you that your request cannot be considered and have been deleted by an administrator.', 'formcreator'),
               'notified' => self::AUTHOR,
            ),
      );

      // Create the notification template
      $template  = new NotificationTemplate();
      $found_tpl = $template->find("itemtype = 'PluginFormcreatorFormanswer'");
      if (count($found_tpl) == 0) {
         foreach ($notifications as $event => $datas)
         {
            $template_id = $template->add(array(
               'name'     => $datas['name'],
               'comment'  => '',
               'itemtype' => 'PluginFormcreatorFormanswer',
            ));

            // Add a default translation for the template
            $translation = new NotificationTemplateTranslation();
            $translation->add(array(
               'notificationtemplates_id' => $template_id,
               'language'                 => '',
               'subject'                  => $datas['subject'],
               'content_text'             => $datas['content'],
               'content_html'             => '<p>'.str_replace('\n', '<br />', $datas['content']).'</p>',
            ));

            // Create the notification
            $notification = new Notification();
            $notification_id = $notification->add(array(
               'name'                     => $datas['name'],
               'comment'                  => '',
               'entities_id'              => 0,
               'is_recursive'             => 1,
               'is_active'                => 1,
               'itemtype'                 => 'PluginFormcreatorFormanswer',
               'notificationtemplates_id' => $template_id,
               'event'                    => $event,
               'mode'                     => 'mail',
            ));

            // Add default notification targets
            $notification_target = new NotificationTarget();
            $notification_target->add(array(
               "items_id"         => $datas['notified'],
               "type"             => Notification::USER_TYPE,
               "notifications_id" => $notification_id,
            ));
         }
      }
   }

   public static function uninstall()
   {
      // Define DB tables
      $table_targets      = getTableForItemType('NotificationTarget');
      $table_notification = getTableForItemType('Notification');
      $table_translations = getTableForItemType('NotificationTemplateTranslation');
      $table_templates    = getTableForItemType('NotificationTemplate');

      // Delete translations
      $query = 'DELETE FROM `' . $table_translations . '`
                WHERE `notificationtemplates_id` IN (
                  SELECT `id` FROM ' . $table_templates . ' WHERE `itemtype` = "PluginFormcreatorFormanswer")';
      $GLOBALS['DB']->query($query);

      // Delete notification templates
      $query = 'DELETE FROM `' . $table_templates . '`
                WHERE `itemtype` = "PluginFormcreatorFormanswer"';
      $GLOBALS['DB']->query($query);

      // Delete notification targets
      $query = 'DELETE FROM `' . $table_targets . '`
                WHERE `notifications_id` IN (
                  SELECT `id` FROM ' . $table_notification . ' WHERE `itemtype` = "PluginFormcreatorFormanswer")';
      $GLOBALS['DB']->query($query);

      // Delete notifications
      $query = 'DELETE FROM `' . $table_notification . '`
                WHERE `itemtype` = "PluginFormcreatorFormanswer"';
      $GLOBALS['DB']->query($query);
   }
}
