(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.digital_signage_preview = Drupal.digital_signage_preview || {};
  drupalSettings.digital_signage = drupalSettings.digital_signage || {};
  drupalSettings.digital_signage.devices = drupalSettings.digital_signage.devices || [];
  drupalSettings.digital_signage.preview = false;
  drupalSettings.digital_signage.diagram = false;
  drupalSettings.digital_signage.screenshot = false;
  drupalSettings.digital_signage.log = false;

  Drupal.behaviors.digital_signage_preview = {
    attach: function () {
      drupalSettings.digital_signage.messageTimeStamp = 0;
      window.addEventListener("message", function(event) {
        if (event.timeStamp === drupalSettings.digital_signage.messageTimeStamp) {
          return;
        }
        // TODO: validate event.origin and event.source for security reasons.
        drupalSettings.digital_signage.messageTimeStamp = event.timeStamp;
        if (event.data.action) {
          switch (event.data.action) {
            case 'resetTimeout':
              if (Drupal.digital_signage_preview.timer) {
                Drupal.digital_signage_timer.resetTimeout(Drupal.digital_signage_preview.timer, event.data.timeout);
              }
              break;
            case 'pause':
              Drupal.digital_signage_preview.pause();
              break;
            case 'resume':
              Drupal.digital_signage_preview.resume();
              break;
          }
        }
      }, false);
      $('body:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .each(function () {
          $('body').append('<div id="digital-signage-preview"><div class="background">&nbsp;</div><div class="popup"><div class="content-wrapper"><div class="content"></div></div><ul class="digital-signage-preview controls"><li class="close" title="close"></li><li class="prev" title="prev"></li><li class="next" title="next"></li></ul></div></div>');
          // Get control buttons after inserted in DOM
          drupalSettings.digital_signage.controls = {
            self: $('#digital-signage-preview .popup .digital-signage-preview.controls'),
            close: $('#digital-signage-preview .popup .digital-signage-preview.controls .close'),
            prev: $('#digital-signage-preview .popup .digital-signage-preview.controls .prev'),
            next: $('#digital-signage-preview .popup .digital-signage-preview.controls .next'),
          };
          $(document).on('keydown', function (e) {
            if (e.keyCode === 27) {
              Drupal.digital_signage_preview.stop();
            }
            if (e.keyCode === 37) {
              Drupal.digital_signage_preview.prevSlide();
            }
            if (e.keyCode === 39) {
              Drupal.digital_signage_preview.nextSlide();
            }
          });
          drupalSettings.digital_signage.controls.close.on('click', function () {
            Drupal.digital_signage_preview.stop();
          });
          drupalSettings.digital_signage.controls.prev.on('click', function () {
            Drupal.digital_signage_preview.prevSlide();
          });
          drupalSettings.digital_signage.controls.next.on('click', function () {
            Drupal.digital_signage_preview.nextSlide();
          });
        });

      // Close preview when clicking on the outer background
      $('#digital-signage-preview .background:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.stop();
          return false;
        });

      // Slide button
      $('input.button.digital-signage.slide:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = $(this).attr('stored-schedule');
          Drupal.digital_signage_preview.start(true);
          return false;
        });

      // Schedule button
      $('input.button.digital-signage.preview:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = $(this).attr('stored-schedule');
          Drupal.digital_signage_preview.start(false);
          return false;
        });

      // Diagram button
      $('input.button.digital-signage.diagram:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = $(this).attr('stored-schedule');
          Drupal.digital_signage_preview.showDiagram();
          return false;
        });

      // Screenshot button
      $('input.button.digital-signage.screenshot:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = 'true';
          Drupal.digital_signage_preview.showScreenshot($(this).attr('device-id'), 'true', false);
          return false;
        });
      $('#digital-signage-preview.show .popup > .content-wrapper > .content .screenshot-widget:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $('input.button.digital-signage.screenshot').attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = 'true';
          Drupal.digital_signage_preview.showScreenshot(true);
          return false;
        });

      // Log buttons
      $('input.button.digital-signage.debug-log:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = 'true';
          Drupal.digital_signage_preview.showLog('debug');
          return false;
        });
      $('input.button.digital-signage.error-log:not(.digital-signage-preview-processed)')
        .addClass('digital-signage-preview-processed')
        .on('click', function () {
          Drupal.digital_signage_preview.deviceId = $(this).attr('device-id');
          Drupal.digital_signage_preview.storedSchedule = 'true';
          Drupal.digital_signage_preview.showLog('error');
          return false;
        });
    }
  };

  Drupal.digital_signage_preview.showDiagram = function () {
    drupalSettings.digital_signage.diagram = true;
    $('#digital-signage-preview').addClass('show');
    Drupal.digital_signage_preview.setPreviewSize(0, 10, 0);
    let $settings = drupalSettings.digital_signage.devices[Drupal.digital_signage_preview.deviceId];
    Drupal.ajax({
      url: $settings.schedule.api + '?storedSchedule=' + Drupal.digital_signage_preview.storedSchedule + '&deviceId=' + Drupal.digital_signage_preview.deviceId + '&mode=diagram',
      error: function (e) {
        console.log(e);
        Drupal.digital_signage_preview.stop();
      }
    }).execute();
  };

  Drupal.digital_signage_preview.showLog = function ($type) {
    drupalSettings.digital_signage.log = true;
    $('#digital-signage-preview').addClass('show');
    Drupal.digital_signage_preview.setPreviewSize(0, 10, 0);
    let $settings = drupalSettings.digital_signage.devices[Drupal.digital_signage_preview.deviceId];
    Drupal.ajax({
      url: $settings.schedule.api + '?storedSchedule=' + Drupal.digital_signage_preview.storedSchedule + '&deviceId=' + Drupal.digital_signage_preview.deviceId + '&mode=log&type=' + $type,
      error: function (e) {
        console.log(e);
        Drupal.digital_signage_preview.stop();
      }
    }).execute();
  };

  Drupal.digital_signage_preview.showScreenshot = function ($refresh) {
    let $settings = drupalSettings.digital_signage.devices[Drupal.digital_signage_preview.deviceId];
    if (!$refresh) {
      drupalSettings.digital_signage.screenshot = true;
      $('#digital-signage-preview')
        .addClass('orientation-' + $settings.orientation)
        .addClass('show');
      Drupal.digital_signage_preview.setPreviewSize($settings.proportion, 10, $settings.width);
    }
    Drupal.ajax({
      url: $settings.schedule.api + '?storedSchedule=' + Drupal.digital_signage_preview.storedSchedule + '&deviceId=' + Drupal.digital_signage_preview.deviceId + '&mode=screenshot&refresh=' + $refresh,
      error: function (e) {
        console.log(e);
        Drupal.digital_signage_preview.stop();
      }
    }).execute();
  };

  Drupal.digital_signage_preview.start = function (selectSlide) {
    drupalSettings.digital_signage.preview = true;
    drupalSettings.digital_signage.automatic = true;
    drupalSettings.digital_signage.active = {};
    drupalSettings.digital_signage.active.settings = drupalSettings.digital_signage.devices[Drupal.digital_signage_preview.deviceId];
    drupalSettings.digital_signage.active.index = 0;
    drupalSettings.digital_signage.active.device = Drupal.digital_signage_preview.deviceId;
    // Start preview
    $('#digital-signage-preview')
      .addClass('orientation-' + drupalSettings.digital_signage.active.settings.orientation)
      .addClass('show');
    Drupal.digital_signage_preview.setPreviewSize(drupalSettings.digital_signage.active.settings.proportion, 10, drupalSettings.digital_signage.active.settings.width);
    // Load digital signage schedule
    Drupal.ajax({
      url: drupalSettings.digital_signage.active.settings.schedule.api + '?storedSchedule=' + Drupal.digital_signage_preview.storedSchedule + '&deviceId=' + Drupal.digital_signage_preview.deviceId + '&mode=schedule',
      error: function (e) {
        if (e.status === 200) {
          if (selectSlide) {
            let slides = e.responseJSON.schedule.concat(e.responseJSON.emergencyentities);
            let keys = [];
            let uniqueSlides = [];
            let form = document.createElement("FORM");
            let select = document.createElement("SELECT");
            let option = document.createElement("OPTION");
            option.appendChild(document.createTextNode(''));
            select.appendChild(option);
            let i;
            for (i = 0; i < slides.length; i++) {
              let key = slides[i].entity.type + '/' + slides[i].entity.id;
              if (!keys.includes(key)) {
                keys.push(key);
                uniqueSlides.push(slides[i]);
                let option = document.createElement("OPTION");
                option.appendChild(document.createTextNode(slides[i].label));
                select.appendChild(option);
              }
            }
            form.appendChild(select);
            $('#digital-signage-preview .popup .content-wrapper .content')[0].appendChild(select);
            select.onchange = function(event) {
              drupalSettings.digital_signage.active.settings.schedule.items = [];
              drupalSettings.digital_signage.active.settings.schedule.items.push(uniqueSlides[select.selectedIndex - 1]);
              Drupal.digital_signage_preview.load();
            }
            return;
          }
          drupalSettings.digital_signage.active.settings.schedule.items = e.responseJSON.schedule;
          if (drupalSettings.digital_signage.active.settings.schedule.items.length > 1) {
            drupalSettings.digital_signage.controls.prev.show();
            drupalSettings.digital_signage.controls.next.show();
          }
          Drupal.digital_signage_preview.load();
        } else {
          console.log(e);
          Drupal.digital_signage_preview.stop();
        }
      }
    }).execute();
  };

  Drupal.digital_signage_preview.load = async function () {
    if (drupalSettings.digital_signage.active.index + 1 > drupalSettings.digital_signage.active.settings.schedule.items.length) {
      drupalSettings.digital_signage.active.index = 0;
    }
    if (drupalSettings.digital_signage.active.index < 0) {
      drupalSettings.digital_signage.active.index = drupalSettings.digital_signage.active.settings.schedule.items.length - 1;
    }
    let $item = drupalSettings.digital_signage.active.settings.schedule.items[drupalSettings.digital_signage.active.index];
    drupalSettings.digital_signage.active.index++;
    if (drupalSettings.digital_signage.preview) {
      // Check if new item is different from current item.
      if (drupalSettings.digital_signage.active.currentitem === undefined ||
        drupalSettings.digital_signage.active.currentitem.type !== $item.type ||
        drupalSettings.digital_signage.active.currentitem.entity_type !== $item.entity.type ||
        drupalSettings.digital_signage.active.currentitem.entity_id !== $item.entity.id) {
        // Remember new item as current item.
        drupalSettings.digital_signage.active.currentitem = {
          type: $item.type,
          entity_type: $item.entity.type,
          entity_id: $item.entity.id,
        };
        // Load new item.
        await Drupal.ajax({
          url: drupalSettings.digital_signage.active.settings.schedule.api + '?storedSchedule=' + Drupal.digital_signage_preview.storedSchedule + '&deviceId=' + drupalSettings.digital_signage.active.device + '&mode=preview&type=' + $item.type + '&entityType=' + $item.entity.type + '&entityId=' + $item.entity.id,
          error: function (e) {
            console.log(e);
            Drupal.digital_signage_preview.stop();
          }
        }).execute();
      }
      Drupal.digital_signage_preview.timer = Drupal.digital_signage_timer.setInitialTimeout($item.duration);
      await Drupal.digital_signage_preview.timer.promise;
      if (drupalSettings.digital_signage.automatic) {
        Drupal.digital_signage_preview.load();
      }
    }
  };

  Drupal.digital_signage_preview.pause = function () {
    drupalSettings.digital_signage.automatic = false;
  };


  Drupal.digital_signage_preview.resume = function () {
    drupalSettings.digital_signage.automatic = true;
    Drupal.digital_signage_preview.load();
  };

  Drupal.digital_signage_preview.prevSlide = function () {
    drupalSettings.digital_signage.automatic = false;
    drupalSettings.digital_signage.active.index -= 2;
    Drupal.digital_signage_preview.load();
  };

  Drupal.digital_signage_preview.nextSlide = function () {
    drupalSettings.digital_signage.automatic = false;
    Drupal.digital_signage_preview.load();
  };

  Drupal.digital_signage_preview.setPreviewSize = function ($proportion, $gap, $remoteWidth) {
    let viewport = $('#digital-signage-preview .background');
    let screenH = viewport.height();
    let screenW = viewport.width();
    let width = 0;
    let height = 0;
    let gapLeftRight = '10%';
    let gapTopBottom = '10%';
    if ($remoteWidth > 0) {
      if ($proportion > 1) {
        gapLeftRight = screenW / $gap;
        width = screenW - 2 * gapLeftRight;
        height = width / $proportion;
        gapTopBottom = (screenH - height) / 2;
      }
      else if ($proportion > 0) {
        gapTopBottom = screenH / $gap;
        height = screenH - 2 * gapTopBottom;
        width = height * $proportion;
        gapLeftRight = (screenW - width) / 2;
      }
      gapLeftRight -= 15;
      gapTopBottom -= 15;
      if (gapLeftRight < 0 || gapTopBottom < 0) {
        $gap = $gap - 1;
        Drupal.digital_signage_preview.setPreviewSize($proportion, $gap, $remoteWidth);
        return;
      }
      let scale = width / $remoteWidth;
      let style = document.createElement('style');
      style.innerHTML = '#digital-signage-preview.show .popup > .content-wrapper > .content iframe {-moz-transform: scale(' + scale + ');-moz-transform-origin: 0 0;-o-transform: scale(' + scale + ');-o-transform-origin: 0 0;-webkit-transform: scale(' + scale + ');-webkit-transform-origin: 0 0;}';
      document.head.appendChild(style);
    }
    $('#digital-signage-preview .popup')
      .css('top', gapTopBottom)
      .css('bottom', gapTopBottom)
      .css('left', gapLeftRight)
      .css('right', gapLeftRight);
  };

  Drupal.digital_signage_preview.stop = function ($preview, $diagram, $screenshot, $log) {
    $(document).off('keydown');
    drupalSettings.digital_signage.preview = false;
    drupalSettings.digital_signage.controls.prev.hide();
    drupalSettings.digital_signage.controls.next.hide();
    drupalSettings.digital_signage.diagram = false;
    drupalSettings.digital_signage.screenshot = false;
    drupalSettings.digital_signage.log = false;
    $('#digital-signage-preview')
      .removeClass('orientation-landscape')
      .removeClass('orientation-portrait')
      .removeClass('show');
    $('#digital-signage-preview .popup > .content-wrapper > .content').html('');
  };

})(jQuery, Drupal, drupalSettings);
