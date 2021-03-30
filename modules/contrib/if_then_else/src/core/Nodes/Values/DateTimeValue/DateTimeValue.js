class DateTimeValueControl extends Rete.Control {

  constructor(emitter, key, onChange) {
    super(key);
    this.component = {
      components: {
        // Component included for Multiselect.
        vuejsDatepicker
      },
      props: ['ikey', 'getData', 'putData', 'emitter', 'onChange'],
      template: `
            <div class="fields-container">
              <div class="label">Do you want the output as?</div>
  
              <div class="radio">
                <input type="radio" :id="radio1_uid" value="string" v-model="selection" @change="selectionChanged">
                <label :for="radio1_uid">String</label>
              </div>            
              <div class="radio">
                <input type="radio" :id="radio2_uid" value="unix" v-model="selection" @change="selectionChanged">
                <label :for="radio2_uid">Unix Timestamps</label>
              </div>            
              <div class="label">What should be the output value?</div>       
              <div class="radio">
                <input type="radio" :id="radio3_uid" value="current" v-model="outputValue" @change="selectionValueChanged">
                <label :for="radio3_uid">Current date and time</label>
              </div>
              <div class="radio">
                <input type="radio" :id="radio4_uid" value="fixed" v-model="outputValue" @change="selectionValueChanged">
                <label :for="radio4_uid">Fixed date and time</label>
              </div>             
              <div class="radio">
                <input type="radio" :id="radio5_uid" value="plusoffset" v-model="outputValue" @change="selectionValueChanged">
                <label :for="radio5_uid">Current date and time + offset</label>
              </div>
              <div class="radio">
                <input type="radio" :id="radio6_uid" value="minusoffset" v-model="outputValue" @change="selectionValueChanged">
                <label :for="radio6_uid">Current date and time - offset</label>
              </div>  
              <div class="form-control" v-if="outputValue === 'plusoffset' || outputValue === 'minusoffset'">
                <label class="typo__label">Offset</label>      
                <input type="text" v-model='valueText' @blur="valueTextChanged" placeholder="Offset" />
              </div>                    
              <div class="form-control" v-if="outputValue === 'current' || outputValue === 'plusoffset' || outputValue === 'minusoffset'">
                <label class="typo__label">Date</label>               
                <vuejs-datepicker v-model="value" @selected="fieldDateChanged"></vuejs-datepicker>
                <vue-timepicker format="hh:mm A" :format="Format" v-model="Data" @change="updateTime"></vue-timepicker>
              </div>                                                        
            </div>`,
      data() {
        return {
          type: drupalSettings.if_then_else.nodes.date_time_value.type,
          class: drupalSettings.if_then_else.nodes.date_time_value.class,
          name: drupalSettings.if_then_else.nodes.date_time_value.name,
          classArg: drupalSettings.if_then_else.nodes.date_time_value.classArg,
          Format: 'hh:mm:ss a',
          Data: {
            hh: '',
            mm: '',
            ss: '',
            a: ''
          },
          value: '',
          radio1_uid: '',
          radio2_uid: '',
          selection: 'static',
          outputValue: 'static',
          radio3_uid: '',
          radio4_uid: '',
          radio5_uid: '',
          radio6_uid: '',
          valueText: ''
        }
      },
      methods: {
        fieldDateChanged(date) {
          this.value = date.toLocaleString();
          this.putData('value', this.value);
          editor.trigger('process');
        },
        updateTime(eventData) {
          var date = new Date(this.value);
          date.setHours(parseInt(eventData.data.HH), parseInt(eventData.data.mm), parseInt(eventData.data.ss));
          if (date instanceof Date && !isNaN(date)) {
            this.value = date.toLocaleString();
          }
          this.putData('value', this.value);
          editor.trigger('process');
        },
        selectionChanged() {
          this.putData('selection', this.selection);
          this.onChange(this.selection);
          editor.trigger('process');
        },
        selectionValueChanged() {
          this.putData('outputValue', this.outputValue);
          //this.onChange(this.selection);
          editor.trigger('process');
        },
        valueTextChanged() {
          this.putData('valueText', this.valueText);
          editor.trigger('process');
        },
      },
      mounted() {
        this.putData('type', drupalSettings.if_then_else.nodes.date_time_value.type);
        this.putData('class', drupalSettings.if_then_else.nodes.date_time_value.class);
        this.putData('name', drupalSettings.if_then_else.nodes.date_time_value.name);
        this.putData('classArg', drupalSettings.if_then_else.nodes.date_time_value.classArg);

        //setting values of selected fields when rule edit page loads.
        var value = this.getData('value');
        if (typeof value != 'undefined') {
          this.value = this.getData('value');
        } else {
          var date = new Date();
          this.value = date.toLocaleString();
          this.putData('value', this.value);
        }
        var date = new Date(this.value);
        var date_num = date.getHours();
        if (date_num >= 12) {
          var hr_12 = date_num - 12;
          var day_ap = 'pm'
        } else {
          var hr_12 = date_num;
          var day_ap = 'am';
        }
        var minutes = date.getMinutes();
        if (minutes < 10) {
          minutes = '0' + minutes;
        }
        var seconds = date.getSeconds();
        if (seconds < 10) {
          seconds = '0' + seconds;
        }
        var Datepicker = {
          hh: hr_12,
          mm: minutes,
          ss: seconds,
          a: day_ap
        }
        this.Data = Datepicker;
        this.selection = this.getData('selection');
        this.outputValue = this.getData('outputValue');
        this.valueText = this.getData('valueText');
        this.onChange(this.selection);
      },
      created() {
        //Triggered when loading retejs editor but before mounted function. See documentaion of Vuejs
        this.radio1_uid = _.uniqueId('radio_');
        this.radio2_uid = _.uniqueId('radio_');
        this.radio3_uid = _.uniqueId('radio_');
        this.radio4_uid = _.uniqueId('radio_');
        this.radio5_uid = _.uniqueId('radio_');
        this.radio6_uid = _.uniqueId('radio_');

      }
    };
    this.props = {
      emitter,
      ikey: key,
      onChange
    };
  }
}
class DateTimeValueComponent extends Rete.Component {
  constructor() {
    var nodeName = 'date_time_value';
    var node = drupalSettings.if_then_else.nodes[nodeName];
    super(jsUcfirst(node.type) + ": " + node.label);
  }

  //Event node builder
  builder(eventNode) {

    var node_outputs = [];
    var nodeName = 'date_time_value';
    var node = drupalSettings.if_then_else.nodes[nodeName];
    node_outputs['datetime'] = new Rete.Output('datetime', 'Date Time', sockets['string']);
    node_outputs['datetime']['description'] = node.outputs['datetime'].description;

    eventNode.addOutput(node_outputs['datetime']);

    var nodeName = 'date_time_value';
    var node = drupalSettings.if_then_else.nodes[nodeName];

    function handleInput() {
      return function(selection) {
        let socket_out = eventNode.outputs.get('datetime');

        if (selection == 'static') {
          socket_out.socket = sockets['string'];
        } else {
          socket_out.socket = sockets['number'];
        }
        eventNode.outputs.set('datetime', socket_out);
        eventNode.update();
        editor.view.updateConnections({
          node: eventNode
        });
        editor.trigger('process');
      }
    }

    eventNode.addControl(new DateTimeValueControl(this.editor, nodeName, handleInput()));
    for (let name in node.inputs) {
      let inputLabel = node.inputs[name].label + (node.inputs[name].required ? ' *' : '');
      if (node.inputs[name].sockets.length === 1) {
        let inputObject = new Rete.Input(name, inputLabel, sockets[node.inputs[name].sockets[0]]);
        inputObject['description'] = node.inputs[name].description;
        eventNode.addInput(inputObject);
      }
    }
    eventNode['description'] = node.description;

  }
  worker(eventNode, inputs, outputs) {
    //outputs['form'] = eventNode.data.event;
  }
}
