<?php

namespace Drupal\eca_modeller_bpmn;

use DOMDocument;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Model;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Plugin\ECA\Modeller\ModellerBase;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;
use Mtownsend\XmlToArray\XmlToArray;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class for BPMN modellers, providing generic functionality which
 * is similar to all such modellers.
 */
abstract class ModellerBpmnBase extends ModellerBase {

  /**
   * The model data as an XML string.
   *
   * @var string
   */
  protected string $modeldata;

  /**
   * The unserialized model data as an XML object.
   *
   * @var array
   */
  protected array $xmlModel;

  /**
   * The filename of the BPMN model, if saved in the file system.
   *
   * @var string
   */
  protected string $filename;

  /**
   * The DOM of the XML data for detailed processing.
   *
   * @var \DOMDocument
   */
  protected DOMDocument $doc;

  /**
   * The DOM Xpath object for DOM queries.
   *
   * @var \DOMXPath
   */
  protected \DOMXPath $xpath;

  /**
   * @param \Drupal\eca\Entity\Model $model
   */
  protected function prepareForUpdate(Model $model): void {
    $this->modeldata = $model->getModeldata();
    $this->xmlModel = XmlToArray::convert($this->modeldata);
    $this->doc = new DOMDocument();
    $this->doc->loadXML($this->modeldata);
    $this->xpath = new \DOMXPath($this->doc);
  }

  /**
   * Return the XML namespace prefix used by the BPMN modeller.
   *
   * @return string
   */
  protected function xmlNsPrefix(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function generateId(): string {
    $random = new Random();
    return 'Process_' . $random->name(7);
  }

  /**
   * {@inheritdoc}
   */
  public function createNewModel(string $id, string $model_data, string $filename = NULL, bool $save = FALSE): Eca {
    $eca = Eca::create(['id' => mb_strtolower($id)]);
    $eca->getModel()->setModeldata($model_data);
    $this->setConfigEntity($eca);
    if ($save) {
      $this->save($model_data, $filename);
    }
    return $eca;
  }

  /**
   * {@inheritdoc}
   */
  public function save(string $model_data, string $filename = NULL): bool {
    $this->modeldata = $model_data;
    $this->xmlModel = XmlToArray::convert($model_data);
    $this->filename = $filename ?? '';
    return $this->modellerServices->saveModel($this);
  }

  /**
   * {@inheritdoc}
   */
  public function updateModel(Model $model): bool {
    $this->prepareForUpdate($this->eca->getModel());
    $changed = FALSE;
    $idxExtension = $this->xmlNsPrefix() . 'extensionElements';
    foreach ($this->getTemplates() as $template) {
      foreach ($template['appliesTo'] as $type) {
        switch ($type) {
          case 'bpmn:Event':
            $objects = $this->getStartEvents();
            break;

          case 'bpmn:SequenceFlow':
            $objects = $this->getSequenceFlows();
            break;

          case 'bpmn:Task':
            $objects = $this->getTasks();
            break;

          default:
            $objects = [];

        }
        foreach ($objects as $object) {
          if ($template['id'] === $object['@attributes']['modelerTemplate']) {
            $fields = $this->findFields($object[$idxExtension]);
            $id = $object['@attributes']['id'];
            /** @var \DOMElement $element */
            if ($element = $this->xpath->query("//*[@id='$id']")->item(0)) {
              /** @var \DOMElement $extensions */
              $extensions = $this->xpath->query("//*[@id='$id']/$idxExtension")->item(0);
              if (!$extensions) {
                $node = $this->doc->createElement($idxExtension);
                $extensions = $element->appendChild($node);
              }
              foreach ($template['properties'] as $property) {
                switch ($property['binding']['type']) {
                  case 'camunda:property':
                    if ($this->findProperty($object[$idxExtension], $property['binding']['name']) !== $property['value']) {
                      $element->setAttribute($property['binding']['name'], $property['value']);
                      $changed = TRUE;
                    }
                    break;

                  case 'camunda:field':
                    if (isset($fields[$property['binding']['name']])) {
                      // Field exists, remove it from the list.
                      unset($fields[$property['binding']['name']]);
                    }
                    else {
                      $fieldNode = $this->doc->createElement('camunda:field');
                      $fieldNode->setAttribute('name', $property['binding']['name']);
                      $valueNode = $this->doc->createElement('camunda:string');
                      $valueNode->textContent = $property['value'];
                      $fieldNode->appendChild($valueNode);
                      $extensions->appendChild($fieldNode);
                      $changed = TRUE;
                    }
                    break;
                }
              }
              // Remove remaining fields from the model.
              foreach ($fields as $name => $value) {
                /** @var \DOMElement $fieldElement */
                if ($fieldElement = $this->xpath->query("//*[@id='$id']/$idxExtension/camunda:field[@name='$name']")->item(0)) {
                  $extensions->removeChild($fieldElement);
                  $changed = TRUE;
                }
              }
            }
          }
        }
      }
    }
    if ($changed) {
      $this->modeldata = $this->doc->saveXML();
      $model->setModeldata($this->modeldata);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): ModellerInterface {
    $this->prepareForUpdate($this->eca->getModel());
    if ($element = $this->xpath->query("//*[@id='{$this->getId()}']")->item(0)) {
      $element->setAttribute('isExecutable', 'true');
    }
    $this->save($this->doc->saveXML());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): ModellerInterface {
    $this->prepareForUpdate($this->eca->getModel());
    if ($element = $this->xpath->query("//*[@id='{$this->getId()}']")->item(0)) {
      $element->setAttribute('isExecutable', 'false');
    }
    $this->save($this->doc->saveXML());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clone(): Eca {
    $this->prepareForUpdate($this->eca->getModel());
    $id = $this->generateId();
    if ($element = $this->xpath->query("//*[@id='{$this->getId()}']")->item(0)) {
      $element->setAttribute('id', $id);
      $element->setAttribute('name', $this->getLabel() . ' (' . $this->t('clone') . ')');
    }
    return $this->createNewModel($id, $this->doc->saveXML(), NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isExportable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function export(): ?Response {
    $this->prepareForUpdate($this->eca->getModel());
    return new HtmlResponse($this->modeldata, 200, [
      'Content-Type' => 'application/xml',
      'Content-Disposition' => 'attachment; filename="' . $this->getPluginId() . '-' . $this->getId() . '.xml"'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename(): string {
    return $this->filename;
  }

  /**
   * {@inheritdoc}
   */
  public function getModeldata(): string {
    return $this->modeldata;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->xmlModel[$this->xmlNsPrefix() . 'process']['@attributes']['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->xmlModel[$this->xmlNsPrefix() . 'process']['@attributes']['name'] ?? 'noname';
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): bool {
    return mb_strtolower($this->xmlModel[$this->xmlNsPrefix() . 'process']['@attributes']['isExecutable'] ?? 'true') === 'true';
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): string {
    return $this->xmlModel[$this->xmlNsPrefix() . 'process']['@attributes']['versionTag'] ?? '';
  }

  /**
   * Returns all the startEvent (events) objects from the XML model.
   *
   * @return array
   */
  private function getStartEvents(): array {
    $events = $this->xmlModel[$this->xmlNsPrefix() . 'process'][$this->xmlNsPrefix() . 'startEvent'] ?? [];
    if (isset($events['@attributes'])) {
      return [$events];
    }
    return $events;
  }

  /**
   * Returns all the task objects (actions) from the XML model.
   *
   * @return array
   */
  private function getTasks(): array {
    $actions = $this->xmlModel[$this->xmlNsPrefix() . 'process'][$this->xmlNsPrefix() . 'task'] ?? [];
    if (isset($actions['@attributes'])) {
      return [$actions];
    }
    return $actions;
  }

  /**
   * Returns all the sequenceFlow objects (condition) from the XML model.
   *
   * @return array
   */
  private function getSequenceFlows(): array {
    $conditions = $this->xmlModel[$this->xmlNsPrefix() . 'process'][$this->xmlNsPrefix() . 'sequenceFlow'] ?? [];
    if (isset($conditions['@attributes'])) {
      return [$conditions];
    }
    return $conditions;
  }

  /**
   * Returns all the gateway objects from the XML model.
   *
   * @return array
   */
  private function getGateways(): array {
    $types = [
      $this->conditionServices::GATEWAY_TYPE_EXCLUSIVE => 'exclusiveGateway',
      $this->conditionServices::GATEWAY_TYPE_PARALLEL => 'parallelGateway',
      $this->conditionServices::GATEWAY_TYPE_INCLUSIVE => 'inclusiveGateway',
      $this->conditionServices::GATEWAY_TYPE_COMPLEX => 'complexGateway',
      $this->conditionServices::GATEWAY_TYPE_EVENTBASED => 'eventBasedGateway',
    ];
    $gateways = [];
    foreach ($types as $key => $type) {
      $objects = $this->xmlModel[$this->xmlNsPrefix() . 'process'][$this->xmlNsPrefix() . $type] ?? [];
      if (isset($objects['@attributes'])) {
        $objects = [$objects];
      }
      foreach ($objects as $object) {
        $object['type'] = $key;
        $gateways[] = $object;
      }
    }
    return $gateways;
  }

  /**
   * {@inheritdoc}
   */
  public function readComponents(Eca $eca): ModellerInterface {
    $idxExtension = $this->xmlNsPrefix() . 'extensionElements';

    $flow = [];
    foreach ($this->getSequenceFlows() as $sequenceFlow) {
      if (isset($sequenceFlow[$idxExtension])) {
        $condition = $this->findAttribute($sequenceFlow, 'id');
        $eca->addCondition(
          $condition,
          $this->findProperty($sequenceFlow[$idxExtension], 'pluginid'),
          $this->findFields($sequenceFlow[$idxExtension])
        );
      }
      else {
        $condition = '';
      }
      $flow[$this->findAttribute($sequenceFlow, 'sourceRef')][] = [
        'id' => $this->findAttribute($sequenceFlow, 'targetRef'),
        'condition' => $condition,
      ];
    }

    foreach ($this->getGateways() as $gateway) {
      $gatewayId = $this->findAttribute($gateway, 'id');
      $eca->addGateway($gatewayId, $gateway['type'], $flow[$gatewayId] ?? []);
    }

    foreach ($this->getStartEvents() as $startEvent) {
      $extension = $startEvent[$idxExtension] ?? [];
      $eca->addEvent(
        $this->findAttribute($startEvent, 'id'),
        $this->findProperty($extension, 'pluginid'),
        $this->findAttribute($startEvent, 'name'),
        $this->findFields($extension),
        $flow[$this->findAttribute($startEvent, 'id')] ?? []
      );
    }

    foreach ($this->getTasks() as $task) {
      $extension = $task[$idxExtension] ?? [];
      $eca->addAction(
        $this->findAttribute($task, 'id'),
        $this->findProperty($extension, 'pluginid'),
        $this->findAttribute($task, 'name'),
        $this->findFields($extension),
        $flow[$this->findAttribute($task, 'id')] ?? []
      );
    }

    return $this;
  }

  /**
   * Returns all the templates for events, conditions and actions for the
   * modeller UI.
   *
   * @return array
   */
  protected function getTemplates(): array {
    $templates = [];
    foreach ($this->modellerServices->events() as $event) {
      $templates[] = $this->prepareEventTemplate($event);
    }
    foreach ($this->conditionServices->conditions() as $condition) {
      $templates[] = $this->prepareConditionTemplate($condition);
    }
    foreach ($this->actionServices->actions() as $action) {
      $templates[] = $this->prepareActionTemplate($action);
    }
    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function exportTemplates(): ModellerInterface {
    // Nothing to do by default.
    return $this;
  }

  /**
   * Helper function to build a template for an event, condition or action.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The event, condition or action plugin for which the template should
   *   be build.
   * @param string $applies_to
   *   The string to tell the modeller, to which object type the template will
   *   apply. Valid values are "bpmn:Event", "bpmn:sequenceFlow" or "bpmn:task".
   * @param array $fields
   *   An array of fields, received from the event, condition or action plugin
   *   definition.
   *
   * @return array
   *   The completed template for BPMN modellers for the given plugin and its
   *   fields.
   */
  protected function properties(PluginInspectionInterface $plugin, string $applies_to, array $fields): array {
    $properties = [[
      'label' => 'Plugin ID',
      'type' => 'Hidden',
      'value' => $plugin->getPluginId(),
      'binding' => [
        'type' => 'camunda:property',
        'name' => 'pluginid',
      ],
    ]];
    foreach ($fields as $field) {
      $property = [
        'label' => $field['label'],
        'type' => $field['type'],
        'value' => $field['value'] ?? '',
        'editable' => $field['editable'] ?? TRUE,
        'binding' => [
          'type' => 'camunda:field',
          'name' => $field['name'],
        ],
      ];
      if (isset($field['description'])) {
        $property['description'] = (string) $field['description'];
      }
      if (isset($field['extras'])) {
        /** @noinspection SlowArrayOperationsInLoopInspection */
        $property = array_merge_recursive($property, $field['extras']);
      }
      $properties[] = $property;
    }
    $pluginDefinition = $plugin->getPluginDefinition();
    $template = [
      'name' => (string) $pluginDefinition['label'],
      'id' => 'org.drupal.' . $plugin->getPluginId(),
      'appliesTo' => [$applies_to],
      'properties' => $properties,
    ];
    if (isset($pluginDefinition['description'])) {
      $template['description'] = (string) $pluginDefinition['description'];
    }
    return $template;
  }

  /**
   * Prepares and returns the template of the given event for BPMN modellers.
   *
   * @param \Drupal\eca\Plugin\ECA\Event\EventInterface $event
   *   The event plugin for which the template should be build.
   *
   * @return array
   *   The completed template for BPMN modellers for the given event.
   */
  protected function prepareEventTemplate(EventInterface $event): array {
    return $this->properties($event, 'bpmn:Event', $event->fields());
  }

  /**
   * Prepares and returns the template of the given condition for BPMN modellers.
   *
   * @param \Drupal\eca\Plugin\ECA\Condition\ConditionInterface $condition
   *   The condition plugin for which the template should be build.
   *
   * @return array
   *   The completed template for BPMN modellers for the given condition.
   */
  protected function prepareConditionTemplate(ConditionInterface $condition): array {
    return $this->properties($condition, 'bpmn:SequenceFlow', $this->conditionServices->fields($condition));
  }

  /**
   * Prepares and returns the template of the given action for BPMN modellers.
   *
   * @param \Drupal\Core\Action\ActionInterface $action
   *   The action plugin for which the template should be build.
   *
   * @return array
   *   The completed template for BPMN modellers for the given action.
   */
  protected function prepareActionTemplate(ActionInterface $action): array {
    return $this->properties($action, 'bpmn:Task', $this->actionServices->fields($action));
  }

  /**
   * Return a property of a given BPMN element.
   *
   * @param array $element
   *   The BPMN element from which the property should be returned.
   * @param string $property_name
   *   The name of the property in the BPMN element.
   *
   * @return string
   *   The property's value, default to an empty string.
   */
  protected function findProperty(array $element, string $property_name): string {
    if (isset($element['camunda:properties']['camunda:property'])) {
      $elements = isset($element['camunda:properties']['camunda:property']['@attributes']) ?
        [$element['camunda:properties']['camunda:property']] :
        $element['camunda:properties']['camunda:property'];
      foreach ($elements as $child) {
        if ($child['@attributes']['name'] === $property_name) {
          return $child['@attributes']['value'];
        }
      }
    }
    return '';
  }

  /**
   * Return an attribute of a given BPMN element.
   *
   * @param array $element
   *   The BPMN element from which the attribute should be returned.
   * @param string $attribute_name
   *   The name of the attribute in the BPMN element.
   *
   * @return string
   *   The attribute's value, default to an empty string.
   */
  protected function findAttribute(array $element, string $attribute_name): string {
    return $element['@attributes'][$attribute_name] ?? '';
  }

  /**
   * Return all the field values of a given BPMN element.
   *
   * @param array $element
   *   The BPMN element from which the field values should be returned.
   *
   * @return array
   *   An array containing all the field values, keyed by the field name.
   */
  protected function findFields(array $element): array {
    $fields = [];
    if (isset($element['camunda:field'])) {
      $elements = isset($element['camunda:field']['@attributes']) ? [$element['camunda:field']] : $element['camunda:field'];
      foreach ($elements as $child) {
        $fields[$child['@attributes']['name']] = isset($child['camunda:string']) && is_string($child['camunda:string']) ? $child['camunda:string'] : '';
      }
    }
    return $fields;
  }

}
