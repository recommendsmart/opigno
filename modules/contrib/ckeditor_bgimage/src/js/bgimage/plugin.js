/**
 * @file
 * Drupal File plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('bgimage', {
    init: function (editor) {
      // Add the commands for file and unfile.
      editor.addCommand('bgimage', {
        allowedContent: {
          a: {
            attributes: {
              '!href': true,
              '!data-entity-type': true,
              '!data-entity-uuid': true
            },
            classes: {}
          }
        },
        requiredContent: new CKEDITOR.style(
          {
            element: 'a',
            attributes: {
              'href': '',
              'data-entity-type': '',
              'data-entity-uuid': ''
            }
          }
        ),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          // Set existing values for future updates.
          let existingValues = {};

          // Prepare a save callback to be used upon saving the dialog.
          let saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');

            /* config_image */
            let image = returnValues.attributes.image;
            let background_color = returnValues.background_color;
            let width = '100%';
            let height = '';
            let position = returnValues.background_aling;
            if(returnValues.width !== '') {
              width = returnValues.width + 'px';
            }
            if(returnValues.height !== '') {
              height = returnValues.height + 'px';
            }
            let content = editor.document.getBody().getHtml();
            let matches = content.match(/<div style="(.*)">((.|\n)*?)<\/div>/);
            // styled div already exists
            if (matches) {
              content = matches[2];
            }

            let div = '<div style="';
            div += 'background-image: url(' + image + ');';
            div += 'background-color: ' + background_color + ';';
            div += 'background-repeat: no-repeat;';
            div += 'background-position: ' + position + ';';
            div += 'padding:25px;';
            div += 'background-size:' + width + ' ' + height + ';';
            div += '">';
            div += content;
            div += '</div>';
            editor.setData(div);

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          let dialogSettings = {
            title: editor.config.bgimage_dialogTitleAdd,
            dialogClass: 'editor-file-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('ckeditor_bgimage/dialog/file/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      // Add buttons for file upload.
      if (editor.ui.addButton) {
        editor.ui.addButton('bgimage', {
          label: 'bgimage',
          command: 'bgimage',
          toolbar: 'insert',
          icon: this.path + 'icons/background.png'
        });
      }

      // If the "menu" plugin is loaded, register the menu items.
      if (editor.addMenuItems) {
        editor.addMenuItems({
          file: {
            label: Drupal.t('Edit File'),
            command: 'bgimage',
            group: 'link',
            order: 1
          }
        });
      }

      // If the "contextmenu" plugin is loaded, register the listeners.
      if (editor.contextMenu) {
        editor.contextMenu.addListener(function (element, selection) {
          if (!element || element.isReadOnly()) {
            return null;
          }

          let menu = {};
          if (anchor.getAttribute('href') && anchor.getChildCount()) {
            menu = {file: CKEDITOR.TRISTATE_OFF};
          }
          return menu;
        });
      }
    }
  });
})(jQuery, Drupal, drupalSettings, CKEDITOR);
